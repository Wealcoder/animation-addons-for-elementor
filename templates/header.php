<?php
/**
 * Header Template
 *
 */
/**
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
	global $aae_header_smoother, $aae_header_smoother_offsetY;
	if($aae_header_smoother != 'no'){
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
		do_action( 'wp_body_open' ); 
	}
	
?>
<div id="page" class="hfeed site">
 <?php do_action( 'animation_addons_header_builder_content' ); ?>
	<?php
		if( $aae_header_smoother == 'no' ){
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
			do_action( 'wp_body_open' ); 
			if($aae_header_smoother_offsetY){
				?>
					<style id="aae-elementor-pro-compatibility-smoother">
						html .admin-bar #smooth-wrapper
						{
							top: <?php echo esc_attr($aae_header_smoother_offsetY) + 32; ?>px !important;
						}
					 	body #smooth-wrapper {
							top: <?php echo esc_attr( $aae_header_smoother_offsetY); ?>px !important;
						}
					</style>	
				<?php
			}
		}
	?>
