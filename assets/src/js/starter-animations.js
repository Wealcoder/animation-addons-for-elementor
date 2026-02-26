(function () {

  "use strict";

  /* --------------------------------------------
     GLOBAL OBSERVER (Simple & Stable)
  --------------------------------------------- */

  const observer = new IntersectionObserver((entries) => {

    entries.forEach((entry) => {

      const wrapper = entry.target;

      const isRepeat   = wrapper.classList.contains("wcf-repeat-yes");
      const playedOnce = wrapper.classList.contains("wcf-played");

      /* ---------------- PLAY ---------------- */

      if (entry.isIntersecting) {

        if (isRepeat) {

          playAnimation(wrapper);

        } else {

          if (!playedOnce) {
            playAnimation(wrapper);
            wrapper.classList.add("wcf-played");
          }

        }

      }

      /* ---------------- RESET (Repeat Mode) ---------------- */

      if (!entry.isIntersecting && isRepeat) {

        wrapper.classList.remove("wcf-animate");
        wrapper.classList.remove("wcf-played");

      }

    });

  }, { threshold: 0.1 });

  /* --------------------------------------------
     INIT
  --------------------------------------------- */

  function initStarterAnimations(scope) {

    const wrapper = scope[0];
    if (!wrapper) return;

    if (!wrapper.className.includes("wcf-starter-animations-")) return;

    if (!wrapper.dataset.wcfInit) {

      wrapper.dataset.wcfInit = "1";

      handleChar(wrapper);
      handleWave(wrapper);

    }

    observer.observe(wrapper);
  }

  /* --------------------------------------------
     PLAY FUNCTION
  --------------------------------------------- */

  function playAnimation(wrapper) {

    if (!wrapper) return;

    wrapper.classList.remove("wcf-animate");

    const target =
      wrapper.querySelector(".elementor-widget-container > *") ||
      wrapper.firstElementChild;

    if (wrapper.classList.contains("wcf-starter-animations-text-char-animate")) {

      if (target) {

        const originalText = target.textContent;
        target.innerHTML = originalText;
        target.dataset.charInit = "";
        handleChar(wrapper);

      }
    }

    if (wrapper.classList.contains("wcf-starter-animations-text-wave")) {

      if (target) {

        target.removeAttribute("data-text");
        target.setAttribute("data-text", target.textContent.trim());
        target.dataset.waveInit = "";

      }
    }

    void wrapper.offsetWidth;

    wrapper.classList.add("wcf-animate");
  }

  /* --------------------------------------------
     CHARACTER SPLIT
  --------------------------------------------- */

  function handleChar(wrapper) {

    if (!wrapper.className.includes("text-char")) return;

    const target =
      wrapper.querySelector(".elementor-widget-container > *") ||
      wrapper.firstElementChild;

    if (!target || target.dataset.charInit) return;

    const text = target.textContent;
    if (!text) return;

    target.innerHTML = "";

    let globalIndex = 0;

    // Split by space but preserve spacing
    const words = text.split(" ");

    words.forEach((word, wordIndex) => {

      const wordSpan = document.createElement("span");
      wordSpan.classList.add("wcf-word");
      wordSpan.style.display = "inline-block";

      [...word].forEach((char) => {

        const charSpan = document.createElement("span");
        charSpan.textContent = char;
        charSpan.style.display = "inline-block";
        charSpan.style.setProperty("--i", globalIndex);

        wordSpan.appendChild(charSpan);

        globalIndex++;
      });

      target.appendChild(wordSpan);

      // Add real space between words
      if (wordIndex < words.length - 1) {
        const space = document.createTextNode(" ");
        target.appendChild(space);
      }

    });

    target.dataset.charInit = "1";
  }

  /* --------------------------------------------
     WAVE SUPPORT
  --------------------------------------------- */

  function handleWave(wrapper) {

    if (!wrapper.classList.contains("wcf-starter-animations-text-wave")) return;

    const target =
      wrapper.querySelector(".elementor-widget-container > *") ||
      wrapper.firstElementChild;

    if (!target) return;

    target.setAttribute("data-text", target.textContent.trim());
    target.dataset.waveInit = "1";
  }

  /* --------------------------------------------
     MANUAL REPLAY
  --------------------------------------------- */

  window.wcfReplayAnimation = function (wrapper) {
    playAnimation(wrapper);
  };

  /* --------------------------------------------
     ELEMENTOR HOOK
  --------------------------------------------- */

  window.addEventListener("elementor/frontend/init", function () {

    elementorFrontend.hooks.addAction(
      "frontend/element_ready/global",
      initStarterAnimations
    );

  });

})();