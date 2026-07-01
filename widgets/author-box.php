<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Author Box Widget.
 *
 * Elementor widget that displays an author box.
 *
 * @since 1.0.0
 * @package WCF_ADDONS\Widgets
 */
class Author_Box extends Widget_Base {
	public function get_name() {
		return 'wcf--author-box';
	}

	public function get_title() {
		return esc_html__( 'Author Box', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-person';
	}

	public function get_categories() {
		return array( 'weal-coder-addon' );
	}

	public function get_keywords() {
		return array( 'author', 'user', 'profile', 'biography', 'testimonial', 'avatar' );
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'wcf--author-box' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_author_info',
			array(
				'label' => esc_html__( 'Author Info', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => esc_html__( 'Current Author', 'animation-addons-for-elementor' ),
					'custom'  => esc_html__( 'Custom', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'        => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'left'  => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-left',
					),
					'above' => array(
						'title' => esc_html__( 'Above', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					),
					'right' => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'separator'    => 'before',
				'prefix_class' => 'wcf-author-box--layout-image-',
			)
		);

		$this->add_control(
			'alignment',
			array(
				'label'        => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'prefix_class' => 'wcf-author-box--align-',
			)
		);

		$this->add_control(
			'show_avatar',
			array(
				'label'        => esc_html__( 'Profile Picture', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'wcf-author-box--avatar-',
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'      => 'yes',
				'separator'    => 'before',
				'condition'    => array(
					'source!' => 'custom',
				),
				'render_type'  => 'template',
			)
		);

		// Used by the WordPress `get_avatar_url()` function to set the image size.
		$this->add_control(
			'avatar_size',
			array(
				'label'     => esc_html__( 'Picture Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 300,
				'condition' => array(
					'source!'     => 'custom',
					'show_avatar' => 'yes',
				),
			)
		);

		// This controls for custom source
		$this->add_control(
			'author_avatar',
			array(
				'label'     => esc_html__( 'Profile Picture', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array(
					'url' => Utils::get_placeholder_image_src(),
				),
				'condition' => array(
					'source' => 'custom',
				),
				'separator' => 'before',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);
		// END

		$this->add_control(
			'show_name',
			array(
				'label'        => esc_html__( 'Display Name', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'wcf-author-box--name-',
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'      => 'yes',
				'condition'    => array(
					'source!' => 'custom',
				),
				'render_type'  => 'template',
				'separator'    => 'before',
			)
		);

		// This control for custom source
		$this->add_control(
			'author_name',
			array(
				'label'     => esc_html__( 'Name', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'John Doe', 'animation-addons-for-elementor' ),
				'condition' => array(
					'source' => 'custom',
				),
				'separator' => 'before',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);
		// END

		$this->add_control(
			'author_name_tag',
			array(
				'label'   => esc_html__( 'HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
				),
				'default' => 'h4',
			)
		);

		$this->add_control(
			'link_to',
			array(
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_url( 'https://your-link.com' ),
				'condition'   => array(
					'source!' => 'custom',
				),
				'dynamic'     => array(
					'active' => true,
				),
				'description' => esc_html__( 'Link for the Author Name and Image', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'show_meta',
			array(
				'label'        => esc_html__( 'Author Meta', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'source!' => 'custom',
				),
				'render_type'  => 'template',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'show_biography',
			array(
				'label'        => esc_html__( 'Biography', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'wcf-author-box--biography-',
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'      => 'yes',
				'condition'    => array(
					'source!' => 'custom',
				),
				'render_type'  => 'template',
			)
		);

		$this->add_control(
			'show_link',
			array(
				'label'        => esc_html__( 'Archive Button', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'wcf-author-box--link-',
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'default'      => 'no',
				'condition'    => array(
					'source!' => 'custom',
				),
				'render_type'  => 'template',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'author_website',
			array(
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_url( 'https://your-link.com' ),
				'condition'   => array(
					'source' => 'custom',
				),
				'dynamic'     => array(
					'active' => true,
				),
				'description' => esc_html__( 'Link for the Author Name and Image', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'author_bio',
			array(
				'label'     => esc_html__( 'Biography', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => esc_html__( 'Lorem ipsum dolor sit amet consectetur adipiscing elit dolor', 'animation-addons-for-elementor' ),
				'rows'      => 3,
				'condition' => array(
					'source' => 'custom',
				),
				'separator' => 'before',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'posts_url',
			array(
				'label'       => esc_html__( 'Archive Button', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://your-link.com',
				'dynamic'     => array(
					'active' => true,
				),
				'condition'   => array(
					'source' => 'custom',
				),
			)
		);

		$this->add_control(
			'link_text',
			array(
				'label'   => esc_html__( 'Archive Text', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'All Posts', 'animation-addons-for-elementor' ),
				'dynamic' => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'show_contact',
			array(
				'label'        => esc_html__( 'Contact Info', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'source!' => 'custom',
				),
				'render_type'  => 'template',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'contact_title',
			array(
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Contact:', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your title here', 'animation-addons-for-elementor' ),
				'condition'   => array(
					'source'       => 'current',
					'show_contact' => 'yes',
				),
			)
		);

		$this->add_control(
			'email_label',
			array(
				'label'       => esc_html__( 'Email Label', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Email', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your label here', 'animation-addons-for-elementor' ),
				'condition'   => array(
					'source'       => 'current',
					'show_contact' => 'yes',
				),
			)
		);

		$this->add_control(
			'phone_label',
			array(
				'label'       => esc_html__( 'Phone Label', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Phone', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your label here', 'animation-addons-for-elementor' ),
				'condition'   => array(
					'source'       => 'current',
					'show_contact' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_social_media',
			array(
				'label'        => esc_html__( 'Social Profile', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'source' => 'current',
				),
				'render_type'  => 'template',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'social_title',
			array(
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Socials:', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type your title here', 'animation-addons-for-elementor' ),
				'condition'   => array(
					'source'            => 'current',
					'show_social_media' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_image_style',
			array(
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_vertical_align',
			array(
				'label'        => esc_html__( 'Vertical Align', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'top'    => array(
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					),
					'middle' => array(
						'title' => esc_html__( 'Middle', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-middle',
					),
				),
				'prefix_class' => 'wcf-author-box--image-valign-',
				'condition'    => array(
					'layout!' => 'above',
				),
			)
		);

		$this->add_responsive_control(
			'image_size',
			array(
				'label'      => esc_html__( 'Image Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__avatar img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_responsive_control(
			'image_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'body.rtl {{WRAPPER}}.wcf-author-box--layout-image-left .wcf-author-box__avatar,
					 body:not(.rtl) {{WRAPPER}}:not(.wcf-author-box--layout-image-above) .wcf-author-box__avatar' => 'margin-right: {{SIZE}}{{UNIT}}; margin-left: 0;',

					'body:not(.rtl) {{WRAPPER}}.wcf-author-box--layout-image-right .wcf-author-box__avatar,
					 body.rtl {{WRAPPER}}:not(.wcf-author-box--layout-image-above) .wcf-author-box__avatar' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right:0;',

					'{{WRAPPER}}.wcf-author-box--layout-image-above .wcf-author-box__avatar' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_control(
			'image_border',
			array(
				'label'     => esc_html__( 'Border', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__avatar img' => 'border-style: solid',
				),
			)
		);

		$this->add_control(
			'image_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000',
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__avatar img' => 'border-color: {{VALUE}}',
				),
				'condition' => array(
					'image_border' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'image_border_width',
			array(
				'label'      => esc_html__( 'Border Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'range'      => array(
					'px' => array(
						'max' => 20,
					),
					'em' => array(
						'max' => 2,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__avatar img' => 'border-width: {{SIZE}}{{UNIT}}',
				),
				'condition'  => array(
					'image_border' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__avatar img' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'           => 'input_box_shadow',
				'selector'       => '{{WRAPPER}} .wcf-author-box__avatar img',
				'fields_options' => array(
					'box_shadow_type' => array(
						'separator' => 'default',
					),
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_text_style',
			array(
				'label' => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_name_style',
			array(
				'label'     => esc_html__( 'Name', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .wcf-author-box__name',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				),
			)
		);

		$this->add_responsive_control(
			'name_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__name' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_control(
			'heading_bio_style',
			array(
				'label'     => esc_html__( 'Biography', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'bio_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_TEXT,
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__bio' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'bio_typography',
				'selector' => '{{WRAPPER}} .wcf-author-box__bio',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				),
			)
		);

		$this->add_responsive_control(
			'bio_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__bio' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__button' => 'color: {{VALUE}}; border-color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'button_background_color',
			array(
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__button' => 'background-color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				),
				'selector' => '{{WRAPPER}} .wcf-author-box__button',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'button_hover_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__button:hover' => 'border-color: {{VALUE}}; color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_background_hover_color',
			array(
				'label'     => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-author-box__button:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_animation',
			array(
				'label' => esc_html__( 'Animation', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::HOVER_ANIMATION,
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_width',
			array(
				'label'      => esc_html__( 'Border Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'range'      => array(
					'px' => array(
						'max' => 20,
					),
					'em' => array(
						'max' => 2,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__button' => 'border-width: {{SIZE}}{{UNIT}};',
				),
				'separator'  => 'before',
				'condition'  => array(
					'link_text!' => '',
				),
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__button' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'separator'  => 'after',
				'condition'  => array(
					'link_text!' => '',
				),
			)
		);

		$this->add_control(
			'button_text_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-author-box__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();

		// Meta Style
		$this->style_author_meta();

		// Contact Style
		$this->style_author_contact_info();

		// Social Profile
		$this->style_author_social_profile();
	}

	// Meta
	protected function style_author_meta() {
		$this->start_controls_section(
			'style_author_meta',
			array(
				'label'     => esc_html__( 'Meta', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_meta' => 'yes',
					'source'    => 'current',
				),
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-meta li' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'meta_typo',
				'selector' => '{{WRAPPER}} .wcf--author-meta li',
			)
		);

		$this->add_responsive_control(
			'meta_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-meta li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// Contact Info
	protected function style_author_contact_info() {
		$this->start_controls_section(
			'style_contact',
			array(
				'label'     => esc_html__( 'Contact', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_contact' => 'yes',
					'source'       => 'current',
				),
			)
		);

		$this->add_control(
			'con_info_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-contact a, {{WRAPPER}} .wcf--author-contact span' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'con_info_typo',
				'selector' => '{{WRAPPER}} .wcf--author-contact a, {{WRAPPER}} .wcf--author-contact span',
			)
		);

		$this->add_responsive_control(
			'con_info_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-contact li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Label
		$this->add_control(
			'contact_label_heading',
			array(
				'label'     => esc_html__( 'Label', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'con_label_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-contact .label' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'con_label_typo',
				'selector' => '{{WRAPPER}} .wcf--author-contact .label',
			)
		);

		$this->add_responsive_control(
			'con_label_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-contact .label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Title
		$this->add_control(
			'contact_title_heading',
			array(
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'con_title_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-contact .title' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'con_title_typo',
				'selector' => '{{WRAPPER}} .wcf--author-contact .title',
			)
		);

		$this->add_responsive_control(
			'con_title_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-contact .title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'contact_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-contact' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		// Hover
		$this->add_control(
			'contact_hover_heading',
			array(
				'label'     => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'contact_h_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-contact a:hover' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	// Social Profile
	protected function style_author_social_profile() {
		$this->start_controls_section(
			'style_social',
			array(
				'label'     => esc_html__( 'Social Profile', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_social_media' => 'yes',
					'source'            => 'current',
				),
			)
		);

		// Icon
		$this->add_control(
			'social_icon_heading',
			array(
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'social_icon_size',
			array(
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .icon img' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'social_icon_circle',
			array(
				'label'      => esc_html__( 'Circle Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-media .icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'social_icon_border',
				'selector' => '{{WRAPPER}} .wcf--author-media .icon',
			)
		);

		$this->add_responsive_control(
			'social_b_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-media .icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Follower
		$this->add_control(
			'follower_heading',
			array(
				'label'     => esc_html__( 'Follower', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'follower_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .follower' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'follower_typo',
				'selector' => '{{WRAPPER}} .follower',
			)
		);

		// Title
		$this->add_control(
			'social_title_heading',
			array(
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'social_title_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-media .title' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'social_title_typo',
				'selector' => '{{WRAPPER}} .wcf--author-media .title',
			)
		);

		$this->add_responsive_control(
			'social_title_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf--author-media .title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		// Hover
		$this->add_control(
			'social_hover_heading',
			array(
				'label'     => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'social_h_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--author-media a:hover .icon'     => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .wcf--author-media a:hover .follower' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	// Render Function.
	protected function render() {
		$settings        = $this->get_settings_for_display();
		$author          = array();
		$link_tag        = 'div';
		$link_url        = '';
		$link_target     = '';
		$author_name_tag = Utils::validate_html_tag( $settings['author_name_tag'] );

		$custom_src = ( 'custom' === $settings['source'] );

		if ( 'current' === $settings['source'] ) {
			$avatar_args['size']    = absint( $settings['avatar_size'] );
			$user_id                = get_the_author_meta( 'ID' );
			$author['avatar']       = get_avatar_url( $user_id, $avatar_args );
			$author['display_name'] = get_the_author_meta( 'display_name' );
			$author['website']      = get_the_author_meta( 'user_url' );
			$author['bio']          = get_the_author_meta( 'description' );
			$author['posts_url']    = get_author_posts_url( $user_id );
		} elseif ( $custom_src ) {

			if ( ! empty( $settings['author_avatar']['url'] ) ) {
				$avatar_src = esc_url_raw( $settings['author_avatar']['url'] );

				if ( absint( $settings['author_avatar']['id'] ) ) {
					$attachment_image_src = wp_get_attachment_image_src( absint( $settings['author_avatar']['id'] ), 'medium' );

					if ( ! empty( $attachment_image_src[0] ) ) {
						$avatar_src = $attachment_image_src[0];
					}
				}

				$author['avatar'] = $avatar_src;
			}

			$author['display_name'] = sanitize_text_field( $settings['author_name'] );
			$author['website']      = esc_url_raw( $settings['author_website']['url'] );
			$author['bio']          = wp_kses_post( wpautop( $settings['author_bio'] ) );
			$author['posts_url']    = esc_url_raw( $settings['posts_url']['url'] );
		}

		$print_avatar = ( ( ! $custom_src && 'yes' === $settings['show_avatar'] ) || ( $custom_src && ! empty( $author['avatar'] ) ) );
		$print_name   = ( ( ! $custom_src && 'yes' === $settings['show_name'] ) || ( $custom_src && ! empty( $author['display_name'] ) ) );
		$print_bio    = ( ( ! $custom_src && 'yes' === $settings['show_biography'] ) || ( $custom_src && ! empty( $author['bio'] ) ) );
		$print_link   = ( ( ! $custom_src && 'yes' === $settings['show_link'] ) && ! empty( $settings['link_text'] ) || ( $custom_src && ! empty( $author['posts_url'] ) && ! empty( $settings['link_text'] ) ) );

		// Determine the link configuration based on source and settings.
		if ( $custom_src ) {
			// For custom source, use author_website if provided.
			if ( ! empty( $author['website'] ) ) {
				$link_tag    = 'a';
				$link_url    = $author['website'];
				$link_target = ! empty( $settings['author_website']['is_external'] ) ? '_blank' : '';
			}
		} else {
			// For current source, check link_to settings.
			if ( ! empty( $settings['link_to']['url'] ) ) {
				// Custom URL provided via URL control.
				$link_tag    = 'a';
				$link_url    = esc_url( $settings['link_to']['url'] );
				$link_target = ! empty( $settings['link_to']['is_external'] ) ? '_blank' : '';
			} elseif ( ! empty( $author['posts_url'] ) ) {
				// Default to posts archive if no custom link is set.
				$link_tag = 'a';
				$link_url = $author['posts_url'];
			}
		}

		// Only add attributes if we have a valid URL.
		if ( ! empty( $link_url ) ) {
			$this->add_render_attribute( 'author_link', 'href', esc_url( $link_url ) );

			if ( ! empty( $link_target ) ) {
				$this->add_render_attribute( 'author_link', 'target', $link_target );
			}
		} else {
			// If no valid URL, ensure we don't use anchor tag.
			$link_tag = 'div';
		}

		$this->add_render_attribute(
			'button',
			'class',
			array(
				'wcf-author-box__button',
				'wcf-button',
				'wcf-size-xs',
			)
		);

		if ( $print_link ) {
			$this->add_render_attribute( 'button', 'href', esc_url( $author['posts_url'] ) );
		}

		if ( $print_link && ! empty( $settings['button_hover_animation'] ) ) {
			$this->add_render_attribute(
				'button',
				'class',
				'elementor-animation-' . $settings['button_hover_animation']
			);
		}

		if ( $print_avatar ) {
			$this->add_render_attribute(
				'avatar',
				array(
					'src'     => esc_url( $author['avatar'] ),
					'alt'     => ( ! empty( $author['display_name'] ) ) ? $author['display_name'] : esc_html__( 'Author picture', 'animation-addons-for-elementor' ),
					'loading' => 'lazy',
				)
			);
		}

		?>
		<div class="wcf-author-box">
		<?php if ( $print_avatar ) { ?>
			<<?php Utils::print_validated_html_tag( $link_tag ); ?> <?php $this->print_render_attribute_string( 'author_link' ); ?>
			class="wcf-author-box__avatar">
			<img <?php $this->print_render_attribute_string( 'avatar' ); ?>>
			</<?php Utils::print_validated_html_tag( $link_tag ); ?>>
		<?php } ?>

		<div class="wcf-author-box__text">
		<?php if ( $print_name ) : ?>
			<<?php Utils::print_validated_html_tag( $link_tag ); ?> <?php $this->print_render_attribute_string( 'author_link' ); ?>>
			<<?php Utils::print_validated_html_tag( $author_name_tag ); ?> class="wcf-author-box__name"><?php Utils::print_unescaped_internal_string( $author['display_name'] ); ?></<?php Utils::print_validated_html_tag( $author_name_tag ); ?>>
			</<?php Utils::print_validated_html_tag( $link_tag ); ?>>
		<?php endif; ?>

		<?php
		if ( 'current' === $settings['source'] ) {
			$post_count     = count_user_posts( $user_id );
			$comments_query = new \WP_Comment_Query();
			$comments       = $comments_query->query(
				array(
					'user_id' => $user_id,
					'count'   => true,
				)
			);

			if ( 'yes' === $settings['show_meta'] ) {
				?>
				<ul class="wcf--author-meta">
					<li class="total-posts">
						<?php
						echo intval( $post_count );
						echo esc_html__( ' articles', 'animation-addons-for-elementor' );
						?>
					</li>
					<li class="total-comments">
						<?php
						echo intval( $comments );
						echo esc_html__( ' comments', 'animation-addons-for-elementor' );
						?>
					</li>
				</ul>
				<?php
			}
		}
		?>

		<?php if ( $print_bio ) : ?>
			<div class="wcf-author-box__bio">
				<?php Utils::print_unescaped_internal_string( $author['bio'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $print_link ) : ?>
			<a <?php $this->print_render_attribute_string( 'button' ); ?>>
				<?php $this->print_unescaped_setting( 'link_text' ); ?>
			</a>
		<?php endif; ?>

		<?php
		if ( 'current' === $settings['source'] ) {
			$phone           = get_user_meta( $user_id, 'wcf_phone_number', true );
			$email           = get_the_author_meta( 'user_email' );
			$social_profiles = get_user_meta( $user_id, 'author_social_profiles', true );

			if ( 'yes' === $settings['show_contact'] ) {
				?>
				<div class="wcf--author-contact">
					<div class="title"><?php echo esc_html( $settings['contact_title'] ); ?></div>
					<ul>
						<?php
						if ( ! empty( $email ) ) {
							?>
							<li>
								<?php if ( ! empty( $settings['email_label'] ) ) { ?>
									<span
											class="label"><?php echo esc_html( $settings['email_label'] ); ?></span>
								<?php } ?>
								<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_attr( $email ); ?></a>
							</li>
							<?php
						}
						if ( ! empty( $phone ) ) {
							?>
							<li>
								<?php if ( ! empty( $settings['phone_label'] ) ) { ?>
									<span
											class="label"><?php echo esc_html( $settings['phone_label'] ); ?></span>
								<?php } ?>
								<a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_attr( $phone ); ?></a>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
				<?php
			}

			if ( is_array( $social_profiles ) && count( $social_profiles ) > 0 && 'yes' === $settings['show_social_media'] ) {
				?>
				<div class="wcf--author-media">
					<div class="title"><?php echo esc_html( $settings['social_title'] ); ?></div>
					<ul>
						<?php foreach ( $social_profiles as $profile ) : ?>
							<li>
								<?php
								if ( ! empty( $profile['url'] ) ) {
									$link = $profile['url'];
								} else {
									$link = '#';
								}

								if ( ! empty( $profile['icon'] ) ) :
									?>
									<a href="<?php echo esc_url( $link ); ?>" target="_blank">
										<span class="icon"><img src="<?php echo esc_url( $profile['icon'] ); ?>" alt="Icon"></span>
										<?php if ( isset( $profile['follower'] ) && intval( $profile['follower'] ) > 0 ) : ?>
											<span class="follower"> -<?php echo intval( $profile['follower'] ); ?></span>
										<?php endif; ?>
									</a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
			}
		}
		?>

		</div>
		</div>
		<?php
	}
}