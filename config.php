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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-box',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/image-box/',
						'youtube_url'  => '',
						'description'  => 'Create graceful image sections with animations, refined content placement, and thoughtful design.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-box-slider',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/image-box-slider/',
						'youtube_url'  => '',
						'description'  => 'Craft graceful image sliders with custom styles, animations, and smooth transitions for a polished presentation.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-social-icons',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/social-icons/',
						'youtube_url'  => '',
						'description'  => 'Link your social accounts and customize the look to perfectly match your branding.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image',
						'doc_url'      => 'https://animation-addons.com/docs/docs/general/image-widget/',
						'youtube_url'  => '',
						'description'  => 'Use this widget to display and animate images with precise control and styling options.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/image-gallery-widget',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-image-gallery/',
						'youtube_url'  => '',
						'description'  => 'Create an engaging image gallery that fits perfectly with your site’s design and style.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-text-hover-image',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/text-hover-image/',
						'youtube_url'  => '',
						'description'  => 'Hover over text and reveal images for an interactive, engaging visual surprise!'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-brand-slider',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/brand-slider/',
						'youtube_url'  => '',
						'description'  => 'Boost credibility and visibility by highlighting trusted logos with a smooth, auto-scrolling slider.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-counter',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/counter/',
						'youtube_url'  => '',
						'description'  => 'Impress visitors with live stats and milestones using fully animated number counters.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-icon-box',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/icon-box/',
						'youtube_url'  => '',
						'description'  => 'Refine your content presentation with flexible icon styling, typography, and layout options.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-testimonial',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/testimonial/',
						'youtube_url'  => '',
						'description'  => 'Share real stories and success quotes using animated testimonial sliders and flexible content settings.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-classic-testimonial',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/classic-testimonial/',
						'youtube_url'  => '',
						'description'  => 'Showcase real stories and client praise with a sleek, responsive testimonial carousel.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-modern-testimonial',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/modern-testimonial/',
						'youtube_url'  => '',
						'description'  => 'Highlight customer feedback with modern layouts, smooth sliders, and customizable design elements.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-testimonial',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-testimonial-3/',
						'youtube_url'  => '',
						'description'  => 'Create a stunning, responsive testimonial section with smooth transitions and customization options.'
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
						'demo_url'     => 'https://animation-addons.com/aae-button',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/button/',
						'youtube_url'  => '',
						'description'  => 'Create bold, clickable buttons with text, icons, alignment, and hover animation options.'
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
						'demo_url'     => 'https://animation-addons.com/aae-advanced-button',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/advanced-button/',
						'youtube_url'  => '',
						'description'  => 'Advanced Button lets you tweak padding, border radius, typography, and interactive hover styles'
					],
					'image-compare'        => [
						'label'        => 'Image Comparison',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Image-Compare",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-comparison',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/image-comparison/',
						'youtube_url'  => '',
						'description'  => 'This widget renders two images with synchronized alignment, interactive drag, and responsive customization options.'
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
						'demo_url'     => 'https://animation-addons.com/aae-progress-bar',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/progress-bar/',
						'youtube_url'  => '',
						'description'  => 'Add a clean progress bar that reflects achievements using smooth transitions and clear text labels.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-team',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/team/',
						'youtube_url'  => '',
						'description'  => 'Help visitors meet your team with photos, job titles, and clickable social media icons.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-notification',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-notification/',
						'youtube_url'  => '',
						'description'  => 'Inform users instantly with attention-grabbing notifications, designed to blend seamlessly with your site.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-one-page-nav',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/one-page-nav/',
						'youtube_url'  => '',
						'description'  => 'Hover to preview section names and smoothly swipe between sections on your one-page layout.'
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
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/timeline/',
						'youtube_url'  => '',
						'description'  => 'Use animated timelines to present events or project stages in a clear, organized progression.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-tabs',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/tabs/',
						'youtube_url'  => '',
						'description'  => 'Customize your tabs with various icons, titles, and styles for a tailored design approach.'
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
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/services-tabs/',
						'youtube_url'  => '',
						'description'  => 'Display your services in an attractive, tabbed layout that keeps your visitors engaged.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-floating-elements',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/floating-elements/',
						'youtube_url'  => '',
						'description'  => 'Add floating items to your page, such as buttons or icons, that stay in view as users scroll'
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
						'demo_url'     => 'https://animation-addons.com/aae-event-slider',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/event-slider/',
						'youtube_url'  => '',
						'description'  => 'Organize your events beautifully with image, date, and text in a refined carousel format.'
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
						'demo_url'     => 'https://animation-addons.com/aae-content-slider',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/content-slider/',
						'youtube_url'  => '',
						'description'  => 'Showcase dynamic content in a responsive slider with full layout and animation.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-countdown/',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/countdown/',
						'youtube_url'  => '',
						'description'  => 'Create anticipation for your next event by adding a friendly and customizable countdown.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-typewriter/',
						'doc_url'      => 'https://animation-addons.com/docs/animations-widgets/typewriter/',
						'youtube_url'  => '',
						'description'  => 'Bring back the charm of typing, minus the clunky keys and ink ribbons.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-animated-heading/',
						'doc_url'      => 'https://animation-addons.com/docs/animations-widgets/animated-heading/',
						'youtube_url'  => '',
						'description'  => 'Add stunning movement to your headlines and capture attention in just a second.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-animated-title',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-title/',
						'youtube_url'  => '',
						'description'  => 'Use this widget to animate titles by character, word, or full text block.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-animated-text',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/wcf-animated-text/',
						'youtube_url'  => '',
						'description'  => 'Explore text animation styles and transform static text into dynamic, engaging visual content.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-lottie',
						'doc_url'      => 'https://animation-addons.com/docs/animations-widgets/lottie/',
						'youtube_url'  => '',
						'description'  => 'Grab attention with elegant Lottie motion effects that boost engagement and brand appeal instantly.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-gsap-drawsvg',
						'doc_url'      => 'https://support.crowdytheme.com/docs/widgets/wcf-widgets/draw-svg/',
						'youtube_url'  => '',
						'description'  => 'Animate SVG paths with precision using GSAP’s smooth drawing effect, enhancing your web visuals.'
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
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Animated-Off-Canvas",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-animated-off-canvas/',
						'doc_url'      => 'https://animation-addons.com/docs/header-footer-widgets/animated-off-canvas/',
						'youtube_url'  => '',
						'description'  => 'Captivate your audience with stunning off-canvas reveals that keep users engaged longer on site!'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-site-logo',
						'doc_url'      => 'https://animation-addons.com/docs/header-footer-widgets/site-logo/',
						'youtube_url'  => '',
						'description'  => 'Add your website logo with full customization options here and make your site instantly recognizable.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-nav-menu',
						'doc_url'      => 'https://animation-addons.com/docs/header-footer-widgets/nav-menu/',
						'youtube_url'  => '',
						'description'  => 'Enhance your site’s usability by creating responsive, stylish, and fully customizable navigation menus.'
					],
					'mega-menu'           => [
						'label'        => 'Mega Menu',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => true,
						'icon'         => "wcf-icon-Nav-Menu",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-nav-menu',
						'doc_url'      => 'https://animation-addons.com/docs/header-footer-widgets/nav-menu/',
						'youtube_url'  => '',
						'description'  => 'Enhance your site’s usability by creating responsive, stylish, and fully customizable navigation menus.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts-slider',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/posts-slider/',
						'youtube_url'  => '',
						'description'  => 'Create a dynamic, engaging post slider with custom settings for a polished website experience.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-breaking-news-slider',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/breaking-news-slider/',
						'youtube_url'  => '',
						'description'  => 'Show updates in a sleek, customizable slider with dynamic animation effects.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-category-slider',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Create an interactive category display with a stylish slider for improved user navigation.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-box-slider',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Create a visually appealing video slider that perfectly fits your site’s design with this widget.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-filterable-slider',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Organize content effortlessly with a filterable slider that’s easy to customize and navigate.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/post-title',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/post-title/',
						'youtube_url'  => '',
						'description'  => 'Present your post titles with refined style, precise alignment, and fully customizable typography settings.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/post-featured-image/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/post-featured-image/',
						'youtube_url'  => '',
						'description'  => 'Customize the featured image of any post for a perfect fit across all device screens.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/post-excerpt/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/post-excerpt/',
						'youtube_url'  => '',
						'description'  => 'Use the Post Excerpt Widget to keep layouts clean and boost user engagement.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/post-content',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/post-content/',
						'youtube_url'  => '',
						'description'  => 'Showcase your blog’s latest posts with this widget, perfectly styled for Elementor.'
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
						'icon'         => "wcf-icon-Post-Content",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-post-comments/',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Enable seamless comment integration, enhancing community interaction on your blog or articles.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-post-reactions/',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/post-reactions/',
						'youtube_url'  => '',
						'description'  => 'Customize reaction button styles and separator icons to seamlessly fit your website’s design.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/post-meta-info/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/post-meta-info/',
						'youtube_url'  => '',
						'description'  => 'Use Post Meta Info to keep your readers informed without cluttering your beautiful layout.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/post-pagination/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/post-pagination/',
						'youtube_url'  => '',
						'description'  => 'Customize the pagination with icons, text, and animations to create a unique navigation experience.'
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
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/social-share/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/social-share/',
						'youtube_url'  => '',
						'description'  => 'Customize social share icons to match your design and encourage content sharing with ease.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/posts/',
						'youtube_url'  => '',
						'description'  => 'Create a dynamic blog section by customizing the layout, images, and post metadata display.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-posts',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/advanced-posts/',
						'youtube_url'  => '',
						'description'  => 'Add the Advanced Posts Widget to your Elementor page to showcase blog posts with rich filters.'
					],
					'posts-read-later'         => [
						'label'        => 'Posts Read Later',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => true,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Posts",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Embed trending TikTok videos on your website to keep your content fresh and engaging.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-story',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/video-story/',
						'youtube_url'  => '',
						'description'  => 'Share engaging video content with smooth transitions to tell your story effectively.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts-tabs',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/video-posts-tab/',
						'youtube_url'  => '',
						'description'  => 'Enhance your site with organized video posts displayed in engaging, user-friendly tabbed format.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-filterable-posts',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/filterable-posts/',
						'youtube_url'  => '',
						'description'  => 'Display posts in filterable categories, offering visitors a seamless navigation and content sorting experience.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-posts-rating-form',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Integrate a user-friendly post rating form to gather valuable feedback from your audience.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-post-rating',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Add an intuitive rating system to your posts, allowing users to share their opinions easily.'
					],
					'grid-hover-posts'   => [
						'label'        => 'Grid Hover Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Grid-Hover-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-hover-posts',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/grid-hover-posts/',
						'youtube_url'  => '',
						'description'  => 'Use the Grid Hover Posts Widget to display posts in a visually appealing, interactive grid.'
					],
					'category-showcase'  => [
						'label'        => 'Category Showcase',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Category-Showcase",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-category-showcase',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/category-showcase/',
						'youtube_url'  => '',
						'description'  => 'Category Showcase helps create a professional layout for showcasing your post categories.'
					],
					'banner-posts'       => [
						'label'        => 'Banner Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Banner-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-banner-posts',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/banner-posts/',
						'youtube_url'  => '',
						'description'  => 'Use the Banner Posts Widget to add styled, customizable banners to elevate your post visibility.'
					],
					'current-date'       => [
						'label'        => 'Current Date',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Current-Date",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-current-date',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/current-date-widget/',
						'youtube_url'  => '',
						'description'  => 'The Current Date Widget helps keep your content relevant by displaying the live date.'
					],
					'feature-posts'      => [
						'label'        => 'Featured Posts',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Featured-Posts",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-featured-posts',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/featured-posts/',
						'youtube_url'  => '',
						'description'  => 'Featured Posts allows you to showcase important posts with fully customizable design options.'
					],
					'archive-title'      => [
						'label'        => 'Archive Title',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Archive-Title",
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/archive-title/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/archive-title/',
						'youtube_url'  => '',
						'description'  => 'The Archive Title Widget lets you tailor archive page titles, improving clarity and user experience.'
					],
					'portfolio'          => [
						'label'        => 'Portfolio',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Portfolio",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-portfolio',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/portfolio/',
						'youtube_url'  => '',
						'description'  => 'Enhance your website by featuring your work with the Portfolio Widget’s flexible layout and design options.'
					],
					'search-form'        => [
						'label'        => 'Search Form',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-Form",
						'demo_url'     => 'https://animation-addons.com/wcf-template/search-form',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Add a search form widget for users to efficiently locate content across your website.'
					],
					'search-query'       => [
						'label'        => 'Search Query',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-Query",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-search-query',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/search-query/',
						'youtube_url'  => '',
						'description'  => 'Create a more intuitive search experience by customizing the Search Query Widgets content and style.'
					],
					'search-no-result'   => [
						'label'        => 'Search No Result',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => false,
						'is_extension' => false,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Search-No-Result",
						'demo_url'     => 'https://animation-addons.com/docs/dynamic-widgets/search-no-result/',
						'doc_url'      => 'https://animation-addons.com/docs/dynamic-widgets/search-no-result/',
						'youtube_url'  => '',
						'description'  => 'Customize the "no results" message with rich text, media, or links for better user direction.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-contact-form-7',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/contact-form-7/',
						'youtube_url'  => '',
						'description'  => 'With the Contact Form 7 Widget, you can create secure, user-friendly forms for your site.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-mailchimp',
						'doc_url'      => 'https://animation-addons.com/docs/general-widgets/mailchimp/',
						'youtube_url'  => '',
						'description'  => 'Grow your email list effortlessly by adding and customizing Mailchimp forms with this widget.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-mailchimp',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Easily integrate and customize Mailchimp sign-up forms for a seamless email list growth experience.'
					],
				]
			],
			'video-elements'     => [
				'title'     => 'Video Widgets',
				'is_active' => false,
				'elements'  => [
					'video-popup'   => [
						'label'        => 'Video Popup',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Popup",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-popup',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Use the Video Popup Widget to display videos in popups, making content more engaging.'
					],
					'video-box'     => [
						'label'        => 'Video Box',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-box',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Elevate your website’s look by incorporating a video box that blends seamlessly with your style.'
					],
					'video-mask'    => [
						'label'        => 'Video Mask',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Mask",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-video-mask',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Use the Video Mask Widget to bring artistic, interactive flair to your sites videos.'
					],
					'youtube-video' => [
						'label'        => 'Youtube Video',
						'location'     => [
							'cTab' => 'all'
						],
						'is_active'    => false,
						'is_pro'       => true,
						'is_extension' => true,
						'is_upcoming'  => false,
						'icon'         => "wcf-icon-Video-Mask",
						'demo_url'     => '#',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Use the Youtube Video Widget to bring artistic, interactive flair to your sites videos.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-toggle-switch',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Customize the Toggle Switcher to fit your design and create seamless user interactions.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-pricing-table',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Create bold, interactive pricing tables to present your services with clarity and professionalism.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-scroll-elements/',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Turn static content into a visual journey with smart, responsive scroll element animations.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-portfolio',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Make your portfolio shine with tailored layouts, animated sliders, and customized design elements.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-filterable-gallery',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Craft engaging galleries where visitors can filter, view, and enjoy your creative work.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-breadcrumbs',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Show users exactly where they are with minimal, modern breadcrumbs built for any layout.'
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
						'demo_url'     => 'https://animation-addons.com/widgets/aae-table-of-content',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Structure your headings into a clear, clickable table and keep your readers engaged longer.'
					],
					'image-accordion'       => [
						'label'        => 'Image Accordion',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Image-Accordion",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-image-accordion',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Present images creatively with collapsible sections, enhancing user experience and saving screen space.'
					],
					'author-box'            => [
						'label'        => 'Author Box',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Author-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-author-box',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Create a visually appealing author box with a photo, bio, and social media links.'
					],
					'flip-box'              => [
						'label'        => 'Flip Box',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Flip-Box",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-flip-box',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Create interactive, animated flip boxes that grab attention and highlight your key messages instantly'
					],
					'advance-accordion'     => [
						'label'        => 'Advanced Accordion',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Accordion",
						'demo_url'     => 'https://animation-addons.com/widgets/aae-advanced-accordion',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Keep your pages clean and visitors happy with smart, collapsible advanced accordion designs.'
					],
					'nested-slider'         => [
						'label'        => 'Nested Slider',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Accordion",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Design with the CMS Fill design with text, images, videos, more CMS.'
					],
					'weather'               => [
						'label'        => 'Weather',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Accordion",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Display real-time weather information on your website using OpenWeather API'
					],
					'tiktok-feed'           => [
						'label'        => 'TikTok Feed',
						'is_active'    => false,
						'location'     => [
							'cTab' => 'all'
						],
						'is_upcoming'  => false,
						'is_pro'       => true,
						'is_extension' => false,
						'icon'         => "wcf-icon-Advanced-Accordion",
						'demo_url'     => '',
						'doc_url'      => '',
						'youtube_url'  => '',
						'description'  => 'Embed trending TikTok videos on your website to keep your content fresh and engaging.'
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
								'pro_only'     => true,
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
						'draggable'          => [
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
