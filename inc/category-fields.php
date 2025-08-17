<?php

if(defined('WCF_ADDONS_PRO_WIDGETS_PATH')) {
    return; // Prevents redeclaration if already defined
}

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
 

// Print nonce field in the category forms (add + edit).
add_action('category_add_form_fields', 'aae_category_meta_nonce_field');
add_action('category_edit_form_fields', 'aae_category_meta_nonce_field');

function aae_category_meta_nonce_field( $term = null ) {
    wp_nonce_field( 'aae_category_meta_action', 'aae_category_meta_nonce' );
}


add_action( 'edited_category', 'aae_save_category_light_custom_fields', 10, 2 );
add_action( 'create_category', 'aae_save_category_light_custom_fields', 10, 2 );

function aae_save_category_light_custom_fields( $term_id, $tt_id = null ) {
    // 1) Nonce check
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! isset( $_POST['aae_category_meta_nonce'] ) ) {
        return;
    }

    $nonce =  sanitize_text_field( wp_unslash( $_POST['aae_category_meta_nonce'] ) ); // Input comes from $_POST, so unslash

    if ( ! wp_verify_nonce( $nonce, 'aae_category_meta_action' ) ) {
        return;
    }

    // 2) Capability check
    if ( ! current_user_can( 'edit_term', $term_id ) ) {
        return;
    }

    // 3) (Optional) Ensure we're handling categories
    if ( empty( $_POST['taxonomy'] ) || 'category' !== $_POST['taxonomy'] ) {
        return;
    }

    // 4) Sanitize & save (delete when empty to keep DB clean)
    $additional = isset( $_POST['aae_cate_additional_text'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['aae_cate_additional_text'] ) )
        : '';

    $image = isset( $_POST['aae_category_image'] )
        ? esc_url_raw( wp_unslash( $_POST['aae_category_image'] ) )
        : '';

    $icon = isset( $_POST['aae_category_icon'] )
        ? esc_url_raw( wp_unslash( $_POST['aae_category_icon'] ) )
        : '';

    // If your color pickers store hex (#fff / #ffffff), use sanitize_hex_color.
    // Fallback to sanitize_text_field for non-hex values (e.g., CSS vars).
    $color_raw = isset( $_POST['aae_cat_color'] ) ? sanitize_text_field( wp_unslash( $_POST['aae_cat_color'] ) ) : '';
    $bg_raw    = isset( $_POST['aae_cat_bg_color'] ) ? sanitize_text_field( wp_unslash( $_POST['aae_cat_bg_color'] ) ) : '';

    $color = sanitize_hex_color( $color_raw );
    if ( null === $color ) { $color = sanitize_text_field( $color_raw ); }

    $bg_color = sanitize_hex_color( $bg_raw );
    if ( null === $bg_color ) { $bg_color = sanitize_text_field( $bg_raw ); }

    // Save or delete when empty
    $additional !== '' ? update_term_meta( $term_id, 'aae_cate_additional_text', $additional ) : delete_term_meta( $term_id, 'aae_cate_additional_text' );
    $image      !== '' ? update_term_meta( $term_id, 'aae_category_image', $image )             : delete_term_meta( $term_id, 'aae_category_image' );
    $icon       !== '' ? update_term_meta( $term_id, 'aae_category_icon', $icon )               : delete_term_meta( $term_id, 'aae_category_icon' );
    $color      !== '' ? update_term_meta( $term_id, 'aae_cat_color', $color )                  : delete_term_meta( $term_id, 'aae_cat_color' );
    $bg_color   !== '' ? update_term_meta( $term_id, 'aae_cat_bg_color', $bg_color )            : delete_term_meta( $term_id, 'aae_cat_bg_color' );
}

add_action( 'admin_enqueue_scripts', 'aae_inline_category_light_media_uploader', 10, 1 );

function aae_inline_category_light_media_uploader( $hook_suffix ) {
    // Only load on term screens.
    if ( ! in_array( $hook_suffix, [ 'edit-tags.php', 'term.php' ], true ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || empty( $screen->taxonomy ) || 'category' !== $screen->taxonomy ) {
        return; // Only on the Category taxonomy screens
    }

    // Enqueue the WP media modal (required for wp.media)
    wp_enqueue_media();

    // Enqueue your script
    wp_enqueue_script(
        'aae-category-media',
        WCF_ADDONS_URL . 'assets/js/category-filter.js',
        [ 'jquery' ],
        file_exists( WCF_ADDONS_PATH . 'assets/js/category-filter.js' )
            ? filemtime( WCF_ADDONS_PATH . 'assets/js/category-filter.js' )
            : '1.1',
        true
    );

    // (Optional) Pass data / i18n / nonce to JS
    wp_localize_script( 'aae-category-media', 'AAECategoryMedia', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'aae_category_media' ),
        'i18n'    => [
            'choose' => __( 'Choose image', 'animation-addons-for-elementor' ),
            'use'    => __( 'Use this image', 'animation-addons-for-elementor' ),
            'remove' => __( 'Remove', 'animation-addons-for-elementor' ),
        ],
    ] );
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
