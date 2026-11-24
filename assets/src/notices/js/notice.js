jQuery(document).ready(function ($) {
    "use strict";

    $('.notice.aae-notice').on('click', '.notice-dismiss, [data-dismiss], [data-snooze], [data-hide]', function (event) {
        event.preventDefault();

        const $clickedButton = $(this);
        const $notice = $clickedButton.closest('.notice');

        // A notice that is shown once (the import report): hide it, nothing is
        // left to save.
        if ($clickedButton.is('[data-hide]')) {
            $notice.fadeOut();
            return;
        }

        const noticeData = $.extend({}, $notice.data());

        const snoozeValue = $clickedButton.attr('data-snooze');
        if (snoozeValue !== undefined) {
            noticeData.snooze = 'yes';
            noticeData.snooze_time = snoozeValue;
        }

        // Proceed only if both 'action' and 'notice_id' are present
        if (noticeData.action && noticeData.notice_id) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: noticeData,
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    if (response.success) {
                        $notice.fadeOut();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Notice dismiss failed:', error);
                }
            });
        } else {
            console.warn('Missing required data: action or notice_id');
        }
    });
});