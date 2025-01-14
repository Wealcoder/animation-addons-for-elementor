<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if(function_exists('wcf_set_postview')){
	add_filter( 'single_template', 'wcf_set_postview' );
}


