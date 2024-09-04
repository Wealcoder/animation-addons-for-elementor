<?php
/**
 * Admin View: Settings Home
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sidebar_content = [
	[
		'label' => esc_html__( 'Latest Feature', 'animation-addons-for-elementor' ),
		'posts' => [
			[
				'title'       => esc_html__( 'Text Animation', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'The Animation Addons for Elementor makes it easy to animate text without the need for complicated CSS coding', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
			[
				'title'       => esc_html__( 'Image Animation', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Make your website stand out by adding animation to images with a few clicks', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
			[
				'title'       => esc_html__( 'Pin Elements', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Master the art of pinning elements on a single page with the Animation Addons for Elementor', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
		]

	],
	[
		'label' => esc_html__( 'Latest News', 'animation-addons-for-elementor' ),
		'posts' => [
			[
				'title'       => esc_html__( 'Elevate Your Website Design with the Animation Addons for Elementor Plugin', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Explore the latest update to the Animation Addons for Elementor plugin,', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
			[
				'title'       => esc_html__( 'Customize Your Elementor Designs with Precision and Enhanced Animation Controls' , 'animation-addons-for-elementor'),
				'description' => esc_html__( 'Discover the upgraded Animation Addons for Elementor plugin that offers advanced controls', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
			[
				'title'       => esc_html__( 'Get a Seamless Integration with the Animation Addons for Elementor', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Unleash optimal creativity with Animation Addons for Elementor and learn how it seamlessly integrates with a broad range of animation elements,', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
		]

	],
	[
		'label' => esc_html__( 'Documentation', 'animation-addons-for-elementor' ),
		'posts' => [
			[
				'title'       => esc_html__( 'WCF Posts', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'In this documentation, you can explore the steps of how to use the WCF Posts widget to show a wide collection of posts on your website perfectly', 'animation-addons-for-elementor' ),
				'link'        => '#'
			],
			[
				'title'       => esc_html__( 'WCF Image Gallery', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'You can take a look at the following steps to create a beautiful image gallery for your webpage with the WCF Image Gallery widget:' , 'animation-addons-for-elementor'),
				'link'        => '#'
			],
		]

	]
];


$widget_statistic = [
	'total-item'    => [
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25.5 25.5">
<path d="M23.78,16.89a.75.75,0,0,0-1,.4,11.14,11.14,0,0,1-8.16,6.49,11.07,11.07,0,0,1-9.95-2.89,11.17,11.17,0,0,1-3-10A11.09,11.09,0,0,1,8.07,2.7a.76.76,0,0,0,.39-1,.75.75,0,0,0-1-.38A12.55,12.55,0,0,0,.23,10.62,12.61,12.61,0,0,0,3.64,22a12.35,12.35,0,0,0,8.74,3.55,13,13,0,0,0,2.55-.25,12.62,12.62,0,0,0,9.25-7.37A.76.76,0,0,0,23.78,16.89Z" />
<path d="M24.69,8.17A13.63,13.63,0,0,0,17.33.81C15.56.07,14.39-.41,13,.48l-.2.14c-1.38,1-1.38,2.48-1.38,5V8.24c0,2.54,0,3.95,1,4.92s2.38,1,4.92,1h2.67c2.47,0,4,0,4.95-1.38l.14-.2C25.91,11.11,25.43,9.94,24.69,8.17Zm-1,3.6-.07.11c-.52.71-1.37.75-3.74.75H17.26c-2.36,0-3.34,0-3.86-.53s-.53-1.5-.53-3.86V5.57c0-2.37,0-3.22.75-3.74l.11-.07a1.58,1.58,0,0,1,.83-.26,6.67,6.67,0,0,1,2.19.69,12.16,12.16,0,0,1,6.56,6.56C24,10.48,24.21,11,23.74,11.77Z" />
</svg>',
		'label' => wp_kses_post( __( 'Total <br> Widgets', 'animation-addons-for-elementor' ) ),
		'count' => wcf_addons_get_all_widgets_count(),
	],
	'active-item'   => [
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25.5 25.5">
<path d="M6.43,17.05a.76.76,0,0,0-.75.75v2.53a.75.75,0,0,0,1.5,0V17.8A.75.75,0,0,0,6.43,17.05Z" />
<path d="M12.75,15.79a.75.75,0,0,0-.75.75v3.79a.75.75,0,0,0,1.5,0V16.54A.75.75,0,0,0,12.75,15.79Z" />
<path d="M19.07,13.26a.75.75,0,0,0-.75.75v6.32a.75.75,0,0,0,1.5,0V14A.76.76,0,0,0,19.07,13.26Z" />
<path d="M18.35,7.9a.74.74,0,0,0,.92.52.76.76,0,0,0,.52-.93l-.4-1.4a6.66,6.66,0,0,0-.26-.79,1.53,1.53,0,0,0-.61-.69,1.64,1.64,0,0,0-.89-.19c-.25,0-.56.07-.87.12l-1.61.26a.76.76,0,0,0-.61.87.74.74,0,0,0,.86.61l1.33-.22c-2.41,3.65-6.35,5.31-11.56,5.31a.75.75,0,0,0,0,1.5c5.57,0,10.12-1.79,12.87-6Z" />
<path d="M23.52,2c-2-2-4.91-2-10.77-2S4,0,2,2,0,6.89,0,12.75s0,8.8,2,10.77,4.91,2,10.77,2,8.8,0,10.77-2,2-4.91,2-10.77S25.5,4,23.52,2ZM22.46,22.46C20.92,24,18.19,24,12.75,24S4.58,24,3,22.46,1.5,18.19,1.5,12.75,1.5,4.58,3,3,7.31,1.5,12.75,1.5s8.17,0,9.71,1.54S24,7.31,24,12.75,24,20.92,22.46,22.46Z" />
</svg>',
		'label' => wp_kses_post( __( 'Active <br> Widgets', 'animation-addons-for-elementor' ) ),
		'count' => wcf_addons_get_active_widgets_count(),
	],
	'inactive-item' => [
		'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25.5 25.5">
    <path d="M19.07,17.05a.75.75,0,0,0-.75.75v2.53a.75.75,0,0,0,1.5,0V17.8A.76.76,0,0,0,19.07,17.05Z"/>
    <path d="M12.75,15.79a.75.75,0,0,0-.75.75v3.79a.75.75,0,0,0,1.5,0V16.54A.75.75,0,0,0,12.75,15.79Z"/>
    <path d="M6.43,13.26a.76.76,0,0,0-.75.75v6.32a.75.75,0,0,0,1.5,0V14A.75.75,0,0,0,6.43,13.26Z"/>
    <path d="M20.36,8.9,17.74,7.62A.75.75,0,0,0,17.08,9l1.38.67A13,13,0,0,1,5.71,5.91.75.75,0,1,0,4.64,7a14.44,14.44,0,0,0,14.09,4.16l-.87,1.19a.75.75,0,0,0,.16,1,.76.76,0,0,0,.45.15.74.74,0,0,0,.6-.31l1.75-2.38a1.27,1.27,0,0,0-.46-1.91Z"/>
    <path d="M23.52,2c-2-2-4.91-2-10.77-2S4,0,2,2,0,6.89,0,12.75s0,8.8,2,10.77,4.91,2,10.77,2,8.8,0,10.77-2,2-4.91,2-10.77S25.5,4,23.52,2ZM22.46,22.46C20.92,24,18.19,24,12.75,24S4.58,24,3,22.46,1.5,18.19,1.5,12.75,1.5,4.58,3,3,7.31,1.5,12.75,1.5s8.17,0,9.71,1.54S24,7.31,24,12.75,24,20.92,22.46,22.46Z"/>
</svg>',
		'label' => wp_kses_post( __( 'InActive <br> Widgets', 'animation-addons-for-elementor' ) ),
		'count' => wcf_addons_get_inactive_widgets_count(),
	]
];

//svg allowed
$kses_defaults = wp_kses_allowed_html( 'post' );

$svg_args = array(
	'svg'   => array(
		'class'           => true,
		'aria-hidden'     => true,
		'aria-labelledby' => true,
		'role'            => true,
		'xmlns'           => true,
		'width'           => true,
		'height'          => true,
		'viewbox'         => true // <= Must be lower case!
	),
	'g'     => array( 'fill' => true ),
	'title' => array( 'title' => true ),
	'path'  => array(
		'd'               => true,
		'fill'            => true
	)
);

$allowed_svg = array_merge( $kses_defaults, $svg_args );
?>

<div class="settings-home-wrap">
    <div class="content-wrap">
        <div class="wcf-statistic">
            <div class="statistic-head">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22.5 22.5">
                        <path d="M20.69,3.26C19.54,1.43,19,.52,18,.18a2.84,2.84,0,0,0-2,0c-.94.34-1.51,1.25-2.67,3.08l.64.4-.64-.4c-1.29,2-1.93,3.07-1.79,4.11a2.91,2.91,0,0,0,1,1.85c.81.68,2,.68,4.44.68s3.62,0,4.44-.68a2.93,2.93,0,0,0,1-1.85C22.62,6.32,22,5.3,20.69,3.26ZM20.5,8.07C20.11,8.4,19,8.4,17,8.4s-3.09,0-3.48-.33a1.4,1.4,0,0,1-.49-.9c-.07-.51.54-1.48,1.57-3.11h0c.91-1.44,1.46-2.31,1.91-2.47a1.35,1.35,0,0,1,1,0c.46.16,1,1,1.91,2.47,1,1.62,1.64,2.6,1.58,3.11A1.46,1.46,0,0,1,20.5,8.07Z"></path>
                        <path d="M1.27,9.31C2.08,9.9,3.1,9.9,5,9.9s2.87,0,3.68-.59a3.13,3.13,0,0,0,.68-.68C9.9,7.82,9.9,6.8,9.9,5s0-2.87-.59-3.68A3.13,3.13,0,0,0,8.63.59C7.82,0,6.8,0,5,0S2.08,0,1.27.59a3.13,3.13,0,0,0-.68.68C0,2.08,0,3.1,0,5S0,7.82.59,8.63A3.13,3.13,0,0,0,1.27,9.31ZM1.8,2.15a1.93,1.93,0,0,1,.35-.35c.42-.3,1.27-.3,2.8-.3s2.38,0,2.8.3a1.93,1.93,0,0,1,.35.35c.3.42.3,1.27.3,2.8s0,2.38-.3,2.8a1.93,1.93,0,0,1-.35.35c-.42.3-1.26.3-2.8.3s-2.38,0-2.8-.3a1.93,1.93,0,0,1-.35-.35c-.3-.42-.3-1.26-.3-2.8S1.5,2.57,1.8,2.15Z"></path>
                        <path d="M17,12.6a5,5,0,1,0,4.95,5A5,5,0,0,0,17,12.6ZM17,21a3.45,3.45,0,1,1,3.45-3.45A3.46,3.46,0,0,1,17,21Z"></path>
                        <path d="M9.15,16.8H5.7V13.35a.75.75,0,0,0-1.5,0V16.8H.75a.75.75,0,0,0,0,1.5H4.2v3.45a.75.75,0,0,0,1.5,0V18.3H9.15a.75.75,0,1,0,0-1.5Z"></path>
                    </svg>
                </div>
                <div>
                    <span><?php echo esc_html__( 'Statistics', 'animation-addons-for-elementor' ); ?></span>
                    <div class="title-2"><?php echo esc_html__( 'Animation Addon for Elementor', 'animation-addons-for-elementor' ); ?></div>
                </div>
            </div>
            <div class="statistic-body">
	            <?php foreach ( $widget_statistic as $key => $value ) { ?>
                    <div class="item <?php echo esc_attr( $key ); ?>">
                        <div class="head">
                            <div class="icon">
	                            <?php echo wp_kses( $value['icon'], $allowed_svg ); ?>
                            </div>
                            <div class="title"><?php echo wp_kses_post( $value['label'] ); ?></div>
                        </div>
                        <div class="count"><?php echo esc_html( $value['count'] ); ?></div>
                    </div>
	            <?php } ?>
            </div>
        </div>

        <div class="wcf-sidebar">
            <div id="wcf-accordion">
	            <?php foreach ( $sidebar_content as $data ) { ?>
                    <h3 class="title-2"><?php echo esc_html( $data['label'] ); ?></h3>
                    <ul class="overview__posts">
	                    <?php foreach ( $data['posts'] as $post ) { ?>
                            <li>
                                <a href="<?php echo esc_url( $post['link'] ); ?>"><?php echo esc_html( $post['title'] ); ?></a>
                                <div class="desc"><?php echo esc_html( $post['description'] ); ?></div>
                            </li>
	                    <?php } ?>
                    </ul>
	            <?php } ?>
            </div>
        </div>

    </div>
</div>
