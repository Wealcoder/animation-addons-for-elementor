<?php
/**
 * WPML integration and compatibility manager
 */
namespace WCFAddonsPro\INC\WPML;

defined( 'ABSPATH' ) || die();

class WPML_Manager {

	/**
	 * Recreate HappyAddons widgets usage on transtion save
	 *
	 * @param int $new_post_id
	 * @param array $fields
	 * @param object $job
	 *
	 * @return void
	 */
	// public static function on_translation_job_saved( $new_post_id, $fields, $job ) {
	// 	$elements_data = get_post_meta( $job->original_doc_id, Widgets_Cache::META_KEY, true );

	// 	if ( ! empty( $elements_data ) ) {
	// 		update_post_meta( $new_post_id, Widgets_Cache::META_KEY, $elements_data );

	// 		$assets_cache = new Assets_Cache( $new_post_id );
	// 		$assets_cache->delete();
	// 	}
	// }

	public static function load_integration_files() {
		// Load repeatable module class
		include_once( WCF_ADDONS_PRO_PATH . 'inc/wpml-module-with-items.php' );
		// 2️⃣ Load all widget integration files
		foreach ( glob( WCF_ADDONS_PRO_PATH . 'inc/wpml/*.php' ) as $file ) {
			include_once $file;
		}
	}

	public static function add_widgets_to_translate( $widgets ) {
		self::load_integration_files();

		$widgets_map = [

			/*--------------------------------------------------------------
			# Button (Shared / Pro Button)
			--------------------------------------------------------------*/
			'wcf--button' => [
				'conditions' => [ 'widgetType' => 'wcf--button' ],
				'fields'     => [
					[
						'field'       => 'btn_text',
						'type'        => esc_html__( 'Button: Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'btn_link',
						'type'        => esc_html__( 'Button: Link', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINK',
					],
				],
			],
			/*
			|--------------------------------------------------------------------------
			| Advanced Button
			|--------------------------------------------------------------------------
			*/
			'aae--advanced-button' => [
				'conditions' => [ 'widgetType' => 'aae--advanced-button' ],
				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => esc_html__( 'Button: Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'btn_link',
						'type'        => esc_html__( 'Button: Link', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINK',
					],
				],
			],

			/*--------------------------------------------------------------
			# Advanced Pricing Table
			--------------------------------------------------------------*/
			'wcf--a-pricing-table' => [
				'conditions' => [ 'widgetType' => 'wcf--a-pricing-table' ],
				'fields'     => [

					/* Header */
					[
						'field'       => 'title',
						'type'        => esc_html__( 'Pricing: Title', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'sub_title',
						'type'        => esc_html__( 'Pricing: Sub Title', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'currency_symbol_custom',
						'type'        => esc_html__( 'Pricing: Currency Symbol', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'price',
						'type'        => esc_html__( 'Pricing: Price', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'original_price',
						'type'        => esc_html__( 'Pricing: Orginal Price', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'period',
						'type'        => esc_html__( 'Pricing: Period', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],
					
					/* Features */
					[
						'field'       => 'feature_title',
						'type'        => esc_html__( 'Pricing: Features Title', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],

					// Button Text
					[
						'field'       => 'btn_text',
						'type'        => esc_html__( 'Pricing: Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],

					// Button Link
					[
						'field'       => 'btn_link',
						'type'        => esc_html__( 'Pricing: Button Link', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINK',
					],

					/* Ribbon */
					[
						'field'       => 'ribbon_title',
						'type'        => esc_html__( 'Pricing: Ribbon Title', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],
				],
				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Pricing_Table',]
			],
			/*
			|--------------------------------------------------------------------------
			| Advanced Accordion
			|--------------------------------------------------------------------------
			*/
			// 'wcf--a-accordion' => [
			// 	'conditions' => [ 'widgetType' => 'wcf--a-accordion' ],
			// 	'fields'     => [
			// 		[
			// 			'field'       => 'tabs',
			// 			'type'        => esc_html__( 'Accordion Items', 'animation-addons-for-elementor' ),
			// 			'editor_type' => 'REPEATER',
			// 			'fields'      => [
			// 				[
			// 					'field'       => 'tab_count',
			// 					'type'        => esc_html__( 'Accordion: Number', 'animation-addons-for-elementor' ),
			// 					'editor_type' => 'LINE',
			// 				],
			// 				[
			// 					'field'       => 'tab_title',
			// 					'type'        => esc_html__( 'Accordion: Title', 'animation-addons-for-elementor' ),
			// 					'editor_type' => 'LINE',
			// 				],
			// 				[
			// 					'field'       => 'tab_content',
			// 					'type'        => esc_html__( 'Accordion: Content', 'animation-addons-for-elementor' ),
			// 					'editor_type' => 'VISUAL',
			// 				],
			// 				[
			// 					'field'       => 'tab_btn_text',
			// 					'type'        => esc_html__( 'Accordion: Button Text', 'animation-addons-for-elementor' ),
			// 					'editor_type' => 'LINE',
			// 				],
			// 				[
			// 					'field'       => 'tab_btn_link',
			// 					'type'        => esc_html__( 'Accordion: Button Link', 'animation-addons-for-elementor' ),
			// 					'editor_type' => 'LINK',
			// 				],
			// 			],
			// 		],
			// 	],
			// ],

			/*--------------------------------------------------------------
			# Flip Box
			--------------------------------------------------------------*/
			'wcf--flip-box' => [
				'conditions' => [ 'widgetType' => 'wcf--flip-box' ],
				'fields'     => [
					[ 'field' => 'flipbox_front_title',   'type' => 'Flip Box: Front Title',   'editor_type' => 'LINE' ],
					[ 'field' => 'flipbox_front_text', 'type' => 'Flip Box: Front Content', 'editor_type' => 'AREA' ],
					[ 'field' => 'flipbox_back_title',    'type' => 'Flip Box: Back Title',    'editor_type' => 'LINE' ],
					[ 'field' => 'flipbox_back_text',  'type' => 'Flip Box: Back Content',  'editor_type' => 'AREA' ],
					[ 'field' => 'btn_text',   'type' => 'Flip Box: Button Text',   'editor_type' => 'LINE' ],
					[ 'field' => 'bun_link',   'type' => 'Flip Box: Button Link',   'editor_type' => 'LINK' ],
				],
			],

			/*--------------------------------------------------------------
			# Notification
			--------------------------------------------------------------*/
			'aae--notification' => [
				'conditions' => [ 'widgetType' => 'aae--notification' ],
				'fields'     => [
					[ 'field' => 'notify_text', 'type' => 'Notification: Notification Text', 'editor_type' => 'AREA' ],
					[ 'field' => 'btn_text',    'type' => 'Notification: Button Text',       'editor_type' => 'LINE' ],
					[ 'field' => 'btn_link',    'type' => 'Notification: Button Link',       'editor_type' => 'LINK' ],
				],
			],

			/*--------------------------------------------------------------
			# Table of Contents
			--------------------------------------------------------------*/
			'wcf--table-of-contents' => [
				'conditions' => [ 'widgetType' => 'wcf--table-of-contents' ],
				'fields'     => [
					
					[ 'field' => 'title',    'type' => 'Table Of Content: Button Text',       'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Image Accordion
			--------------------------------------------------------------*/
			'wcf--image-accordion' => [
				'conditions' => [ 'widgetType' => 'wcf--image-accordion' ],
				'fields'     => [],

				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Image_Accordion',]
				
			],

			/*--------------------------------------------------------------
			# Image Compare
			--------------------------------------------------------------*/
			'wcf--image-compare' => [
				'conditions' => [ 'widgetType' => 'wcf--image-compare' ],
				'fields' => [
					[ 'field' => 'before_caption', 'type' => 'Image Compare: Before Caption', 'editor_type' => 'LINE' ],
					[ 'field' => 'after_caption',  'type' => 'Image Compare: After Caption',  'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Category Widgets
			--------------------------------------------------------------*/
			'category-showcase' => [
				'conditions' => [ 'widgetType' => 'category-showcase' ],
				'fields' => [
					[ 'field' => 'empty_message', 'type' => 'Empty Message', 'editor_type' => 'LINE' ],
				],
			],

			'aae--category-slider' => [
				'conditions' => [ 'widgetType' => 'aae--category-slider' ],
				'fields' => [
					[ 'field' => 'count_text', 'type' => 'Category Slider: Article Text', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Mega Menu
			--------------------------------------------------------------*/
			'aae-nested-mega-menu' => [
				'conditions' => [ 'widgetType' => 'aae-nested-mega-menu' ],
				'fields' => [
					[ 'field' => 'menu_title', 'type' => 'Menu Title', 'editor_type' => 'LINE' ],
				],
			],

			/*
			|--------------------------------------------------------------------------
			| Stacked Cards
			|--------------------------------------------------------------------------
			*/
			'aae--stacked-cards' => [
				'conditions' => [ 'widgetType' => 'aae-stacked-cards' ],
				'fields' => [
				],

				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Stacked_Cards',]
			],

			/*
			|--------------------------------------------------------------------------
			| Nested Motion Card
			|--------------------------------------------------------------------------
			*/
			'aae-nested-motion-card' => [
				'conditions' => [ 'widgetType' => 'aae-nested-motion-card' ],
				'fields' => [
					[
						'field'       => 'card_title',
						'type'        => esc_html__( 'Motion Card: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
						'context'     => 'card_items',
					],
				],
				
				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Nested_Motion_Card',]

			],

			'wcf--scrollmotion-cards' => [
				'conditions' => [ 'widgetType' => 'wcf--scrollmotion-cards' ],
				'fields'     => [

					// Section title
					[
						'field'       => 'section_title',
						'type'        => esc_html__( 'Section Title', 'animation-addons-for-elementor-pro' ),
						'editor_type' => 'LINE',
					],

				
					
				],
				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Scrollmotion_Cards',]
			],
				
			/*
			|--------------------------------------------------------------------------
			| Advanced Testimonial
			|--------------------------------------------------------------------------
			*/
			'wcf--a-testimonial' => [
				'conditions' => [ 'widgetType' => 'wcf--a-testimonial' ],
				'fields' => [
					
				],
				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Advanced_Testimonial']
			],

			/*
			|--------------------------------------------------------------------------
			| Team Slider
			|--------------------------------------------------------------------------
			*/
			'wfc--team-slider' => [
				'conditions' => [ 'widgetType' => 'wfc--team-slider' ],
				'fields' => [
					
				],

				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Team_Slider']
			
				
			],

			/*--------------------------------------------------------------
			# Filterable Slider
			--------------------------------------------------------------*/
			'wcf--filterable-slider' => [
				'conditions' => [ 'widgetType' => 'wcf--filterable-slider' ],
				'fields' => [
					[
						'field'       => 'filter_all_label',
						'type'        => esc_html__( 'Filterable Slider: Filter All Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
						
					],
				],

				'integration-class' => [
					'WCF_ADDONS\INC\WPML\WIDGET\Filterable_Slider_Filters',
					'WCF_ADDONS\INC\WPML\WIDGET\Filterable_Slider_Projects',
				]


			],

			/*
			|--------------------------------------------------------------------------
			| Filterable Gallery
			|--------------------------------------------------------------------------
			*/
			'wcf--filterable-gallery' => [
				'conditions' => [ 'widgetType' => 'wcf--filterable-gallery' ],

				'fields' => [
					[
						'field'       => 'filter_all_label',
						'type'        => esc_html__( 'Filterable Gallery: Filter All Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
						
					],
				],

				'integration-class' => [
					'WCF_ADDONS\INC\WPML\WIDGET\Filterable_Gallary_Filters',
					'WCF_ADDONS\INC\WPML\WIDGET\Filterable_Gallery_Items',
				]

			],

			/*
			|--------------------------------------------------------------------------
			| Scroll Elements
			|--------------------------------------------------------------------------
			*/
			'wcf--scroll-elements' => [
				'conditions' => [ 'widgetType' => 'wcf--scroll-elements' ],
				'fields' => [
				
				],

				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Scroll_Element']

				
			],

			'wcf--a-portfolio' => [
				'conditions' => [ 'widgetType' => 'wcf--a-portfolio' ],
				'fields' => [
				
				],				
			],

			
			/*--------------------------------------------------------------
			# Vertical Marquee
			--------------------------------------------------------------*/
			'aae--vertical-marquee' => [
				'conditions' => [ 'widgetType' => 'aae--vertical-marquee' ],
				'fields' => [
				],
				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Vertical_Marquee']
			],
			/*--------------------------------------------------------------
			# Video Box Slider
			--------------------------------------------------------------*/
			'wcf--video-box-slider' => [
				'conditions' => [ 'widgetType' => 'wcf--video-box-slider' ],
				'fields' => [
					[
						
						[ 'field' => 'btn_text',      
						 'type' => esc_html__( 'Video Box Slider: Button Text', 'animation-addons-for-elementor' ),       
						 'editor_type' => 'LINE' ],
					],
					
					'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Video_Box_Slider']
				],
			],
			/*--------------------------------------------------------------
			# Animated Offcanvas
			--------------------------------------------------------------*/
			'wcf--animated-offcanvas' => [
				'conditions' => [ 'widgetType' => 'wcf--animated-offcanvas' ],
				'fields' => [
					[ 'field' => 'menu_button_text',   'type' => esc_html__( 'Title', 'animation-addons-for-elementor' ),   'editor_type' => 'LINE' ],
					[ 'field' => 'close_text', 'type' => esc_html__( 'Close Button Text', 'animation-addons-for-elementor' ), 'editor_type' => 'LINE' ],
					
				],

				'integration-class' => [ 
					'WCF_ADDONS\INC\WPML\WIDGET\Animated_Offcanvas_Contact_Info',
					'WCF_ADDONS\INC\WPML\WIDGET\Animated_Offcanvas_Language_Info',
					'WCF_ADDONS\INC\WPML\WIDGET\Animated_Offcanvas_Follow_Info'
				]
			],
			/*--------------------------------------------------------------
			# Post Reactions
			--------------------------------------------------------------*/
			'aaeaddon-post-reactions' => [
				'conditions' => [ 'widgetType' => 'aaeaddon-post-reactions' ],
				'fields' => [
					[
						'field'       => 'reactions_list',
						'type'        => esc_html__( 'Post Reactions: Reactions', 'animation-addons-for-elementor' ),
						'editor_type' => 'REPEATER',
						'fields_in_item' => [
							[ 'field' => 'reaction_label', 'type' => esc_html__( 'Reaction Label', 'animation-addons-for-elementor' ), 'editor_type' => 'LINE' ],
						],
					],
				],

				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Post_Reactions']
			],
			/*--------------------------------------------------------------
			# Posts Slider
			--------------------------------------------------------------*/
			'wcf--posts-slider' => [
				'conditions' => [ 'widgetType' => 'wcf--posts-slider' ],
				'fields' => [
					[ 'field' => 'read_more_text',   'type' => 'Posts Slider: Read More Text',   'editor_type' => 'LINE' ],
					[ 'field' => 'no_posts_message', 'type' => 'Posts Slider: No Posts Message', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Posts Slider: Posted By Text', 'editor_type' => 'LINE' ],
					// Repeater: meta_data
					[ 'field' => 'meta_label', 'type' => 'Posts Slider: Meta Label', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Post Timeline
			--------------------------------------------------------------*/
			'wcf--posts-timeline' => [
				'conditions' => [ 'widgetType' => 'wcf--posts-timeline' ],
				'fields' => [
					[ 'field' => 'read_more_text',   'type' => 'Post Timeline: Read More Text',   'editor_type' => 'LINE' ],
					[ 'field' => 'no_posts_message', 'type' => 'Post Timeline: No Posts Message', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Post Timeline: Posted By Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'load_more_btn_text', 'type' => 'Post Timeline: Load More Text', 'editor_type' => 'LINE' ],
					// Repeater: meta_data
					[ 'field' => 'meta_label', 'type' => 'Post Timeline: Meta Label', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Grid Hover Posts
			--------------------------------------------------------------*/
			'wcf--grid-hover-posts' => [
				'conditions' => [ 'widgetType' => 'wcf--grid-hover-posts' ],
				'fields' => [
					[ 'field' => 'read_more_text', 'type' => 'Grid Hover Post: Read More Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Grid Hover Post: Posted By Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'load_more_btn_text', 'type' => 'Grid Hover Post: Load More Text', 'editor_type' => 'LINE' ],
					// Repeater: meta_data
					[ 'field' => 'meta_label', 'type' => 'Grid Hover Post: Meta Label', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Advanced Post
			--------------------------------------------------------------*/
			'wcf--posts-pro' => [
				'conditions' => [ 'widgetType' => 'wcf--posts-pro' ],
				'fields' => [
					[ 'field' => 'filter_all_label', 'type' => 'Advanced Post: Filter All Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Advanced Post: Posted By Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'read_more_text', 'type' => 'Advanced Post: Read More Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'load_more_btn_text', 'type' => 'Advanced Post: Load More Text', 'editor_type' => 'LINE' ],
					  
				],
			],

			/*--------------------------------------------------------------
			# Video Story
			--------------------------------------------------------------*/
			'aae--video-story' => [
				'conditions' => [ 'widgetType' => 'aae--video-story' ],
				'fields' => [
					[ 'field' => 'post_by', 'type' => 'Video Story: Posted By Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'read_more_text', 'type' => 'Video Story: Read More Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'load_more_btn_text', 'type' => 'Video Story: Load More Text', 'editor_type' => 'LINE' ],
					  
				],
			],

			/*--------------------------------------------------------------
			# Advanced Post
			--------------------------------------------------------------*/
			'wcf--posts-filter' => [
				'conditions' => [ 'widgetType' => 'wcf--posts-filter' ],
				'fields' => [
					[ 'field' => 'filter_all_label', 'type' => 'Post Filter: Filter All Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Post Filter: Posted By Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'read_more_text', 'type' => 'Post Filter: Read More Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'load_more_btn_text', 'type' => 'Post Filter: Load More Text', 'editor_type' => 'LINE' ],
					  
				],
			],

			/*--------------------------------------------------------------
			# Feature Posts
			--------------------------------------------------------------*/
			'wcf--feature-posts' => [
				'conditions' => [ 'widgetType' => 'wcf--feature-posts' ],
				'fields' => [
					[ 'field' => 'read_more_text', 'type' => 'Feature Posts: Read More Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Feature Posts: Posted By Text', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Banner Posts
			--------------------------------------------------------------*/
			'wcf--banner-posts' => [
				'conditions' => [ 'widgetType' => 'wcf--banner-posts' ],
				'fields' => [
					[ 'field' => 'read_more_text', 'type' => 'Banner Posts: Read More Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'post_by', 'type' => 'Banner Posts: Posted By Text', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Live Events
			--------------------------------------------------------------*/
			'aae--live-events' => [
				'conditions' => [ 'widgetType' => 'aae--live-events' ],
				'fields' => [
					[ 'field' => 'post_by', 'type' => 'Live Event: Posted By Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'read_more_text',   'type' => 'Live Event: Read More Text',   'editor_type' => 'LINE' ],
					[ 'field' => 'load_more_btn_text',   'type' => 'Live Event: Load More Text',   'editor_type' => 'LINE' ],
					[ 'field' => 'no_events_message','type' => 'Live Event: No Events Message','editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Post Rating Form
			--------------------------------------------------------------*/
			'aae--post-rating-form' => [
				'conditions' => [ 'widgetType' => 'aae--post-rating-form' ],
				'fields' => [
					[ 'field' => 'form_title',         'type' => 'Post Rating: Form Title',         'editor_type' => 'LINE' ],
					[ 'field' => 'name_placeholder',   'type' => 'Post Rating: Name Placeholder',   'editor_type' => 'LINE' ],
					[ 'field' => 'email_placeholder',  'type' => 'Post Rating: Email Placeholder',  'editor_type' => 'LINE' ],
					[ 'field' => 'review_placeholder', 'type' => 'Post Rating: Review Placeholder', 'editor_type' => 'AREA' ],
					[ 'field' => 'submit_button_text', 'type' => 'Post Rating: Submit Button Text', 'editor_type' => 'LINE' ],
					[ 'field' => 'success_message',    'type' => 'Post Rating: Success Message',    'editor_type' => 'AREA' ],
					[ 'field' => 'error_message',      'type' => 'Post Rating: Error Message',      'editor_type' => 'AREA' ],
				],
			],
			/*--------------------------------------------------------------
			# Weather
			--------------------------------------------------------------*/
			'aae--weather' => [
				'conditions' => [ 'widgetType' => 'aae--weather' ],
				'fields' => [
					[ 'field' => 'city_name', 'type' => 'City Name', 'editor_type' => 'LINE' ],
					[ 'field' => 'error_message', 'type' => 'Error Message', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Scrollable Video Widget
			--------------------------------------------------------------*/
			'aae--scrollable-video' => [],

			/*--------------------------------------------------------------
			# Video Popup
			--------------------------------------------------------------*/
			'wcf--video-popup' => [
				'conditions' => [ 'widgetType' => 'wcf--video-popup' ],
				'fields' => [
					[ 'field' => 'btn_text', 'type' => 'Video Popup: Button Text', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Video Box
			--------------------------------------------------------------*/
			'wcf--video-box' => [
				'conditions' => [ 'widgetType' => 'wcf--video-box' ],
				'fields' => [
					[ 'field' => 'title',       'type' => 'Video Box: Title', 'editor_type' => 'LINE' ],
					[ 'field' => 'subtitle', 'type' => 'Video Box: Sub Title', 'editor_type' => 'AREA' ],
					[ 'field' => 'btn_text',     'type' => 'Video Box: Button Text', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Video Mask
			--------------------------------------------------------------*/
			'wcf--video-mask' => [
				'conditions' => [ 'widgetType' => 'wcf--video-mask' ],
				'fields' => [
					[ 'field' => 'title', 'type' => 'Video Mask: Title', 'editor_type' => 'LINE' ],
					[ 'field' => 'close_title', 'type' => 'Video Mask: Title', 'editor_type' => 'LINE' ],
					
				],
			],

			/*--------------------------------------------------------------
			# YouTube Video
			--------------------------------------------------------------*/
			'aae-pro-youtube-videos' => [
				'conditions' => [ 'widgetType' => 'aae-pro-youtube-videos' ],
				'fields' => [
					[ 'field' => 'no_videos_message', 'type' => 'YouTube Video: No Videos Message', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# TikTok Feed
			--------------------------------------------------------------*/
			'aae--tiktok-feed' => [
				'conditions' => [ 'widgetType' => 'aae--tiktok-feed' ],
				'fields' => [
					[ 'field' => 'no_videos_message', 'type' => 'No Videos Message', 'editor_type' => 'LINE' ],
				],
			],

			/*--------------------------------------------------------------
			# Lottie
			--------------------------------------------------------------*/
			'wcf--lottie' => [
				'conditions' => [ 'widgetType' => 'wcf--lottie' ],
				'fields' => [
					[ 'field' => 'lottie_url', 'type' => 'Lottie URL', 'editor_type' => 'LINK' ],
				],
			],

			/*--------------------------------------------------------------
			# Draw SVG
			--------------------------------------------------------------*/
			'wcf--draw-svg' => [
				'conditions' => [ 'widgetType' => 'wcf--draw-svg' ],
				'fields' => [
					[ 'field' => 'svg_code', 'type' => 'SVG Code', 'editor_type' => 'AREA' ],
				],
			],

		
			/*--------------------------------------------------------------
			# Advanced Mailchimp
			--------------------------------------------------------------*/
			'aae--advanced-mailchimp' => [
				'conditions' => [ 'widgetType' => 'wcf--advanced-mailchimp' ],
				'fields'     => [
					[
						'field'       => 'email_label',
						'type'        => esc_html__( 'Mailchimp: Form Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_placeholder',
						'type'        => esc_html__( 'Mailchimp: Email Placeholder', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'submit_button_text',
						'type'        => esc_html__( 'Mailchimp: Submit Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'success_message',
						'type'        => esc_html__( 'Mailchimp: Success Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'confirmation_message',
						'type'        => esc_html__( 'Mailchimp: Confirmation Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'button_text',
						'type'        => esc_html__( 'Mailchimp: Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
				'integration-class' => [ 'WCF_ADDONS\INC\WPML\WIDGET\Advanced_Mailchimp',]
			],
			/*--------------------------------------------------------------
			# Breaking News Slider
			--------------------------------------------------------------*/
			'wcf--breaking-news-slider' => [
				'conditions' => [ 'widgetType' => 'wcf--breaking-news-slider' ],
				'fields' => [
					[ 'field' => 'news_label', 'type' => 'News Label', 'editor_type' => 'LINE' ],
				],
			],
		];

		/**
		 * Register widgets in WPML Elementor translation config
		 */
		foreach ( $widgets_map as $widget_name => $data ) {

			$entry = [
				'conditions' => [
					'widgetType' => $widget_name,
				],
			];

			if ( ! empty( $data['fields'] ) ) {
				$entry['fields'] = $data['fields'];
			}

			if ( ! empty( $data['fields_in_item'] ) ) {
				$entry['fields_in_item'] = $data['fields_in_item'];
			}

			if ( isset( $data['integration-class'] ) ) {
				$entry['integration-class'] = $data['integration-class'];
			}

			$widgets[ $widget_name ] = $entry;
		}

		return $widgets;
	}

}
