<?php
/**
 * Archive Template.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>
<main id="content" class="site-main">
	<?php do_action( 'wcf_archive_builder_content' ); ?>
</main>
<?php
get_footer();
