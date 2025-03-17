<?php

namespace WCF_ADDONS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class BlackList_Widget extends \Elementor\Widget_Base {
    public $custom_name = '';
    public $custom_heading = '';
    public function get_name() {
        return $this->custom_name;
    }
    
    public function setWdigetName($name){
       $this->custom_name = $name; 
    }
    public function setWdigetLabel($name){
        $this->custom_heading = $name; 
    }

    public function get_title() {
        return $this->custom_heading;
    }

    public function get_icon() {
        return 'wcf eicon-lock'; // Example: Locked icon to indicate Pro
    }

    public function get_categories() {
        return ['general'];
    }  
    
    protected function render() {     
        echo wp_kses_post( wcfaddon_get_pronotice_html());
    }
   
}