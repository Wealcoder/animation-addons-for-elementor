(function($) {
	/**
	 * @param $scope The Widget wrapper element as a jQuery element
	 * @param $ The jQuery alias
	 */
	var WcfTypewriter = function WcfTypewriter($scope, $) {
		var type_list = $('.typed_list', $scope)[0];
		var typed = $('.typed', $scope)[0];
		if (type_list) {
			new Typed(typed, {
				stringsElement: type_list,
				typeSpeed: 50,
				backSpeed: 50,
				cursorChar: '|',
				loop: true
			});
		}
	};

	// Make sure you run this code under Elementor.
	$(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction('frontend/element_ready/wcf--typewriter.default', WcfTypewriter);
	});
})(jQuery);
//# sourceMappingURL=typewriter.js.map
