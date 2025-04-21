<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\Widgets\Nav_Menu\WCF_Menu_Walker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Nav Menu
 *
 * Elementor widget for navigation manu
 *
 * @since 1.0.0
 */
class Nav_Menu extends Widget_Base {
	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_name() {
		return 'wcf--nav-menu';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_title() {
		return esc_html__( 'Nav Menu', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_icon() {
		return 'wcf eicon-nav-menu';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @return array Widget categories.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_categories() {
		return [ 'wcf-hf-addon' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [
//		        'wcf--nav-menu'
		];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_script_depends() {
		return [ 'wcf--nav-menu' ];
	}

	public function get_menus(){
		$list = [];
		$menus = wp_get_nav_menus();
		foreach($menus as $menu){
			$list[$menu->slug] = $menu->name;
		}

		return $list;
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_menu_settings',
			[
				'label' => esc_html__( 'Menu Settings', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'nav_menu',
			[
				'label'     => esc_html__( 'Select menu', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => $this->get_menus(),
			]
		);

		$this->add_control(
			'submenu_indicator',
			[
				'label'       => esc_html__( 'Submenu Indicator', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-angle-down',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'menu_layout',
			[
				'label'       => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => false,
				'options'     => [
					'horizontal' => esc_html__( 'Horizontal', 'animation-addons-for-elementor' ),
					'vertical'   => esc_html__( 'Vertical', 'animation-addons-for-elementor' ),
				],
				'default'     => 'horizontal',
			]
		);

		$this->add_responsive_control(
			'menu_alignment',
			[
				'label'       => esc_html__( 'Menu Alignment', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'label_block' => false,
				'default'     => '',
				'options'     => [
					'flex-start'    => [
						'title' => esc_html__( 'Start', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-start-h',
					],
					'center'        => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-center-h',
					],
					'flex-end'      => [
						'title' => esc_html__( 'End', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-end-h',
					],
					'space-between' => [
						'title' => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-flex eicon-align-stretch-h',
					],
				],
				'selectors'   => [
					'{{WRAPPER}} .wcf-nav-menu-nav' => 'justify-content: {{VALUE}};',
				],
				'condition'   => [ 'menu_layout' => 'horizontal' ]
			]
		);

		$this->add_control(
			'menu_hover_pointer',
			[
				'label'       => esc_html__( 'Hover Pointer', 'animation-addons-for-elementor' ),
				'description' => esc_html__( 'Apply on desktop menu first depth', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					''             => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'dot'          => esc_html__( 'Dot', 'animation-addons-for-elementor' ),
					'underline'    => esc_html__( 'Underline', 'animation-addons-for-elementor' ),
					'overline'     => esc_html__( 'Overline', 'animation-addons-for-elementor' ),
					'line-through' => esc_html__( 'Line Through', 'animation-addons-for-elementor' ),
					'flip' => esc_html__( 'Flip', 'animation-addons-for-elementor' ),
				],
			]
		);	

		$this->end_controls_section();

		$this->start_controls_section(
			'section_mobile_menu_settings',
			[
				'label' => esc_html__( 'Mobile Menu Settings', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'hamburger_icon',
			[
				'label'       => esc_html__( 'Hamburger Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-bars',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'mobile_close',
			[
				'label'       => esc_html__( 'Mobile Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-times',
					'library' => 'fa-solid',
				],
			]
		);

		$dropdown_options = [
			'' => esc_html__( 'None', 'animation-addons-for-elementor' ),
			'all' => esc_html__( 'All', 'animation-addons-for-elementor' ),
		];

		$excluded_breakpoints = [
			'widescreen',
		];

		foreach ( Plugin::$instance->breakpoints->get_active_breakpoints() as $breakpoint_key => $breakpoint_instance ) {
			// Exclude the larger breakpoints from the dropdown selector.
			if ( in_array( $breakpoint_key, $excluded_breakpoints, true ) ) {
				continue;
			}

			$dropdown_options[ $breakpoint_key ] = sprintf(
			/* translators: 1: Breakpoint label, 2: `>` character, 3: Breakpoint value. */
				esc_html__( '%1$s (%2$s %3$dpx)', 'animation-addons-for-elementor' ),
				$breakpoint_instance->get_label(),
				'>',
				$breakpoint_instance->get_value()
			);
		}

		$this->add_control(
			'mobile_menu_breakpoint',
			[
				'label'        => esc_html__( 'Breakpoint', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'separator'    => 'before',
				'description'  => esc_html__( 'Note: Choose at which breakpoint Mobile Menu will Show.', 'animation-addons-for-elementor' ),
				'options'      => $dropdown_options,
				'frontend_available' => true,
				'default'      => 'mobile',
			]
		);

		$this->end_controls_section();

		//desktop menu item style
        $this->register_desktop_menu_item_style();

		//desktop submenu item style
        $this->register_desktop_submenu_item_style();

		//desktop submenu indicator style
        $this->register_submenu_indicator_style();

        //hover pointer style
        $this->register_hover_pointer_style();

        //mobile menu item
        $this->register_mobile_menu_style();

        //hamburger
        $this->register_hamburger_style();

        //mobile menu close
        $this->register_mobile_menu_close_style();

        //mobile menu back style
		$this->register_mobile_menu_back_style();
	}

	protected function register_desktop_menu_item_style() {
		$this->start_controls_section(
			'section_desktop_menu_item_style',
			[
				'label' => esc_html__( 'Desktop Menu Item', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desktop_menu_item_typography',
				'selector' => '{{WRAPPER}} .desktop-menu-active .menu-item a',
			]
		);

		$this->add_responsive_control(
			'desktop_menu_item_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .wcf-nav-menu-nav' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'desktop_menu_item_border',
				'selector' => '{{WRAPPER}} .desktop-menu-active .menu-item a',
			]
		);

		$this->add_responsive_control(
			'desktop_menu_item_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator' => 'before',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .menu-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'desktop_menu_item_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .menu-item a.wcf-nav-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'desktop_menu_item_style_tabs'
		);

		$this->start_controls_tab(
			'desktop_menu_item_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'desktop_menu_item_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .menu-item a' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'desktop_menu_item_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .desktop-menu-active .menu-item a',
			]
		);

		$this->end_controls_tab();

		//hover
		$this->start_controls_tab(
			'desktop_menu_item_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'desktop_menu_item_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .menu-item a:hover, {{WRAPPER}} .desktop-menu-active .menu-item a:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'desktop_menu_item_hover_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .desktop-menu-active .menu-item a:hover, {{WRAPPER}} .desktop-menu-active .menu-item a:focus',
			]
		);

		$this->add_control(
			'desktop_menu_item_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .menu-item a:hover, {{WRAPPER}} .desktop-menu-active .menu-item a:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'desktop_menu_item_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		//active
		$this->start_controls_tab(
			'desktop_menu_item_style_active_tab',
			[
				'label' => esc_html__( 'Active', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'desktop_menu_item_active_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .menu-item.current-menu-item > a' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'desktop_menu_item_active_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .desktop-menu-active .menu-item.current-menu-item > a',
			]
		);

		$this->add_control(
			'desktop_menu_item_active_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .menu-item.current-menu-item > a' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'desktop_menu_item_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_desktop_submenu_item_style() {
		$this->start_controls_section(
			'section_desktop_submenu_item_style',
			[
				'label' => esc_html__( 'Desktop Submenu Item', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'desktop_submenu_width',
			[
				'label' => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'desktop_submenu_background',
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .desktop-menu-active .sub-menu',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'desktop_submenu_border',
				'selector' => '{{WRAPPER}} .desktop-menu-active .sub-menu',
			]
		);

		$this->add_responsive_control(
			'desktop_submenu_padding',
			[
				'label'      => esc_html__( 'Wrapper Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'desktop_submenu_border_radius',
			[
				'label'      => esc_html__( 'Wrapper Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator' => 'after',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'desktop_submenu_heading',
			[
				'label' => esc_html__( 'Submenu Items', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desktop_submenu_item_typography',
				'selector' => '{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'desktop_submenu_item_border',
				'selector' => '{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a',
			]
		);

		$this->add_responsive_control(
			'desktop_submenu_item_padding',
			[
				'label'      => esc_html__( 'Item Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator' => 'before',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'desktop_submenu_item_border_radius',
			[
				'label'      => esc_html__( 'Item Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'desktop_submenu_item_style_tabs'
		);

		$this->start_controls_tab(
			'desktop_submenu_item_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'desktop_submenu_item_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'desktop_submenu_item_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a',
			]
		);

		$this->end_controls_tab();

		//hover
		$this->start_controls_tab(
			'desktop_submenu_item_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'desktop_submenu_item_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a:hover, {{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'desktop_submenu_item_hover_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a:hover, {{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a:focus',
			]
		);

		$this->add_control(
			'desktop_submenu_item_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a:hover, {{WRAPPER}} .desktop-menu-active .sub-menu .menu-item a:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'desktop_submenu_item_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		//active
		$this->start_controls_tab(
			'desktop_submenu_item_style_active_tab',
			[
				'label' => esc_html__( 'Active', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'desktop_submenu_item_active_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item.current-menu-item > a' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'desktop_submenu_item_active_background',
				'types'     => [ 'classic', 'gradient' ],
				'exclude'   => [ 'image' ],
				'selector'  => '{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item.current-menu-item > a',
			]
		);

		$this->add_control(
			'desktop_submenu_item_active_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .desktop-menu-active .sub-menu .menu-item.current-menu-item > a' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'desktop_submenu_item_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_submenu_indicator_style() {
		$this->start_controls_section(
			'section_submenu_indicator_style',
			[
				'label' => esc_html__( 'Submenu Indicator', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'submenu_indicator_size',
			[
				'label'      => esc_html__( 'Font Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-submenu-indicator' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'submenu_indicator_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-submenu-indicator' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_mobile_menu_style() {
		$this->start_controls_section(
			'section_mobile_menu_style',
			[
				'label' => esc_html__( 'Mobile Menu', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'mobile_menu_position',
			[
				'label'       => esc_html__( 'Position', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options'     => [
					'left'  => [
						'title' => esc_html__( 'left', 'animation-addons-for-elementor' ),
						'icon'  => 'fa fa-angle-left',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'fa fa-angle-right',
					],
				],
				'default'     => 'right',
			]
		);

		$this->add_responsive_control(
			'mobile_menu_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .mobile-menu-active .wcf-nav-menu-container' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'      => 'mobile_menu_background',
				'types'     => [ 'classic', 'gradient' ],
				'separator' => 'after',
				'selector'  => '{{WRAPPER}} .mobile-menu-active .wcf-nav-menu-container, {{WRAPPER}} .mobile-menu-active .menu-item-has-children .sub-menu',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'mobile_menu_item_typography',
				'selector' => '{{WRAPPER}} .mobile-menu-active .menu-item a',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'mobile_menu_item_border',
				'selector' => '{{WRAPPER}} .mobile-menu-active .menu-item a, {{WRAPPER}} .mobile-menu-active .menu-item-has-children .sub-menu .menu-item a',
			]
		);

		$this->add_responsive_control(
			'mobile_menu_item_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .mobile-menu-active .menu-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'mobile_menu_item_style_tabs'
		);

		$this->start_controls_tab(
			'mobile_menu_item_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'mobile_menu_item_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mobile-menu-active .menu-item a' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'mobile_menu_item_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .mobile-menu-active .menu-item a',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'mobile_menu_item_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'mobile_menu_item_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mobile-menu-active .menu-item a:hover, {{WRAPPER}} .mobile-menu-active .menu-item a:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'mobile_menu_item_hover_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .mobile-menu-active .menu-item a:hover, {{WRAPPER}} .mobile-menu-active .menu-item a:focus',
			]
		);

		$this->add_control(
			'mobile_menu_item_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mobile-menu-active .menu-item a:hover, {{WRAPPER}} .mobile-menu-active .menu-item a:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'mobile_menu_item_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_hamburger_style() {
		$this->start_controls_section(
			'section_hamburger_style',
			[
				'label' => esc_html__( 'Hamburger', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'hamburger_size',
			[
				'label'      => esc_html__( 'Font Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-menu-hamburger' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'hamburger_border',
				'selector' => '{{WRAPPER}} .wcf-menu-hamburger',
			]
		);

		$this->add_responsive_control(
			'hamburger_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-hamburger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'hamburger_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-menu-hamburger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'hamburger_style_tabs'
		);

		$this->start_controls_tab(
			'hamburger_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'hamburger_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-hamburger' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'hamburger_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .wcf-menu-hamburger',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'hamburger_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'hamburger_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-hamburger:hover, {{WRAPPER}} .wcf-menu-hamburger:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'hamburger_hover_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .wcf-menu-hamburger:hover, {{WRAPPER}} .wcf-menu-hamburger:focus',
			]
		);

		$this->add_control(
			'hamburger_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-hamburger:hover, {{WRAPPER}} .wcf-menu-hamburger:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'hamburger_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_mobile_menu_close_style() {
		$this->start_controls_section(
			'section_mobile_menu_close_style',
			[
				'label' => esc_html__( 'Mobile Menu Close', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'mobile_menu_close_size',
			[
				'label'      => esc_html__( 'Font Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-menu-close' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'mobile_menu_close_border',
				'selector' => '{{WRAPPER}} .wcf-menu-close',
			]
		);

		$this->add_responsive_control(
			'mobile_menu_close_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-menu-close' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'mobile_menu_close_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf-menu-close' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'mobile_menu_close_style_tabs'
		);

		$this->start_controls_tab(
			'mobile_menu_close_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'mobile_menu_close_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-close' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'mobile_menu_close_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .wcf-menu-close',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'mobile_menu_close_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'mobile_menu_close_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-close:hover, {{WRAPPER}} .wcf-menu-close:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'mobile_menu_close_hover_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .wcf-menu-close:hover, {{WRAPPER}} .wcf-menu-close:focus',
			]
		);

		$this->add_control(
			'mobile_menu_close_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf-menu-close:hover, {{WRAPPER}} .wcf-menu-close:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'mobile_menu_close_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_mobile_menu_back_style() {
		$this->start_controls_section(
			'section_mobile_menu_back_style',
			[
				'label' => esc_html__( 'Mobile Menu Back', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'back_icon',
			[
				'label'              => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::ICONS,
				'default'            => [
					'value'   => 'fas fa-arrow-left',
					'library' => 'fa-solid',
				],
				'skin'               => 'inline',
				'label_block'        => false,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'mobile_menu_back__typography',
				'selector' => '{{WRAPPER}} .mobile-menu-active a.nav-back-link',
			]
		);

		$this->add_responsive_control(
			'mobile_menu_back_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .mobile-menu-active a.nav-back-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'mobile_menu_back_style_tabs'
		);

		$this->start_controls_tab(
			'mobile_menu_back_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'mobile_menu_back_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .mobile-menu-active a.nav-back-link' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'mobile_menu_back_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .mobile-menu-active a.nav-back-link',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'mobile_menu_back_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'mobile_menu_back_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .mobile-menu-active a.nav-back-link:hover, {{WRAPPER}} .mobile-menu-active a.nav-back-link:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'mobile_menu_back_hover_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .mobile-menu-active a.nav-back-link:hover, {{WRAPPER}} .mobile-menu-active a.nav-back-link:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_hover_pointer_style() {
		$this->start_controls_section(
			'section_hover_pointer_style',
			[
				'label'     => esc_html__( 'Hover Pointer', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'menu_hover_pointer!' => '',
				],
			]
		);

		$this->add_control(
			'hover_pointer_width',
			[
				'label'      => esc_html__( 'Hover Pointer Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .menu-item a:after' => 'width: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'hover_pointer_height',
			[
				'label'      => esc_html__( 'Hover Pointer Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .menu-item a:after' => 'height: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_control(
			'hover_pointer_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .menu-item a:after' => 'background-color: {{VALUE}} !important',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Return if menu not selected
		if ( empty( $settings['nav_menu'] ) ) {
			return;
		}

		//include nav menu walker
		if ( ! class_exists( 'WCF_ADDONS\Widgets\Nav_Menu\WCF_Menu_Walker' ) ) {
			include_once WCF_ADDONS_PATH . 'widgets/nav-menu/walker-nav-menu.php';
		}

		$close_button = '<button class="wcf-menu-close" type="button">' . Icons_Manager::try_get_icon_html( $settings['mobile_close'], [ 'aria-hidden' => 'true' ] ) . '</button>';
		$remove_span = isset($settings['aae_scmscroll_enb']) && $settings['aae_scmscroll_enb'] == 'yes' ? true : false; 	
		
		//nav menu arguments
		$arg = [
			'items_wrap'             => '<ul id="%1$s" class="%2$s">%3$s</ul>' . $close_button,
			'menu'                   => $settings['nav_menu'],
			'fallback_cb'            => 'wp_page_menu',
			'container'              => 'div',
			'container_class'        => 'wcf-nav-menu-container',
			'menu_class'             => 'wcf-nav-menu-nav ' . 'menu-layout-' . $settings['menu_layout'],			
			'submenu_indicator_icon' => Icons_Manager::try_get_icon_html( $settings['submenu_indicator'], [ 'aria-hidden' => 'true' ] ),
			'walker'                 => ( class_exists( 'WCF_ADDONS\Widgets\Nav_Menu\WCF_Menu_Walker' ) ? new WCF_Menu_Walker(['remove_span'=> $remove_span]) : '' )
		];

		//necessary preloaded class for style breaking
		$active_menu_class = 'mobile-menu-active';
		if ( empty( $settings['mobile_menu_breakpoint'] ) ) {
			$active_menu_class = 'desktop-menu-active';
		}

		//wrapper class
		$this->add_render_attribute( 'wrapper', 'class', [
			'wcf__nav-menu ' . $active_menu_class,
			'mobile-menu-' . $settings['mobile_menu_position'],
			'hover-pointer-' . $settings['menu_hover_pointer']
		] );
		?>
        <style>
            .wcf__nav-menu{display:none}.wcf__nav-menu svg{width:1em;height:1em}.wcf__nav-menu .wcf-submenu-indicator{display:inline-flex;justify-content:center;align-items:center;margin-left:auto;padding-left:5px}.wcf__nav-menu .wcf-menu-badge{display:none;font-size:12px;font-weight:500;line-height:1;position:absolute;right:15px;padding:5px 10px;border-radius:5px;background-color:var(--badge-bg);box-shadow:0 2px 5px 2px rgba(0,0,0,.1);margin-top:-22px}.wcf__nav-menu .wcf-menu-badge:after{content:"";position:absolute;top:100%;left:50%;transform:translateX(-50%);border:5px solid var(--badge-bg);border-bottom-color:transparent!important;border-inline-end-color:transparent!important;border-inline-end-width:7px;border-inline-start-width:0}.wcf__nav-menu .wcf-menu-hamburger{margin-left:auto;cursor:pointer;font-size:25px;padding:4px 8px;border:1px solid #dee1e7;outline:0;background:0 0;line-height:1;display:inline-flex;align-items:center;justify-content:center}.wcf__nav-menu.mobile-menu-active{display:block}.wcf__nav-menu.mobile-menu-active .wcf-submenu-indicator{padding:8px 10px;margin:-8px -10px -8px auto}.wcf__nav-menu.mobile-menu-active .wcf-menu-hamburger{display:inline-block}.wcf__nav-menu.mobile-menu-active .wcf-menu-close{align-self:flex-end;margin:10px 10px 10px auto;padding:8px 10px;border:1px solid #555;outline:0;background:0 0;font-size:15px;line-height:1;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;min-width:40px;min-height:40px}.wcf__nav-menu.mobile-menu-active .wcf-menu-overlay{position:fixed;top:0;left:0;z-index:1000;background-color:rgba(0,0,0,.5);height:100%;width:100%;transition:.4s;opacity:0;visibility:hidden}.wcf__nav-menu.mobile-menu-active.wcf-nav-is-toggled .wcf-nav-menu-container{transform:translateX(0)!important}.wcf__nav-menu.mobile-menu-active.wcf-nav-is-toggled .wcf-menu-overlay{opacity:1;visibility:visible}.wcf__nav-menu.mobile-menu-active .wcf-nav-menu-container{display:flex;flex-direction:column;position:fixed;z-index:1001;top:0;bottom:0;width:250px;background-color:#fff;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;transition:.45s}.wcf__nav-menu.mobile-menu-active .wcf-nav-menu-container .wcf-nav-menu-nav{flex:0 0 100%;padding:0;margin:0;order:1}.wcf__nav-menu.mobile-menu-active .menu-item{list-style:none}.wcf__nav-menu.mobile-menu-active .menu-item:not(:last-child) a{border-bottom:solid 1px #dee1e7}.wcf__nav-menu.mobile-menu-active .menu-item a{text-decoration:none;display:flex;padding:.5em 1em;font-size:1rem;line-height:1.5em;transition:.4s}.wcf__nav-menu.mobile-menu-active .menu-item-has-children .sub-menu{position:absolute;top:0;left:0;width:100%;height:100%;background:#fff;transform:translateX(100%);transition:.3s;visibility:hidden;padding:0;margin:0;flex:0 0 100%}.wcf__nav-menu.mobile-menu-active .menu-item-has-children .sub-menu .nav-back-link{display:flex;align-items:center;background-color:#064af3;color:#fff;border:none!important}.wcf__nav-menu.mobile-menu-active .menu-item-has-children.active>.sub-menu{transform:translateX(0);visibility:visible}.wcf__nav-menu.mobile-menu-active .wcf-mega-menu .sub-menu{display:none}.wcf__nav-menu.mobile-menu-active .wcf-mega-menu .wcf-mega-menu-panel{display:none;max-width:100%!important;transition:.3s;opacity:0;visibility:hidden}.wcf__nav-menu.mobile-menu-active .wcf-mega-menu.active>.wcf-mega-menu-panel{display:block;opacity:1;visibility:visible}.wcf__nav-menu.mobile-menu-active .wcf-mega-menu.mobile-wp-submenu .wcf-mega-menu-panel{display:none!important}.wcf__nav-menu.mobile-menu-active .wcf-mega-menu.mobile-wp-submenu .sub-menu{display:block}.wcf__nav-menu.mobile-menu-active.mobile-menu-right .wcf-nav-menu-container{transform:translateX(100%);right:0}.wcf__nav-menu.mobile-menu-active.mobile-menu-left .wcf-nav-menu-container{transform:translateX(-100%);left:0}.wcf__nav-menu.desktop-menu-active{display:block}.wcf__nav-menu.desktop-menu-active .wcf-menu-close,.wcf__nav-menu.desktop-menu-active .wcf-menu-hamburger{display:none}.wcf__nav-menu.desktop-menu-active .wcf-menu-badge{display:block}.wcf__nav-menu.desktop-menu-active .wcf-nav-menu-nav{display:flex;flex-wrap:wrap;margin:0;padding:0}.wcf__nav-menu.desktop-menu-active .wcf-nav-menu-nav.menu-layout-vertical{flex-direction:column}.wcf__nav-menu.desktop-menu-active .wcf-nav-menu-nav.menu-layout-vertical .menu-item-has-children .sub-menu,.wcf__nav-menu.desktop-menu-active .wcf-nav-menu-nav.menu-layout-vertical .wcf-mega-menu .wcf-mega-menu-panel{left:100%;top:auto}.wcf__nav-menu.desktop-menu-active .menu-item{list-style:none;position:relative;white-space:nowrap}.wcf__nav-menu.desktop-menu-active .menu-item a{position:relative;text-decoration:none;display:flex;padding:.5em 1em;transition:.4s;color:#1c1d20;fill:#1c1d20}.wcf__nav-menu.desktop-menu-active .menu-item a:after{content:"";position:absolute;left:0;transition:transform .25s ease-out;transform:scaleX(0);transform-origin:bottom right;height:2px;width:100%;background-color:#3f444b;z-index:2}.wcf__nav-menu.desktop-menu-active .menu-item-has-children .sub-menu{position:absolute;top:100%;left:0;transform:translateY(-10px);background:#fff;transition:.3s;padding:0;margin:0;box-shadow:2px 2px 6px rgba(0,0,0,.2);min-width:12em;z-index:99;opacity:0;visibility:hidden}.wcf__nav-menu.desktop-menu-active .menu-item-has-children .sub-menu a{border-top:solid 1px #dee1e7}.wcf__nav-menu.desktop-menu-active .menu-item-has-children .sub-menu .sub-menu{top:0;left:100%}.wcf__nav-menu.desktop-menu-active .menu-item-has-children:not(.wcf-mega-menu):hover>.sub-menu{transform:translateY(0);opacity:1;visibility:visible}.wcf__nav-menu.desktop-menu-active .wcf-mega-menu.mega-position-static{position:static!important}.wcf__nav-menu.desktop-menu-active .wcf-mega-menu .wcf-mega-menu-panel{position:absolute;top:100%;left:0;transform:translateY(-10px);transition:.3s;padding:0;margin:0;min-width:12em;z-index:99;opacity:0;visibility:hidden}.wcf__nav-menu.desktop-menu-active .wcf-mega-menu:hover>.wcf-mega-menu-panel{transform:translateY(0);opacity:1;visibility:visible}.wcf__nav-menu.desktop-menu-active.hover-pointer-dot a:after{width:6px;height:6px;border-radius:100px;bottom:0;left:50%;transform:translateX(-50%) scale(0);transform-origin:center}.wcf__nav-menu.desktop-menu-active.hover-pointer-dot a:hover:after{transform:translateX(-50%) scale(1)}.wcf__nav-menu.desktop-menu-active.hover-pointer-underline a:after{bottom:0}.wcf__nav-menu.desktop-menu-active.hover-pointer-underline a:hover:after{transform:scaleX(1);transform-origin:bottom left}.wcf__nav-menu.desktop-menu-active.hover-pointer-overline a:after{top:0}.wcf__nav-menu.desktop-menu-active.hover-pointer-overline a:hover:after{transform:scaleX(1);transform-origin:bottom left}.wcf__nav-menu.desktop-menu-active.hover-pointer-line-through a:after{top:50%;transform:translateY(-50%) scaleX(0)}.wcf__nav-menu.desktop-menu-active.hover-pointer-line-through a:hover:after{transform:translateY(-50%) scaleX(1);transform-origin:bottom left}.wcf__nav-menu.desktop-menu-active.hover-pointer-flip a .menu-text{position:relative;transition:transform .3s;transform-origin:50% 0;transform-style:preserve-3d}.wcf__nav-menu.desktop-menu-active.hover-pointer-flip a .menu-text:before{position:absolute;top:100%;left:0;width:100%;height:100%;content:attr(data-text);transform:rotateX(-90deg);transform-origin:50% 0;text-align:center}.wcf__nav-menu.desktop-menu-active.hover-pointer-flip a:hover .menu-text{transform:rotateX(90deg) translateY(-12px)}
        </style>
        <div class="mobile-sub-back" style="display: none">
			<?php Icons_Manager::render_icon( $settings['back_icon'], [ 'aria-hidden' => 'true' ] ); ?>
			<?php esc_html_e( 'Back', 'animation-addons-for-elementor' ) ?>
        </div>

        <div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
            <button class="wcf-menu-hamburger" type="button" aria-label="hamburger-icon">
	            <?php Icons_Manager::render_icon( $settings['hamburger_icon'], [ 'aria-hidden' => 'true' ] ); ?>
            </button>
			<?php wp_nav_menu( $arg ); ?>
            <div class="wcf-menu-overlay"></div>
		</div>
		<?php if ( ! empty( $settings['mobile_menu_breakpoint'] ) && 'all' !== $settings['mobile_menu_breakpoint'] ): ?>
            <script type="text/javascript">
				<?php $breakpoint = Plugin::$instance->breakpoints->get_active_breakpoints()[ $settings['mobile_menu_breakpoint'] ]->get_value(); ?>
                (function () {
                    const windowWidth = window.innerWidth;
                    const menu = document.querySelector('[data-id="<?php echo esc_attr( $this->get_id() ) ?>"] .wcf__nav-menu');

                    //desktop menu active
                    if (windowWidth > <?php echo esc_attr( $breakpoint ) ?>) {
                        menu.classList.remove('mobile-menu-active');
                        menu.classList.add('desktop-menu-active');
                    }
                })();
            </script>
		<?php endif; ?>

		<?php
	}
}
