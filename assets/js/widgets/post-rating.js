(function ($) {
	'use strict';

	// Delegated click handler for Logged-Out visitors (Login Modal Trigger)
	$(document).on('click', '.aae--post-rating-form.aae-logged-out .rating label, .aae--post-rating-form.aae-logged-out input[name="rating"], .aae--post-rating-form.aae-logged-out #aae-post-rating-btn, .aae--post-rating-form.aae-logged-out .aae-login-trigger-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $modal = $(this).closest('.aae--post-rating-form').find('.aae-rating-login-modal');
		if ($modal.length) {
			$modal.fadeIn(200);
		}
	});

	// Close Modal handlers
	$(document).on('click', '.aae-rating-modal-close, .aae-rating-modal-backdrop', function (e) {
		e.preventDefault();
		$(this).closest('.aae-rating-login-modal').fadeOut(200);
	});

	$(document).on('keydown', function (e) {
		if (e.keyCode === 27) {
			$('.aae-rating-login-modal').fadeOut(200);
		}
	});

	// Elementor widget ready hook
	var PostRatingFormHandler = function ($scope, $) {
		var $form = $scope.find('.aae--post-rating-form');
		if (!$form.length || $form.hasClass('aae-logged-out')) {
			return;
		}

		$scope.on('click', '#aae-post-rating-btn', function (e) {
			e.preventDefault();
			var post_id = $form.find('#post_id').val(),
				element_id = $form.find('#element_id').val() || $form.data('element-id') || '',
				rating = $form.find('input[name="rating"]:checked').val(),
				review = $form.find('#review_text').val() || '';

			if (!rating) {
				alert('Please select a rating!');
				return;
			}

			$.ajax({
				url: WCF_ADDONS_JS.ajaxUrl,
				type: 'POST',
				data: {
					action: 'aaeaddon_submit_post_review_rating',
					post_id: post_id,
					element_id: element_id,
					rating: rating,
					review: review,
					nonce: WCF_ADDONS_JS._wpnonce
				},
				success: function (res) {
					if (res.success) {
						$form.find('#aae-review-success-message').html('<p>' + res.data.message + '</p>').show().delay(2000).fadeOut();
						$form.find('textarea[name="review"]').val('');
						$form.find('input[name="rating"]').prop('checked', false);
						$form.find('#aae-review-error-message').empty();
					} else {
						$form.find('#aae-review-error-message').html('<p>' + res.data.message + '</p>');
					}
				},
				error: function () {
					$form.find('#aae-review-error-message').html('<p>Something went wrong. Please try again later.</p>');
				}
			});
		});
	};

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/aae--post-rating-form.default', PostRatingFormHandler);
	});

})(jQuery);