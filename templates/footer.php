<?php
/**
 * Footer Template
 *
 */
/**
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
<?php do_action( 'wcf_footer_builder_content' ); ?>
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
