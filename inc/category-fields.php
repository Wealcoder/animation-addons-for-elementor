<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if(aaeaddon_pro_defined( 'WIDGETS_PATH' )) {
    return; // Prevents redeclaration if already defined
}

add_action('category_add_form_fields', 'aaeaddon_add_category_light_custom_fields');
add_action('category_edit_form_fields', 'aaeaddon_edit_category_light_custom_fields');

if ( ! function_exists( 'aaeaddon_add_category_light_custom_fields' ) ) :
function aaeaddon_add_category_light_custom_fields($taxonomy)
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
endif;

if ( ! function_exists( 'aaeaddon_edit_category_light_custom_fields' ) ) :
function aaeaddon_edit_category_light_custom_fields($term)
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
endif;
 

// Print nonce field in the category forms (add + edit).
add_action('category_add_form_fields', 'aaeaddon_category_meta_nonce_field');
add_action('category_edit_form_fields', 'aaeaddon_category_meta_nonce_field');

if ( ! function_exists( 'aaeaddon_category_meta_nonce_field' ) ) :
function aaeaddon_category_meta_nonce_field( $term = null ) {
    wp_nonce_field( \Wealcoder\AnimationAddons\Nonce::CATEGORY_META, 'aaeaddon_category_meta_nonce' );
}
endif;


add_action( 'edited_category', 'aaeaddon_save_category_light_custom_fields', 10, 2 );
add_action( 'create_category', 'aaeaddon_save_category_light_custom_fields', 10, 2 );

if ( ! function_exists( 'aaeaddon_save_category_light_custom_fields' ) ) :
function aaeaddon_save_category_light_custom_fields( $term_id, $tt_id = null ) {
    // 1) Nonce check
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! isset( $_POST['aaeaddon_category_meta_nonce'] ) ) {
        return;
    }

    $nonce =  sanitize_text_field( wp_unslash( $_POST['aaeaddon_category_meta_nonce'] ) ); // Input comes from $_POST, so unslash

    if ( ! \Wealcoder\AnimationAddons\Nonce::verify( $nonce, \Wealcoder\AnimationAddons\Nonce::CATEGORY_META ) ) {
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
endif;

add_action( 'admin_enqueue_scripts', 'aaeaddon_inline_category_light_media_uploader', 10, 1 );

if ( ! function_exists( 'aaeaddon_inline_category_light_media_uploader' ) ) :
function aaeaddon_inline_category_light_media_uploader( $hook_suffix ) {
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
        AAEADDON_URL . 'assets/js/category-filter.js',
        [ 'jquery' ],
        file_exists( AAEADDON_PATH . 'assets/js/category-filter.js' )
            ? filemtime( AAEADDON_PATH . 'assets/js/category-filter.js' )
            : '1.1',
        true
    );

    // (Optional) Pass data / i18n / nonce to JS
    wp_localize_script( 'aae-category-media', 'AAECategoryMedia', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => \Wealcoder\AnimationAddons\Nonce::create( \Wealcoder\AnimationAddons\Nonce::CATEGORY_MEDIA ),
        'i18n'    => [
            'choose' => __( 'Choose image', 'animation-addons-for-elementor' ),
            'use'    => __( 'Use this image', 'animation-addons-for-elementor' ),
            'remove' => __( 'Remove', 'animation-addons-for-elementor' ),
        ],
    ] );
}
endif;



if ( ! function_exists( 'aaeaddon_build_cat_badge_css' ) ) :
/**
 * Build the `.aae-cat-<slug>` badge CSS, cached in a transient.
 *
 * This used to walk every category (`hide_empty => false`) and read two term
 * metas each, on EVERY front-end request, only to produce a string that is
 * empty unless a term has a badge colour set. The result changes only when a
 * category's badge colour changes, so it is cached and rebuilt on those term
 * events (see aaeaddon_flush_cat_badge_css). Returns '' when nothing is
 * styled — stored as-is, so the walk does not repeat on a site that uses no
 * badge colours.
 *
 * @return string
 */
function aaeaddon_build_cat_badge_css()
{
    // String literal, not a top-level const: this file returns early (line ~9)
    // when the Pro plugin is present, so a const there would never be defined,
    // yet the function declarations below are hoisted and could still be called.
    $transient = 'aaeaddon_cat_badge_css';

    $cached = get_transient($transient);
    if (false !== $cached) {
        return $cached;
    }

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

    set_transient($transient, $custom_css, WEEK_IN_SECONDS);

    return $custom_css;
}
endif;

if ( ! function_exists( 'aaeaddon_flush_cat_badge_css' ) ) :
/** Drop the cache when a category's badge colour is added/changed/removed. */
function aaeaddon_flush_cat_badge_css($meta_id = 0, $object_id = 0, $meta_key = '')
{
    // Called both directly (category save hooks) and from *_term_meta hooks,
    // where the third arg is the meta key — only ours matter.
    if ('' !== $meta_key && ! in_array($meta_key, array('aae_cat_bg_color', 'aae_cat_color'), true)) {
        return;
    }
    delete_transient('aaeaddon_cat_badge_css');
}
endif;
add_action('added_term_meta', 'aaeaddon_flush_cat_badge_css', 10, 3);
add_action('updated_term_meta', 'aaeaddon_flush_cat_badge_css', 10, 3);
add_action('deleted_term_meta', 'aaeaddon_flush_cat_badge_css', 10, 3);
add_action('edited_category', 'aaeaddon_flush_cat_badge_css');
add_action('delete_category', 'aaeaddon_flush_cat_badge_css');

if ( ! function_exists( 'aaeaddon_tax_category_light_styles' ) ) :
function aaeaddon_tax_category_light_styles()
{
    $custom_css = aaeaddon_build_cat_badge_css();

    if ($custom_css != '') {
        // Attached to the always-enqueued inline carrier, not the legacy
        // wcf--addons stylesheet — that one only loads when v3 is in use, and
        // WordPress drops inline CSS whose parent handle isn't enqueued.
        wp_add_inline_style(\Wealcoder\AnimationAddons\Plugin::INLINE_STYLE_HANDLE, $custom_css);
    }
}
endif;

add_action('wp_enqueue_scripts', 'aaeaddon_tax_category_light_styles', 20);
