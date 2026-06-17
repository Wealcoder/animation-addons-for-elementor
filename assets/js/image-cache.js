/* assets/js/image-cache.js
 * Animation Addons for Elementor — Dashboard/Editor Idle Image Cache Prefetch
 * - Runs on allowed admin screens + Elementor editor (enqueued via PHP)
 * - Waits for idle, then:
 *    - In Elementor editor: forces all images in preview iframe to load (incl. lazy)
 *    - On other admin pages: warms images discovered from template APIs
 * - Supports MULTIPLE API endpoints + themecrowdy custom schema
 * - Uses a Web Worker (Blob) to fetch & parse API responses off the main thread
 */

 document.addEventListener('DOMContentLoaded', () => {
  const CFG = Object.assign(
    {
      idleMs: 6000,
      maxConcurrency: 8,
      simulateScroll: true,
      scrollStep: 800,
      dailyOnce: true,
      apiUrls: [],     // array of endpoints
      apiUrl: "",      // or a single endpoint
      debug: false,
    },
    window.AAE_EDITOR_PRELOAD || {}
  );

  const log = (...a) => {
    if (CFG.debug) console.log("[AAE image-cache]", ...a);
  };

  // ============ Idle & visibility ============
  let running = false;
  let idleTimer = null;
  const ACT = [
    "mousemove",
    "mousedown",
    "keydown",
    "wheel",
    "touchstart",
    "pointerdown",
  ];

  const resetIdle = () => {
    if (idleTimer) clearTimeout(idleTimer);
    if (running) return;
    idleTimer = setTimeout(kickoff, CFG.idleMs || 6000);
  };

  ACT.forEach((ev) =>
    document.addEventListener(ev, resetIdle, { passive: true })
  );
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) resetIdle();
  });
  setTimeout(resetIdle, 1000);

  // ============ LocalStorage day flags ============
  const kBase = "aae_editor_preload_";
  const today = new Date().toISOString().slice(0, 10);
  const dayKey = `${kBase}day_${today}`;
  const setLS = (k, v) => {
    try {
      localStorage.setItem(k, JSON.stringify(v));
    } catch (e) {}
  };
  const getLS = (k, d = null) => {
    try {
      const v = localStorage.getItem(k);
      return v ? JSON.parse(v) : d;
    } catch (e) {
      return d;
    }
  };
  const onceToday = () => !!getLS(dayKey, false);
  const markToday = () => setLS(dayKey, true);

  // ============ Utilities ============
  const uniq = (arr) => Array.from(new Set(arr.filter(Boolean)));
  const getIframe = () => document.querySelector("#elementor-preview-iframe");
  const getDoc = () => {
    const ifr = getIframe();
    return ifr && (ifr.contentDocument || ifr.contentWindow?.document);
  };

  const backgroundUrl = (el, win) => {
    try {
      const cs = win.getComputedStyle(el);
      const bg = cs.getPropertyValue("background-image");
      const m = /url\((?:"|')?(.*?)(?:"|')?\)/.exec(bg);
      const u = m && m[1] ? m[1] : null;
      return u && !u.startsWith("data:") ? u : null;
    } catch (e) {
      return null;
    }
  };

  const prefetch = (doc, url) => {
    try {
      const l = doc.createElement("link");
      l.rel = "prefetch";
      l.as = "image";
      l.href = url;
      doc.head.appendChild(l);
    } catch (e) {}
  };

  const forceImgLoad = (img) => {
    try {
      img.loading = "eager";
      img.decoding = "async";
      const ds = img.getAttribute("data-src");
      const dss = img.getAttribute("data-srcset");
      if (ds && !img.src) img.src = ds;
      if (dss && !img.getAttribute("srcset")) img.setAttribute("srcset", dss);
      if (!img.src && img.currentSrc) img.src = img.currentSrc;
      (img.classList || { remove() {} }).remove(
        "lazyloaded",
        "lazyload",
        "is-lazy"
      );
    } catch (e) {}
  };

  const collectAllImagesInIframe = (doc) => {
    const win = doc.defaultView || window;
    const urls = [];
    const imgs = Array.from(doc.querySelectorAll("img"));

    imgs.forEach((img) => {
      forceImgLoad(img);
      if (img.currentSrc) urls.push(img.currentSrc);
      if (img.src) urls.push(img.src);
      const srcset = img.getAttribute("srcset");
      if (srcset)
        srcset.split(",").forEach((p) => {
          const u = p.trim().split(/\s+/)[0];
          if (u) urls.push(u);
        });
    });

    doc.querySelectorAll("picture source[srcset]").forEach((s) => {
      const ss = s.getAttribute("srcset");
      if (ss)
        ss.split(",").forEach((p) => {
          const u = p.trim().split(/\s+/)[0];
          if (u) urls.push(u);
        });
    });

    doc.querySelectorAll("*").forEach((el) => {
      const u = backgroundUrl(el, win);
      if (u) urls.push(u);
    });

    return { imgs, urls: uniq(urls).filter((u) => !u.startsWith("data:")) };
  };

  const decodeWithLimit = async (imgs, concurrency = 8) => {
    const q = imgs.slice();
    const workers = new Array(Math.max(1, concurrency)).fill(0).map(async () => {
      while (q.length) {
        const img = q.shift();
        try {
          if ("decode" in img) {
            await img.decode();
          } else {
            await new Promise((res) => {
              img.complete
                ? res()
                : img.addEventListener("load", res, { once: true });
            });
          }
        } catch (e) {}
      }
    });
    await Promise.allSettled(workers);
  };

  const simulateScroll = async (win, step = 800) => {
    try {
      const { document: doc } = win;
      const H = Math.max(
        doc.body.scrollHeight,
        doc.documentElement.scrollHeight
      );
      for (let y = 0; y <= H; y += step) {
        win.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 16));
      }
      win.scrollTo(0, 0);
    } catch (e) {}
  };

  const warmUrls = (doc, urls, cap = 300) => {
    urls.slice(0, cap).forEach((u) => {
      try {
        prefetch(doc, u);
      } catch (e) {}
      try {
        const im = new Image();
        im.decoding = "async";
        im.loading = "eager";
        im.src = u;
      } catch (e) {}
    });
  };

  // ============ Web Worker (Blob) for MULTI-API fetch (supports WP REST + themecrowdy schema) ============
  const makeApiWorker = () => {
    const workerCode = `
      const uniq = (arr) => Array.from(new Set(arr.filter(Boolean)));

      // Normalize any page JSON into a flat items array
      const toItems = (json) => {
        if (!json) return [];
        if (Array.isArray(json)) return json;                // WP REST array
        if (json.templates && Array.isArray(json.templates)) return json.templates; // themecrowdy
        if (json.data && Array.isArray(json.data.templates)) return json.data.templates;
        return [];
      };

      const extractWPUrls = (items) => {
        const urls = [];
        items.forEach(item => {
          if (!item || typeof item !== 'object') return;
          if (item.featured_media_url) urls.push(item.featured_media_url);
          if (item.jetpack_featured_media_url) urls.push(item.jetpack_featured_media_url);
          if (item.better_featured_image?.source_url) urls.push(item.better_featured_image.source_url);
          if (item.meta?.preview_image) urls.push(item.meta.preview_image);
          if (item.thumbnail) urls.push(item.thumbnail);

          if (item.content?.rendered) {
            const html = item.content.rendered;
            let m;
            const srcRe = /<img[^>]+src=["']([^"']+)["']/gi;
            while ((m = srcRe.exec(html)) !== null) { urls.push(m[1]); }

            let n;
            const ssRe = /srcset=["']([^"']+)["']/gi;
            while ((n = ssRe.exec(html)) !== null) {
              n[1].split(',').forEach(p => {
                const u = p.trim().split(/\\s+/)[0];
                if (u) urls.push(u);
              });
            }
          }
        });
        return urls;
      };

      const extractCrowdyUrls = (items) => {
        const urls = [];
        items.forEach(t => {
          if (!t || typeof t !== 'object') return;

          if (t.template_preview) urls.push(t.template_preview);
          if (t.landing_image?.url) urls.push(t.landing_image.url);

          const sizes = t.landing_image?.sizes || {};
          Object.keys(sizes).forEach(k => {
            const v = sizes[k];
            if (typeof v === 'string' && /^https?:\\/\\//i.test(v)) urls.push(v);
          });

          if (t.content?.rendered) {
            const html = t.content.rendered;
            let m;
            const srcRe = /<img[^>]+src=["']([^"']+)["']/gi;
            while ((m = srcRe.exec(html)) !== null) { urls.push(m[1]); }

            let n;
            const ssRe = /srcset=["']([^"']+)["']/gi;
            while ((n = ssRe.exec(html)) !== null) {
              n[1].split(',').forEach(p => {
                const u = p.trim().split(/\\s+/)[0];
                if (u) urls.push(u);
              });
            }
          }
        });
        return urls;
      };

      const fetchAllPages = async (baseUrl) => {
        const out = [];
        try {
          const first = await fetch(baseUrl, { credentials: 'omit' });
          if (!first.ok) return out;
          const j1 = await first.json();
          out.push(j1);

          const totalPages = Math.min(parseInt(first.headers.get('X-WP-TotalPages') || '1', 10), 3);
          for (let p = 2; p <= totalPages; p++) {
            const url = baseUrl.replace(/([?&])page=\\d+/, '$1page=' + p) + (baseUrl.includes('page=') ? '' : (baseUrl.includes('?') ? '&' : '?') + 'page=' + p);
            const r = await fetch(url, { credentials: 'omit' });
            if (!r.ok) break;
            const j = await r.json();
            out.push(j);
          }
        } catch (err) {}
        return out; // array of page JSONs
      };

      self.onmessage = async (e) => {
        const { apiUrls = [], apiUrl = '' } = e.data || {};
        try {
          const endpoints = uniq([...(Array.isArray(apiUrls) ? apiUrls : []), apiUrl].filter(Boolean));
          let allUrls = [];

          for (const ep of endpoints) {
            const pages = await fetchAllPages(ep);
            for (const pageJson of pages) {
              const items = toItems(pageJson);
              const isCrowdy = !!(pageJson && pageJson.templates);
              const urls = isCrowdy ? extractCrowdyUrls(items) : extractWPUrls(items);
              allUrls.push(...urls);
            }
          }

          postMessage({ urls: uniq(allUrls).filter(u => !/^data:/i.test(u)) });
        } catch (err) {
          postMessage({ urls: [] });
        }
      };
    `;
    const blob = new Blob([workerCode], { type: "application/javascript" });
    return new Worker(URL.createObjectURL(blob));
  };

  const fetchApiUrlsInWorker = () =>
    new Promise((resolve) => {
      const hasAny = (CFG.apiUrls && CFG.apiUrls.length) || CFG.apiUrl;
      if (!hasAny) return resolve([]);
      try {
        const w = makeApiWorker();
        const t = setTimeout(() => {
          try {
            w.terminate();
          } catch (e) {}
          resolve([]);
        }, 25000);
        w.onmessage = (e) => {
          clearTimeout(t);
          try {
            w.terminate();
          } catch (e) {}
          const arr = Array.isArray(e.data?.urls) ? e.data.urls : [];
          resolve(arr);
        };
        w.postMessage({ apiUrls: CFG.apiUrls || [], apiUrl: CFG.apiUrl || "" });
      } catch (e) {
        resolve([]);
      }
    });

  // ============ Core flow ============
  const runPreload = async () => {
    const ifr = getIframe();
    const isEditor = !!ifr;

    // Always get library images via worker (off main thread)
    const libUrls = await fetchApiUrlsInWorker();

    if (isEditor) {
      const doc = getDoc();
      if (!doc || !doc.body) return false;

      if (CFG.simulateScroll) {
        await simulateScroll(ifr.contentWindow, CFG.scrollStep || 800);
      }

      const { imgs, urls } = collectAllImagesInIframe(doc);
      const all = uniq([...urls, ...libUrls]);

      warmUrls(doc, all, 300);
      await decodeWithLimit(imgs, CFG.maxConcurrency || 8);

      setLS(`${kBase}last_report`, {
        at: Date.now(),
        mode: "editor",
        totalImgs: imgs.length,
        totalUrls: all.length,
      });
      log("editor preload complete", {
        totalImgs: imgs.length,
        totalUrls: all.length,
      });
      return true;
    }

    // Admin screens (no iframe): just warm library images into cache
    warmUrls(document, libUrls, 300);
    setLS(`${kBase}last_report`, {
      at: Date.now(),
      mode: "admin",
      totalUrls: libUrls.length,
    });
    log("admin preload complete", { totalUrls: libUrls.length });
    return true;
  };

  const kickoff = async () => {
    if (running) return;
    if (CFG.dailyOnce && onceToday()) {
      log("already ran today");
      return;
    }
    running = true;

    const start = async () => {
      try {
        await runPreload();
        if (CFG.dailyOnce) markToday();
      } finally {
        running = false;
        resetIdle();
      }
    };

    // If Elementor exists, wait for preview to be ready; otherwise run
    if (window.elementor && typeof elementor.on === "function") {
      if (elementor.previewView && elementor.previewView.loaded) {
        start();
      } else {
        const h = () => {
          elementor.off("preview:loaded", h);
          start();
        };
        elementor.on("preview:loaded", h);
      }
    } else {
      start();
    }
  };

  // Manual trigger for debugging
  window.AAE_forcePreload = () => {
    running = false;
    kickoff();
  };
  window.AAE_forcePreload();
});

