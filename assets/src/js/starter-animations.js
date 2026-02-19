(function () {

  "use strict";

  function initStarterAnimations(scope) {
    console.log('Initializing Starter Animations');

    const wrapper = scope[0];
    if (!wrapper) return;

    const hasStarterAnimation = [...wrapper.classList].some(cls =>
      cls.startsWith('wcf-starter-animations-')
    );

    if (!hasStarterAnimation) return;

    /* ==========================================
      CHARACTER TEXT SUPPORT (ADDED BACK)
    ========================================== */

    if ([...wrapper.classList].some(cls =>
      cls.startsWith('wcf-starter-animations-text-char')
    )) {

      const target = wrapper.querySelector('.elementor-widget-container > *');

      if (target && !target.dataset.charInit) {

        const text = target.textContent;

        if (text && text.trim().length) {

          target.innerHTML = '';

          text.split('').forEach((char, i) => {
            const span = document.createElement('span');
            span.textContent = char;
            span.style.setProperty('--i', i);
            target.appendChild(span);
          });

          target.dataset.charInit = "true";
        }
      }
    }

    /* ==========================================
       WAVE TEXT SUPPORT (ADDED ONLY THIS PART)
    ========================================== */

    if (wrapper.classList.contains('wcf-starter-animations-text-wave')) {

      const target = wrapper.querySelector('.elementor-widget-container > *');
      if (target && !target.dataset.waveInit) {

        const text = target.textContent.trim();
        if (text) {
          target.setAttribute('data-text', text);
          target.dataset.waveInit = "true";
        }

      }
    }

/* ==========================================
      RESET (editor live change safe)
    ========================================== */

    wrapper.classList.remove('wcf-animate');
    wrapper.style.animation = 'none';
    wrapper.offsetHeight;
    wrapper.style.animation = '';

    /* ==========================================
      OBSERVER (Viewport Play)
    ========================================== */

    if (!wrapper.dataset.observerInit) {

      const observer = new IntersectionObserver((entries, obs) => {

        entries.forEach(entry => {

          if (entry.isIntersecting) {
            entry.target.classList.add('wcf-animate');
            obs.unobserve(entry.target);
          }

        });

      }, { threshold: 0.3 });

      observer.observe(wrapper);
      wrapper.dataset.observerInit = "true";
    }

  }

  window.addEventListener('elementor/frontend/init', function () {

    elementorFrontend.hooks.addAction(
      'frontend/element_ready/global',
      initStarterAnimations
    );

  });

})();
