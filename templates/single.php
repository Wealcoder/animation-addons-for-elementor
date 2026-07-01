<?php
/**
 * single Template.
 *
 */
/**
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

?>
<main data-aee="builder" id="content" <?php post_class( 'site-main aae-single-sitecontent' ); ?>>
<?php do_action( 'animation_addons_single_builder_content' ); ?>
</main>
<?php
get_footer();

