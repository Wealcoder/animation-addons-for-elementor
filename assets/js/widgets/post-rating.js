(function ($) {
    const Post_Rating = function ($scope, $) {
        const widgetId = $scope.data('id') || $scope.find('.aae--post-rating-form').data('element-id');
        const $modal = $scope.find('.aae-rating-login-modal');

        // Move modal to body if not already moved (prevents CSS transform/overflow clipping from parent containers)
        if ($modal.length && !$modal.parent().is('body')) {
            $modal.attr('data-modal-widget', widgetId);
            $modal.appendTo('body');
        }

        const getModal = function () {
            if (widgetId && $('body > .aae-rating-login-modal[data-modal-widget="' + widgetId + '"]').length) {
                return $('body > .aae-rating-login-modal[data-modal-widget="' + widgetId + '"]');
            }
            return $scope.find('.aae-rating-login-modal');
        };

        const openModal = function () {
            const $m = getModal();
            $m.fadeIn(200).css('display', 'flex');
        };

        const closeModal = function () {
            const $m = getModal();
            $m.fadeOut(200);
        };

        // Open modal when login trigger button is clicked
        $scope.on('click', '.aae-login-trigger-btn', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openModal();
        });

        // Close modal on close button or backdrop click
        $(document).on('click', '.aae-rating-modal-close, .aae-rating-modal-backdrop', function (event) {
            event.preventDefault();
            const $targetModal = $(this).closest('.aae-rating-login-modal');
            if ($targetModal.length) {
                $targetModal.fadeOut(200);
            } else {
                closeModal();
            }
        });

        // Close modal on ESC key
        $(document).on('keydown.aaeRatingModal_' + widgetId, function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                closeModal();
            }
        });

        // Rating form submit button handler
        $scope.on('click', '#aae-post-rating-btn', function (event) {
            event.preventDefault();

            const $btn = $(this);
            const $form = $scope.find('.aae--post-rating-form');
            const isLoggedOut = $btn.hasClass('aae-login-trigger-btn') || $form.data('is-logged-in') === false || $form.data('is-logged-in') === 'false';

            if (isLoggedOut) {
                openModal();
                return;
            }

            const postID = $scope.find("#post_id").val();
            const rating = $scope.find("input[name='rating']:checked").val();
            const reviewText = $scope.find("#review_text").val();
            const reviewerName = $scope.find("#reviewer_name").val();  // Optional for guest
            const reviewerEmail = $scope.find("#reviewer_email").val(); // Optional for guest
            const requireApproval = $form.data('require-approval') === 'yes';

            if (!rating) {
                alert("Please select a rating!");
                return;
            }

            $.ajax({
                url: WCF_ADDONS_JS.ajaxUrl,
                type: "POST",
                data: {
                    action: "aaeaddon_submit_post_review_rating",
                    post_id: postID,
                    rating: rating,
                    review: reviewText,
                    name: reviewerName,
                    email: reviewerEmail,
                    require_approval: requireApproval ? 'yes' : 'no',
                    nonce: WCF_ADDONS_JS._wpnonce
                },
                success: function (response) {
                    if (response.success) {
                        const $successMsg = $scope.find("#aae-review-success-message");
                        $successMsg.html("<p>" + response.data.message + "</p>").show().delay(2000).fadeOut();
                        // Clear input fields after success
                        $scope.find("textarea[name='review']").val('');
                        $scope.find("#reviewer_name").val('');
                        $scope.find("#reviewer_email").val('');
                        $scope.find("input[name='rating']").prop('checked', false);
                        $scope.find("#aae-review-error-message").empty();
                    } else {
                        $scope.find("#aae-review-error-message").html("<p>" + response.data.message + "</p>");
                    }
                },
                error: function () {
                    $scope.find("#aae-review-error-message").html("<p>Something went wrong. Please try again later.</p>");
                }
            });
        });
    };

    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/aae--post-rating-form.default', Post_Rating);
    });

})(jQuery);
//# sourceMappingURL=post-rating.js.map
