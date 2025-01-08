<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;  // Exit if accessed directly.
}

$config = [
	'widgets'            => [
		'is_active' => false,
		'elements'  => [
			'general-elements'   => [
				'title'     => esc_html__( 'General Widgets', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'image-box'        => [
						'label'        => esc_html__( 'Image Box', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Box",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-box/',
						'youtube_url'  => '',
					],
					'image-box-slider' => [
						'label'        => esc_html__( 'Image Box Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Box-Slider",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-box-slider/',
						'youtube_url'  => '',
					],

					'social-icons'      => [
						'label'        => esc_html__( 'Social Icons', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Social-Icons",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-social-icons/',
						'youtube_url'  => '',
					],
					'image'             => [
						'label'        => esc_html__( 'Image', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image/',
						'youtube_url'  => '',
					],
					'image-gallery'     => [
						'label'        => esc_html__( 'Image Gallery', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Gallery",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-gallery/',
						'youtube_url'  => '',
					],
					'text-hover-image'  => [
						'label'        => esc_html__( 'Text Hover Image', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Text-Hover-Image",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-text-hover-image/',
						'youtube_url'  => '',
					],
					'brand-slider'      => [
						'label'        => esc_html__( 'Brand Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Brand-Slider",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-brand-slider/',
						'youtube_url'  => '',
					],
					'counter'           => [
						'label'        => esc_html__( 'Counter', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Counter",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-counter/',
						'youtube_url'  => '',
					],
					'icon-box'          => [
						'label'        => esc_html__( 'Icon Box', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Icon-Box",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-icon-box/',
						'youtube_url'  => '',
					],
					'testimonial'       => [
						'label'        => esc_html__( 'Testimonial', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial/',
						'youtube_url'  => '',
					],
					'testimonial2'      => [
						'label'        => esc_html__( 'Testimonial 2', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial-2",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-2/',
						'youtube_url'  => '',
					],
					'testimonial3'      => [
						'label'        => esc_html__( 'Testimonial 3', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial-3",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-3/',
						'youtube_url'  => '',
					],

					'testimonial-pro'      => [
						'label'        => esc_html__( 'Testimonial Pro', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-pro/',
						'youtube_url'  => '',
					],
					'button'            => [
						'label'        => esc_html__( 'Button', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Button",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-button/',
						'youtube_url'  => '',
					],

					'button-pro'            => [
						'label'        => esc_html__( 'Button Pro', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Button",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-button-pro/',
						'youtube_url'  => '',
					],

					'image-compare'     => [
						'label'        => esc_html__( 'Image Compare', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Compare",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-compare/',
						'youtube_url'  => '',
					],
					'progressbar'       => [
						'label'        => esc_html__( 'Progressbar', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Progressbar",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-progressbar/',
						'youtube_url'  => '',
					],
					'team'              => [
						'label'        => esc_html__( 'Team', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Team",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-team/',
						'youtube_url'  => '',
					],
					'team-slider'              => [
						'label'        => esc_html__( 'Team Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Team",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-team-slider/',
						'youtube_url'  => '',
					],
					'notification'     => [
						'label'        => esc_html__( 'Notification', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Team",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-notification/',
						'youtube_url'  => '',
					],
					'one-page-nav'      => [
						'label'        => esc_html__( 'One Page Nav', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-One-Page-Nav",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-one-page-nav/',
						'youtube_url'  => '',
					],
					'timeline'          => [
						'label'        => esc_html__( 'Timeline', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Timeline",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-timeline/',
						'youtube_url'  => '',
					],
					'tabs'              => [
						'label'        => esc_html__( 'Tabs', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Tabs",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-tabs/',
						'youtube_url'  => '',
					],
					'services-tab'      => [
						'label'        => esc_html__( 'Services Tabs', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Services-Tabs",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-services-tab/',
						'youtube_url'  => '',
					],
					'floating-elements' => [
						'label'        => esc_html__( 'Floating Elements', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-floating-elements/',
						'youtube_url'  => '',
					],
					'event-slider'      => [
						'label'        => esc_html__( 'Event Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Event-Slider",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-event-slider/',
						'youtube_url'  => '',
					],
					'content-slider'    => [
						'label'        => esc_html__( 'Content Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Content-Slider",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-content-slider/',
						'youtube_url'  => '',
					],
					'countdown'         => [
						'label'        => esc_html__( 'Countdown', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Countdown",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-countdown/',
						'youtube_url'  => '',
					],
					'animated-heading'  => [
						'label'        => esc_html__( 'Animated Heading', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Heading",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-heading/',
						'youtube_url'  => '',
					],
				]
			],
			'animation-elements' => [
				'title'     => esc_html__( 'Animations', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'typewriter'     => [
						'label'        => esc_html__( 'Typewriter', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Typewriter",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-typewriter/',
						'youtube_url'  => '',
					],
					'animated-title' => [
						'label'        => esc_html__( 'Animated Title', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Title",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-title/',
						'youtube_url'  => '',
					],
					'animated-text'  => [
						'label'        => esc_html__( 'Animated Text', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Text",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-text/',
						'youtube_url'  => '',
					],
					'lottie'  => [
						'label'        => esc_html__( 'Lottie', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Text",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-text/',
						'youtube_url'  => '',
					],
					'draw-svg'  => [
						'label'        => esc_html__( 'DrawSvg', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Text",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/draw-svg/',
						'youtube_url'  => '',
					],
				]
			],
			'hf-elements'        => [
				'title'     => esc_html__( 'Header/Footer Widgets', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'animated-offcanvas' => [
						'label'        => __( 'Animated Offcanvas', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => '',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Author-Box",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'site-logo'          => [
						'label'        => esc_html__( 'Site Logo', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Site-Logo",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'nav-menu'           => [
						'label'        => esc_html__( 'Nav Menu', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Nav-Menu",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			],
			'dynamic-elements'   => [
				'title'     => esc_html__( 'Dynamic Widgets', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'post-title'         => [
						'label'        => esc_html__( 'Post Title', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Title",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-feature-image' => [
						'label'        => esc_html__( 'Post Feature Image', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Feature-Image",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-excerpt'       => [
						'label'        => esc_html__( 'Post Excerpt', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Excerpt",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-content'       => [
						'label'        => esc_html__( 'Post Content', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Content",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-comment'       => [
						'label'        => esc_html__( 'Post Comment', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Comment",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-meta-info'     => [
						'label'        => esc_html__( 'Post Meta Info', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Meta-Info",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-paginate'      => [
						'label'        => esc_html__( 'Post Paginate', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Paginate",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-social-share'  => [
						'label'        => esc_html__( 'Post Social Share', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Social-Share",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'posts'              => [
						'label'        => esc_html__( 'Posts', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts/',
						'youtube_url'  => '',
					],

					'posts-pro'              => [
						'label'        => esc_html__( 'Posts Pro', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts-pro/',
						'youtube_url'  => '',
					],

					'posts-slider'              => [
						'label'        => esc_html__( 'Posts Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts-slider/',
						'youtube_url'  => '',
					],

					'banner-posts'              => [
						'label'        => esc_html__( 'Banner Posts', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-banner-posts/',
						'youtube_url'  => '',
					],

					'current-date'              => [
						'label'        => esc_html__( 'Current Date', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-current-date/',
						'youtube_url'  => '',
					],

					'feature-posts'              => [
						'label'        => esc_html__( 'Feature Posts', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-feature-posts/',
						'youtube_url'  => '',
					],

					'archive-title'      => [
						'label'        => esc_html__( 'Archive Title', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Archive-Title",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'portfolio'          => [
						'label'        => esc_html__( 'Portfolio', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Portfolio",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-portfolio/',
						'youtube_url'  => '',
					],
					'search-form'        => [
						'label'        => esc_html__( 'Search Form', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-Form",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'search-query'       => [
						'label'        => esc_html__( 'Search Query', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-Query",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'search-no-result'   => [
						'label'        => esc_html__( 'Search No Result', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-No-Result",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			],
			'form-elements'      => [
				'title'     => esc_html__( 'Form Widgets', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'contact-form-7' => [
						'label'        => esc_html__( 'Contact Form 7', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Contact-Form-7",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-contact-form-7/',
						'youtube_url'  => '',
					],
					'mailchimp'      => [
						'label'        => esc_html__( 'Mailchimp', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Mailchimp",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-mailchimp/',
						'youtube_url'  => '',
					],
				]
			],
			'video-elements'     => [
				'title'     => esc_html__( 'Video Widgets', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'video-popup'      => [
						'label'        => esc_html__( 'Video Popup', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Popup",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-popup/',
						'youtube_url'  => '',
					],
					'video-box'        => [
						'label'        => esc_html__( 'Video Box', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Box",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-box/',
						'youtube_url'  => '',
					],
					'video-box-slider' => [
						'label'        => esc_html__( 'Video Box Slider', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Box-Slider",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-box-slider/',
						'youtube_url'  => '',
					],
					'video-mask'       => [
						'label'        => esc_html__( 'Video Mask', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Mask",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-mask/',
						'youtube_url'  => '',
					],
				]
			],
			'advanced-elements'  => [
				'title'     => esc_html__( 'Advanced', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [

					'toggle-switcher'       => [
						'label'        => __( 'Toggle Switcher', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Toggle-Switch",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advance-pricing-table' => [
						'label'        => __( 'Advance Pricing Table', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advance-Pricing-Table",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'scroll-elements'       => [
						'label'        => __( 'Scroll Elements', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Scroll-Elements",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advance-portfolio'     => [
						'label'        => __( 'Advance Portfolio', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Portfolio",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'filterable-gallery'    => [
						'label'        => __( 'Filterable Gallery', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Filterable-Gallery",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'breadcrumbs'           => [
						'label'        => __( 'Breadcrumbs', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Breadcrumbs",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'table-of-contents'     => [
						'label'        => __( 'Table Of Content', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Table-of-Contents",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'image-accordion'       => [
						'label'        => __( 'Image Accordion', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => '',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Image-Accordion",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'author-box'            => [
						'label'        => __( 'Author Box', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => '',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Author-Box",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'flip-box'              => [
						'label'        => __( 'Flip Box', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => '',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Flip-Box",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advance-slider'        => [
						'label'        => __( 'Advance Slider', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => true,
						'icon'         => "wcf-icon-Advance-Slider",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'filterable-slider'     => [
						'label'        => __( 'Filterable Slider', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Filterable-Slider",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advance-accordion'     => [
						'label'        => __( 'Advance Accordion', 'animation-addons-for-elementor' ),
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => '',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advance-Accordion",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			]
		]
	],
	'extensions'         => [
		'is_active' => false,
		'elements'  => [
			'general-extensions' => [
				'title'     => esc_html__( 'General Extension', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'custom-css'       => [
						'label'        => esc_html__( 'Custom CSS', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => false,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'youtube_url'  => '',
					],
					'wrapper-link'     => [
						'label'        => esc_html__( 'Wrapper Link', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'popup'            => [
						'label'        => esc_html__( 'Popup', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => false,
						'is_active'    => false,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-popup/',
						'youtube_url'  => '',
					],
					'tilt-effect'      => [
						'label'        => esc_html__( 'Tilt Effect', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advanced-tooltip' => [
						'label'        => esc_html__( 'Advanced Tooltip', 'animation-addons-for-elementor' ),
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'custom-fonts'     => [
						'label'        => esc_html__( 'Custom Fonts', 'animation-addons-for-elementor' ),
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'is_extension' => false,
						'is_active'    => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'icon'         => "wcf-icon-Floating-Elements",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'custom-cpt'       => [
						'label'        => esc_html__( 'Custom Post Type', 'animation-addons-for-elementor' ),
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'is_extension' => false,
						'is_active'    => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'icon'         => "wcf-icon-Floating-Elements",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'mega-menu'        => [
						'label'        => esc_html__( 'Mega Menu', 'animation-addons-for-elementor' ),
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'icon'         => "wcf-icon-Floating-Elements",
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			],
			'gsap-extensions'    => [
				'title'     => esc_html__( 'Gsap Extension', 'animation-addons-for-elementor' ),
				'is_active' => false,
				'elements'  => [
					'wcf-smooth-scroller' => [
						'title'     => esc_html__( 'Smooth Scroller', 'animation-addons-for-elementor' ),
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => false,
						'is_active' => false,
						'elements'  => [
							'animation-effects'       => [
								'label'        => esc_html__( 'Animation', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-animation/',
								'youtube_url'  => '',
							],
							'pin-element'             => [
								'label'        => esc_html__( 'Pin Element', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/pin-element/',
								'youtube_url'  => '',
							],
							'text-animation-effects'  => [
								'label'        => esc_html__( 'Text Animation', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/animation/animation/text-animation/',
								'youtube_url'  => '',
							],
							'image-animation-effects' => [
								'label'        => esc_html__( 'Image Animation', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/animation/animation/image-animation/',
								'youtube_url'  => '',
							],
						]
					],
					'effect'              => [
						'title'     => esc_html__( 'Effect', 'animation-addons-for-elementor' ),
						'doc_url'   => '#',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'cursor-hover-effect' => [
								'label'        => esc_html__( 'Cursor Hover Effect', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'effect'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
							'hover-effect-image'  => [
								'label'        => esc_html__( 'Hover Effect Image', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'effect'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
							'cursor-move-effect'  => [
								'label'        => esc_html__( 'Cursor Move Effect', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'effect'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
						]
					],
					'scroll-trigger'      => [
						'title'     => esc_html__( 'ScrollTrigger', 'animation-addons-for-elementor' ),
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'horizontal-scroll' => [
								'label'        => esc_html__( 'Horizontal', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'scroll-trigger'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
						]
					],
					'draw-svg'            => [
						'title'     => esc_html__( 'DrawSVG', 'animation-addons-for-elementor' ),
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => []
					],
					'flip'                => [
						'title'     => esc_html__( 'Flip', 'animation-addons-for-elementor' ),
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => false,
						'is_active' => false,
						'elements'  => [
							'portfolio-filter' => [
								'label'        => esc_html__( 'Portfolio Filter', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'flip'
								],
								'is_pro'       => true,
								'is_active'    => true,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],


						]
					],
					'gsap-builder'        => [
						'title'     => esc_html__( 'Builder', 'animation-addons-for-elementor' ),
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => false,
						'is_active' => false,
						'elements'  => [
							'animation-builder' => [
								'label'        => esc_html__( 'Animation Builder', 'animation-addons-for-elementor' ),
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'gsap-builder'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Floating-Elements",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
						]
					],
				]
			],
		]
	],
	'integrations'       => [
		'plugins' => [
			'title'    => esc_html__( 'Plugins', 'animation-addons-for-elementor' ),
			'elements' => [
//				'extension-for-animation-addons'   => [
//					'label'        => esc_html__( 'Extension For Animation Addon', 'animation-addons-for-elementor' ),
//					'basename'     => 'extension-for-animation-addons/extension-for-animation-addons.php',
//					'source'       => 'custom',
//					'is_pro'       => false,
//					'slug'         => '',
//					'download_url' => 'https://animation-addons.com/',
//				],
				'animation-addon-for-elementorpro' => [
					'label'        => esc_html__( 'Animation Addon Pro', 'animation-addons-for-elementor' ),
					'basename'     => 'wcf-addons-pro/wcf-addons-pro.php',
					'source'       => 'custom',
					'is_pro'       => true,
					'slug'         => '',
					'download_url' => "",
				],
			]
		],
	],
	'dashboardProWidget' => [
		'advance-portfolio'  => [
			'label'        => __( 'Advance Portfolio', 'animation-addons-for-elementor' ),
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Advanced-Portfolio",
			'demo_url'     => '',
			'doc_url'      => '',
			'youtube_url'  => '',
		],
		'filterable-gallery' => [
			'label'        => __( 'Filterable Gallery', 'animation-addons-for-elementor' ),
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Filterable-Gallery",
			'demo_url'     => '',
			'doc_url'      => '',
			'youtube_url'  => '',
		],
		'breadcrumbs'        => [
			'label'        => __( 'Breadcrumbs', 'animation-addons-for-elementor' ),
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Breadcrumbs",
			'demo_url'     => '',
			'doc_url'      => '',
			'youtube_url'  => '',
		],
		'table-of-contents'  => [
			'label'        => __( 'Table Of Content', 'animation-addons-for-elementor' ),
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Table-of-Contents",
			'demo_url'     => '',
			'doc_url'      => '',
			'youtube_url'  => '',
		],
		'image-accordion'    => [
			'label'        => __( 'Image Accordion', 'animation-addons-for-elementor' ),
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'demo_url'     => '',
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Image-Accordion",
			'doc_url'      => '',
			'youtube_url'  => '',
		],
		'author-box'         => [
			'label'        => __( 'Author Box', 'animation-addons-for-elementor' ),
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'demo_url'     => '',
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Author-Box",
			'doc_url'      => '',
			'youtube_url'  => '',
		],

	]
];

$GLOBALS['wcf_addons_config'] = $config;
