/* global WCF_ADDONS_JS */

(function ($) {
  /**
   * @param $scope The Widget wrapper element as a jQuery element
   * @param $ The jQuery alias
   */
  // Make sure you run this code under Elementor.
  $(window).on("elementor/frontend/init", function () {
    const Modules = elementorModules.frontend.handlers.Base;

    // Widget settings are author-controlled (Contributor and up can set
    // them) and reach the browser raw, so they must never go into the page
    // as-is (CVE-2025-8444). Plain escaping would print any tags as text,
    // so instead keep a small set of formatting tags and strip the rest.
    const escapeHtml = (value) =>
      String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

    // Tags kept as-is. Every attribute on them is dropped except the ones
    // listed, and an href must use a safe scheme.
    const ALLOWED_TAGS = {
      B: [], STRONG: [], I: [], EM: [], U: [], S: [], SMALL: [], MARK: [],
      SUB: [], SUP: [], BR: [], SPAN: ["class"], P: ["class"],
      A: ["href", "target", "rel", "class"],
    };

    // Tags removed together with everything inside them. Any other tag
    // that is not allowed is unwrapped: the tag goes, its text stays.
    const DROPPED_TAGS = new Set([
      "SCRIPT", "STYLE", "IFRAME", "FRAME", "FRAMESET", "OBJECT", "EMBED",
      "IMG", "SVG", "MATH", "VIDEO", "AUDIO", "SOURCE", "TRACK", "PICTURE",
      "CANVAS", "LINK", "META", "BASE", "FORM", "INPUT", "BUTTON",
      "TEXTAREA", "SELECT", "OPTION", "TEMPLATE", "NOSCRIPT", "TITLE",
      "XMP", "NOEMBED", "NOFRAMES", "PLAINTEXT", "LISTING",
    ]);

    const SAFE_HREF = /^(https?:|mailto:|tel:|#|\/(?!\/)|[^:]*$)/i;

    const cleanNode = (node) => {
      [...node.childNodes].forEach((child) => {
        if (child.nodeType === 3) {
          return; // text
        }
        if (child.nodeType !== 1) {
          child.remove(); // comments and the like
          return;
        }
        const tag = child.tagName.toUpperCase();
        if (DROPPED_TAGS.has(tag)) {
          child.remove();
          return;
        }
        cleanNode(child);
        if (!ALLOWED_TAGS[tag]) {
          child.replaceWith(...child.childNodes);
          return;
        }
        [...child.attributes].forEach((attr) => {
          const name = attr.name.toLowerCase();
          const keep =
            ALLOWED_TAGS[tag].includes(name) &&
            (name !== "href" || SAFE_HREF.test(attr.value.replace(/[\s\u0000-\u001f]/g, "")));
          if (!keep) {
            child.removeAttribute(attr.name);
          }
        });
        if (tag === "A" && child.getAttribute("target") === "_blank") {
          child.setAttribute("rel", "noopener noreferrer");
        }
      });
    };

    // DOMParser builds an inert document: nothing in it loads or runs.
    // Without DOMParser, fall back to printing everything as text.
    const sanitizeHtml = (value) => {
      const html = String(value ?? "");
      if (typeof window.DOMParser !== "function") {
        return escapeHtml(html);
      }
      const doc = new window.DOMParser().parseFromString(
        `<!doctype html><body>${html}`,
        "text/html"
      );
      cleanNode(doc.body);
      return doc.body.innerHTML;
    };

    const Countdown = Modules.extend({
      bindEvents: function bindEvents() {
        this.run();
      },

      run: function run() {
        // Update the count down every 1 second
        const x = setInterval(() => {
          this.count_down(x);
        }, 1000);

        this.count_down(x);
      },

      count_down: function count_down(x) {
        // Set the date we're counting down to
        const countDownDate = new Date(
          this.getElementSettings("countdown_timer_due_date")
        ).getTime();

        // Get today's date and time
        let now = new Date().getTime();

        // Find the distance between now and the count down date
        let distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor(
          (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // If the count down is over, write some text
        if (distance < 0) {
          clearInterval(x);
          this.findElement(".wcf--countdown").html(this.time_finish_content());
        } else {
          this.findElement(".wcf--countdown").html(
            this.timer_content({
              days: days,
              hours: hours,
              minutes: minutes,
              seconds: seconds,
            })
          );
        }
      },

      timer_content: function timer_content(times = []) {
        if (0 === times.length) {
          return;
        }
        let time_content = "";

        $.each(times, (index, time) => {
          const title = this.getElementSettings(
            `countdown_timer_${index}_label`
          );
          time_content += `<div class="timer-content timer-item-${index} "><span class="time-count ${index}-count">${time}</span><span class="time-title ${index}-title">${sanitizeHtml(title)}</span></div>`;
        });

        return time_content;
      },

      time_finish_content: function () {
        const title = this.getElementSettings("time_expire_title");
        const description = this.getElementSettings("time_expire_desc");
        let finish_content = '<div class="countdown-expire">';
        if (title) {
          finish_content += `<div class="countdown-expire-title">${sanitizeHtml(title)}</div>`;
        }
        if (description) {
          // Turn the textarea line breaks into <br> first, then sanitize,
          // so multi-line descriptions keep their layout.
          const safe_desc = sanitizeHtml(String(description).replace(/\r?\n/g, "<br>"));
          finish_content += `<div class="countdown-expire-desc">${safe_desc}</div>`;
        }
        finish_content += "</div>";

        return finish_content;
      },
    });

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/wcf--countdown.default",
      function ($scope) {
        elementorFrontend.elementsHandler.addHandler(Countdown, {
          $element: $scope,
        });
      }
    );
  });
})(jQuery);
