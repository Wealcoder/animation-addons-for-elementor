(function () {

  "use strict";

  // --------------------------------------------------
  // GLOBAL OBSERVER (single instance)
  // --------------------------------------------------

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        playAnimation(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  // --------------------------------------------------
  // INIT
  // --------------------------------------------------

  function initStarterAnimations(scope) {

    const wrapper = scope[0];
    if (!wrapper) return;

    if (!wrapper.className.includes('wcf-starter-animations-')) return;

    if (wrapper.dataset.wcfInit) return;
    wrapper.dataset.wcfInit = "1";

    handleChar(wrapper);
    handleWave(wrapper);

    // If already visible → play immediately
    if (isVisible(wrapper)) {
      playAnimation(wrapper);
    } else {
      observer.observe(wrapper);
    }
  }

  // --------------------------------------------------
  // PLAY FUNCTION (Reveal Safe)
  // --------------------------------------------------

  function playAnimation(wrapper) {

    if (!wrapper) return;

    wrapper.classList.remove('wcf-animate');

    // Character Animation Special Reset
    if (wrapper.classList.contains('wcf-starter-animations-text-char-animate')) {

      const target =
        wrapper.querySelector('.elementor-widget-container > *') ||
        wrapper.firstElementChild;

      if (target) {

        const originalText = target.textContent;

        // Reset DOM completely
        target.innerHTML = originalText;
        target.dataset.charInit = "";

        // Re-split characters
        handleChar(wrapper);
      }
    }

    void wrapper.offsetWidth;

    wrapper.classList.add('wcf-animate');
  }

  // --------------------------------------------------
  // VISIBILITY CHECK
  // --------------------------------------------------

  function isVisible(el) {
    const rect = el.getBoundingClientRect();
    return rect.top < window.innerHeight && rect.bottom > 0;
  }

  // --------------------------------------------------
  // CHARACTER SPLIT
  // --------------------------------------------------

  function handleChar(wrapper) {

    if (!wrapper.className.includes('text-char')) return;

    const target =
      wrapper.querySelector('.elementor-widget-container > *') ||
      wrapper.firstElementChild;

    if (!target || target.dataset.charInit) return;

    const text = target.textContent.trim();
    if (!text) return;

    target.innerHTML = '';

    [...text].forEach((char, i) => {
      const span = document.createElement('span');
      span.textContent = char;
      span.style.setProperty('--i', i);
      target.appendChild(span);
    });

    target.dataset.charInit = "1";
  }

  // --------------------------------------------------
  // WAVE SUPPORT
  // --------------------------------------------------

  function handleWave(wrapper) {

    if (!wrapper.classList.contains('wcf-starter-animations-text-wave')) return;

    const target =
      wrapper.querySelector('.elementor-widget-container > *') ||
      wrapper.firstElementChild;

    if (!target || target.dataset.waveInit) return;

    target.setAttribute('data-text', target.textContent.trim());
    target.dataset.waveInit = "1";
  }

  // --------------------------------------------------
  // SIMPLE REPLAY (Widget Based)
  // --------------------------------------------------

  window.wcfReplayAnimation = function (wrapper) {
    playAnimation(wrapper);
  };

  // --------------------------------------------------
  // ELEMENTOR HOOK
  // --------------------------------------------------

  window.addEventListener('elementor/frontend/init', function () {

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/global',
      initStarterAnimations
    );

  });

})();