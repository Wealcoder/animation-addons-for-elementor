<?php
/**
 * Archive Template.
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
<main id="content" class="site-main">
	<?php do_action( 'animation_addons_archive_builder_content' ); ?>
</main>
<?php
get_footer();
