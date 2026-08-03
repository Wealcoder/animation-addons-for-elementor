# Lazy Animation — Implementation Plan (Atomic / V4)

**Status:** planned, not started.
**Scope:** the atomic (V4) runtime only — `src/modules/atomic/`. The V3
legacy path (`animation-addons-for-elementor-pro/src/js/animations.js`) has
the same problem and a sketch of its own fix at the end, but is **not** part
of this plan.

---

## 1. What "lazy animation" means here

Two independent layers. Only one of them is a problem on the atomic side.

| Layer | What is deferred | Atomic status |
|---|---|---|
| **A. Lazy asset** | the JS files themselves (gsap, ScrollTrigger, SplitText, effect bundles) | **already solved** |
| **B. Lazy init** | the per-element `bind()` work — `ScrollTrigger.create()`, `new SplitText()`, from-state `gsap.set()` | **this plan** |

### Layer A is already done — do not redo it

`inc/Atomic/Assets.php` registers every bundle but enqueues none;
`Render.php` calls `wp_enqueue_script()` per widget only when an animation
setting actually applies. A page with no AAE animation ships zero AAE JS.
See "On-demand asset loading" in `CLAUDE.md`.

### Layer B is the actual cost

`scan()` in `src/modules/atomic/common.js` binds **every**
`[data-interaction-id]` element on the page at load:

```js
const candidates = Array.from(scope.querySelectorAll('[data-interaction-id]'));
for (const el of candidates) {
  for (const kind of kindsFor(el)) {
    el.classList.add(kind.boundFlag);
    kind.bind(el, config);        // ← the cost
  }
}
```

`kind.bind` reaches `ScrollTrigger.create()` in
`effects/animation/triggers.js`, and for text animation `new SplitText()`,
which rewrites the heading into per-char spans and forces layout. On a
281-element page that is 281 binds at load — including the footer content a
visitor may never scroll to.

**The single most important architectural fact: `scan()` is the only choke
point.** Every kind binds through it. Unlike V3, the fix lands in one
function.

---

## 2. Phases

### Phase 0 — Baseline measurement

Before touching anything, measure on a real animation-heavy page (the
281-element page is the target case) using the global Playwright harness:

- TBT and total main-thread block at load
- `ScrollTrigger.getAll().length` immediately after load
- time spent inside `scan()`
- LCP, CLS

Without these numbers no "it got faster" claim afterwards is verifiable.

### Phase 1 — `lazy` flag on the Kind interface

Add one optional field to the Kind contract documented in `CLAUDE.md`:

```js
{ name: 'regular', mapName: '…', boundFlag: '…', lazy: true, … }
```

**Default is `false` — opt in per kind.** Blanket deferral breaks
position-dependent effects. The table below is the deliverable of this
phase, and getting it wrong is the main risk in the whole plan:

| Kind | lazy | Reason |
|---|---|---|
| `regular` | ✅ | the element's own viewport entry is the trigger |
| `text` | ✅ | same, and `SplitText` is the most expensive bind |
| `image-animation` | ✅ | same |
| `custom-css` | ❌ | it is styling, not animation — deferring changes the page's appearance |
| `sticky` | ❌ | ScrollTrigger pinning affects surrounding layout |
| `horizontal` | ❌ | same |
| `parallax` | ❌ | scrub tied to positions computed at bind time |
| `tilt`, `mouse-move`, `cursor-hover`, `advance-tooltip` | ❌ | interaction-driven — a viewport gate is the wrong gate for these (an idle/first-gesture defer would be a separate piece of work) |
| `nested-slider`, `image-hover` | ❌ | need measurement even while off-screen |

Start with the three ✅ kinds. They are the most common, carry the most
cost, and carry the least risk.

### Phase 2 — The gate in `scan()`

See the code sketch in §3. Invariants that must hold:

- **A deferred kind must NOT get `kind.boundFlag`.** `rebind()` and
  `resetEl()` both treat that class (and `playedKey`) as proof that the kind
  actually bound on the element, and skip teardown without it. Deferral uses
  a separate `aae-lazy-pending` class plus an `el.__aaeLazyKinds` queue.
- **One shared `IntersectionObserver` for the page.** One per element would
  cost more than the bind it defers.
- **On intersect: `unobserve` → bind → clear the pending flag.** Bind once.
- **`rebind()` / `replay()` / `replayRow()` must flush the queue first;
  `resetEl()` should cancel it** (binding just to tear down immediately is
  wasted work). Missing these hooks produces the classic "works in the
  editor, not on the frontend" bug — this is the biggest trap in the phase.
- **No `IntersectionObserver` → bind immediately.** Never leave an element
  unbound on an old browser.

### Phase 3 — FOUC, solved by position rather than by CSS

`gsap.fromTo` sets its from-state at bind time. Deferring a bind would show
the element in its final state first and then jump. There is currently no
pre-hide CSS anywhere in the atomic path (verified — no `visibility` /
`opacity: 0` / pending-class mechanism exists in `RegularAnimation/Render.php`
or `StyleManager/`).

**Solution: only defer elements below the initial viewport.** Above-the-fold
elements bind exactly as they do today, so there is no window in which a
flash can occur; below-the-fold elements are not visible, so a flash cannot
be seen. No critical CSS, no `visibility: hidden` hack, no safety timeout.

Implementation detail: do **one batched `getBoundingClientRect()` read pass**
in `scan()` — all reads together with no writes in between, so it costs one
forced reflow rather than one per element. Do not rely on the
`IntersectionObserver`'s first callback for this decision; it is not
guaranteed to fire before paint, which reintroduces a one-frame flash risk.

**Exception:** a config carrying a custom `startTrigger` / `endTrigger`
points somewhere else on the page, so the element's own position says
nothing about when it should run. Never defer those.

### Phase 4 — Dashboard → Optimize section

1. **Nav** — add `{ name: "Optimize", path: "optimize" }` to
   `src/modules/dashboard/config/nav/main-nav.jsx`.
2. **Page** — `src/modules/dashboard/pages/Optimize.jsx`, following the
   toggle-card pattern already in `Extensions.jsx`.
3. **Save** — no new endpoint. Reuse the existing
   `wp_ajax_aae_save_dynamic_settings` handler (`inc/admin/dashboard.php`),
   option name `aae_optimize_settings`.
4. **Runtime delivery** — no new channel. `AAE_CONFIG` is already localized
   onto the core runtime handle in `inc/Atomic/Assets.php` and already read
   by `common.js` (`window.AAE_CONFIG.breakpoints`). Add a `lazy` key.

> ⚠️ **Trap:** that `wp_localize_script()` call sits inside an
> `if ( … breakpoints … )` guard. If breakpoints do not resolve, `AAE_CONFIG`
> is never printed at all and the lazy settings silently never reach the
> runtime. Move the localize call out of the guard as part of this phase.

Controls:

| Setting | Values | Default |
|---|---|---|
| Lazy Animation | Off / On | On |
| Viewport margin (`rootMargin`) | 0–800 px | 300 px |
| Respect reduced motion | on / off | on |

`Off` must reproduce today's behaviour exactly — it is the one-click
rollback if a site breaks.

### Phase 5 — Editor

`elementorFrontend.isEditMode()` → never lazy. A builder must see every
effect immediately. This matches the decision already taken in the Pro
plugin's `inc/AtomicV4/Support/LazyAssets.php`, which likewise keeps
immediate enqueues for the editor preview.

### Phase 6 — Verification and docs

- Re-run the Phase 0 measurements on the same page.
- **ScrollSmoother on and off** — see §4, this is the highest-risk case.
- **A page with an eager pinning trigger (sticky / horizontal) ABOVE lazy
  elements.** The §4 probe had no pinning; pinning is exactly the case where
  ScrollTrigger's docs say creation order matters (`refreshPriority`). The
  pin-spacer is a real DOM element so a late-created trigger below it should
  measure correctly, but this is the one geometry case the probe did not
  cover.
- Popup / off-canvas content: an element inside a `display: none` ancestor
  never intersects, so it must still animate when the popup opens. This case
  needs an explicit test.
- Loop grid / AJAX-inserted content: `scan(root)` runs again on new nodes;
  confirm deferral works for them too.
- Document the `lazy` field in the Kind interface block in `CLAUDE.md`.

---

## 3. Code sketch

All of this lands in `src/modules/atomic/common.js`. No effect file changes
beyond adding `lazy: true` to three `register()` calls.

### 3.1 Config and the shared observer

Place just above `scan()`.

```js
/* =====================================================================
 * Lazy binding — defer below-the-fold elements
 *
 * bind() is where the real cost lives: ScrollTrigger.create() per
 * element, SplitText's DOM rewrite per text node. On a long page most
 * of that is spent on elements the visitor may never reach. Elements
 * inside the first viewport bind exactly as before (so a deferred
 * from-state can never flash); the rest wait for an IntersectionObserver.
 *
 * Opt-in per kind (kind.lazy === true) — sticky/parallax/custom-css
 * must never defer, see the kind table in AAE-LAZY-ANIMATION-PLAN.md.
 * =================================================================== */

// NOTE: common.js has no isEditMode() helper today — only an inline
// elementorFrontend check inside currentBreakpoint(). Define one:
//   const isEditMode = () =>
//     !!(window.elementorFrontend?.isEditMode?.());
const LAZY         = window?.AAE_CONFIG?.lazy || {};
const LAZY_ON      = LAZY.enabled !== false && !isEditMode();
const LAZY_MARGIN  = Number(LAZY.margin) || 300;
const LAZY_PENDING = 'aae-lazy-pending';
const LAZY_KEY     = '__aaeLazyKinds';

let lazyObserver = null;

function getLazyObserver() {
	// One observer for the whole page — one per element would cost more
	// than the bind it defers.
	if (lazyObserver || typeof IntersectionObserver === 'undefined') return lazyObserver;
	lazyObserver = new IntersectionObserver((entries) => {
		for (const entry of entries) {
			if (entry.isIntersecting) flushLazy(entry.target);
		}
	}, { rootMargin: `${LAZY_MARGIN}px 0px` });
	return lazyObserver;
}
```

### 3.2 Deferral rule

```js
function canDefer(kind, config) {
	if (!LAZY_ON || kind.lazy !== true) return false;
	// A custom start/end trigger points somewhere else on the page, so
	// this element's own position says nothing about when it should run.
	if (config.startTrigger || config.endTrigger) return false;
	return true;
}
```

### 3.3 Queue, flush, cancel

```js
function queueLazy(el, kind, config) {
	// Deliberately NOT kind.boundFlag — rebind()/resetEl() read that as
	// "this kind actually bound here" and would skip a real teardown.
	if (!el[LAZY_KEY]) el[LAZY_KEY] = [];
	el[LAZY_KEY].push({ kind, config });
	el.classList.add(LAZY_PENDING);
	getLazyObserver()?.observe(el);
}

/** Bind everything queued on this element, now. Idempotent. */
function flushLazy(el) {
	const queued = el?.[LAZY_KEY];
	if (!queued) return;
	delete el[LAZY_KEY];
	el.classList.remove(LAZY_PENDING);
	lazyObserver?.unobserve(el);
	for (const { kind, config } of queued) {
		if (el.classList.contains(kind.boundFlag)) continue;
		el.classList.add(kind.boundFlag);
		try { kind.bind(el, config); } catch (_) { }
	}
}

/** Drop the queue without binding — for resetEl(), where binding just to
 *  immediately tear down would be wasted work. */
function cancelLazy(el) {
	if (!el?.[LAZY_KEY]) return;
	delete el[LAZY_KEY];
	el.classList.remove(LAZY_PENDING);
	lazyObserver?.unobserve(el);
}
```

### 3.4 `scan()` split into passes

The only invasive change. Reads and writes must not interleave, or the
batched-rect optimisation is pointless.

```js
function scan(root) {
	// … candidates collected as today …

	// Pass 1 — collect work, no DOM writes yet. Interleaving rect reads
	// with bind()'s style writes would thrash layout once per element.
	const work = [];
	for (const el of candidates) {
		for (const kind of kindsFor(el)) {
			if (el.classList.contains(kind.boundFlag)) continue;
			const config = kind.read(el);
			if (!config || config.preventBindInEditor) continue;
			work.push({ el, kind, config });
		}
	}

	// Pass 2 — one batched read over deferral candidates only. Skipped
	// entirely when lazy is off, so that path stays identical to today.
	const belowFold = new Map();
	if (LAZY_ON) {
		const fold = window.innerHeight + LAZY_MARGIN;
		for (const { el } of work) {
			if (belowFold.has(el)) continue;
			belowFold.set(el, el.getBoundingClientRect().top > fold);
		}
	}

	// Pass 3 — writes.
	for (const { el, kind, config } of work) {
		if (belowFold.get(el) && canDefer(kind, config)) {
			queueLazy(el, kind, config);
			continue;
		}
		el.classList.add(kind.boundFlag);
		kind.bind(el, config);
	}
}
```

### 3.5 Flush hooks — one line each

```js
function rebind(el, playGroup = "") { if (!el) return; flushLazy(el);  /* … */ }
function replay(el, …)             { flushLazy(el);  /* … */ }
function replayRow(el, …)          { flushLazy(el);  /* … */ }
function resetEl(el, …)            { cancelLazy(el); /* … */ }
```

Also flush from `rebindIfChanged()` (the debounced resize / device-mode
path) — a deferred element whose effective config changes at a new
breakpoint must not be re-evaluated while still pending.

### 3.6 Effect files

```js
window.AAERegistry.register({ name: 'regular', /* … */ lazy: true });
```

---

## 4. ScrollSmoother interaction

The Pro plugin's ScrollSmoother extension wraps the whole page in
`#smooth-wrapper > #smooth-content` (`inc/hook.php`, ~line 540) and creates
the smoother in `src/js/wcf-addons-ex.js` (~line 101):

```js
window.wcf_smoother = ScrollSmoother.create({
  smooth: smooth_value, effects: true, smoothTouch: smooth_value,
  normalizeScroll: isTouch, ignoreMobileResize: true,
});
```

`#smooth-content` carries a `transform: translateY()` that holds the visual
position **behind** the native scroll by `smooth` seconds.

### ScrollSmoother OFF

No effect on this plan. Native scroll, no transform, `IntersectionObserver`
and `getBoundingClientRect()` behave plainly.

### ScrollSmoother ON — what is safe

All three read the same geometry, so they stay consistent with each other:

- `getBoundingClientRect()` includes ancestor transforms, so an element's
  rect reports the lagged, *visible* position.
- `IntersectionObserver` likewise reports against visible geometry — it
  matches what the visitor actually sees.
- At load, scroll is 0 and the transform is 0, so **Phase 3's above-the-fold
  detection is unaffected**.

### MEASURED — late ScrollTrigger creation is safe

This was the plan's blocking open question: does a ScrollTrigger created
**late**, while ScrollSmoother is active, compute its own start/end
correctly — or does each lazy batch need a `ScrollTrigger.refresh()`? A
refresh recalculates every trigger on the page, which would erase the entire
performance gain.

Probe: `E:\Local Testing\st-late-probe\` (`index.html` + `probe.mjs`,
re-runnable with `node probe.mjs`). Standalone harness — the plugin's own
`assets/lib/` GSAP builds, the exact `ScrollSmoother.create()` config from
`wcf-addons-ex.js`, `smooth: 1.5`, 20 targets on a 14 000 px page, 800 px
viewport.

| Condition | start/end vs eagerly-created baseline |
|---|---|
| Created at load, scroll 0 (baseline) | — |
| Created late, scrolled to 6000 px, smoother settled | **identical** |
| Created late, mid-ease at maximum lag (content transform still 0 while native scroll is 6000) | **identical** |
| `ScrollTrigger.refresh()` afterwards | **changed nothing** |

**Conclusion: ScrollTrigger measures in the native scroll coordinate space
and is unaffected by the smoother's transient transform. No refresh is
needed, and the plan's performance premise holds.**

The harness asserts the smoother genuinely engaged before comparing
(`smooth()` returns 1.5, settled transform `matrix(1,0,0,1,0,-5999.99)`) —
without that check a smoother that silently failed to initialise would make
every comparison trivially pass.

### MEASURED — the fast-scroll pop-in is real but small, and rootMargin does not fix it

An element that binds *after* its ScrollTrigger start point has gone by fires
synchronously on creation: it appears finished instead of animating. Run E of
the probe models this properly — an `IntersectionObserver` drives creation
during a 6000 px smoother ease, so the IO callback latency is included.

| Condition | popped in mid-ease (of 10) |
|---|---|
| Smoother on, `rootMargin` 300 px | 2–3 |
| Smoother on, `rootMargin` 1150 px | 2–4 |
| Smoother off, `rootMargin` 300 px (control) | 2 (only 5 elements bind at all — an unsmoothed jump skips the rest entirely) |

Two findings, both of which correct an earlier assumption in this document:

1. **Scaling `rootMargin` with the smooth value does not help.** Across three
   runs the larger margin was never better and sometimes worse. The limiting
   factor is IntersectionObserver callback delivery latency during a fast
   ease, and a wider observation band does not make a late callback arrive
   sooner. An earlier draft of this plan proposed an `effectiveMargin()`
   helper scaling the margin by `smooth * 500`; **it is unsupported by
   measurement and should not be built.**
2. **The effect is narrow.** It only appears during an anchor jump / fast
   flick, where content streams past at roughly 4000 px/s and the difference
   between "animated" and "appeared" is barely perceptible. Ordinary
   scrolling binds and animates correctly.

Treat this as a known, accepted limitation rather than something to engineer
around. Confirm it on a real page in Phase 6 before deciding otherwise.

**Caveats on the above:** synthetic page with uniform 700 px sections,
headless Chromium only, one `smooth` value, and only the anchor-jump extreme
was exercised. These numbers justify *removing* a speculative mitigation;
they are not a substitute for the Phase 6 measurement on a real page.

### Dashboard precedent

ScrollSmoother already has its own global dashboard setting
(`wcf_smooth_scroller`, saved via `save_smooth_scroller_settings` in
`inc/admin/dashboard.php`). The Optimize section in Phase 4 can follow the
same panel pattern.

---

## 5. Alternatives considered, and complementary wins found in review

Researched against GSAP's own docs/forums (2026-07-30) before committing to
the IO approach.

### `ScrollTrigger.batch()` — considered, rejected

GSAP's answer to "many triggers" sounds like it should replace this plan. It
does not:

- It still creates **one ScrollTrigger per element** (per its own docs) — it
  does not reduce trigger count.
- It solves *callback coordination* (staggering elements that enter
  together), not creation cost.
- One `vars` object applies to the whole batch — **no per-element configs**,
  and per-element config read from the interactions map is the core of this
  runtime.

### IO-gated creation — no objection upstream

GreenSock staff position on IO vs ScrollTrigger is "both viable, no
intrinsic penalty either way." Nothing upstream contradicts lazy creation,
and the §4 probe confirms late creation computes correct start/end without a
refresh.

### `refreshPriority` — noted, not needed yet

GSAP recommends setting `refreshPriority` when triggers are created out of
document order (our lazy case) **and pinning is involved**. Our lazy kinds
never pin (sticky/horizontal are excluded in Phase 1), and the probe showed
`refresh()` changes nothing on a pin-free page. The pinning-above-lazy case
is on the Phase 6 checklist; if it misbehaves, `refreshPriority` is the
documented fix.

### Complementary quick win #1 — SplitText granularity ✅ SHIPPED 2026-07-30

`effects/animation/text.js` used to always split `type: 'lines,words,chars'`
regardless of which unit the effect animates — ~3× the DOM nodes needed.
Now the split covers only the union of units the element's rows actually
animate (`unitsFor()` / `stashUnits()` in text.js). Key decisions:

- **Chars always come with words** (`'words,chars'`) — chars alone lose
  word integrity (line can break mid-word), and text_reveal's overflow clip
  sits on each char's `parentElement`, which must stay the word.
- **The union across ALL rows is stashed BEFORE any tween builds** — if
  each row split only its own units, a later row would force a rebuild and
  detach nodes from under an already-running row's tween.
- Editor resting canvas stays unsplit (the stash is just a Set).
- `text_invert`'s dedicated lines-only split is untouched.

Verified with `E:\Local Testing\split-granularity-probe\` (built bundles +
the plugin's own GSAP libs, 11 assertions, all pass): word→9 divs vs the
old-style 46 for a same-length chars element; multi-row unions correctly;
reveal's clip parent is still the word; tweens build and text stays intact.

### Complementary quick win #2 — fonts and SplitText ✅ SHIPPED 2026-07-30

`bindText()` now waits for webfonts on the frontend before splitting
(editor binds immediately — its document has long settled). Decisions:

- **`document.fonts.ready` gate, NOT `autoSplit`.** autoSplit re-splits on
  font load, detaching nodes from under running tweens (the exact bug class
  the shared-split design prevents), and the editor preview ships SplitText
  3.11.2 which lacks it (frontend lib is 3.14.2). V3 parity too.
- **Double-rAF before checking `fonts.status`** — measured: @font-face
  loads only START with a paint. At scan time `fonts.status` reads
  `'loaded'` because the browser hasn't even requested the fonts yet; a
  forced layout (`offsetWidth`) is NOT enough. Two frames guarantee a paint
  so the status check sees reality.
- A `FONT_WAIT_KEY` token invalidates the parked bind on reset/rebind — a
  switched-off effect must not resurrect when fonts arrive.

Verified: `probe-fonts.mjs` (7 asserts) — gate holds mid-load, binds with
correct granularity after fonts settle, reset-during-wait never binds.
**Playwright trap found:** `page.goto`'s default `'load'` waits for
in-flight font requests, so every "mid-load" assertion silently ran after
the fact — use `waitUntil: 'domcontentloaded'` when testing font states.

### Bangla / Arabic (probe-i18n.mjs, all pass)

- **Bangla:** SplitText 3.14's grapheme segmentation keeps conjuncts and
  matras whole — measured pieces: `ক্ষু · দ্র · যু · ক্তা · স্ত্রী · ন্দ্ব`. Char
  effects are safe in the frontend lib. (Editor preview's 3.11 predates the
  i18n rewrite — verify there before relying on char effects in Bangla.)
- **Arabic:** words/lines safe. Chars keep the TEXT intact but cursive
  joining is a rendering property — DOM-separated letters render in
  isolated glyph forms. Recommend word/line effects for Arabic.
- **The granularity fix above is itself an i18n fix:** before it, EVERY
  text element was char-split regardless of effect, so even a word-effect
  Arabic heading had its letters DOM-isolated (joining broken). Now word/
  line effects never create char nodes.

---

## 6. Expected result

**Nothing changes for the visitor. That is the success criterion.** The gain
is in the numbers. These are estimates — Phase 0/6 exist to replace them
with measurements.

### What improves

| Metric | Now | After | Why |
|---|---|---|---|
| TBT / main-thread block at load | worst offender | ~60–80 % lower | only the first viewport's elements bind at load |
| Live `ScrollTrigger` instances at load | 281 | ~20–40 | the rest are created on scroll |
| `ScrollTrigger.refresh()` cost | recalculates all 281 on every resize/load | proportional to live triggers | an **ongoing** win, not just a load-time one |
| `SplitText` DOM rewrites | every text element, at load | only visible ones | the single most expensive bind |
| Lighthouse Performance score | — | a few to 10+ points | TBT carries 30 % of the score weight |

### What does NOT improve — stated plainly

- **Bundle size: unchanged.** gsap (~70 KB) + ScrollTrigger (~40 KB) +
  SplitText still download exactly as before. Deferring those is a separate,
  riskier piece of work not covered here.
- **LCP: roughly unchanged.** The hero heading is above the fold and stays
  eager by design. Moving LCP requires deferring assets, not binds.
- **A visitor who scrolls the whole page pays the same total cost.** The
  work is spread out, not removed. The win is (a) at the load moment and
  (b) entirely, for visitors who never reach the bottom.

### Risks

- A batch of elements entering together (e.g. a 12-card grid) binds in one
  go — theoretically a micro-jank frame. Expected to complete within a
  frame, but it needs measuring.
- A wrong entry in the Phase 1 kind table breaks sticky/parallax. This is
  why the default is `false` and the list is opt-in.

### Where the gain is largest

Proportional to element count and page length. On a 30-element page the
difference is close to zero — everything is already in the first viewport.
The 281-element page is the real target.

---

## 7. File size impact

Measured baseline: `assets/build/modules/atomic/common.js` = **5,922 bytes**
(gzip **2,209 bytes**).

| File | Now | After (est.) | Delta |
|---|---|---|---|
| `common.js` (built) | 5,922 B | ~6,800 B | **+~900 B** |
| `common.js` (gzip) | 2,209 B | ~2,600 B | **+~400 B** |
| effect bundles | — | unchanged | `lazy: true` is one property |
| `inc/Atomic/Assets.php` | — | — | zero frontend bytes |
| `pages/Optimize.jsx` | — | — | admin-only, zero frontend bytes |

**Net: roughly +0.4 KB gzipped**, against the ~110 KB of GSAP libraries whose
work it reduces — well under a quarter of one percent.

And `common.js` is not loaded unconditionally: it is register-only in
`Assets.php` and arrives as an effect bundle's dependency, so a page without
AAE animation does not ship even those 0.4 KB.

---

## 8. Open questions

1. ~~**Late ScrollTrigger creation under ScrollSmoother.**~~ **Answered by
   measurement — safe, no refresh needed. See §4.**
2. **Kind table scope.** Start with `regular` / `text` / `image-animation`
   only, or include more from the start?
3. **Ordering of Phase 4.** Build the runtime first with hardcoded defaults
   and measure, then add the Optimize UI — or build the UI first?
   Recommendation: runtime first. A switch with nothing behind it is not
   worth shipping.

---

## Appendix — the V3 legacy path (not in scope)

The same problem exists in
`animation-addons-for-elementor-pro/src/js/animations.js`, which attaches an
Elementor handler to **every** widget and container:

```js
elementorFrontend.hooks.addAction("frontend/element_ready/widget",    …);
elementorFrontend.hooks.addAction("frontend/element_ready/container", …);
```

Every element runs `run()` at load → `new SplitText()` and
`ScrollTrigger.create()` (see `apply_trigger`). The equivalent fix would gate
`bindEvents()` behind a shared IntersectionObserver.

Two things make V3 materially harder than atomic and are the reason it is
excluded here:

- there is no single choke point equivalent to `scan()`;
- V3 has no above-the-fold guarantee to lean on, so it **would** need the
  critical-CSS / pre-hide machinery that Phase 3 avoids entirely, with a
  safety timeout so a missing script can never leave content permanently
  invisible.

Treat V3 as a separate project, only after the atomic version has shipped
and been measured.
