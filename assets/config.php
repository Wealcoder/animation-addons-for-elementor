<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;  // Exit if accessed directly.
}

$config = [
	'widgets'            => [
		'is_active' => false,
		'elements'  => [
			'general-elements'   => [
				'title'     => 'General Widgets',
				'is_active' => false,
				'elements'  => [
					'image-box'            => [
						'label'        => 'Image Box',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'setup'        => [ 'basic' ],
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-box/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-box/',
						'youtube_url'  => '',
					],
					'image-box-slider'     => [
						'label'        => 'Image Box Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Box-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-box-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-box-slider/',
						'youtube_url'  => '',
					],
					'social-icons'         => [
						'label'        => 'Social Icons',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Social-Icons",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-social-icons/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-social-icons/',
						'youtube_url'  => '',
					],
					'image'                => [
						'label'        => 'Image',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image/',
						'youtube_url'  => '',
					],
					'image-gallery'        => [
						'label'        => 'Image Gallery',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Gallery",
						'demo_url'     => 'https://animation-addons.com/widgets/image-gallery-widget/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-gallery/',
						'youtube_url'  => '',
					],
					'text-hover-image'     => [
						'label'        => 'Text Hover Image',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Text-Hover-Image",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-text-hover-image/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-text-hover-image/',
						'youtube_url'  => '',
					],
					'brand-slider'         => [
						'label'        => 'Brand Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Brand-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-brand-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-brand-slider/',
						'youtube_url'  => '',
					],
					'counter'              => [
						'label'        => 'Counter',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Counter",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-counter/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-counter/',
						'youtube_url'  => '',
					],
					'icon-box'             => [
						'label'        => 'Icon Box',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Icon-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-icon-box/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-icon-box/',
						'youtube_url'  => '',
					],
					'testimonial'          => [
						'label'        => 'Testimonial',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-testimonial/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial/',
						'youtube_url'  => '',
					],
					'testimonial2'         => [
						'label'        => 'Classic Testimonial',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial-2",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-classic-testimonial/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-2/',
						'youtube_url'  => '',
					],
					'testimonial3'         => [
						'label'        => 'Modern Testimonial',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial-3",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-modern-testimonial/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-3/',
						'youtube_url'  => '',
					],
					'advanced-testimonial' => [
						'label'        => 'Advanced Testimonial',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Testimonial-3",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-testimonial/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-3/',
						'youtube_url'  => '',
					],
					'button'               => [
						'label'        => 'Button',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Button",
						'demo_url'     => 'https://animation-addons.com/aae-button/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-button/',
						'youtube_url'  => '',
					],
					'button-pro'           => [
						'label'        => 'Advanced Button',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Button-Pro",
						'demo_url'     => 'https://animation-addons.com/aae-advanced-button/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-button-pro/',
						'youtube_url'  => '',
					],
					'image-compare'        => [
						'label'        => 'Image Comparison',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Compare",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-comparison/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-compare/',
						'youtube_url'  => '',
					],
					'progressbar'          => [
						'label'        => 'Progress Bar',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Progress-Bar",
						'demo_url'     => 'https://animation-addons.com/aae-progress-bar/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-progressbar/',
						'youtube_url'  => '',
					],
					'team'                 => [
						'label'        => 'Team',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Team",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-team/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-team/',
						'youtube_url'  => '',
					],
					'notification'         => [
						'label'        => 'Notification',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Notification",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-notification/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-notification/',
						'youtube_url'  => '',
					],
					'one-page-nav'         => [
						'label'        => 'One Page Nav',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-One-Page-Nav",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-one-page-nav/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-one-page-nav/',
						'youtube_url'  => '',
					],
					'timeline'             => [
						'label'        => 'Timeline',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Timeline",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-timeline',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-timeline/',
						'youtube_url'  => '',
					],
					'tabs'                 => [
						'label'        => 'Tabs',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Tabs",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-tabs/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-tabs/',
						'youtube_url'  => '',
					],
					'services-tab'         => [
						'label'        => 'Services Tabs',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Services-Tabs",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-services-tabs',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-services-tab/',
						'youtube_url'  => '',
					],
					'floating-elements'    => [
						'label'        => 'Floating Elements',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Floating-Elements",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-floating-elements/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-floating-elements/',
						'youtube_url'  => '',
					],
					'event-slider'         => [
						'label'        => 'Event Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Event-Slider",
						'demo_url'     => 'https://animation-addons.com/aae-event-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-event-slider/',
						'youtube_url'  => '',
					],
					'content-slider'       => [
						'label'        => 'Content Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Content-Slider",
						'demo_url'     => 'https://animation-addons.com/aae-content-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-content-slider/',
						'youtube_url'  => '',
					],
					'countdown'            => [
						'label'        => 'Countdown',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Countdown",
						'demo_url'     => 'https://animation-addons.com/aae-countdown/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-countdown/',
						'youtube_url'  => '',
					],
				]
			],
			'animation-elements' => [
				'title'     => 'Animations',
				'is_active' => false,
				'elements'  => [
					'typewriter'       => [
						'label'        => 'Typewriter',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Typewriter",
						'demo_url'     => 'https://animation-addons.com/aae-typewriter/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-typewriter/',
						'youtube_url'  => '',
					],
					'animated-heading' => [
						'label'        => 'Animated Heading',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Heading",
						'demo_url'     => 'https://animation-addons.com/aae-animated-heading/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-heading/',
						'youtube_url'  => '',
					],
					'animated-title'   => [
						'label'        => 'Animated Title',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Title",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-animated-title/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-title/',
						'youtube_url'  => '',
					],
					'animated-text'    => [
						'label'        => 'Animated Text',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Animated-Text",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-animated-text/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-text/',
						'youtube_url'  => '',
					],
					'lottie'           => [
						'label'        => 'Lottie',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Lottie",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-lottie/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-text/',
						'youtube_url'  => '',
					],
					'draw-svg'         => [
						'label'        => 'GSAP DrawSvg',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-GSAP-DrawSvg",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-gsap-drawsvg/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/draw-svg/',
						'youtube_url'  => '',
					],
				]
			],
			'hf-elements'        => [
				'title'     => 'Header & Footer Widgets',
				'is_active' => false,
				'elements'  => [
					'animated-offcanvas' => [
						'label'        => 'Animated Off-Canvas',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => '',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Animated-Off-Canvas",
						'doc_url'      => 'https://animation-addons.com/widgets/aae-animated-off-canvas/',
						'youtube_url'  => '',
					],
					'site-logo'          => [
						'label'        => 'Site Logo',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Site-Logo",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-site-logo/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'nav-menu'           => [
						'label'        => 'Nav Menu',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Nav-Menu",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-nav-menu/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			],
			'slider'             => [
				'title'     => 'Slider',
				'is_active' => false,
				'elements'  => [
					'posts-slider'         => [
						'label'        => 'Posts Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts-slider/',
						'youtube_url'  => '',
					],
					'breaking-news-slider' => [
						'label'        => 'Breaking News Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Brand-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-breaking-news-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/breaking-news-slider',
						'youtube_url'  => '',
					],
					'category-slider'      => [
						'label'        => 'Category Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Content-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-category-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/category-slider/',
						'youtube_url'  => '',
					],
					'video-box-slider'     => [
						'label'        => 'Video Box Slider',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Box-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-box-slider/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-box-slider/',
						'youtube_url'  => '',
					],
					'filterable-slider'    => [
						'label'        => 'Filterable Slider',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Filterable-Slider",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-filterable-slider/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			],
			'dynamic-elements'   => [
				'title'     => 'Dynamic Widgets',
				'is_active' => false,
				'elements'  => [
					'post-title'         => [
						'label'        => 'Post Title',
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
						'label'        => 'Post Featured Image',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Featured-Image",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-excerpt'       => [
						'label'        => 'Post Excerpt',
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
						'label'        => 'Post Content',
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
						'label'        => 'Post Comments',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Comments",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-reactions'     => [
						'label'        => 'Post Reactions',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Content",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'post-meta-info'     => [
						'label'        => 'Post Meta Info',
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
						'label'        => 'Post Pagination',
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
						'label'        => 'Social Share',
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
						'label'        => 'Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts/',
						'youtube_url'  => '',
					],
					'posts-pro'          => [
						'label'        => 'Advanced Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts-Pro",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-posts/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts-pro/',
						'youtube_url'  => '',
					],
					'video-story'        => [
						'label'        => 'Video Story',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-story/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/aae-video-story/',
						'youtube_url'  => '',
					],
					'video-posts-tab'    => [
						'label'        => 'Video Posts Tab',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Posts-Tab",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts-tabs/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts/',
						'youtube_url'  => '',
					],
					'posts-filter'       => [
						'label'        => 'Filterable Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Filterable-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-filterable-posts/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-posts-filter/',
						'youtube_url'  => '',
					],
					'post-rating-form'   => [
						'label'        => 'Post Rating Form',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Rating",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts-rating-form/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/post-ratting/',
						'youtube_url'  => '',
					],
					'post-rating'        => [
						'label'        => 'Post Rating',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Post-Rating",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/post-ratting/',
						'youtube_url'  => '',
					],

					'grid-hover-posts'  => [
						'label'        => 'Grid Hover Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Grid-Hover-Posts",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-banner-posts/',
						'youtube_url'  => '',
					],
					'category-showcase' => [
						'label'        => 'Category Showcase',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Category-Showcase",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-category-showcase/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-banner-posts/',
						'youtube_url'  => '',
					],
					'banner-posts'      => [
						'label'        => 'Banner Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Banner-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-banner-posts/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-banner-posts/',
						'youtube_url'  => '',
					],
					'current-date'      => [
						'label'        => 'Current Date',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Current-Date",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-current-date/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-current-date/',
						'youtube_url'  => '',
					],
					'feature-posts'     => [
						'label'        => 'Featured Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Featured-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-featured-posts/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-feature-posts/',
						'youtube_url'  => '',
					],
					'archive-title'     => [
						'label'        => 'Archive Title',
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
					'portfolio'         => [
						'label'        => 'Portfolio',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Portfolio",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-portfolio/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-portfolio/',
						'youtube_url'  => '',
					],
					'search-form'       => [
						'label'        => 'Search Form',
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
					'search-query'      => [
						'label'        => 'Search Query',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-Query",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-search-query/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'search-no-result'  => [
						'label'        => 'Search No Result',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-No-Result",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-search-no-result/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
				]
			],
			'form-elements'      => [
				'title'     => 'Form Widgets',
				'is_active' => false,
				'elements'  => [
					'contact-form-7'     => [
						'label'        => 'Contact Form 7',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Contact-Form-7",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-contact-form-7/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-contact-form-7/',
						'youtube_url'  => '',
					],
					'mailchimp'          => [
						'label'        => 'Mailchimp',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Mailchimp",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-mailchimp/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-mailchimp/',
						'youtube_url'  => '',
					],
					'advanced-mailchimp' => [
						'label'        => 'Advanced Mailchimp',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Mailchimp",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-mailchimp/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-mailchimp/',
						'youtube_url'  => '',
					],
				]
			],
			'video-elements'     => [
				'title'     => 'Video Widgets',
				'is_active' => false,
				'elements'  => [
					'video-popup' => [
						'label'        => 'Video Popup',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Popup",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-popup/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-popup/',
						'youtube_url'  => '',
					],
					'video-box'   => [
						'label'        => 'Video Box',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-box/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-box/',
						'youtube_url'  => '',
					],
					'video-mask'  => [
						'label'        => 'Video Mask',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Mask",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-mask/',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-video-mask/',
						'youtube_url'  => '',
					],
				]
			],
			'advanced-elements'  => [
				'title'     => 'Advanced Widgets',
				'is_active' => false,
				'elements'  => [

					'toggle-switcher'       => [
						'label'        => 'Toggle Switch',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Toggle-Switch",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-toggle-switch/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advance-pricing-table' => [
						'label'        => 'Advanced Pricing Table',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Pricing-Table",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-pricing-table/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'scroll-elements'       => [
						'label'        => 'Scroll Elements',
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
						'label'        => 'Advanced Portfolio',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Portfolio",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-portfolio/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'filterable-gallery'    => [
						'label'        => 'Filterable Gallery',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Filterable-Gallery",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-filterable-gallery/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'breadcrumbs'           => [
						'label'        => 'Breadcrumbs',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Breadcrumbs",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-breadcrumbs/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'table-of-contents'     => [
						'label'        => 'Table Of Content',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Table-Of-Content",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-table-of-content/',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'image-accordion'       => [
						'label'        => 'Image Accordion',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-accordion/',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Image-Accordion",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'author-box'            => [
						'label'        => 'Author Box',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => 'https://animation-addons.com/widgets/aae-author-box/',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Author-Box",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'flip-box'              => [
						'label'        => 'Flip Box',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => 'https://animation-addons.com/widgets/aae-flip-box/',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Flip-Box",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advance-accordion'     => [
						'label'        => 'Advanced Accordion',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-accordion/',
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Accordion",
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
				'title'     => 'General Extensions',
				'is_active' => false,
				'elements'  => [
					'custom-css'       => [
						'label'        => 'Custom CSS',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => false,
						'is_active'    => false,
						'setup'        => [ 'basic' ],
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Custom-CSS",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'youtube_url'  => '',
					],
					'dynamic-tags'     => [
						'label'        => 'Dynamic Tags',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Dynamic-Tags",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/dynamic-tags/',
						'youtube_url'  => '',
					],
					'template-library' => [
						'label'        => 'Template library',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => false,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Template-library",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/dynamic-tags/',
						'youtube_url'  => '',
					],
					'wrapper-link'     => [
						'label'        => 'Wrapper Link',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Wrapper-Link",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'popup'            => [
						'label'        => 'Popup',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => false,
						'is_active'    => false,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Popup",
						'demo_url'     => '',
						'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-popup/',
						'youtube_url'  => '',
					],
					'tilt-effect'      => [
						'label'        => 'Tilt Effect',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Tilt-Effect",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'advanced-tooltip' => [
						'label'        => 'Advanced Tooltip',
						'location'     => [
							'cTab' => 'general'
						],
						'is_pro'       => true,
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Advanced-Tooltip",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'custom-fonts'     => [
						'label'        => 'Custom Fonts',
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'is_extension' => false,
						'is_active'    => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'icon'         => "wcf-icon-Custom-Fonts",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'custom-cpt'       => [
						'label'        => 'Post Type Builder',
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'is_extension' => false,
						'is_active'    => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'icon'         => "wcf-icon-Custom-Post-Type",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'custom-icon'      => [
						'label'        => 'Custom Icon',
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'is_extension' => false,
						'is_active'    => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'icon'         => "wcf-icon-Custom-Icons",
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'mega-menu'        => [
						'label'        => 'Mega Menu',
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'icon'         => "wcf-icon-Mega-Menu",
						'is_active'    => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
					],
					'restrict-content' => [
						'label'        => 'Content Protection',
						'is_pro'       => true,
						'location'     => [
							'cTab' => 'general'
						],
						'icon'         => "wcf-icon-Content-Protection",
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
				'title'     => 'GSAP Extensions',
				'is_active' => false,
				'elements'  => [
					'wcf-smooth-scroller' => [
						'title'     => 'Scroll Smoother',
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'animation-effects'       => [
								'label'        => 'Animation',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Animation",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-animation/',
								'youtube_url'  => '',
							],
							'pin-element'             => [
								'label'        => 'Pin Elements',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Pin-Elements",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/pin-element/',
								'youtube_url'  => '',
							],
							'text-animation-effects'  => [
								'label'        => 'Text Animation',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Text-Animation",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/animation/animation/text-animation/',
								'youtube_url'  => '',
							],
							'image-animation-effects' => [
								'label'        => 'Image Animation',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'wcf-smooth-scroller'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Image-Animation",
								'demo_url'     => '',
								'doc_url'      => 'https://support.crowdytheme.com/docs/animation/animation/image-animation/',
								'youtube_url'  => '',
							],
						]
					],
					'effect'              => [
						'title'     => 'Effects',
						'doc_url'   => '#',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'cursor-hover-effect' => [
								'label'        => 'Cursor Hover Effect',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'effect'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Cursor-Hover-Effect",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
							'hover-effect-image'  => [
								'label'        => 'Image Hover Effect',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'effect'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Image-Hover-Effect",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
							'cursor-move-effect'  => [
								'label'        => 'Cursor Move Effect',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'effect'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Cursor-Move-Effect",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
						]
					],
					'scroll-trigger'      => [
						'title'     => 'ScrollTrigger',
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'horizontal-scroll' => [
								'label'        => 'Horizontal',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'scroll-trigger'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Horizontal",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],
						]
					],
					'draw-svg'            => [
						'title'     => 'DrawSVG',
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => []
					],
					'flip'                => [
						'title'     => 'Flips',
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'portfolio-filter' => [
								'label'        => 'Portfolio Filter',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'flip'
								],
								'is_pro'       => true,
								'is_active'    => true,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Portfolio-Filter",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],

							'gallery-filter' => [
								'label'        => 'Gallery Filter',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'flip'
								],
								'is_pro'       => true,
								'is_active'    => true,
								'is_extension' => true,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Gallery-Filter",
								'demo_url'     => '',
								'doc_url'      => '',
								'youtube_url'  => '',
							],

						]
					],
					'gsap-builder'        => [
						'title'     => 'Builders',
						'doc_url'   => 'https://support.crowdytheme.com/docs/advanced-settings/advanced-settings/wcf-custom-css/',
						'is_pro'    => true,
						'is_active' => false,
						'elements'  => [
							'animation-builder' => [
								'label'        => 'Animation Builder',
								'location'     => [
									'cTab'     => 'gsap',
									'pluginId' => 'gsap-builder'
								],
								'is_pro'       => true,
								'is_active'    => false,
								'is_extension' => false,
								'is_upcoming'  => false,
								'icon'         => "wcf-icon-Animation-Builder",
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
			'title'    => 'Plugins',
			'elements' => [
				'animation-addon-for-elementorpro' => [
					'label'        => 'Animation Addon Pro',
					'basename'     => 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php',
					'source'       => 'custom',
					'is_pro'       => true,
					'slug'         => '',
					'download_url' => "",
				],
			]
		],
		'library' => [
			'title'    => 'Library',
			'elements' => [
				'gsap-library' => [
					'title'     => 'GSAP Library',
					'is_pro'    => true,
					'is_active' => false,
					'elements'  => [
						'Draggable'          => [
							'label'     => 'Draggable',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/Draggable',
						],
						'easel'              => [
							'label'     => 'Easel',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/EaselPlugin',
						],
						'flip'               => [
							'label'     => 'Flip',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/Flip',
						],
						'motion-path'        => [
							'label'     => 'MotionPath',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/MotionPathPlugin',
						],
						'observer'           => [
							'label'     => 'Observer',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/Observer',
						],
						'pixi'               => [
							'label'     => 'Pixi',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/PixiPlugin',
						],
						'scroll-to'          => [
							'label'     => 'ScrollTo',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/ScrollToPlugin',
						],
						'scroll-trigger'     => [
							'label'     => 'ScrollTrigger',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/ScrollTrigger/?page=1',
						],
						'text'               => [
							'label'     => 'Text',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/TextPlugin',
						],
						'draw-svg'           => [
							'label'     => 'DrawSVG',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/DrawSVGPlugin',
						],
						'physics-2d'         => [
							'label'     => 'Physics2D',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/Physics2DPlugin',
						],
						'physics-props'      => [
							'label'     => 'PhysicsProps',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/PhysicsPropsPlugin',
						],
						'scramble-text'      => [
							'label'     => 'ScrambleText',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/ScrambleTextPlugin',
						],
						'gs-dev-tools'       => [
							'label'     => 'GSDevTools',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/GSDevTools',
						],
						'inertia'            => [
							'label'     => 'Inertia',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/InertiaPlugin',
						],
						'morph-svg'          => [
							'label'     => 'MorphSVG',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/MorphSVGPlugin',
						],
						'motion-path-helper' => [
							'label'     => 'MotionPathHelper',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/MotionPathHelper',
						],
						'scroll-smoother'    => [
							'label'     => 'ScrollSmoother',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/ScrollSmoother',
						],
						'split-text'         => [
							'label'     => 'SplitText',
							'is_pro'    => true,
							'is_active' => false,
							'icon'      => "wcf-icon-Animation-Builder",
							'doc_url'   => 'https://gsap.com/docs/v3/Plugins/SplitText',
						],
					]
				],
			]
		]
	],
	'dashboardProWidget' => [
		'advance-portfolio'  => [
			'label'        => 'Advanced Portfolio',
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
			'label'        => 'Filterable Gallery',
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
			'label'        => 'Breadcrumbs',
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
			'label'        => 'Table Of Content',
			'is_active'    => false,
			'location'     => [
				'cTab' => 'all'
			],
			'is_upcoming'  => false,
			'is_pro'       => true,
			'is_extension' => false,
			'icon'         => "wcf-icon-Table-Of-Content",
			'demo_url'     => '',
			'doc_url'      => '',
			'youtube_url'  => '',
		],
		'image-accordion'    => [
			'label'        => 'Image Accordion',
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
			'label'        => 'Author Box',
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