<?php

namespace WCF_ADDONS;

use Elementor\Controls_Manager;
use Elementor\Plugin as ElementorPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WCFAddon_BlackList_Notice {
	public static function init() {
		
        add_action( 'elementor/element/common/_section_style/after_section_end', [
			__CLASS__,
			'tooltip_controls_section'
		], -1 );

		add_action( 'elementor/element/container/section_layout/after_section_end', [
			__CLASS__,
			'tooltip_controls_section'
		], -1 );
		
		// Layout 
		add_action( 'elementor/element/container/section_layout/after_section_end', [
			__CLASS__,
			'register_cursor_hover_effect_controls'
		] );

		add_action( 'elementor/element/wcf--a-portfolio/section_layout/after_section_end', [
			__CLASS__,
			'register_cursor_hover_effect_controls'
		] );
		
		$image_elements = [
			[
				'name'    => 'image',
				'section' => 'section_image',
			],
			[
				'name'    => 'wcf--image',
				'section' => 'section_content',
			],
		];
		foreach ( $image_elements as $element ) {
			add_action( 'elementor/element/' . $element['name'] . '/' . $element['section'] . '/after_section_end', [
				__CLASS__,
				'register_image_animation_controls',
			], 10, 2 );
		}
		
		$text_elements = [
			[
				'name'    => 'heading',
				'section' => 'section_title',
			],
			[
				'name'    => 'text-editor',
				'section' => 'section_editor',
			],
			[
				'name'    => 'wcf--title',
				'section' => 'section_content',
			],
			[
				'name'    => 'wcf--text',
				'section' => 'section_content',
			],
		];
		foreach ( $text_elements as $element ) {
			add_action( 'elementor/element/' . $element['name'] . '/' . $element['section'] . '/after_section_end', [
				__CLASS__,
				'register_text_animation_controls',
			], 10, 2 );
		}		
				
	}
	
	public static function register_text_animation_controls( $element ) {
		$element->start_controls_section(
			'_section_wcf_text_animation',
			[
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Text Animation', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
			]
		);
		
		$element->add_control(
            'wcfaddon_ctextrver_notice',
            [
                'label' => '',
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => wcfaddon_get_pronotice_html(),
                'content_classes' => 'wcfaddon-getpro-clr',
            ]
        );
		
        $element->end_controls_section();
    }
	
	public static function register_image_animation_controls( $element ) {
	
		$element->start_controls_section(
			'_section_wcf_image_animation',
			[
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Image Animation', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
			]
		);
		
		$element->add_control(
            'wcfaddon_cursomagover_notice',
            [
                'label' => '',
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => wcfaddon_get_pronotice_html(),
                'content_classes' => 'wcfaddon-getpro-clr',
            ]
        );
		
        $element->end_controls_section();
    
    }
	
	public static function register_cursor_hover_effect_controls( $element ) {
		$tab  = Controls_Manager::TAB_CONTENT;

		if ( 'container' === $element->get_name() ) {
			$tab = Controls_Manager::TAB_LAYOUT;
		}

		$element->start_controls_section(
			'notice_section_wcf_cursor_hover_area',
			[
				'tab'   => $tab,
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Cursor hover effect', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
			]
		);
		
		$element->add_control(
            'wcfaddon_cursorhover_notice',
            [
                'label' => '',
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => wcfaddon_get_pronotice_html(),
                'content_classes' => 'wcfaddon-getpro-clr',
            ]
        );
		
		$element->end_controls_section();
		
		$element->start_controls_section(
			'notice_section_wcf_hover_image_area',
			[			
				'tab' => Controls_Manager::TAB_LAYOUT,
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Hover effect image', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
			]
		);
		
    		$element->add_control(
                'wcfaddon_image_effect_notice',
                [
                    'label' => '',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => wcfaddon_get_pronotice_html(),
                    'content_classes' => 'wcfaddon-getpro-clr',
                ]
            );
            
		$element->end_controls_section();
		
		$element->start_controls_section(
			'notice_section_wcf_popup_area',
			[
				'tab'   => Controls_Manager::TAB_LAYOUT,
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'PopUp', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
			]
		);
		
    		$element->add_control(
                'wcfaddon_popup_notice',
                [
                    'label' => '',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => wcfaddon_get_pronotice_html(),
                    'content_classes' => 'wcfaddon-getpro-clr',
                ]
            );
            
		$element->end_controls_section();
    }
	   
	
	public static function tooltip_controls_section( $element ) {

		$element->start_controls_section(
			'_section_wcf_advanced_tooltip',
			[
				
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Tooltip', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		
    		$element->add_control(
    			'wcfaddon_tooltips_notice',
    			[
    				'label' => '',
    				'type' => \Elementor\Controls_Manager::RAW_HTML,
    				'raw' => wcfaddon_get_pronotice_html(),
    				'content_classes' => 'wcfaddon-getpro-clr',
    			]
    		);
		
		$element->end_controls_section();
		$element->start_controls_section(
			'notice_section_wcf_tilt_area',
			[				
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Tilt', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
    		
    		$element->add_control(
                'wcfaddon_tilt_notice',
                [
                    'label' => '',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => wcfaddon_get_pronotice_html(),
                    'content_classes' => 'wcfaddon-getpro-clr',
                ]
            );
    
		
		$element->end_controls_section();
		$element->start_controls_section(
			'_section_wcf_mouse_move_area',
			[
				'tab'   => Controls_Manager::TAB_ADVANCED,
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Mouse Move Effect', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
			]
		);
		
		$element->add_control(
            'wcfaddon_moveeffect_notice',
            [
                'label' => '',
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => wcfaddon_get_pronotice_html(),
                'content_classes' => 'wcfaddon-getpro-clr',
            ]
        );
		
		$element->end_controls_section();
		
		$element->start_controls_section(
			'_section_wcf_horizontal_scroll_area',
			[
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Horizontal Scroll', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		
            $element->add_control(
                'wcfaddon_hscroll_notice',
                [
                    'label' => '',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => wcfaddon_get_pronotice_html(),
                    'content_classes' => 'wcfaddon-getpro-clr',
                ]
            );
		$element->end_controls_section();
		
		$element->start_controls_section(
			'_section_wcf_animation_area',
			[
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Animation', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		
            $element->add_control(
                'wcfaddon_animation_notice',
                [
                    'label' => '',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => wcfaddon_get_pronotice_html(),
                    'content_classes' => 'wcfaddon-getpro-clr',
                ]
            );
		$element->end_controls_section();
		
		$element->start_controls_section(
			'_section_pin-area',
			[			
				'label' => sprintf( '<i class="wcf-logo"></i> %s <span class="wcfpro_text">%s<span>', __( 'Pin Element', 'animation-addons-for-elementor' ), __( 'Pro', 'animation-addons-for-elementor' ) ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		
            $element->add_control(
                'wcfaddon_pinelementn_notice',
                [
                    'label' => '',
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => wcfaddon_get_pronotice_html(),
                    'content_classes' => 'wcfaddon-getpro-clr',
                ]
            );
		$element->end_controls_section();
    }		

}

if(!defined('WCF_ADDONS_PRO_FILE')){   
    WCFAddon_BlackList_Notice::init();
}

