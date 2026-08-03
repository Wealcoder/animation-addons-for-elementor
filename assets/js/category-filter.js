 jQuery(document).ready(function($) {
    var mediaUploader;

    function openMediaUploader(button, inputField, previewContainer) {
        mediaUploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $(inputField).val(attachment.url);
            $(previewContainer).html('<img src="' + attachment.url + '" style="max-width: 100px;">');
        });

        mediaUploader.open();
    }

    // Image Upload
    $('.aae-category-image-upload').click(function(e) {
        e.preventDefault();
        openMediaUploader(this, '#aae_category_image', '#aae_category_image_preview');
    });

    // Icon Upload
    $('.aae-category-icon-upload').click(function(e) {
        e.preventDefault();
        openMediaUploader(this, '#aae_category_icon', '#aae_category_icon_preview');
    });
});