<?php
/**
 * Header Template
 *
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
	global $aaeaddon_header_smoother, $aaeaddon_header_smoother_offsety;
	if($aaeaddon_header_smoother != 'no'){
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
		do_action( 'wp_body_open' ); 
	}
	
?>
<div id="page" class="hfeed site">
 <?php do_action( 'aaeaddon_aaeaddon_animation_addons_header_builder_content' ); ?>
	<?php
		if( $aaeaddon_header_smoother == 'no' ){
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility hook.
			do_action( 'wp_body_open' ); 
			if($aaeaddon_header_smoother_offsety){
				?>
					<style id="aae-elementor-pro-compatibility-smoother">
						html .admin-bar #smooth-wrapper
						{
							top: <?php echo esc_attr($aaeaddon_header_smoother_offsety) + 32; ?>px !important;
						}
					 	body #smooth-wrapper {
							top: <?php echo esc_attr( $aaeaddon_header_smoother_offsety); ?>px !important;
						}
					</style>	
				<?php
			}
		}
	?>
