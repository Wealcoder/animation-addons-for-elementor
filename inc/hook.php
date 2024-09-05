<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

add_filter( 'single_template', 'wcf_set_postview' );
