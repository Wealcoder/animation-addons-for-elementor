<?php

/**
 * WPML integration and compatibility manager
 */

namespace WCF_ADDONS\INC\WPML;

defined('ABSPATH') || die();

class WPML_Manager
{

	/**
	 * Recreate Animation Addoon widgets usage on transtion save
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

	public static function load_integration_files()
	{
		// Load repeatable module class
		include_once(WCF_ADDONS_PATH . 'inc/wpml-module-with-items.php');
		foreach (glob(WCF_ADDONS_PATH . 'inc/wpml/*.php') as $file) {
			include_once $file;
		}
	}

	public static function add_widgets_to_translate($widgets)
	{
		self::load_integration_files();

		$widgets_map = [

			/**
			 * Animated Title Widget
			 */
			'wcf--title' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __('Animated Title: Content: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'link',
						'type'        => esc_html__('Animated Title: Link', 'animation-addons-for-elementor'),
						'editor_type' => 'LINK',
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
						'type'        => __('Button: Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Advanced Button: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					// [
					// 	'field'       => 'btn_link',
					// 	'type'        => __('Button: Link', 'animation-addons-for-elementor'),
					// 	'editor_type' => 'LINK',
					// ],

				],
			],

			/**
			 * Image Box Widget
			 */
			'wcf--image-box' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __('Image Box: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'subtitle',
						'type'        => __('Image Box: Sub Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description',
						'type'        => __('Image Box: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'btn_text',
						'type'        => __('Image Box: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Image Box Slider Widget
			 */
			'wcf--image-box-slider' => [
				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => __('Image Box Slider: Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Image_Box_Slider',]
			],

			/**
			 * Icon Box Widget
			 */
			'wcf--icon-box' => [
				'fields' => [
					[
						'field'       => 'title_text',
						'type'        => __('Icon Box: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description_text',
						'type'        => __('Icon Box: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'VISUAL',
					],

					[
						'field'       => 'btn_text',
						'type'        => __('Icon Box: Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					
				],
			],

			/**
			 * Testimonial Widget
			 */
			'wcf--testimonial' => [
				// 'fields_in_item' => [
				// 	'testimonials' => [
				// 		[
				// 			'field'       => 'testimonial_name',
				// 			'type'        => __('Testimonial: Name', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 		[
				// 			'field'       => 'testimonial_job',
				// 			'type'        => __('Testimonial: Designation', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 		[
				// 			'field'       => 'testimonial_content',
				// 			'type'        => __('Testimonial: Content', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'AREA',
				// 		],
				// 	],
				// ],
				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Testimonial',]
			],

			/**
			 * Classic Testimonial Widget
			 */
			'wcf--testimonial2' => [
				// 'fields_in_item' => [
				// 	'testimonials' => [
				// 		[
				// 			'field'       => 'testimonial_name',
				// 			'type'        => __('Testimonial: Name', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 		[
				// 			'field'       => 'testimonial_job',
				// 			'type'        => __('Testimonial: Designation', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 		[
				// 			'field'       => 'testimonial_content',
				// 			'type'        => __('Testimonial: Content', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'AREA',
				// 		],
				// 	],
				// ],
				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Testimonial_Two',]
			],

			/**
			 * Testimonial 3 Widget
			 */
			'wcf--testimonial3' => [

				'fields' => [
					[
						'field'       => 'testimonial_sect_title',
						'type'        => __('Modern Testimonial: Section Title', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
				],

				'fields_in_item' => [
					'testimonials' => [
						[
							'field'       => 'testimonial_name',
							'type'        => __('Modern Testimonial: Name', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __('Modern Testimonial: Designation', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __('Modern Testimonial: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'AREA',
						],
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Testimonial_Three',]
			],

			/**
			 * Advanced Testimonial Widget
			 */
			'wcf--a-testimonial' => [
				'conditions' => [ 'widgetType' => 'wcf--a-testimonial' ],
			    'fields' => [
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Advanced_Testimonial']
			],

			/**
			 * Team Widget
			 */
			'wcf--team' => [
				'fields' => [
					[
						'field'       => 'member_name',
						'type'        => __('Team: Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'member_designation',
						'type'        => __('Team: Designation', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'member_description',
						'type'        => __('Team: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Team Slider Widget
			 */
			'wfc--team-slider' => [
				'fields_in_item' => [
					'team_slides' => [
						[
							'field'       => 'title',
							'type'        => __('Team: Name', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'desc',
							'type'        => __('Team: Position', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						// // not sure if it's exist
						// [
						// 	'field'       => 'member_description',
						// 	'type'        => __('Team: Description', 'animation-addons-for-elementor'),
						// 	'editor_type' => 'AREA',
						// ],
					],
				],
				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Team_Slider']
			],
			
			/**
			 * Counter Widget
			 */
			'wcf--counter' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __('Counter: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'suffix',
						'type'        => __('Counter: Suffix', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'prefix',
						'type'        => __('Counter: Prefix', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Progressbar Widget
			 */
			'wcf--progressbar' => [
				'fields' => [
					// [
					// 	'field'       => 'title',
					// 	'type'        => __('Content: Title', 'animation-addons-for-elementor'),
					// 	'editor_type' => 'LINE',
					// ],
				],
			],

			/**
			 * Typewriter Widget
			 */
			'wcf--typewriter' => [
				'fields' => [
					[
						'field'       => 'typewriter_normal_text',
						'type'        => __('Typewriter: Non-Animated Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'typewriter_animated_text' => [
						[
							'field'       => 'list_text',
							'type'        => __('Typewriter: Text', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					],
				],
				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\TypeWriter']

			],

			/**
			 * Animated Heading Widget
			 */
			'wcf--animated-heading' => [
				'fields' => [
					[
						'field'       => 'heading',
						'type'        => __('Animation Heading: Animated Heading', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
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
						'type'        => __('Animated Text: Animated Text', 'animation-addons-for-elementor'),
						'editor_type' => 'VISUAL',
					],
				],
			],

			/**
			 * Text Hover Image Widget
			 */
			'wcf--t-h-image' => [
				'fields' => [
					[
						'field'       => 'before_hover_text',
						'type'        => __('Text Hover Image: Before Hover Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'hover_text',
						'type'        => __('Text Hover Image: Hover Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'after_hover_text',
						'type'        => __('Text Hover Image: After Hover Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Timeline Widget
			 */
			'wcf--timeline' => [
	
				'fields' => [
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Timeline']

			],

			/**
			 * Tabs Widget
			 */
			'wcf--tabs' => [
				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Tabs']
			],

			/**
			 * Services Tab Widget
			 */
			'wcf--services-tab' => [

				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => __('Services Tab: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Services_Tab']
			],

			/**
			 * Advance Accordion Widget
			 */
			'wcf--a-accordion' => [
			
				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Advance_Accordion']
			],

			/*--------------------------------------------------------------
			# Image Accordion
			--------------------------------------------------------------*/
			'wcf--image-accordion' => [
				'conditions' => ['widgetType' => 'wcf--image-accordion'],
				'fields'     => [],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Image_Accordion',]

			],

			/**
			 * Countdown Widget
			 */
			'wcf--countdown' => [
				'fields' => [
					[
						'field'       => 'countdown_timer_days_label',
						'type'        => __('Countdown: Label Days', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'countdown_timer_hours_label',
						'type'        => __('Countdown: Label Hours', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'countdown_timer_minutes_label',
						'type'        => __('Countdown: Label Minutes', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'countdown_timer_seconds_label',
						'type'        => __('Countdown: Label Seconds', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'time_expire_title',
						'type'        => __('Countdown: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'time_expire_desc',
						'type'        => __('Countdown: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
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
						'type'        => __('Post: Read More Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'load_more_btn_text',
						'type'        => __('Content: Load More Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				
				],
			],

			/*--------------------------------------------------------------
			# Feature Posts
			--------------------------------------------------------------*/
			'wcf--feature-posts' => [
				'conditions' => ['widgetType' => 'wcf--feature-posts'],
				'fields' => [
					['field' => 'read_more_text', 'type' => 'Feature Posts: Read More Text', 'editor_type' => 'LINE'],
					['field' => 'meta_separator', 'type' => 'Feature Posts: Separator Between', 'editor_type' => 'LINE'],
					['field' => 'post_by', 'type' => 'Feature Posts: Posted By Text', 'editor_type' => 'LINE'],
				],
			],

			/*--------------------------------------------------------------
			# Banner Posts
			--------------------------------------------------------------*/
			'wcf--banner-posts' => [
				'conditions' => ['widgetType' => 'wcf--banner-posts'],
				'fields' => [
					['field' => 'read_more_text', 'type' => 'Banner Posts: Read More Text', 'editor_type' => 'LINE'],
					['field' => 'meta_separator', 'type' => 'Banner Posts: Separator Between', 'editor_type' => 'LINE'],
					['field' => 'post_by', 'type' => 'Banner Posts: Posted By Text', 'editor_type' => 'LINE'],
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
			'wcf--blog--post--meta-info' => [
				'fields_in_item' => [
					'list' => [
						[
							'field'       => 'list_title',
							'type'        => __('Post Meta: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'meta_separator',
							'type'        => __('Post Meta: Separator', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						]
					]
				],
			],

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
						'field'       => 'prev_title',
						'type'        => __('Post Paginate: Previous Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'next_title',
						'type'        => __('Post Paginate: Next Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Post Social Share Widget (no fields)
			 */
			'wcf--blog--post--social-share' => [

				'fields' => [
					[
						'field'       => 'share_text',
						'type'        => __('Content: Share Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'fields_in_item' => [
					'list' => [
						[
							'field'       => 'list_title',
							'type'        => __('Post Social Share: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Post_Social_Share']

			],

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
						'type'        => __('Post Rating Form: Submit Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'title',
						'type'        => __('Post Rating Form: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'text',
						'type'        => __('Post Rating Form: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'name_plh_text',
						'type'        => __('Post Rating Form: Name Placeholder Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_plh_text',
						'type'        => __('Post Rating Form: Email Placeholder Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'review_placeholder',
						'type'        => __('Post Rating Form: Review Placeholder Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'btn_text',
						'type'        => __('Post Rating Form: Button Text', 'animation-addons-for-elementor'),
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
			'wcf--posts-timeline' => [
				'fields' => [
					[
						'field'       => 'meta_separator',
						'type'        => __('Post Timeline: Separator Between', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'post_by',
						'type'        => __('Post Timeline: Author By', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'read_more_text',
						'type'        => __('Post Timeline: Read More Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'load_more_btn_text',
						'type'        => __('Post Timeline: Load More Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Archive Title Widget (no fields)
			 */
			'wcf--blog--archive--title' => [
				'fields_in_item' => [
					'list' => [
						[
							'field'       => 'list_title',
							'type'        => __('Archive Title: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'list_content',
							'type'        => __('Archive Title: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'AREA',
						],
						
					],
				],
			],

			/**
			 * Search Form Widget
			 */
			'wcf--blog--search--form' => [
				'fields' => [
					[
						'field'       => 'placeholder',
						'type'        => __('Search Form: Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'button_text',
						'type'        => __('Search Form: Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Search Query Widget (no fields)
			 */
			'wcf--blog--search--query' => [
				'fields' => [
					[
						'field'       => 'search_text',
						'type'        => __('Search Query: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Search No Result Widget
			 */
			'wcf--blog--search--result-message' => [
				'fields' => [
					[
						'field'       => 'search_text',
						'type'        => __('Search No Result: No Results Message', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'search_content',
						'type'        => __('Search No Result: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'VISUAL',
					],
				],
			],

			/**
			 * Author Box Widget (no fields)
			 */
			'wcf--author-box' => [
				'fields' => [
					[
						'field'       => 'author_name',
						'type'        => __('Author Box: Author Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'author_bio',
						'type'        => __('Author Box: Author Biography', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'link_text',
						'type'        => __('Author Box: Archive Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'contact_title',
						'type'        => __('Author Box: Contact Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_label',
						'type'        => __('Author Box: Email Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'phone_label',
						'type'        => __('Author Box: Phone Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'social_title',
						'type'        => __('Author Box: Social Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			// 8) Breadcrumbs (separator text only)
			'wcf--breadcrumbs' => [
				'conditions' => ['widgetType' => 'wcf--breadcrumbs'],
				'fields'     => [
					[
						'field'       => 'br_separator',
						'type'        => esc_html__('Breadcrumbs: Separator Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Site Logo Widget (no fields)
			 */
			// Site Logo
			'wcf--site-logo' => [
				'conditions' => ['widgetType' => 'wcf--site-logo'],
				'fields'     => [
					// [
					// 	'field'       => 'caption',
					// 	'type'        => esc_html__('Site Logo: Caption', 'animation-addons-for-elementor'),
					// 	'editor_type' => 'LINE',
					// ],
					// [
					// 	'field'       => 'link',
					// 	'type'        => esc_html__('Site Logo: Link', 'animation-addons-for-elementor'),
					// 	'editor_type' => 'LINK',
					// ],
				],
			],

			/**
			 * Current Date Widget
			 */
			'wcf--current-date' => [
				'fields' => [
					[
						'field'       => 'day_separator',
						'type'        => __('Current Date: Date Separator', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Social Icons Widget
			 */
			'wcf--social-icons' => [
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
							'type'        => __('One Page Nav: Text', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
			
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\One_Page_Nav']
			],

			/**
			 * Image Widget
			 */
			'wcf--image' => [
				// 'fields' => [
				// 	[
				// 		'field'       => 'caption',
				// 		'type'        => __('Content: Caption', 'animation-addons-for-elementor'),
				// 		'editor_type' => 'LINE',
				// 	],

				// 	[
				// 		'field'       => 'link',
				// 		'type'        => __('Image: Link', 'animation-addons-for-elementor'),
				// 		'editor_type' => 'LINK',
				// 	],

				// ],
			],

			/**
			 * Image Gallery Widget (no fields)
			 */
			'wcf--image-gallery' => [
				// 'fields_in_item' => [
				// 	'wcf_image_gallery' => [
				// 		[
				// 			'field'       => 'link',
				// 			'type'        => __('Image Gallery: Link', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINK',
				// 		],
				// 	],
				// ],
			],

			/**
			 * Image Hotspot Widget
			 */
			'aae--image-hotspot' => [
				'fields_in_item' => [
					'hsp_list' => [
						[
							'field'       => 'hsp_text',
							'type'        => __('Image Hotspot: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tlp_content',
							'type'        => __('Image Hotspot: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
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
						'type'        => __('Image Compare: Before Caption', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'after_caption',
						'type'        => __('Image Compare: After Caption', 'animation-addons-for-elementor'),
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
							'type'        => __('Brand Slider: Text', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Brand_Slider']
			],

			/**
			 * Category Slider Widget (no fields)
			 */
			'aae--category-slider' => [
				'fields' => [
					[
						'field'       => 'count_text',
						'type'        => __('Article Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

			],

			/**
			 * Content Slider Widget
			 */
			'wcf--content-slider' => [


				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Content_Slider']
			],

			/**
			 * Nested Slider Widget (no fields)
			 */
			'wcf--nested-slider' => [
				'conditions' => [ 'widgetType' => 'wcf--nested-slider' ],
				'fields' => [
					[
						'field'       => 'carousel_name',
						'type'        => __('Nested Slider: Carousel Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'integration-class' => ['WCF_ADDONS\INC\WPML\WIDGET\Nested_Slider']
			],

			/**
			 * Filterable Slider Widget (multiple repeaters)
			 */
			'wcf--filterable-slider' => [
				'conditions' => ['widgetType' => 'wcf--filterable-slider'],
				'fields' => [
					[
						'field'       => 'filter_all_label',
						'type'        => esc_html__('Filterable Slider: Filter All Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',

					],
				],

				'integration-class' => [
					'WCF_ADDONS\INC\WPML\WIDGET\Filterable_Slider_Filters',
					'WCF_ADDONS\INC\WPML\WIDGET\Filterable_Slider_Projects',
				]
			],

			/**
			 * Event Slider Widget (no fields)
			 */
			'wcf--event-slider' => [
				'fields' => [
				    [
						'field'       => 'btn_text',
						'type'        => __('Event Slider: Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'integration-class' => [
					'WCF_ADDONS\INC\WPML\WIDGET\Event_Slider',
				]
			],

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
						'field'       => 'confirmation_message',
						'type'        => __('Mailchimp: Confirmation Message', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'success_message',
						'type'        => __('Mailchimp: Success Message', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'fname_label',
						'type'        => __('Mailchimp: First Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'fname_placeholder',
						'type'        => __('Mailchimp: First Name Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'lname_label',
						'type'        => __('Mailchimp: Last Name Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'lname_placeholder',
						'type'        => __('Mailchimp: Last Name Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'phone_label',
						'type'        => __('Mailchimp: Phone Name Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'phone_placeholder',
						'type'        => __('Mailchimp: Phone Name Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_label',
						'type'        => __('Mailchimp: Email Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_placeholder',
						'type'        => __('Mailchimp: Email Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'button_text',
						'type'        => __('Mailchimp: Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

				],
			],

			/*--------------------------------------------------------------
			# Notification
			--------------------------------------------------------------*/
			'aae--notification' => [
				'conditions' => ['widgetType' => 'aae--notification'],
				'fields'     => [
					['field' => 'notify_text', 'type' => 'Notification: Notification Text', 'editor_type' => 'AREA'],
					['field' => 'btn_text',    'type' => 'Notification: Button Text',       'editor_type' => 'LINE'],
				],
			],

			/**
			 * Toggle Switcher Widget
			 */
			'wcf--toggle-switch' => [
				'fields_in_item' => [
					'toggle_switcher' => [
						[
							'field'       => 'switch_title',
							'type'        => __('Toggle Switcher: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'switch_content',
							'type'        => __('Toggle Switcher: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
						],
					]
				]
			],

			/**
			 * Click Drop Widget
			 */
			'aae--clickdrop' => [
				'fields' => [
					[
						'field'       => 'login_label',
						'type'        => __('Click Drop: Login label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'logged_label',
						'type'        => __('Click Drop: Logged label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'menus_url' => [
						[
							'field'       => 'menu_title',
							'type'        => __('Click Drop: Menu title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					]
				]
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
		foreach ($widgets_map as $widget_name => $data) {

			$entry = [
				'conditions' => [
					'widgetType' => $widget_name,
				],
			];

			if (! empty($data['fields'])) {
				$entry['fields'] = $data['fields'];
			}

			if (! empty($data['fields_in_item'])) {
				$entry['fields_in_item'] = $data['fields_in_item'];
			}

			if ( isset( $data['integration-class'] ) ) {
				$entry['integration-class'] = $data['integration-class'];
			}

			$widgets[$widget_name] = $entry;
		}

		return $widgets;
	}
}
