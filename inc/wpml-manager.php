<?php
/**
 * WPML integration and compatibility manager
 */
namespace WCF_ADDONS\INC\WPML;

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
		include_once( WCF_ADDONS_PATH . 'inc/wpml-module-with-items.php' );
	}

	public static function add_widgets_to_translate( $widgets ) {
		//self::load_integration_files();

		$widgets_map = [

			/**
			 * Animated Title Widget
			 */
			'wcf--title' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'title_prefix_list' => [
						[
							'field'       => 'label',
							'type'        => __( 'Animated Title: Label', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'label_on',
							'type'        => __( 'Animated Title: Label On', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'label_off',
							'type'        => __( 'Animated Title: Label Off', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Button Widget
			 */
			'wcf--button' => [
				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => __( 'Button: Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'btn_sub_text',
						'type'        => __( 'Button: Sub Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Advanced Button Widget
			 */
			'aae--advanced-button' => [
				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => __( 'Button: Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'btn_sub_text',
						'type'        => __( 'Button: Sub Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Image Box Widget
			 */
			'wcf--image-box' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description',
						'type'        => __( 'Content: Description', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Image Box Slider Widget
			 */
			'wcf--image-box-slider' => [
				'fields_in_item' => [
					'slider_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Slider Item: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __( 'Slider Item: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
						[
							'field'       => 'button_text',
							'type'        => __( 'Slider Item: Button Text', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Icon Box Widget
			 */
			'wcf--icon-box' => [
				'fields' => [
					[
						'field'       => 'title_text',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description_text',
						'type'        => __( 'Content: Description', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Testimonial Widget
			 */
			'wcf--testimonial' => [
				'fields_in_item' => [
					'testimonials' => [
						[
							'field'       => 'testimonial_name',
							'type'        => __( 'Testimonial: Name', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __( 'Testimonial: Designation', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __( 'Testimonial: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Testimonial 2 Widget
			 */
			'wcf--testimonial2' => [
				'fields_in_item' => [
					'testimonials' => [
						[
							'field'       => 'testimonial_name',
							'type'        => __( 'Testimonial: Name', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __( 'Testimonial: Designation', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __( 'Testimonial: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Testimonial 3 Widget
			 */
			'wcf--testimonial3' => [
				'fields_in_item' => [
					'testimonials' => [
						[
							'field'       => 'testimonial_name',
							'type'        => __( 'Testimonial: Name', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __( 'Testimonial: Designation', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __( 'Testimonial: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Advanced Testimonial Widget
			 */
			'wcf--a-testimonial' => [
				'fields_in_item' => [
					'testimonials' => [
						[
							'field'       => 'testimonial_name',
							'type'        => __( 'Testimonial: Name', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __( 'Testimonial: Designation', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __( 'Testimonial: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Team Widget
			 */
			'wcf--team' => [
				'fields' => [
					[
						'field'       => 'member_name',
						'type'        => __( 'Content: Name', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'member_position',
						'type'        => __( 'Content: Position', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'member_description',
						'type'        => __( 'Content: Description', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Team Slider Widget
			 */
			'wcf--team-slider' => [
				'fields_in_item' => [
					'team_list' => [
						[
							'field'       => 'member_name',
							'type'        => __( 'Team: Name', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'member_position',
							'type'        => __( 'Team: Position', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'member_description',
							'type'        => __( 'Team: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Counter Widget
			 */
			'wcf--counter' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'suffix',
						'type'        => __( 'Content: Suffix', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'prefix',
						'type'        => __( 'Content: Prefix', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Progressbar Widget
			 */
			'wcf--progressbar' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Typewriter Widget
			 */
			'wcf--typewriter' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'typewriter_list' => [
						[
							'field'       => 'text',
							'type'        => __( 'Typewriter: Text', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Animated Heading Widget
			 */
			'wcf--animated-heading' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'animated_text_list' => [
						[
							'field'       => 'text',
							'type'        => __( 'Animated Heading: Text', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Animated Text Widget
			 */
			'wcf--text' => [
				'fields' => [
					[
						'field'       => 'text',
						'type'        => __( 'Content: Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Text Hover Image Widget
			 */
			'wcf--t-h-image' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description',
						'type'        => __( 'Content: Description', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Timeline Widget
			 */
			'wcf--timeline' => [
				'fields_in_item' => [
					'timeline_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Timeline: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'content',
							'type'        => __( 'Timeline: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Tabs Widget
			 */
			'wcf--tabs' => [
				'fields_in_item' => [
					'tabs_list' => [
						[
							'field'       => 'tab_title',
							'type'        => __( 'Tab: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tab_content',
							'type'        => __( 'Tab: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Services Tab Widget
			 */
			'wcf--services-tab' => [
				'fields_in_item' => [
					'services_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Service: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __( 'Service: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Advance Accordion Widget
			 */
			'wcf--a-accordion' => [
				'fields_in_item' => [
					'accordion_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Accordion: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'content',
							'type'        => __( 'Accordion: Content', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Image Accordion Widget
			 */
			'wcf--image-accordion' => [
				'fields_in_item' => [
					'accordion_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Accordion: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __( 'Accordion: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Countdown Widget
			 */
			'wcf--countdown' => [
				'fields' => [
					[
						'field'       => 'label_days',
						'type'        => __( 'Content: Label Days', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'label_hours',
						'type'        => __( 'Content: Label Hours', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'label_minutes',
						'type'        => __( 'Content: Label Minutes', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'label_seconds',
						'type'        => __( 'Content: Label Seconds', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Posts Widget
			 */
			'wcf--posts' => [
				'fields' => [
					[
						'field'       => 'read_more_text',
						'type'        => __( 'Content: Read More Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'no_posts_message',
						'type'        => __( 'Content: No Posts Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Feature Posts Widget
			 */
			'wcf--feature-posts' => [
				'fields' => [
					[
						'field'       => 'read_more_text',
						'type'        => __( 'Content: Read More Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'no_posts_message',
						'type'        => __( 'Content: No Posts Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Banner Posts Widget
			 */
			'wcf--banner-posts' => [
				'fields' => [
					[
						'field'       => 'read_more_text',
						'type'        => __( 'Content: Read More Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Post Title Widget (no fields)
			 */
			'wcf--blog--post--title' => [],

			/**
			 * Post Excerpt Widget (no fields)
			 */
			'wcf--blog--post--excerpt' => [],

			/**
			 * Post Content Widget (no fields)
			 */
			'wcf--theme-post-content' => [],

			/**
			 * Post Meta Info Widget (no fields)
			 */
			'wcf--blog--post--meta-info' => [],

			/**
			 * Post Feature Image Widget (no fields)
			 */
			'wcf--theme-post-image' => [],

			/**
			 * Post Comment Widget (no fields)
			 */
			'wcf--blog--post--comment' => [],

			/**
			 * Post Paginate Widget
			 */
			'wcf--blog--post--paginate' => [
				'fields' => [
					[
						'field'       => 'prev_text',
						'type'        => __( 'Content: Previous Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'next_text',
						'type'        => __( 'Content: Next Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Post Social Share Widget (no fields)
			 */
			'wcf--blog--post--social-share' => [],

			/**
			 * Post Rating Widget (no fields)
			 */
			'aae--post-rating' => [],

			/**
			 * Post Rating Form Widget
			 */
			'aae--post-rating-form' => [
				'fields' => [
					[
						'field'       => 'submit_text',
						'type'        => __( 'Content: Submit Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Post Reactions Widget (no fields)
			 */
			'wcf--post-reactions' => [],

			/**
			 * Post Timeline Widget (no fields)
			 */
			'wcf--posts-timeline' => [],

			/**
			 * Archive Title Widget (no fields)
			 */
			'wcf--blog--archive--title' => [],

			/**
			 * Search Form Widget
			 */
			'wcf--blog--search--form' => [
				'fields' => [
					[
						'field'       => 'placeholder',
						'type'        => __( 'Content: Placeholder', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'button_text',
						'type'        => __( 'Content: Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Search Query Widget (no fields)
			 */
			'wcf--blog--search--query' => [],

			/**
			 * Search No Result Widget
			 */
			'wcf--blog--search--result-message' => [
				'fields' => [
					[
						'field'       => 'no_results_message',
						'type'        => __( 'Content: No Results Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Author Box Widget (no fields)
			 */
			'wcf-author-box' => [
				'fields' => [
					[
						'field'       => 'author_name',
						'type'        => __( 'Author Name', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'author_bio',
						'type'        => __( 'Author Biography', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'link_text',
						'type'        => __( 'Archive Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'contact_title',
						'type'        => __( 'Contact Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_label',
						'type'        => __( 'Email Label', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'phone_label',
						'type'        => __( 'Phone Label', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'social_title',
						'type'        => __( 'Social Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Breadcrumbs Widget (no fields)
			 */
			'wcf--breadcrumbs' => [],

			/**
			 * Site Logo Widget (no fields)
			 */
			'wcf--site-logo' => [],

			/**
			 * Current Date Widget
			 */
			'wcf--current-date' => [
				'fields' => [
					[
						'field'       => 'date_format',
						'type'        => __( 'Content: Date Format', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Social Icons Widget
			 */
			'wcf--social-icons' => [
				'fields_in_item' => [
					'social_icons_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Social Icon: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Nav Menu Widget (no fields)
			 */
			'wcf--nav-menu' => [],

			/**
			 * One Page Nav Widget (no fields)
			 */
			'wcf--one-page-nav' => [
				'fields_in_item' => [
					'wcf_one_page_nav' => [
						[
							'field'       => 'nav_text',
							'type'        => __( 'One Page Nav: Text', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'section_id',
							'type'        => __( 'One Page Nav: Section ID', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Image Widget
			 */
			'wcf--image' => [
				'fields' => [
					[
						'field'       => 'caption',
						'type'        => __( 'Content: Caption', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'link',
						'type'        => __( 'Image: Link', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINK',
					],
					
				],
			],

			/**
			 * Image Gallery Widget (no fields)
			 */
			'wcf--image-gallery' => [
				'fields_in_item' => [
					'wcf_image_gallery' => [
						[
							'field'       => 'link',
							'type'        => __( 'Image Gallery: Link', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINK',
						],
					],
				],
			],

			/**
			 * Image Hotspot Widget
			 */
			'aae--image-hotspot' => [
				'fields_in_item' => [
					'hotspot_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Hotspot: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __( 'Hotspot: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Image Compare Widget (no fields)
			 */
			'wcf--image-compare' => [
				'fields' => [
					[
						'field'       => 'before_caption',
						'type'        => __( 'Content: Before Caption', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'after_caption',
						'type'        => __( 'Content: After Caption', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Brand Slider Widget (no fields)
			 */
			'wcf--brand-slider' => [
				'fields_in_item' => [
					'repeat_list_text' => [
						[
							'field'       => 'list_text',
							'type'        => __( 'Brand Slider: Text', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Category Slider Widget (no fields)
			 */
			'aae--category-slider' => [],

			/**
			 * Content Slider Widget
			 */
			'wcf--content-slider' => [
				'fields_in_item' => [
					'slider_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Slider: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __( 'Slider: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
						[
							'field'       => 'button_text',
							'type'        => __( 'Slider: Button Text', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Nested Slider Widget (no fields)
			 */
			'wcf--nested-slider' => [
				'fields' => [
					[
						'field'       => 'carousel_name',
						'type'        => __( 'Carousel Name', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'carousel_items' => [
						[
							'field'       => 'slide_title',
							'type'        => __( 'Slide Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Filterable Slider Widget (multiple repeaters)
			 */
			'wcf--filterable-slider' => [
				'fields_in_item' => [
					'filter_list' => [
						[
							'field'       => 'filter_label',
							'type'        => __( 'Filter: Label', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
					],
					'slider_list' => [
						[
							'field'       => 'title',
							'type'        => __( 'Slider: Title', 'animation-addons-for-elementor' ),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __( 'Slider: Description', 'animation-addons-for-elementor' ),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Event Slider Widget (no fields)
			 */
			'wcf--event-slider' => [],

			/**
			 * Video Posts Tab Widget (no fields)
			 */
			'aae--video-posts-tab' => [],

			/**
			 * Contact Form 7 Widget (no fields)
			 */
			'wcf--contact-form-7' => [],

			/**
			 * Mailchimp Widget
			 */
			'wcf--mailchimp' => [
				'fields' => [
					[
						'field'       => 'placeholder',
						'type'        => __( 'Content: Placeholder', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'button_text',
						'type'        => __( 'Content: Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'success_message',
						'type'        => __( 'Content: Success Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'error_message',
						'type'        => __( 'Content: Error Message', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Notification Widget
			 */
			'aae--notification' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description',
						'type'        => __( 'Content: Description', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'button_text',
						'type'        => __( 'Content: Button Text', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Toggle Switcher Widget
			 */
			'wcf--toggle-switch' => [
				'fields' => [
					[
						'field'       => 'label_on',
						'type'        => __( 'Content: Label On', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'label_off',
						'type'        => __( 'Content: Label Off', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Click Drop Widget
			 */
			'aae--clickdrop' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __( 'Content: Title', 'animation-addons-for-elementor' ),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description',
						'type'        => __( 'Content: Description', 'animation-addons-for-elementor' ),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Floating Elements Widget (no fields)
			 */
			'wcf--floating-elements' => [],

			/**
			 * Loop Grid Widget (no fields)
			 */
			'aae--loop-grid' => [],
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

			// if ( isset( $data['integration-class'] ) ) {
			// 	$entry['integration-class'] = $data['integration-class'];
			// }

			$widgets[ $widget_name ] = $entry;
		}

		return $widgets;
	}

}
