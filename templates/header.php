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
	global $aae_header_smoother, $aae_header_smoother_offsetY;
	if($aae_header_smoother != 'no'){
		do_action( 'wp_body_open' ); 
	}
	
?>
<div id="page" class="hfeed site">
    <?php do_action( 'wcf_header_builder_content' ); ?>
	<?php
		if( $aae_header_smoother == 'no' ){
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
