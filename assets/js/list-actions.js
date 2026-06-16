jQuery(document).ready(function($) {
    // Toggle the switcher
    $(document).on('change','.aaeaddon-global-load-switcher-toggle',function() {
        var post_id = $(this).data('post-id');
        var switcher_state = $(this).prop('checked') ? 'yes' : 'no';
        // Send AJAX request to save the switcher's state
        $.ajax({
            url: WCF_ADDONS_ADMIN.ajaxurl,
            type: 'POST',
            data: {
                action: 'aaeaddon_custom_icon_settings_state',
                post_id: post_id,
                option_name : 'aae_gl_load',
                option_value: switcher_state,
                nonce: WCF_ADDONS_ADMIN.nonce,
            }
          
        });
    });
});
