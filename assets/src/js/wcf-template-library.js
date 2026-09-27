/* eslint-disable semi */
/* eslint-disable arrow-parens */
/**
 * WCF Template Library Editor Core
 *
 * The AAE button in the "Drag widget here" row opens a lightbox listing the
 * blocks and pages on block.animation-addons.com; Insert drops one into the
 * document at that row.
 *
 * Before either path: `aaeaddon_template_dependencies` lists the AAE
 * widgets/extensions the template uses that are switched off here, and a
 * dialog shows them — with "Switch on & insert" for an administrator, which
 * enables them, parks the insert and reloads the editor once. A V3 section
 * used to insert with those widgets silently dropped.
 *
 * Two insert paths, decided by the card's `data-builder`:
 *
 *  - V3 (classic) — unchanged: the file goes to `get_wcf_template_data`,
 *    whose controls walk (`on_import`) copies images, and the result goes to
 *    `document/elements/import`.
 *  - V4 (atomic) — the file is a Save-as-Template export and carries the
 *    block's global classes and variables beside its content. The server
 *    pass (`aaeaddon_prepare_v4_block`) gives every element a fresh id — and,
 *    through Elementor's own replace_id filter, fresh local style ids —
 *    copies images / SVGs / Lotties into this site's media library, and
 *    switches on the AAE widgets the block uses. The classes and variables
 *    then go through Elementor's OWN `process_global_styles` (the same
 *    Match / Keep dialog its library shows), whose result the editor's
 *    Classes and Variables panels pick up without a reload. Only then is the
 *    content imported.
 *
 *    One case reloads: a widget that was switched on in THIS request is not
 *    in `elementor.config.elements` yet and the insert would throw, so the
 *    document is autosaved, the insert is parked in sessionStorage, the
 *    editor reloads and the insert resumes on `preview:loaded`.
 *
 * Every handler is bound ONCE, delegated from `document`, and reads what it
 * needs off the clicked node — an earlier version re-bound the Insert handler
 * on every open and every filter change, so one click fired several imports.
 *
 * @version 2.0.0
 */

/* global jQuery, WCF_TEMPLATE_LIBRARY, elementor, elementorCommon, $e, wp */

(function ($, window, document) {
  const CFG = window.WCF_TEMPLATE_LIBRARY || {};
  const I18N = CFG.i18n || {};
  // AAEADDON_BLOCK_LIBRARY_URL — a staging site points it at a local block library.
  const HOST = String(CFG.block_host || "https://block.animation-addons.com").replace(/\/+$/, "");
  const API = HOST + "/wp-json/wp/v2/wcf-templates";
  const CATEGORY_API = HOST + "/wp-json/templates/v2/wcf-tpl-category";
  const JSON_API = API + "/json-content?url=";
  const PENDING_KEY = "aaeaddon_pending_block";
  const PER_PAGE = 100;

  // PHP ships these through esc_html__(), and every caller escapes again
  // before writing markup — decode first, or "&" reaches the screen as
  // "&amp;" (measured on the dialog's "Switch on & insert" button).
  const decode = (value) => {
    const box = document.createElement("textarea");
    box.innerHTML = String(value);
    return box.value;
  };
  const text = (key, fallback) => (typeof I18N[key] === "string" && I18N[key] ? decode(I18N[key]) : fallback);
  const atomicAvailable = () => !!CFG.atomic_available;

  /**
   * Which eras this site actually USES — an era is "on" when at least one of
   * its widgets is switched on (PHP decides: template_library_eras()).
   *
   * It decides the DEFAULT the Version filter opens on and the one-line hint
   * under the toolbar, and NOTHING else. It never hides: every version is
   * always listed and always in the filter. Hiding an era was tried and is
   * wrong — a catalogue that silently omits half its blocks reads as "the
   * sections are gone", and there is nothing to protect against anyway,
   * because Insert already offers to switch on whatever a template needs.
   */
  const eras = () => {
    const e = CFG.eras && typeof CFG.eras === "object" ? CFG.eras : {};
    const v3 = !!e.v3;
    const v4 = !!e.v4;
    return v3 || v4 ? { v3, v4 } : { v3: true, v4: true };
  };
  /**
   * Which version the list OPENS on — never what it offers; all three are
   * always in the control.
   *
   * What this editor can insert wins first: without Elementor's atomic
   * setting every V4 card carries a disabled Insert, and opening on a list
   * where nothing can be inserted is the worst first screen the library has.
   * Then the era this site uses, so a V4-only site lands on V4 and a V3 site
   * on V3. With both (or neither) in use, V4 — it is the catalogue being
   * built now.
   */
  const defaultBuilder = () => {
    if (!atomicAvailable()) {
      return "v3";
    }
    const offered = eras();
    if (offered.v3 !== offered.v4) {
      return offered.v4 ? "v4" : "v3";
    }
    return "v4";
  };

  const state = {
    categories: [],
    page: 1,
    category: "",
    type: "block",
    color: "",
    builder: defaultBuilder(),
    search: "",
    exhausted: false,
    total: null,        // how many the server says match this filter …
    totalPages: null,   // … and in how many pages. Both come back on every
                        // response and were thrown away until 2026-09-24.
    section: null,      // the "add section" area whose button opened the modal
    content: null,      // the lightbox's widgetContent element
    listHtml: "",       // the list markup, kept while a preview is open
    inserting: false,
    resized: false,
    observer: null,
    seq: 0,             // bumped by every renderList(); a fetch that started
                        // under an older value is a stale tab/filter and is dropped
  };

  window.wcftmLibrary = null;

  /* ------------------------------------------------------------------ data */

  /**
   * The category list, fetched once at editor load and again on every modal
   * open until it has arrived. The first fetch races the block server's own
   * boot (a 500 from a site still starting up, reported as a CORS error by
   * the browser), and a list that is only ever asked for once leaves the
   * Category dropdown empty for the whole editor session.
   */
  let categoriesRequest = null;
  const loadCategories = () => {
    if (state.categories.length) {
      return Promise.resolve(state.categories);
    }
    if (!categoriesRequest) {
      categoriesRequest = fetch(CATEGORY_API)
        .then((res) => (res.ok ? res.json() : Promise.reject(res.status)))
        .then((res) => {
          state.categories = Array.isArray(res) ? res : [];
          return state.categories;
        })
        .catch(() => {
          // Forget the failure so the next open asks again.
          categoriesRequest = null;
          return [];
        });
    }
    return categoriesRequest;
  };

  loadCategories();

  /** Fill the Category dropdown of an open modal without re-rendering the list. */
  const fillCategories = () => {
    const $select = $("#wcf-template-library-filter-subtype");
    if (!$select.length || $select.find("option").length > 1) {
      return;
    }
    state.categories.forEach((item) => {
      // Same unescaped name the wp.template prints ({{{item.name}}}).
      $select.append($("<option>", { value: item.id }).html(item.name));
    });
    $select.val(state.category);
  };

  const isV4 = (item) => item && item.builder_version === "v4";
  const isAnimated = (item) => item && (item.is_animated === true || item.is_animated === "1" || item.is_animated === 1);

  /**
   * Mark what this site may insert (licence); apply the Version filter HERE
   * as well as on the request — a block server that does not know `builder=`
   * yet answers with everything, and "V4" must not then list V3 blocks; and
   * put the V4 blocks first on an unfiltered list — the same "V4 first, then
   * the rest in its own order" the dashboard grids use, done here so it holds
   * whatever order the block server happens to return.
   */
  const validate = (remote) => {
    let list = (Array.isArray(remote) ? remote : []).map((template) => {
      if ((CFG.config && CFG.config.wcf_valid === true) || template.is_pro == "0") {
        template.valid = "yes";
      }
      return template;
    });

    list.reverse();

    if (state.builder === "v4") {
      return list.filter(isV4);
    }
    if (state.builder === "v3") {
      return list.filter((item) => !isV4(item));
    }

    return list.filter(isV4).concat(list.filter((item) => !isV4(item)));
  };

  const buildQuery = (page) => {
    const url = new URL(API);
    url.searchParams.set("per_page", PER_PAGE);
    url.searchParams.set("subtype", state.type || "block");
    url.searchParams.set("page", page || 1);
    if (state.category) {
      url.searchParams.set("cat", state.category);
    }
    if (state.color) {
      url.searchParams.set("color_type", state.color);
    }
    if (state.builder && state.builder !== "all") {
      url.searchParams.set("builder", state.builder);
    }
    if (state.search) {
      url.searchParams.set("s", state.search);
    }
    return url;
  };

  /**
   * One page of the list, plus what the server says the whole list is.
   *
   * `total` / `totalpages` ride every response and used to be discarded, so
   * the modal could never say how many blocks there are — 1,464 of them
   * arrive 100 at a time and a pause while a page is in flight reads as the
   * end of the catalogue. They also end the scroll one request earlier: the
   * old code only learned it was done by asking for a page that came back
   * empty, which every filter and every empty tab paid for.
   */
  const fetchTemplates = async (page) => {
    let items = [];
    let total = null;
    let pages = null;
    try {
      const response = await fetch(buildQuery(page));
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      const data = await response.json();
      items = data.templates || [];
      total = Number.isFinite(Number(data.total)) ? Number(data.total) : null;
      pages = Number.isFinite(Number(data.totalpages)) ? Number(data.totalpages) : null;
    } catch (error) {
      console.error("Fetch Error:", error);
    }
    return { items: validate(items), total, pages };
  };

  /* --------------------------------------------------------------- markup */

  const escapeAttr = (value) =>
    String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");

  const insertDisabledReason = (item) =>
    isV4(item) && !atomicAvailable() ? text("needs_v4", "Needs Elementor V4 (atomic elements) to insert") : "";

  const badges = (item) => {
    const out = [];
    if (isV4(item)) {
      out.push(`<span class="aae-tl-badge aae-tl-badge--v4" data-aae-v4-badge>${escapeAttr(text("v4", "V4"))}</span>`);
    }
    if (isAnimated(item)) {
      out.push(`<span class="aae-tl-badge aae-tl-badge--animated" data-aae-animated-badge>&#10022; ${escapeAttr(text("animated", "Animated"))}</span>`);
    }
    return out.length ? `<div class="aae-tl-badges">${out.join("")}</div>` : "";
  };

  const actionButton = (item) => {
    if (item && item.valid) {
      const reason = insertDisabledReason(item);
      return `
        <button class="library--action insert" data-aae-import-button ${reason ? `disabled title="${escapeAttr(reason)}"` : ""}>
          <i class="eicon-file-download"></i>
          ${escapeAttr(text("insert", "Insert"))}
        </button>`;
    }

    if (!CFG.pro_installed) {
      return `
        <a href="https://animation-addons.com" class="library--action pro" target="_blank">
          <i class="eicon-external-link-square"></i>
          Go Premium
        </a>`;
    }

    if (CFG.pro_installed && CFG.pro_active && !(CFG.config && CFG.config.wcf_valid)) {
      return `
        <a href="${escapeAttr(CFG.dashboard_link)}" class="library--action pro" target="_blank">
          <i class="eicon-external-link-square"></i>
          Activate License
        </a>`;
    }

    if (CFG.pro_installed && !CFG.pro_active) {
      return `
        <button class="library--action pro aaeplugin-activate">
          <i class="eicon-external-link-square"></i>
          Activate
        </button>`;
    }

    return "";
  };

  const generateTemplate = (item) => `
    <div class="wcf-library-template${isV4(item) ? " is-v4" : ""}" data-jurl="${escapeAttr(item.json_url)}" data-id="${escapeAttr(item.id)}" data-url="${escapeAttr(item.template_demo_url)}" data-builder="${isV4(item) ? "v4" : "v3"}" data-valid="${item.valid ? "1" : ""}" data-insert-disabled="${escapeAttr(insertDisabledReason(item))}">
      <div class="thumbnail">
        <img src="${escapeAttr(item && item.preview ? item.preview.url : "")}" alt="${escapeAttr(item.title)}">
      </div>
      ${badges(item)}
      ${actionButton(item)}
      <p class="title">${item.title}</p>
    </div>`;

  const listContainer = () => (state.content ? state.content.find(".wcf-library-templates").get(0) : null);

  /**
   * The load-more footer, which shipped rendering a literal ".", now says how
   * far down the list you are. It is the one place a visitor asks "is that
   * all of them", and the honest answer was already in every response.
   *
   * Note the count is the SERVER's, so it is the count for this filter — not
   * the number of cards drawn, which the era ordering re-sorts per page.
   */
  /**
   * Bold the search term in every card on screen, including the ones a later
   * page appends -- it used to run once over the first hundred and everything
   * after that arrived unmarked. Idempotent: it reads textContent each time,
   * never its own innerHTML.
   */
  const highlightSearch = () => {
    const container = listContainer();
    if (!container || !state.search) {
      return;
    }
    const re = new RegExp(state.search.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"), "i");
    container.querySelectorAll(".wcf-library-template .title").forEach((title) => {
      const plain = title.textContent;
      title.innerHTML = re.test(plain) ? plain.replace(re, "<b>$&</b>") : plain;
    });
  };

  const updateFooter = () => {
    const footer = state.content ? state.content.find(".aaeaadon-loadmore-footer").get(0) : null;
    if (!footer) {
      return;
    }
    const container = listContainer();
    const shown = container ? container.querySelectorAll(".wcf-library-template").length : 0;
    if (!shown || state.total === null) {
      footer.textContent = "";
      return;
    }
    const num = (n) => Number(n).toLocaleString();
    footer.textContent =
      shown >= state.total
        ? sprintf(text("all_shown", "All %s shown"), num(state.total))
        : sprintf(text("showing", "Showing %s of %s — keep scrolling for more"), num(shown), num(state.total));
  };

  const appendTemplates = (items) => {
    const container = listContainer();
    if (!container) {
      return;
    }
    items.forEach((item) => {
      container.insertAdjacentHTML("beforeend", generateTemplate(item));
    });
    const empty = container.querySelector(".aae-tl-empty");
    if (empty) {
      empty.remove();
    }
    highlightSearch();
    updateFooter();
    if (!container.querySelector(".wcf-library-template")) {
      // On an empty V4 list the version filter is the way out, so name it —
      // and name the right thing: the Pages tab holds no blocks.
      const what = state.builder === "v4"
        ? (state.type === "page"
          ? text("empty_v4_pages", "No Elementor V4 pages in this list yet — switch the version filter to V3 or All.")
          : text("empty_v4", "No Elementor V4 blocks in this list yet — switch the version filter to V3 or All."))
        : text("empty", "No templates found.");
      container.insertAdjacentHTML("beforeend", `<p class="aae-tl-empty" data-aae-tl-empty>${escapeAttr(what)}</p>`);
    }
  };

  const loading = (on) => {
    const $loading = state.content ? state.content.find(".wcf-template-library--loading") : $(".wcf-template-library--loading");
    if (on) {
      $loading.show().removeAttr("hidden");
    } else {
      $loading.hide().attr("hidden", "hidden");
    }
  };

  const notify = (message, sticky) => {
    try {
      if (window.elementor && elementor.notifications && elementor.notifications.showToast) {
        elementor.notifications.showToast({ message, sticky: !!sticky });
        return;
      }
    } catch (_) {
      /* fall through */
    }
    // eslint-disable-next-line no-alert
    window.alert(message);
  };

  /* ------------------------------------------------------------ rendering */

  const stopObserver = () => {
    if (state.observer) {
      state.observer.disconnect();
      state.observer = null;
    }
  };

  const watchLoadMore = () => {
    stopObserver();
    const items = document.querySelectorAll(".aaeaadon-loadmore-footer");
    if (!items.length || typeof IntersectionObserver === "undefined") {
      return;
    }
    const lastItem = items[items.length - 1];
    const seq = state.seq;
    let busy = false;

    state.observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(async (entry) => {
          if (!entry.isIntersecting || busy || state.exhausted) {
            return;
          }
          busy = true;
          try {
            const next = state.page + 1;
            const res = await fetchTemplates(next);
            if (seq !== state.seq) {
              return; // the list was re-rendered meanwhile; this page belongs to the old one
            }
            state.page = next;
            if (res.total !== null) {
              state.total = res.total;
              state.totalPages = res.pages;
            }
            // Either signal ends it: the server's own page count, or a page
            // that came back empty (a block server too old to send one).
            if (!res.items.length || (state.totalPages !== null && next >= state.totalPages)) {
              state.exhausted = true;
            }
            appendTemplates(res.items);
          } finally {
            busy = false;
          }
        });
      },
      { root: null, rootMargin: "0px", threshold: 0.1 }
    );
    state.observer.observe(lastItem);
  };

  const syncSelects = () => {
    $("#wcf-template-library-filter-subtype").val(state.category);
    $("#wcf-template-library-color-subtype").val(state.color);
    // All three versions, always. The era this site does not use decides
    // which one the filter OPENS on (defaultBuilder), never what it offers —
    // removing an option is how someone ends up unable to reach the V3
    // catalogue at all, with nothing on screen saying the control exists.
    const $builder = $("#wcf-template-library-builder");
    $builder.closest("#elementor-template-library-builder-toolbar-remote").removeAttr("hidden");
    $builder.val(state.builder);
  };

  /**
   * One quiet line under the toolbar when the list can show cards from an era
   * whose every widget is switched off. Those cards insert perfectly well —
   * the dependency dialog offers to switch on what each one needs — so this
   * says there is an extra step, not that anything is broken.
   *
   * Nothing when both eras are in use, and nothing while the filter is
   * standing on the era that IS in use: no card from the off era is on
   * screen then, so the line would be about nothing the reader can see.
   */
  const eraNotice = () => {
    const offered = eras();
    if (offered.v3 === offered.v4 || !state.content) {
      return;
    }
    const off = offered.v3 ? "v4" : "v3";
    if (state.builder !== "all" && state.builder !== off) {
      return;
    }
    const link = `<a href="${escapeAttr(CFG.widgets_link || CFG.dashboard_link || "#")}" target="_blank" rel="noopener">${escapeAttr(text("widgets_screen", "Animation Addons → Widgets"))}</a>`;
    const fallback = off === "v3"
      ? "Every Elementor V3 widget is switched off on this site. V3 sections still insert — the editor offers to switch on the widgets each one needs. You can also switch them on first in %s."
      : "Every Elementor V4 widget is switched off on this site. V4 blocks still insert — the editor offers to switch on the widgets each one needs. You can also switch them on first in %s.";
    const message = escapeAttr(text(`era_off_${off}`, fallback)).replace("%s", link);
    state.content.find("#elementor-template-library-toolbar").after(
      `<div class="aae-tl-notice" data-aae-tl-era-notice="${off}"><i class="eicon-info-circle" aria-hidden="true"></i><span>${message}</span></div>`
    );
  };

  const renderList = async () => {
    const $content = state.content;
    if (!$content) {
      return;
    }

    $content.find(".dialog-message").remove();
    $content.append(wp.template("wcf-templates")({ templates: [], categories: state.categories }));
    loading(true);
    syncSelects();
    eraNotice();

    if (!state.resized) {
      $(window).trigger("resize");
      state.resized = true;
    }

    // Switching the tab or a filter while a list is still loading used to
    // append BOTH answers into the new list: the Sections response landed in
    // the Pages tab (measured: 12 sections listed under "Pages", and a section
    // inserted from it). Only the most recent render may paint.
    const seq = ++state.seq;
    const res = await fetchTemplates(1);
    if (seq !== state.seq) {
      return;
    }
    state.page = 1;
    state.total = res.total;
    state.totalPages = res.pages;
    // An empty first page is the whole list, and so is a single-page one.
    // Without this the load-more footer under an empty tab intersected at
    // once and asked for page 2 of nothing (measured: every empty tab cost
    // a second request; so did the last page of every full list).
    state.exhausted = res.items.length === 0 || (res.pages !== null && res.pages <= 1);
    appendTemplates(res.items);
    watchLoadMore();

    const $last = $(".wcf-library-template").last().find("img");
    if ($last.length) {
      $last.on("load error", () => {
        loading(false);
        $(window).trigger("resize");
      });
    } else {
      loading(false);
    }
  };

  const openDialog = (section) => {
    if (window.wcftmLibrary) {
      // Already open: a second click on the same button must not stack a
      // second lightbox (and a second list) on top of the first.
      return;
    }
    state.section = section;

    window.wcftmLibrary = elementorCommon.dialogsManager.createWidget("lightbox", {
      id: "wcf-template-library",
      onShow() {
        this.getElements("widget").addClass("elementor-templates-modal");
        this.getElements("header").remove();
        this.getElements("message").remove();
        this.getElements("buttonsWrapper").remove();
        state.content = this.getElements("widgetContent");
        state.content.html(wp.template("wcf-templates-header")({ template_types: CFG.template_types }));
        state.type = state.content.find(".elementor-template-library-menu-item.elementor-active").attr("data-tab") || "block";
        renderList();
        if (!state.categories.length) {
          loadCategories().then(fillCategories);
        }
      },
      onHide() {
        teardown();
      },
    });

    window.wcftmLibrary.getElements("header").remove();
    window.wcftmLibrary.show();
    $(window).trigger("resize");
  };

  /** Forget the open modal and remove its markup. Idempotent. */
  const teardown = () => {
    stopObserver();
    state.content = null;
    state.listHtml = "";
    const dialog = window.wcftmLibrary;
    window.wcftmLibrary = null;
    if (dialog) {
      dialog.destroy();
    }
  };

  const hideDialog = () => {
    const dialog = window.wcftmLibrary;
    if (!dialog) {
      return;
    }
    dialog.hide();
    if (window.wcftmLibrary !== dialog) {
      return; // onHide ran
    }
    // DialogsManager's hide() returns silently while another dialog sits
    // above this one in its stack — a toast does (measured: the "images
    // stay linked" notice left the modal open over the inserted page, with
    // nothing to close it once the toast was gone). The insert is done;
    // take the modal down ourselves. destroy() drops it from the stack.
    teardown();
  };

  /* ------------------------------------------------------ insert helpers */

  const fetchJson = (jurl) =>
    new Promise((resolve, reject) => {
      $.get({ url: JSON_API + jurl, crossDomain: true })
        .done((data) => resolve(data))
        .fail(() => reject(new Error(text("failed", "The block could not be inserted."))));
    });

  const errorMessage = (data) => {
    if (!data) {
      return text("failed", "The block could not be inserted.");
    }
    if (typeof data === "string") {
      return data;
    }
    if (Array.isArray(data)) {
      return data.map((item) => (item && item.message) || item).join(" ");
    }
    if (data.message) {
      return data.message;
    }
    return text("failed", "The block could not be inserted.");
  };

  const editorAjax = (action, data) =>
    new Promise((resolve, reject) => {
      elementorCommon.ajax.addRequest(action, {
        data,
        success: resolve,
        error: (err) => reject(new Error(errorMessage(err))),
      });
    });

  const insertPosition = () => {
    if (!state.section || !state.section.length) {
      return undefined;
    }
    const index = state.section.parents(".elementor-add-section-inline").index();
    // The bottom "add section" button is not inline and yields -1, which
    // Backbone reads as "append" — the same behaviour as before.
    return index;
  };

  const runImport = (content, at, pageSettings) =>
    $e.run("document/elements/import", {
      model: window.elementor.elementsModel,
      data: { content, page_settings: pageSettings || {} },
      options: { at },
    });

  const hasGlobalStyles = (tpl) =>
    !!(
      (tpl.global_classes && tpl.global_classes.items && Object.keys(tpl.global_classes.items).length) ||
      (tpl.global_variables && tpl.global_variables.data && Object.keys(tpl.global_variables.data).length)
    );

  /**
   * Elementor's own "Choose how to apply styles" dialog, with the KEEP option
   * pre-selected and "add the classes and variables" ticked — a block from
   * the catalogue is inserted for its design, so `keep_create` is what "same
   * design" means. The markup is Elementor's printed template, so the dialog
   * looks exactly like the one its library shows. Resolves the import mode;
   * rejects when the user cancels.
   */
  const chooseGlobalStylesMode = () =>
    new Promise((resolve, reject) => {
      const template = wp.template && document.getElementById("tmpl-elementor-global-styles-dialog") ? wp.template("elementor-global-styles-dialog") : null;
      if (!template) {
        resolve("keep_create");
        return;
      }

      let settled = false;
      const dialog = elementorCommon.dialogsManager.createWidget("lightbox", {
        id: "elementor-global-styles-dialog",
        headerMessage: "",
        message: template(),
        position: { my: "center", at: "center" },
        hide: { onBackgroundClick: false },
        onShow() {
          const $content = dialog.getElements("message");
          const $match = $content.find("#elementor-global-styles-match");
          const $keep = $content.find("#elementor-global-styles-keep");
          const $create = $content.find("#elementor-global-styles-create");
          const $checkbox = $content.find(".elementor-global-styles-dialog__checkbox-container");

          $keep.prop("checked", true);
          $create.prop("checked", true);
          $checkbox.show();

          $match.on("change", () => $checkbox.hide());
          $keep.on("change", () => $checkbox.show());

          $content.find("#elementor-global-styles-insert").on("click", () => {
            if (settled) {
              return;
            }
            settled = true;
            let mode = "keep_flatten";
            if ($match.is(":checked")) {
              mode = "match_site";
            } else if ($create.is(":checked")) {
              mode = "keep_create";
            }
            resolve(mode);
            dialog.hide();
          });

          $content.find("#elementor-global-styles-cancel").on("click", () => {
            if (settled) {
              return;
            }
            settled = true;
            reject(new Error("cancelled"));
            dialog.hide();
          });
        },
        onHide() {
          if (!settled) {
            settled = true;
            reject(new Error("cancelled"));
          }
          // A hidden lightbox keeps its node; a second insert would create a
          // second one under the same id. Drop it once answered.
          setTimeout(() => dialog.destroy(), 400);
        },
      });
      dialog.show();
    });

  /**
   * Elementor's `process_global_styles` — rewrites the content's class
   * references for the chosen mode, creates / matches the classes and
   * variables, and answers with what changed. The `elementor/global-styles/
   * imported` event is what the V2 Classes and Variables stores listen to,
   * so the panel shows the new entries without a reload.
   */
  const applyGlobalStyles = async (tpl, content, mode) => {
    const result = await editorAjax("process_global_styles", {
      content: JSON.stringify(content),
      import_mode: mode,
      global_classes: tpl.global_classes ? JSON.stringify(tpl.global_classes) : null,
      global_variables: tpl.global_variables ? JSON.stringify(tpl.global_variables) : null,
    });

    if (result && (result.updated_global_classes || result.updated_global_variables)) {
      window.dispatchEvent(
        new CustomEvent("elementor/global-styles/imported", {
          detail: {
            global_classes: result.updated_global_classes,
            global_variables: result.updated_global_variables,
          },
        })
      );
    }

    // A created variable is in the kit but not yet in the kit's stylesheet;
    // the server clears Elementor's CSS cache so the next front-end render
    // declares it. Best-effort: the insert itself does not depend on it.
    if (result && result.updated_global_variables && Object.keys(result.updated_global_variables).length) {
      editorAjax("aaeaddon_v4_block_inserted", { variables: 1 }).catch(() => {});
    }

    return result && result.content ? result.content : content;
  };

  const asList = (content) => {
    if (Array.isArray(content)) {
      return content;
    }
    if (content && typeof content === "object") {
      return content.elType ? [content] : Object.values(content);
    }
    return [];
  };

  /** The editor-bridge's model sanitisers, when its bundle is on the page. */
  const sanitise = (roots) => {
    const helpers = window.AAEPresetApply || {};
    roots.forEach((root) => {
      if (typeof helpers.migrateLegacyWidgetShape === "function") {
        helpers.migrateLegacyWidgetShape(root);
      }
      if (typeof helpers.normalizeElementShape === "function") {
        helpers.normalizeElementShape(root);
      }
      if (typeof helpers.sanitizeBorderWidthType === "function") {
        helpers.sanitizeBorderWidthType(root);
      }
    });
    return roots;
  };

  const afterInsert = (created) => {
    const helpers = window.AAEPresetApply || {};
    const list = (Array.isArray(created) ? created : [])
      .filter((container) => container && container.id)
      .map((container) => ({ containerId: container.id }));
    if (!list.length) {
      return;
    }
    try {
      if (typeof helpers.stampContainerClassesIntoPreview === "function") {
        helpers.stampContainerClassesIntoPreview(list);
      }
      if (typeof helpers.syncAaeInteractionsToPreview === "function") {
        helpers.syncAaeInteractionsToPreview(list);
      }
    } catch (_) {
      /* cosmetic only */
    }
  };

  const sprintf = (str, ...args) => {
    let i = 0;
    return String(str).replace(/%[sd]/g, () => (i < args.length ? args[i++] : ""));
  };

  /* ------------------------------------------------ what a block needs */

  /**
   * The list of what this template uses that is switched off (or absent)
   * on this site, shown BEFORE anything is inserted. Resolves true when
   * the user chose to switch the listed widgets on, false when there is
   * nothing to switch on (nothing off, or only unavailable parts, which
   * the insert drops); rejects "cancelled".
   *
   * @param {{widgets:Array,extensions:Array,missing:Array,can_enable:boolean}} deps
   * @return {Promise<boolean>}
   */
  const confirmDependencies = (deps) =>
    new Promise((resolve, reject) => {
      const off = [].concat(deps.widgets || [], deps.extensions || []);
      const missing = deps.missing || [];
      if (!off.length && !missing.length) {
        resolve(false);
        return;
      }
      const canEnable = !!deps.can_enable && off.length > 0;

      const row = (item, kind, unavailable) => `
        <li class="aae-tl-deps__item${unavailable ? " is-unavailable" : ""}" data-aae-dep="${escapeAttr(item.slug)}">
          <span class="aae-tl-deps__label">${escapeAttr(item.label || item.slug)}</span>
          <span class="aae-tl-deps__kind">${escapeAttr(unavailable ? text("deps_unavailable", "Not available on this site — needs Animation Addons Pro") : kind)}</span>
        </li>`;

      const items = []
        .concat((deps.widgets || []).map((w) => row(w, text("deps_widget", "Widget"), false)))
        .concat((deps.extensions || []).map((x) => row(x, text("deps_extension", "Extension"), false)))
        .concat(missing.map((m) => row(m, "", true)))
        .join("");

      let body = "";
      if (off.length) {
        body = deps.can_enable
          ? text("deps_body", "Switch them on to insert it. The editor saves and reloads once to register them.")
          : text("deps_body_admin", "An administrator has to switch them on in Animation Addons → Widgets before it can be inserted.");
      }
      const note = missing.length ? `<p class="aae-tl-deps__note">${escapeAttr(text("deps_unavailable_note", "The parts marked as unavailable will be left out of the insert."))}</p>` : "";

      const message = `
        <div class="aae-tl-deps" data-aae-tl-deps data-aae-tl-deps-off="${off.length}" data-aae-tl-deps-missing="${missing.length}">
          <h3 class="aae-tl-deps__title">${escapeAttr(text("deps_title", "This template uses widgets that are switched off"))}</h3>
          ${body ? `<p class="aae-tl-deps__body">${escapeAttr(body)}</p>` : ""}
          <ul class="aae-tl-deps__list">${items}</ul>
          ${note}
          <div class="aae-tl-deps__actions">
            <button type="button" class="aae-tl-deps__btn aae-tl-deps__btn--cancel" data-aae-tl-deps-cancel>${escapeAttr(canEnable ? text("deps_cancel", "Cancel") : text("deps_close", "Close"))}</button>
            ${canEnable ? `<button type="button" class="aae-tl-deps__btn aae-tl-deps__btn--enable" data-aae-tl-deps-enable>${escapeAttr(text("deps_enable", "Switch on & insert"))}</button>` : ""}
            ${!canEnable && !off.length ? `<button type="button" class="aae-tl-deps__btn aae-tl-deps__btn--enable" data-aae-tl-deps-continue>${escapeAttr(text("insert", "Insert"))}</button>` : ""}
          </div>
        </div>`;

      let settled = false;
      const dialog = elementorCommon.dialogsManager.createWidget("lightbox", {
        id: "aae-tl-deps-dialog",
        headerMessage: "",
        message,
        position: { my: "center", at: "center" },
        hide: { onBackgroundClick: false },
        onShow() {
          const $content = dialog.getElements("message");
          $content.find("[data-aae-tl-deps-enable]").on("click", () => {
            if (settled) {
              return;
            }
            settled = true;
            resolve(true);
            dialog.hide();
          });
          $content.find("[data-aae-tl-deps-continue]").on("click", () => {
            if (settled) {
              return;
            }
            settled = true;
            resolve(false);
            dialog.hide();
          });
          $content.find("[data-aae-tl-deps-cancel]").on("click", () => {
            if (settled) {
              return;
            }
            settled = true;
            reject(new Error("cancelled"));
            dialog.hide();
          });
        },
        onHide() {
          if (!settled) {
            settled = true;
            reject(new Error("cancelled"));
          }
          setTimeout(() => dialog.destroy(), 400);
        },
      });
      dialog.show();
    });

  /**
   * Ask the server what the template needs, show it, and — when the user
   * says so — switch it on, park the insert and reload the editor (a widget
   * switched on in this request is not in elementor.config yet).
   *
   * @return {Promise<boolean>} true when the caller may go on and insert;
   *                            false when the editor is reloading.
   */
  const resolveDependencies = async ({ id, jurl, at, builder, tpl }) => {
    if (!tpl || !tpl.content) {
      return true;
    }
    const deps = await editorAjax("aaeaddon_template_dependencies", { content: asList(tpl.content), builder });
    const enable = await confirmDependencies(deps || {});
    if (!enable) {
      return true;
    }

    const widgets = (deps.widgets || []).map((w) => w.slug);
    const extensions = (deps.extensions || []).map((x) => x.slug);
    const result = await editorAjax("aaeaddon_enable_template_widgets", { builder, widgets, extensions });
    const enabled = [].concat((result && result.enabled) || [], (result && result.enabled_extensions) || []);
    if (!enabled.length) {
      return true; // nothing changed — already on, nothing to reload for
    }

    try {
      window.sessionStorage.setItem(
        PENDING_KEY,
        JSON.stringify({ id, jurl, at, builder, documentId: elementor.config.document.id })
      );
    } catch (_) {
      /* private mode: the user re-clicks Insert after the reload */
    }
    const names = [].concat(
      (deps.widgets || []).filter((w) => enabled.includes(w.slug)).map((w) => w.label || w.slug),
      (deps.extensions || []).filter((x) => enabled.includes(x.slug)).map((x) => x.label || x.slug)
    );
    notify(sprintf(text("switched_on", "Switched on %s. Saving and reloading the editor to finish the insert…"), names.join(", ")), true);
    try {
      const saved = await $e.run("document/save/auto", { force: true });
      if (saved && typeof saved.then === "function") {
        await saved;
      }
    } catch (_) {
      /* an autosave that fails still leaves the reload as the way forward */
    }
    window.location.reload();
    return false;
  };

  /* ------------------------------------------------------ the two paths */

  const insertV3 = async ({ id, jurl, at, tpl }) => {
    const data = {
      edit_mode: true,
      display: true,
      template_id: id,
    };
    if (jurl) {
      const file = tpl || (await fetchJson(jurl));
      if (!file || !file.content) {
        throw new Error(text("failed", "The block could not be inserted."));
      }
      data.json_data = file;
    }
    const result = await editorAjax("get_wcf_template_data", data);
    runImport(result.content, at, result.page_settings);
  };

  /**
   * @param {{id:string|number, jurl:string, at:number|undefined, resumed?:boolean}} args
   * @return {Promise<boolean>} true when inserted; false when the editor is
   *                            about to reload to finish the insert.
   */
  const insertV4 = async ({ id, jurl, at, resumed, tpl: prefetched }) => {
    if (!jurl) {
      throw new Error(text("failed", "The block could not be inserted."));
    }

    const tpl = prefetched || (await fetchJson(jurl));
    if (!tpl || !tpl.content) {
      throw new Error(text("failed", "The block could not be inserted."));
    }

    const prep = await editorAjax("aaeaddon_prepare_v4_block", { content: asList(tpl.content) });

    if (prep.missing && prep.missing.length) {
      throw new Error(sprintf(text("missing_widgets", "This block uses widgets that are switched off on this site (%s)."), prep.missing.join(", ")));
    }

    if (prep.enabled && prep.enabled.length && !resumed) {
      // A widget switched on in this request is not in elementor.config.elements
      // yet; the model constructor would throw. Park the insert, save, reload.
      try {
        window.sessionStorage.setItem(
          PENDING_KEY,
          JSON.stringify({ id, jurl, at, documentId: elementor.config.document.id })
        );
      } catch (_) {
        /* private mode: the user re-clicks Insert after the reload */
      }
      notify(sprintf(text("switched_on", "Switched on %s. Saving and reloading the editor to finish the insert…"), prep.enabled.join(", ")), true);
      try {
        const saved = await $e.run("document/save/auto", { force: true });
        if (saved && typeof saved.then === "function") {
          await saved;
        }
      } catch (_) {
        /* an autosave that fails still leaves the reload as the way forward */
      }
      window.location.reload();
      return false;
    }

    let content = sanitise(asList(prep.content));

    if (hasGlobalStyles(tpl)) {
      const mode = await chooseGlobalStylesMode();
      content = asList(await applyGlobalStyles(tpl, content, mode));
    }

    const created = runImport(content, at, tpl.page_settings);
    afterInsert(created);

    // Told AFTER the modal is gone: a toast is a dialog on the same stack,
    // and a stack with a toast on top refuses to hide the modal under it.
    const failed = prep.localized && prep.localized.failed ? prep.localized.failed : 0;
    if (failed) {
      window.setTimeout(() => notify(sprintf(text("linked", "%d image(s) could not be copied and stay linked to the template server."), failed)), 0);
    }

    return true;
  };

  const insertFromButton = async ($btn) => {
    if (state.inserting) {
      return;
    }
    const $card = $btn.closest(".wcf-library-template");
    const id = $btn.attr("data-id") || $card.data("id");
    const jurlBase = $btn.attr("data-jurl") || $card.attr("data-jurl") || "";
    const jurl = jurlBase ? jurlBase + ".json" : "";
    const builder = $btn.attr("data-builder") || $card.attr("data-builder") || "v3";
    const at = insertPosition();

    state.inserting = true;
    loading(true);
    $btn.hide();

    try {
      const tpl = jurl ? await fetchJson(jurl) : null;
      if (jurl && !(tpl && tpl.content)) {
        throw new Error(text("failed", "The block could not be inserted."));
      }
      let done = await resolveDependencies({ id, jurl, at, builder, tpl });
      if (done) {
        done = builder === "v4" ? await insertV4({ id, jurl, at, tpl }) : (await insertV3({ id, jurl, at, tpl }), true);
      }
      if (done) {
        hideDialog();
      }
    } catch (error) {
      if (!error || error.message !== "cancelled") {
        console.error("[AAE] template insert failed", error);
        notify(errorMessage(error));
      }
      $btn.show();
      loading(false);
    } finally {
      state.inserting = false;
    }
  };

  /**
   * An insert parked before a reload (a widget was switched on). Runs once
   * the preview is up, so the new widget's config is in the editor.
   */
  const resumePending = async () => {
    let pending = null;
    try {
      const raw = window.sessionStorage.getItem(PENDING_KEY);
      pending = raw ? JSON.parse(raw) : null;
      if (raw) {
        window.sessionStorage.removeItem(PENDING_KEY);
      }
    } catch (_) {
      return;
    }
    if (!pending || !pending.jurl || !window.elementor || !elementor.config || !elementor.config.document) {
      return;
    }
    if (String(pending.documentId) !== String(elementor.config.document.id)) {
      return;
    }
    state.inserting = true;
    try {
      if (pending.builder === "v3") {
        await insertV3({ id: pending.id, jurl: pending.jurl, at: pending.at });
      } else {
        await insertV4({ id: pending.id, jurl: pending.jurl, at: pending.at, resumed: true });
      }
    } catch (error) {
      if (!error || error.message !== "cancelled") {
        console.error("[AAE] template insert failed", error);
        notify(errorMessage(error));
      }
    } finally {
      state.inserting = false;
    }
  };

  /* ---------------------------------------------------------- handlers */

  $(function () {
    const templateAddSection = $("#tmpl-elementor-add-section");
    if (templateAddSection.length) {
      templateAddSection.html(
        templateAddSection
          .html()
          .replace(
            '<div class="elementor-add-section-drag-title',
            '<div class="elementor-add-section-area-button elementor-add-wcf-template-button"></div><div class="elementor-add-section-drag-title'
          )
      );
    }

    if (!window.elementor || !elementor.on) {
      return;
    }

    let resumed = false;
    elementor.on("preview:loaded", function () {
      $(elementor.$previewContents[0].body)
        .off("click.aaeTemplateLibrary")
        .on("click.aaeTemplateLibrary", ".elementor-add-wcf-template-button", function (event) {
          event.preventDefault();
          openDialog($(this));
        });

      if (!resumed) {
        resumed = true;
        setTimeout(resumePending, 300);
      }
    });
  });

  // Tabs (Block / Page)
  $(document).on("click", "#wcf-template-library .elementor-template-library-menu-item", function () {
    const $item = $(this);
    if ($item.hasClass("elementor-active")) {
      return;
    }
    $("#wcf-template-library .elementor-template-library-menu-item").removeClass("elementor-active");
    $item.addClass("elementor-active");
    state.type = $item.attr("data-tab") || "block";
    state.search = "";
    renderList();
  });

  // Filters
  $(document).on("change", "#wcf-template-library-filter-subtype", function () {
    state.category = this.value;
    renderList();
  });
  $(document).on("change", "#wcf-template-library-color-subtype", function () {
    state.color = this.value;
    renderList();
  });
  $(document).on("change", "#wcf-template-library-builder", function () {
    state.builder = this.value || "all";
    renderList();
  });

  // Search
  let searchTimer = null;
  $(document).on("keyup", "#wcf-template-library-filter-text", function () {
    const value = this.value;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
      state.search = value.trim();
      const container = listContainer();
      if (!container) {
        return;
      }
      stopObserver();
      const res = await fetchTemplates(1);
      container.innerHTML = "";
      state.page = 1;
      state.total = res.total;
      state.totalPages = res.pages;
      state.exhausted = res.items.length === 0 || (res.pages !== null && res.pages <= 1);
      appendTemplates(res.items);
      // Always re-arm. A search used to stop the observer and never start it
      // again, so a term matching 400 blocks showed the first hundred and
      // nothing on earth would load the rest.
      watchLoadMore();
    }, 300);
  });

  // Preview
  $(document).on("click", "#wcf-template-library .thumbnail", function () {
    const $card = $(this).closest(".wcf-library-template");
    const $content = state.content;
    if (!$content) {
      return;
    }
    stopObserver();
    state.listHtml = $content.html();
    $content.html(
      wp.template("wcf-templates-single")({
        template_link: $card.data("url"),
        template_id: $card.data("id"),
        jurl: $card.attr("data-jurl") || "",
        builder: $card.attr("data-builder") || "v3",
        valid: !!$card.attr("data-valid"),
        insert_disabled: $card.attr("data-insert-disabled") || "",
      })
    );
    loading(true);
    $("#wcf-template-library iframe").on("load", () => loading(false));
  });

  $(document).on("click", "#wcf-template-library-header-preview-back", function () {
    if (!state.content || !state.listHtml) {
      return;
    }
    state.content.html(state.listHtml);
    state.listHtml = "";
    loading(false);
    syncSelects();
    watchLoadMore();
  });

  $(document).on("click", "#wcf-template-library .elementor-templates-modal__header__close", function () {
    hideDialog();
  });

  // Insert — ONE handler for the grid card and the preview header.
  $(document).on("click", "#wcf-template-library .library--action.insert", function (event) {
    event.preventDefault();
    const $btn = $(this);
    if ($btn.is(":disabled") || $btn.attr("disabled")) {
      return;
    }
    insertFromButton($btn);
  });

  $(document).on("click", ".aaeplugin-activate", function (e) {
    e.preventDefault();
    // eslint-disable-next-line no-alert
    if (window.confirm("Are you sure you want to activate plugin? Any unsaved changes will be lost. Please Save change.")) {
      fetch(CFG.ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded", Accept: "application/json" },
        body: new URLSearchParams({
          action: "aaeaddon_activate_from_editor_plugin",
          action_base: "animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php",
          nonce: CFG.nonce,
        }),
      })
        .then((response) => response.json())
        .then((res) => {
          if (res && res.success) {
            window.location.reload();
          }
        })
        .catch(() => {});
    }
  });

  // For the suite and for debugging: the state and the two paths.
  window.aaeaddonTemplateLibrary = { state, insertV4, insertV3, renderList, resolveDependencies, eras, PENDING_KEY };
})(jQuery, window, document);
