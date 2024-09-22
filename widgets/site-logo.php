<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Site_Logo extends Widget_Base {

	public function get_name() {
		// `theme` prefix is to avoid conflicts with a dynamic-tag with same name.
		return 'wcf--site-logo';
	}

	public function get_title() {
		return esc_html__( 'Site Logo', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-site-logo';
	}

	public function get_categories() {
		return [ 'wcf-hf-addon' ];
	}

	public function get_keywords() {
		return [ 'site', 'logo', 'branding' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_image',
			[
				'label' => esc_html__( 'Site Logo', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'logo_to',
			[
				'label'   => esc_html__( 'Logo', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'site_log',
				'options' => [
					'site_log' => esc_html__( 'Site Logo', 'animation-addons-for-elementor' ),
					'custom'   => esc_html__( 'Custom Logo', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'image',
			[
				'label'     => esc_html__( 'Choose Logo', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => [
					'url' => Utils::get_placeholder_image_src(),
				],
				'condition' => [
					'logo_to' => 'custom',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				// Usage: `{name}_size` and `{name}_custom_dimension`, in this case `image_size` and `image_custom_dimension`.
				'default'   => 'full',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'link_to',
			[
				'label'   => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'site_url',
				'options' => [
					'none'     => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'site_url' => esc_html__( 'Site URL', 'animation-addons-for-elementor' ),
					'custom'   => esc_html__( 'Custom URL', 'animation-addons-for-elementor' ),
					'file'     => esc_html__( 'Media File', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'link',
			[
				'label'      => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::URL,
				'dynamic'    => [
					'active' => true,
				],
				'condition'  => [
					'link_to' => 'custom',
				],
				'show_label' => false,
			]
		);

		$this->add_control(
			'open_lightbox',
			[
				'label'       => esc_html__( 'Lightbox', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'description' => sprintf(
				/* translators: 1: Link open tag, 2: Link close tag. */
					esc_html__( 'Manage your site’s lightbox settings in the %1$sLightbox panel%2$s.', 'animation-addons-for-elementor' ),
					'<a href="javascript: $e.run( \'panel/global/open\' ).then( () => $e.route( \'panel/global/settings-lightbox\' ) )">',
					'</a>'
				),
				'default'     => 'default',
				'options'     => [
					'default' => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'yes'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
					'no'      => esc_html__( 'No', 'animation-addons-for-elementor' ),
				],
				'condition'   => [
					'link_to' => 'file',
				],
			]
		);

		$this->add_control(
			'caption_source',
			[
				'label'   => esc_html__( 'Caption', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'none'       => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'attachment' => esc_html__( 'Attachment Caption', 'animation-addons-for-elementor' ),
				],
				'default' => 'none',
			]
		);

		$this->end_controls_section();

		//style controls
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Site Logo', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'width',
			[
				'label'          => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'size_units'     => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'          => [
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'space',
			[
				'label'          => esc_html__( 'Max Width', 'animation-addons-for-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'default'        => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'size_units'     => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range'          => [
					'%'  => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'      => [
					'{{WRAPPER}} img' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'height',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vh', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'object-fit',
			[
				'label'     => esc_html__( 'Object Fit', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'condition' => [
					'height[size]!' => '',
				],
				'options'   => [
					''        => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'fill'    => esc_html__( 'Fill', 'animation-addons-for-elementor' ),
					'cover'   => esc_html__( 'Cover', 'animation-addons-for-elementor' ),
					'contain' => esc_html__( 'Contain', 'animation-addons-for-elementor' ),
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} img' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'object-position',
			[
				'label'     => esc_html__( 'Object Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'center center' => esc_html__( 'Center Center', 'animation-addons-for-elementor' ),
					'center left'   => esc_html__( 'Center Left', 'animation-addons-for-elementor' ),
					'center right'  => esc_html__( 'Center Right', 'animation-addons-for-elementor' ),
					'top center'    => esc_html__( 'Top Center', 'animation-addons-for-elementor' ),
					'top left'      => esc_html__( 'Top Left', 'animation-addons-for-elementor' ),
					'top right'     => esc_html__( 'Top Right', 'animation-addons-for-elementor' ),
					'bottom center' => esc_html__( 'Bottom Center', 'animation-addons-for-elementor' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'animation-addons-for-elementor' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'animation-addons-for-elementor' ),
				],
				'default'   => 'center center',
				'selectors' => [
					'{{WRAPPER}} img' => 'object-position: {{VALUE}};',
				],
				'condition' => [
					'height[size]!' => '',
					'object-fit'    => 'cover',
				],
			]
		);

		$this->add_control(
			'separator_panel_style',
			[
				'type'  => Controls_Manager::DIVIDER,
				'style' => 'thick',
			]
		);

		$this->start_controls_tabs( 'image_effects' );

		$this->start_controls_tab( 'normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'opacity',
			[
				'label'     => esc_html__( 'Opacity', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} img' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'     => 'css_filters',
				'selector' => '{{WRAPPER}} img',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'hover',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'opacity_hover',
			[
				'label'     => esc_html__( 'Opacity', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max'  => 1,
						'min'  => 0.10,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}}:hover img' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'     => 'css_filters_hover',
				'selector' => '{{WRAPPER}}:hover img',
			]
		);

		$this->add_control(
			'background_hover_transition',
			[
				'label'     => esc_html__( 'Transition Duration', 'animation-addons-for-elementor' ) . ' (s)',
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} img' => 'transition-duration: {{SIZE}}s',
				],
			]
		);

		$this->add_control(
			'hover_animation',
			[
				'label' => esc_html__( 'Hover Animation', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'image_border',
				'selector'  => '{{WRAPPER}} img',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_box_shadow',
				'exclude'  => [
					'box_shadow_position',
				],
				'selector' => '{{WRAPPER}} img',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_caption',
			[
				'label'     => esc_html__( 'Caption', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'image[url]!'     => '',
					'caption_source!' => 'none',
				],
			]
		);

		$this->add_responsive_control(
			'caption_align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .widget-image-caption' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .widget-image-caption' => 'color: {{VALUE}};',
				],
				'global'    => [
					'default' => Global_Colors::COLOR_TEXT,
				],
			]
		);

		$this->add_control(
			'caption_background_color',
			[
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .widget-image-caption' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'caption_typography',
				'selector' => '{{WRAPPER}} .widget-image-caption',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'caption_text_shadow',
				'selector' => '{{WRAPPER}} .widget-image-caption',
			]
		);

		$this->add_responsive_control(
			'caption_space',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range'      => [
					'px'  => [
						'max' => 100,
					],
					'em'  => [
						'min' => 0,
						'max' => 10,
					],
					'rem' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .widget-image-caption' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( ! Plugin::$instance->experiments->is_feature_active( 'e_dom_optimization' ) ) {
			$this->add_render_attribute( 'wrapper', 'class', 'elementor-image' );
		}

		$has_caption = $this->has_caption( $settings );

		$link = $this->get_link_url( $settings );

		if ( $link ) {
			$this->add_link_attributes( 'link', $link );

			if ( Plugin::$instance->editor->is_edit_mode() ) {
				$this->add_render_attribute( 'link', 'class', 'elementor-clickable' );
			}

			if ( 'file' === $settings['link_to'] ) {
				$this->add_lightbox_data_attributes( 'link', $custom_logo_id, $settings['open_lightbox'] );
			}
		} ?>
		<?php if ( ! Plugin::$instance->experiments->is_feature_active( 'e_dom_optimization' ) ) { ?>
            <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
		<?php } ?>
		<?php if ( $has_caption ) : ?>
        <figure class="wp-caption">
	<?php endif; ?>
		<?php if ( $link ) : ?>
        <a <?php $this->print_render_attribute_string( 'link' ); ?> aria-label="<?php echo esc_attr__('Site Logo', 'animation-addons-for-elementor'); ?>">
	<?php endif; ?>
		<?php $this->get_site_logo( $settings ) ?>
		<?php if ( $link ) : ?>
        </a>
	<?php endif; ?>
		<?php if ( $has_caption ) : ?>
            <figcaption class="widget-image-caption wp-caption-text"><?php
				echo wp_kses_post( $this->get_caption( $settings ) );
				?></figcaption>
		<?php endif; ?>
		<?php if ( $has_caption ) : ?>
        </figure>
	<?php endif; ?>
		<?php if ( ! Plugin::$instance->experiments->is_feature_active( 'e_dom_optimization' ) ) { ?>
            </div>
		<?php } ?>
		<?php
	}

	protected function get_link_url( $settings ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$image          = wp_get_attachment_image_src( $custom_logo_id, 'full' );
		//for link type media file
		$media_url = '';
		if ( 'custom' === $settings['logo_to'] ) {
			$media_url = $settings['image']['url'];
		} else {
			if ( ! empty( $custom_logo_id ) ) {
				$media_url = $image[0];
			} else {
				$media_url = Utils::get_placeholder_image_src();
			}
		}

		switch ( $settings['link_to'] ) {
			case 'none':
				return false;

			case 'custom':
				return ( ! empty( $settings['link']['url'] ) ) ? $settings['link'] : false;

			case 'site_url':
				return [ 'url' => esc_url( home_url( '/' ) ) ?? '' ];

			default:
				return [ 'url' => $media_url ];
		}
	}

	private function has_caption( $settings ) {
		return ( ! empty( $settings['caption_source'] ) && 'none' !== $settings['caption_source'] );
	}

	private function get_caption( $settings ) {
		$caption = '';
		if ( ! empty( $settings['caption_source'] ) ) {
			switch ( $settings['caption_source'] ) {
				case 'attachment':
					$caption = wp_get_attachment_caption( get_theme_mod( 'custom_logo' ) );
					break;
				case 'custom':
					$caption = ! Utils::is_empty( $settings['caption'] ) ? $settings['caption'] : '';
			}
		}

		return $caption;
	}

	// Get the site logo
	private function get_site_logo( $settings ) {
		$html           = '';
		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( 'custom' === $settings['logo_to'] ) {
			Group_Control_Image_Size::print_attachment_image_html( $settings );
		} else {
			if ( ! empty( $custom_logo_id ) ) {
				$html .= wp_get_attachment_image( $custom_logo_id, $settings['image_size'], false );
			} else {
				$html .= sprintf(
					'<img src="%1$s" loading="lazy" />',
					esc_url( Utils::get_placeholder_image_src() ),
				);
			}
			echo wp_kses_post( $html );
		}
	}
}
