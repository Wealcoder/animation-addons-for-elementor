(function waitForElementorReady() {
  if (typeof window.elementorFrontend !== 'undefined' && elementorFrontend.hooks && typeof elementorFrontend.hooks.addAction === 'function') {
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--contact-form-7.default', function ($scope) {
      console.log('[Debug] A widget is ready');
    });
  } else {
    setTimeout(waitForElementorReady, 100);
  }
})();