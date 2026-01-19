<?php

/**
 * WPML integration and compatibility manager
 */

namespace WCF_ADDONS\INC\WPML;

defined('ABSPATH') || die();

class WPML_Manager
{

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

	public static function load_integration_files()
	{
		// Load repeatable module class
		include_once(WCF_ADDONS_PATH . 'inc/wpml-module-with-items.php');
	}

	public static function add_widgets_to_translate($widgets)
	{
		//self::load_integration_files();

		$widgets_map = [

			/**
			 * Animated Title Widget
			 */
			'wcf--title' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __('Content: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'link',
						'type'        => esc_html__('TItle: Link', 'animation-addons-for-elementor'),
						'editor_type' => 'LINK',
					],

				],
				// 'fields_in_item' => [
				// 	'title_prefix_list' => [
				// 		[
				// 			'field'       => 'label',
				// 			'type'        => __('Animated Title: Label', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 		[
				// 			'field'       => 'label_on',
				// 			'type'        => __('Animated Title: Label On', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 		[
				// 			'field'       => 'label_off',
				// 			'type'        => __('Animated Title: Label Off', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 	],
				// ],
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
					[
						'field'       => 'btn_sub_text',
						'type'        => __('Button: Sub Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Button: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'btn_link',
						'type'        => __('Button: Link', 'animation-addons-for-elementor'),
						'editor_type' => 'LINK',
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
						'type'        => __('Content: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'subtitle',
						'type'        => __('Content: Sub Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description',
						'type'        => __('Content: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Image Box Slider Widget
			 */
			'wcf--image-box-slider' => [
				'fields_in_item' => [
					'image_box_slider' => [
						[
							'field'       => 'title',
							'type'        => __('Slider Item: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'subtitle',
							'type'        => __('Slider Item: Sub Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __('Slider Item: Description', 'animation-addons-for-elementor'),
							'editor_type' => 'AREA',
						],
						[
							'field'       => 'btn_text',
							'type'        => __('Slider Item: Button Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'description_text',
						'type'        => __('Content: Description', 'animation-addons-for-elementor'),
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
							'type'        => __('Testimonial: Name', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __('Testimonial: Designation', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __('Testimonial: Content', 'animation-addons-for-elementor'),
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
							'type'        => __('Testimonial: Name', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __('Testimonial: Designation', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __('Testimonial: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'AREA',
						],
					],
				],
			],

			/**
			 * Testimonial 3 Widget
			 */
			'wcf--testimonial3' => [

				'fields' => [
					[
						'field'       => 'testimonial_sect_title',
						'type'        => __('Content: Section Title', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
				],

				'fields_in_item' => [
					'testimonials' => [
						[
							'field'       => 'testimonial_name',
							'type'        => __('Testimonial: Name', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_job',
							'type'        => __('Testimonial: Designation', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'testimonial_content',
							'type'        => __('Testimonial: Content', 'animation-addons-for-elementor'),
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
							'field'       => 'tsm_content',
							'type'        => __('Testimonial: Feedback', 'animation-addons-for-elementor'),
							'editor_type' => 'AREA',
						],
						[
							'field'       => 'tsm_reason',
							'type'        => __('Testimonial: Feedback Reason', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tsm_rating',
							'type'        => __('Testimonial: Rating', 'animation-addons-for-elementor'),
							'editor_type' => 'NUMBER',
						],
						[
							'field'       => 'tsm_name',
							'type'        => __('Testimonial: Client Name', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tsm_role',
							'type'        => __('Testimonial: Designation', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
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
						'type'        => __('Content: Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'member_designation',
						'type'        => __('Content: Designation', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'member_description',
						'type'        => __('Content: Description', 'animation-addons-for-elementor'),
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
						[
							'field'       => 'member_description',
							'type'        => __('Team: Description', 'animation-addons-for-elementor'),
							'editor_type' => 'AREA',
						],
					],
				],
			],
			+
			/**
			 * Counter Widget
			 */
			'wcf--counter' => [
				'fields' => [
					[
						'field'       => 'title',
						'type'        => __('Content: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'suffix',
						'type'        => __('Content: Suffix', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'prefix',
						'type'        => __('Content: Prefix', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: Non-Animated Text', 'animation-addons-for-elementor'),
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
			],

			/**
			 * Animated Heading Widget
			 */
			'wcf--animated-heading' => [
				'fields' => [
					[
						'field'       => 'heading',
						'type'        => __('Content: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
				// 'fields_in_item' => [
				// 	'animated_text_list' => [
				// 		[
				// 			'field'       => 'text',
				// 			'type'        => __('Animated Heading: Text', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 	],
				// ],
			],

			/**
			 * Animated Text Widget
			 */
			'wcf--text' => [
				'fields' => [
					[
						'field'       => 'text',
						'type'        => __('Content: Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: Before Hover Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'hover_text',
						'type'        => __('Content: Hover Text', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'after_hover_text',
						'type'        => __('Content: After Hover Text', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
				],
			],

			/**
			 * Timeline Widget
			 */
			'wcf--timeline' => [
				'fields_in_item' => [
					'timelines' => [

						[
							'field'       => 'step_text',
							'type'        => __('Timeline: Step Text', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'timeline_date',
							'type'        => __('Timeline: Date', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'timeline_title',
							'type'        => __('Timeline: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'timeline_sub',
							'type'        => __('Timeline: Sub Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'timeline_desc',
							'type'        => __('Timeline: Content', 'animation-addons-for-elementor'),
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
					'tabs' => [
						[
							'field'       => 'tab_title',
							'type'        => __('Tab: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tab_content',
							'type'        => __('Tab: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
						],
					],
				],
			],

			/**
			 * Services Tab Widget
			 */
			'wcf--services-tab' => [

				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => __('Button: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'fields_in_item' => [
					'tabs' => [
						[
							'field'       => 'tab_number',
							'type'        => __('Service: Tab Number', 'animation-addons-for-elementor'),
							'editor_type' => 'NUMBER',
						],
						[
							'field'       => 'tab_title',
							'type'        => __('Service: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tab_content',
							'type'        => __('Service: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
						],
					],
				],
			],

			/**
			 * Advance Accordion Widget
			 */
			'wcf--a-accordion' => [
				'fields_in_item' => [
					'tabs' => [
						[
							'field'       => 'tab_count',
							'type'        => __('Accordion: Tab Count', 'animation-addons-for-elementor'),
							'editor_type' => 'NUMBER',
						],

						[
							'field'       => 'tab_title',
							'type'        => __('Accordion: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tab_content',
							'type'        => __('Accordion: Content', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
						],
					],
				],
			],

			/**
			 * Image Accordion Widget
			 */
			'wcf--image-accordion' => [

				'fields' => [
					[
						'field'       => 'btn_text',
						'type'        => __('Button: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],

				'fields_in_item' => [
					'accordions' => [
						[
							'field'       => 'title',
							'type'        => __('Accordion: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'subtitle',
							'type'        => __('Accordion: Sub Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __('Accordion: Description', 'animation-addons-for-elementor'),
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
						'field'       => 'countdown_timer_days_label',
						'type'        => __('Content: Label Days', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'countdown_timer_hours_label',
						'type'        => __('Content: Label Hours', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'countdown_timer_minutes_label',
						'type'        => __('Content: Label Minutes', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'countdown_timer_seconds_label',
						'type'        => __('Content: Label Seconds', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],

					[
						'field'       => 'time_expire_title',
						'type'        => __('Time Expire: Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'time_expire_desc',
						'type'        => __('Time Expire: Description', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: Read More Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					// [
					// 	'field'       => 'no_posts_message',
					// 	'type'        => __('Content: No Posts Message', 'animation-addons-for-elementor'),
					// 	'editor_type' => 'LINE',
					// ],
				],
			],

			/**
			 * Feature Posts Widget
			 */
			'wcf--feature-posts' => [
				'fields' => [
					[
						'field'       => 'read_more_text',
						'type'        => __('Content: Read More Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					// [
					// 	'field'       => 'no_posts_message',
					// 	'type'        => __('Content: No Posts Message', 'animation-addons-for-elementor'),
					// 	'editor_type' => 'LINE',
					// ],
				],
			],

			/**
			 * Banner Posts Widget
			 */
			'wcf--banner-posts' => [
				'fields' => [
					[
						'field'       => 'read_more_text',
						'type'        => __('Content: Read More Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'post_by',
						'type'        => __('Author: By', 'animation-addons-for-elementor'),
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
						'field'       => 'prev_title',
						'type'        => __('Content: Previous Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'next_title',
						'type'        => __('Content: Next Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Post Social Share Widget (no fields)
			 */
			'wcf--blog--post--social-share' => [

				'fields_in_item' => [
					'list' => [
						[
							'field'       => 'list_title',
							'type'        => __('Icon: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					],
				],

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
						'type'        => __('Content: Submit Button Text', 'animation-addons-for-elementor'),
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
						'field'       => 'read_more_text',
						'type'        => __('Content: Read More Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

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
						'type'        => __('Content: Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'button_text',
						'type'        => __('Content: Button Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Search: Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: No Results Message', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'search_content',
						'type'        => __('Content: Description', 'animation-addons-for-elementor'),
						'editor_type' => 'VISUAL',
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
						'type'        => __('Author Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'author_bio',
						'type'        => __('Author Biography', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'link_text',
						'type'        => __('Archive Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'contact_title',
						'type'        => __('Contact Title', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'email_label',
						'type'        => __('Email Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'phone_label',
						'type'        => __('Phone Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'social_title',
						'type'        => __('Social Title', 'animation-addons-for-elementor'),
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
					[
						'field'       => 'caption',
						'type'        => esc_html__('Site Logo: Caption', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'link',
						'type'        => esc_html__('Site Logo: Link', 'animation-addons-for-elementor'),
						'editor_type' => 'LINK',
					],
				],
			],

			/**
			 * Current Date Widget
			 */
			'wcf--current-date' => [
				'fields' => [
					[
						'field'       => 'day_separator',
						'type'        => __('Content: Date Separator', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
			],

			/**
			 * Social Icons Widget
			 */
			'wcf--social-icons' => [
				// 'fields_in_item' => [
				// 	'social_icons_list' => [
				// 		[
				// 			'field'       => 'title',
				// 			'type'        => __('Social Icon: Title', 'animation-addons-for-elementor'),
				// 			'editor_type' => 'LINE',
				// 		],
				// 	],
				// ],
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
						// [
						// 	'field'       => 'section_id',
						// 	'type'        => __('One Page Nav: Section ID', 'animation-addons-for-elementor'),
						// 	'editor_type' => 'LINE',
						// ],
					],
				],
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
							'type'        => __('Hotspot: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'tlp_content',
							'type'        => __('Hotspot: Content', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: Before Caption', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'after_caption',
						'type'        => __('Content: After Caption', 'animation-addons-for-elementor'),
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
					'content_slider' => [
						[
							'field'       => 'slide_content',
							'type'        => __('Slider: Description', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
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
						'type'        => __('Carousel Name', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'carousel_items' => [
						[
							'field'       => 'slide_title',
							'type'        => __('Slide Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					],
				],
			],

			/**
			 * Filterable Slider Widget (multiple repeaters)
			 */
			'wcf--filterable-slider' => [
				'fields' => [
					[
						'field'       => 'filter_all_label',
						'type'        => __('Filter: All Label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'filter_items' => [
						[
							'field'       => 'filter_title',
							'type'        => __('Filter: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
					],
					'project_items' => [
						// [
						// 	'field'       => 'project_item_filter_name',
						// 	'type'        => __('Slider: Title', 'animation-addons-for-elementor'),
						// 	'editor_type' => 'Filter name',
						// ],
						[
							'field'       => 'title',
							'type'        => __('Slider: Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'subtitle',
							'type'        => __('Slider: Sub Title', 'animation-addons-for-elementor'),
							'editor_type' => 'LINE',
						],
						[
							'field'       => 'description',
							'type'        => __('Slider: Description', 'animation-addons-for-elementor'),
							'editor_type' => 'VISUAL',
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
						'type'        => __('Content: Placeholder', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'button_text',
						'type'        => __('Content: Button Text', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'success_message',
						'type'        => __('Content: Success Message', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'error_message',
						'type'        => __('Content: Error Message', 'animation-addons-for-elementor'),
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
						'field'       => 'notify_text',
						'type'        => __('Content: Text', 'animation-addons-for-elementor'),
						'editor_type' => 'AREA',
					],
					[
						'field'       => 'btn_text',
						'type'        => __('Content: Button Text', 'animation-addons-for-elementor'),
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
						'type'        => __('Content: Label On', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'label_off',
						'type'        => __('Content: Label Off', 'animation-addons-for-elementor'),
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
						'field'       => 'login_label',
						'type'        => __('Content: Login label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
					[
						'field'       => 'logged_label',
						'type'        => __('Content: Logged label', 'animation-addons-for-elementor'),
						'editor_type' => 'LINE',
					],
				],
				'fields_in_item' => [
					'menus_url' => [
						[
							'field'       => 'menu_title',
							'type'        => __('Content: Menu title', 'animation-addons-for-elementor'),
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

			// if ( isset( $data['integration-class'] ) ) {
			// 	$entry['integration-class'] = $data['integration-class'];
			// }

			$widgets[$widget_name] = $entry;
		}

		return $widgets;
	}
}
