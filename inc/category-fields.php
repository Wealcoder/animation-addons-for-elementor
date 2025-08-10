<?php

add_action('category_add_form_fields', 'aae_add_category_light_custom_fields');
add_action('category_edit_form_fields', 'aae_edit_category_light_custom_fields');

function aae_add_category_light_custom_fields($taxonomy)
{
?>
    <div class="form-field">
        <label
            for="aae_cate_additional_text"><?php echo esc_html__('Additional Text', 'animation-addons-for-elementor'); ?></label>
        <textarea name="aae_cate_additional_text" id="aae_cate_additional_text" rows="2"></textarea>
        <p class="description">
            <?php echo esc_html__('Enter additional information for this category.', 'animation-addons-for-elementor'); ?>
        </p>
    </div>
    <div class="form-field">
        <label for="aae_category_image"><?php echo esc_html__('Upload Image', 'animation-addons-for-elementor'); ?></label>
        <input type="button" class="button aae-category-image-upload" value="Upload Image">
        <input type="hidden" name="aae_category_image" id="aae_category_image" value="">
        <div id="aae_category_image_preview"></div>
        <p class="description">
            <?php echo esc_html__('Upload an image for this category.', 'animation-addons-for-elementor'); ?></p>
    </div>
    <div class="form-field">
        <label for="aae_category_icon"><?php echo esc_html__('Upload Icon', 'animation-addons-for-elementor'); ?></label>
        <input type="button" class="button aae-category-icon-upload" value="Upload Icon">
        <input type="hidden" name="aae_category_icon" id="aae_category_icon" value="">
        <div id="aae_category_icon_preview"></div>
        <p class="description">
            <?php echo esc_html__('Upload an image as a icon for this category.', 'animation-addons-for-elementor'); ?>
        </p>
    </div>
    <div class="form-field">
        <label for="aae_cat_color"><?php echo esc_html__('Color', 'animation-addons-for-elementor'); ?></label>
        <input type="color" class="cat-color-picker" data-default-color="#ffffff">
        <input type="hidden" name="aae_cat_color" id="aae_cat_color" value="">
    </div>
    <div class="form-field">
        <label
            for="aae_cat_bg_color"><?php echo esc_html__('Background Color', 'animation-addons-for-elementor'); ?></label>
        <input type="color" class="color-picker" data-default-color="#ffffff">
        <input type="hidden" name="aae_cat_bg_color" id="aae_cat_bg_color" value="">
    </div>
<?php
}

function aae_edit_category_light_custom_fields($term)
{
    $category_text    = get_term_meta($term->term_id, 'aae_cate_additional_text', true);
    $category_image   = get_term_meta($term->term_id, 'aae_category_image', true);
    $category_icon    = get_term_meta($term->term_id, 'aae_category_icon', true);
    $background_color = get_term_meta($term->term_id, 'aae_cat_bg_color', true);
    $cat_color        = get_term_meta($term->term_id, 'aae_cat_color', true);
?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="aae_cate_additional_text">Additional Text</label></th>
        <td>
            <textarea name="aae_cate_additional_text" id="aae_cate_additional_text"
                rows="2"><?php echo esc_textarea($category_text); ?></textarea>
            <p class="description">
                <?php echo esc_html__('Enter additional information for this category.', 'animation-addons-for-elementor'); ?>
            </p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label
                for="aae_category_image"><?php echo esc_html__('Upload Image', 'animation-addons-for-elementor'); ?></label>
        </th>
        <td>
            <input type="button" class="button aae-category-image-upload" value="Upload Image">
            <input type="hidden" name="aae_category_image" id="aae_category_image"
                value="<?php echo esc_url($category_image); ?>">
            <div id="aae_category_image_preview">
                <?php if ($category_image): ?>
                    <img src="<?php echo esc_url($category_image); ?>" alt="Category Image" style="max-width: 150px;">
                <?php endif; ?>
            </div>
            <p class="description">
                <?php echo esc_html__('Update the image for this category.', 'animation-addons-for-elementor'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label
                for="aae_category_icon"><?php echo esc_html__('Upload Icon', 'animation-addons-for-elementor'); ?></label>
        </th>
        <td>
            <input type="button" class="button aae-category-icon-upload" value="Upload Icon">
            <input type="hidden" name="aae_category_icon" id="aae_category_icon"
                value="<?php echo esc_url($category_icon); ?>">
            <div id="aae_category_icon_preview">
                <?php if ($category_icon): ?>
                    <img src="<?php echo esc_url($category_icon); ?>" alt="Category Icon" style="max-width: 50px;">
                <?php endif; ?>
            </div>
            <p class="description">
                <?php echo esc_html__('Update the icon for this category.', 'animation-addons-for-elementor'); ?></p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row" valign="top"><label for="aae_cat_color">Color</label></th>
        <td>
            <input type="color" name="aae_cat_color" id="aae_cat_color" value="<?php echo esc_attr($cat_color); ?>">
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row" valign="top"><label for="aae_cat_bg_color">Background Color</label></th>
        <td>
            <input type="color" name="aae_cat_bg_color" id="aae_cat_bg_color"
                value="<?php echo esc_attr($background_color); ?>">
        </td>
    </tr>
<?php
}

add_action('edited_category', 'aae_save_category_light_custom_fields');
add_action('create_category', 'aae_save_category_light_custom_fields');

function aae_save_category_light_custom_fields($term_id)
{
    if (isset($_POST['aae_cate_additional_text'])) {
        update_term_meta($term_id, 'aae_cate_additional_text', sanitize_textarea_field($_POST['aae_cate_additional_text']));
    }
    if (isset($_POST['aae_category_image'])) {
        update_term_meta($term_id, 'aae_category_image', esc_url_raw($_POST['aae_category_image']));
    }
    if (isset($_POST['aae_category_icon'])) {
        update_term_meta($term_id, 'aae_category_icon', esc_url_raw($_POST['aae_category_icon']));
    }
    if (isset($_POST['aae_cat_color'])) {
        update_term_meta($term_id, 'aae_cat_color', esc_url_raw($_POST['aae_cat_color']));
    }
    if (isset($_POST['aae_cat_bg_color'])) {
        update_term_meta($term_id, 'aae_cat_bg_color', esc_url_raw($_POST['aae_cat_bg_color']));
    }
}


add_action('admin_head', 'aae_inline_category_light_media_uploader');
function aae_inline_category_light_media_uploader()
{
    if (! isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'category') {
        return; // Only load on the category taxonomy pages
    }
    wp_enqueue_media();
?>
    <script>
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
    </script>
<?php
}


function aae_addon_tax_category_light_styles()
{
    $custom_css = '';
    $categories = get_terms(array(
        'taxonomy'   => 'category',
        'hide_empty' => false,
    ));

    if (! empty($categories) && ! is_wp_error($categories)) {
        foreach ($categories as $category) {
            $background_color = get_term_meta($category->term_id, 'aae_cat_bg_color', true);
            $cat_color = get_term_meta($category->term_id, 'aae_cat_color', true);
            if ($background_color) {
                $custom_css .= sprintf('
                .aae-cat-%1$s {
                    background-color: %2$s;
                    color: %3$s;
                }', $category->slug, $background_color, $cat_color);
            }
        }
    }

    if ($custom_css != '') {
        wp_add_inline_style('wcf--addons', $custom_css);
    }
}

add_action('wp_enqueue_scripts', 'aae_addon_tax_category_light_styles', 20);
