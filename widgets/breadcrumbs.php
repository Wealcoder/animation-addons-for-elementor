<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Breadcrumbs extends Widget_Base
{

	public function get_name()
	{
		return 'wcf--breadcrumbs';
	}

	public function get_title()
	{
		return esc_html__('Breadcrumbs', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'wcf eicon-yoast';
	}

	public function get_categories()
	{
		return ['weal-coder-addon'];
	}

	public function get_script_depends()
	{
		return ['breadcrumbs'];
	}

	public function get_keywords()
	{
		return ['yoast', 'seo', 'breadcrumbs', 'internal links'];
	}

	protected function register_controls()
	{
		// Content Section
		$this->start_controls_section(
			'section_breadcrumbs_content',
			[
				'label' => esc_html__('Breadcrumbs', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'yoast_seo',
			[
				'label' => esc_html__('Enable Yoast', 'animation-addons-for-elementor'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Yes', 'animation-addons-for-elementor'),
				'label_off' => esc_html__('No', 'animation-addons-for-elementor'),
				'return_value' => 'yes',
				'default' => class_exists('\\WPSEO_Breadcrumbs') ? 'yes' : '',
			]
		);

		// Show warning only if Yoast is not installed but user tries to enable it
		if (! class_exists('\\WPSEO_Breadcrumbs')) {
			$this->add_control(
				'warning_text',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => __('<strong>Yoast SEO</strong> is not installed/activated on your site. Please install and activate <a href="plugin-install.php?s=wordpress-seo&tab=search&type=term" target="_blank" rel="noopener noreferrer">Yoast SEO</a> first.', 'animation-addons-for-elementor'),
					'content_classes' => 'elementor-descriptor',
					'condition'       => [
						'yoast_seo' => 'yes',
					],
				]
			);
		}

		$this->add_responsive_control(
			'align',
			[
				'label'        => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => [
					'left'   => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'prefix_class' => 'elementor%s-align-',
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => esc_html__('HTML Tag', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''     => esc_html__('Default', 'animation-addons-for-elementor'),
					'p'    => 'p',
					'div'  => 'div',
					'nav'  => 'nav',
					'span' => 'span',
				],
				'default' => '',
			]
		);

		$yoast_url = admin_url('admin.php?page=wpseo_titles#top#breadcrumbs');


		$desc_html = sprintf(
			// translators: 1: opening <a> tag linking to Yoast SEO Breadcrumbs settings; 2: closing </a> tag.
			__('Additional settings are available in the Yoast SEO %1$sBreadcrumbs Panel%2$s', 'animation-addons-for-elementor'),
			'<a href="' . esc_url($yoast_url) . '" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);

		$this->add_control(
			'html_description',
			[
				'raw'             => wp_kses_post($desc_html),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
				'condition'       => [
					'yoast_seo' => 'yes',
				],
			]
		);

		$this->add_control(
			'br_separator',
			[
				'label'       => esc_html__('Separator Text', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => ' &raquo; ',
				'placeholder' => esc_html__('Separator text', 'animation-addons-for-elementor'),
				'condition'   => [
					'yoast_seo!' => 'yes'
				]
			]
		);

		$url = 'https://www.toptal.com/designers/htmlarrows/symbols/';

		$link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url($url),
			esc_html__('HTML Symbols', 'animation-addons-for-elementor')
		);


		$desc_html = sprintf(
			// translators: %s is a clickable "HTML Symbols" link to an external reference page.
			__('You can use HTML entities as separators. Check out %s for examples.', 'animation-addons-for-elementor'),
			$link
		);

		$this->add_control(
			'sep_description',
			[
				'raw'             => wp_kses_post($desc_html),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
				'condition'       => [
					'yoast_seo!' => 'yes',
				],
			]
		);


		$this->end_controls_section();


		// Style Section
		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__('Breadcrumbs', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}}',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_SECONDARY,
				],
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__('Text Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}}' => 'color: {{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs('tabs_breadcrumbs_style');

		// Normal Tab
		$this->start_controls_tab(
			'tab_color_normal',
			[
				'label' => esc_html__('Normal', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'link_color',
			[
				'label'     => esc_html__('Link Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_color_hover',
			[
				'label' => esc_html__('Hover', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'link_hover_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}


	private function get_html_tag()
	{
		$html_tag = $this->get_settings('html_tag');

		if (empty($html_tag)) {
			$html_tag = 'div';
		}

		return Utils::validate_html_tag($html_tag);
	}

	protected function render()
	{
		$settings = $this->get_settings_for_display();
		$html_tag = $this->get_html_tag();

		if (class_exists('\\WPSEO_Breadcrumbs') && $settings['yoast_seo'] === 'yes') {
			call_user_func(['WPSEO_Breadcrumbs', 'breadcrumb'], '<' . $html_tag . ' id="breadcrumbs">', '</' . $html_tag . '>');
		} else {
			$separator = isset($settings['br_separator']) ? $settings['br_separator'] : ' &raquo; ';
			aae_addon_breadcrumbs($html_tag, $separator);
		}
	}
}