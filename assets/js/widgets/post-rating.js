(function ($) {
    const Post_Rating = function ($scope, $) {

        $(document).on('click', '#aae-post-rating-btn', function (event) {
            event.preventDefault();

            const postID = $scope.find("#post_id").val();
            const rating = $scope.find("input[name='rating']:checked").val();
            const reviewText = $scope.find("#review_text").val();
            const reviewerName = $scope.find("#reviewer_name").val();  // Optional for guest
            const reviewerEmail = $scope.find("#reviewer_email").val(); // Optional for guest
            const requireApproval = $scope.find('.aae--post-rating-form').data('require-approval') === 'yes';

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