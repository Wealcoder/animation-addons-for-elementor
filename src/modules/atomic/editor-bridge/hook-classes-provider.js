/* eslint-env browser */

/**
 * Stops Elementor's panel from deleting the CSS classes our JavaScript runs on.
 *
 * THE BUG
 * -------
 * Several AAE widgets seed a functional hook class into a child's `classes`
 * prop — `aae-mobile-nav-toggle` on the Nav's hamburger button, and so on. The
 * editing panel treats every entry in `classes` that is not a registered style
 * as a leftover from a deleted class (`use-missing-classes.ts`):
 *
 *     const allKnownIds = new Set(
 *         providers.flatMap((p) => p.actions.all().map((s) => s.id))
 *     );
 *     return appliedIds.filter((id) => !allKnownIds.has(id));
 *
 * …shows "Some classes are missing", and its ✕ is not a dismiss — it runs
 * `unapplyClasses(missingClassesIds)`, which STRIPS them from the saved
 * document. One click on that ✕ kills the mobile menu: nav.js selects on those
 * classes 29 times and nothing puts them back.
 *
 * THE FIX
 * -------
 * Register a READ-ONLY styles provider that reports these classes as known.
 * The alert then never appears, so the ✕ never has anything to strip.
 *
 * Read-only is the load-bearing part. The class PICKER only lists providers
 * that can be written to — `useOptions()` filters on
 * `provider.actions.updateProps` — while the missing-classes check uses every
 * provider with no such filter. Exposing only `all()` and `get()` therefore
 * lands us in exactly one of the two lists. Elementor's own
 * `elementBaseStylesProvider` is the same shape, so this is the intended
 * pattern rather than a workaround.
 *
 * WHY NOT THE FIX CLAUDE.md PRESCRIBES
 * ------------------------------------
 * That one says to render the hook class from the element's own twig instead of
 * putting it in `classes`. It cannot apply here: these classes sit on core
 * `e-flexbox` / `e-svg` children whose twigs Elementor owns. The alternative —
 * giving every one of those children a bespoke AAE element type — would rewrite
 * `define_default_children()` on a shipped, locked widget and break the shape
 * every saved page already holds.
 *
 * WHAT THIS DOES NOT CHANGE
 * -------------------------
 *   - The rendered class name. `transformClassId()` in editor-canvas falls back
 *     to the raw id for an unknown class; for a known one it calls
 *     `resolveCssName`, which `createStylesProvider` defaults to `(id) => id`.
 *     Identical output either way.
 *   - The generated CSS. Every entry carries `variants: []`, and the canvas
 *     renderer's `breakToBreakpoints()` drops a style with no variants before
 *     anything is emitted. `variants` must still be present — that function
 *     does a bare `style.variants.forEach`, so a missing array would throw.
 *   - Which classes exist. This registry only DESCRIBES classes the PHP already
 *     seeds; it never applies one.
 */

import { createStylesProvider, stylesRepository } from '@elementor/editor-styles-repository';

const PROVIDER_KEY = 'aae-functional-hook-classes';

/**
 * Hook classes that JAVASCRIPT depends on — the ones whose loss the user sees
 * as broken behaviour (a dead hamburger, an accordion that will not open)
 * rather than as lost styling.
 *
 * Derived, not hand-written: scratchpad `find-js-hook-classes.py` walks every
 * `Classes_Prop_Type::generate()` in both plugins and keeps the names a .js /
 * .jsx file references. Re-run it after adding a widget; the classes it reports
 * under CSS-only are deliberately NOT here yet — losing one costs appearance,
 * not function, and that pass is scheduled separately.
 *
 * `aae-mobile-nav-arrow-template` is the one entry the scan cannot find on its
 * own: the Nav passes it as an argument to its private `svg()` helper rather
 * than assigning it to a variable. nav.js clones that template into every
 * drill-down toggle, so losing it removes the mobile submenu arrows outright.
 */
export const JS_HOOK_CLASSES = [
	// Nav — mobile companion chrome (nav.js, 29 selector uses)
	'aae-mobile-nav-toggle',
	'aae-mobile-nav-overlay',
	'aae-mobile-nav-drawer',
	'aae-mobile-nav-header',
	'aae-mobile-nav-menu-area',
	'aae-mobile-nav-footer',
	'aae-mobile-nav-close',
	'aae-mobile-nav-back',
	'aae-mobile-nav-arrow-template',

	// Accordion — header/---icon wiring (accordion.js)
	'aae-header-element',
	'aae-header-title-element',
	'aae-header-icon-element',
	'aae-header-icon-open',
	'aae-header-icon-close',
	'aae-content-element',

	// Image Compare — drag handle and captions (image-compare.js)
	'aae-a-image-compare-thumb',
	'aae-a-image-compare-caption-before',
	'aae-a-image-compare-caption-after',
	'aae-ic-default',

	// Counter / Countdown — the nodes whose text the runtime rewrites
	'aae-a-counter-number',
	'aae-a-countdown-unit-count',

	// Form — the row a checkbox group is measured against (form.js)
	'aae-form-checkbox-row',
];

/* A style definition in the shape the repository consumers expect. `variants`
 * is empty ON PURPOSE — see the note above; this provider describes classes, it
 * does not style them. */
const STYLE_DEFS = JS_HOOK_CLASSES.map( ( id ) => ( {
	id,
	label: id,
	type: 'class',
	variants: [],
} ) );

/**
 * Idempotent: the repository's `register` is a bare `providers.push`, so
 * calling twice would list every class twice and run the canvas subscriber an
 * extra time for nothing.
 */
export function registerHookClassesProvider() {
	if ( ! stylesRepository || typeof stylesRepository.register !== 'function' ) {
		return false;
	}

	if ( stylesRepository.getProviderByKey?.( PROVIDER_KEY ) ) {
		return false;
	}

	stylesRepository.register(
		createStylesProvider( {
			key: PROVIDER_KEY,
			// getProviders() sorts DESCENDING by priority, so 1 puts us last:
			// a real style always wins a lookup against these placeholders.
			priority: 1,
			actions: {
				all: () => STYLE_DEFS,
				get: ( id ) => STYLE_DEFS.find( ( style ) => style.id === id ) ?? null,
				// Deliberately NO create / update / delete / updateProps. That
				// absence is what keeps these out of the class picker.
			},
		} )
	);

	return true;
}
