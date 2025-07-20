(function waitForElementorReady() {
<<<<<<< HEAD
	if (typeof window.elementorFrontend !== 'undefined' && elementorFrontend.hooks && typeof elementorFrontend.hooks.addAction === 'function') {
		elementorFrontend.hooks.addAction('frontend/element_ready/wcf--contact-form-7.default', function($scope) {
			console.log('[Debug] A widget is ready');
		});
	} else {
		setTimeout(waitForElementorReady, 100);
	}
})();
//# sourceMappingURL=contact-form.js.map
=======
    
  if (
    typeof window.elementorFrontend !== 'undefined' &&
    elementorFrontend.hooks &&
    typeof elementorFrontend.hooks.addAction === 'function'
  ) {
   
    elementorFrontend.hooks.addAction('frontend/element_ready/wcf--contact-form-7.default', function ($scope) {
      console.log('[Debug] A widget is ready');
    });
  } else {
    setTimeout(waitForElementorReady, 100);
  }

})();

>>>>>>> 2ec11a27d07b4187c9c2bafd4cee1fcdef897434
