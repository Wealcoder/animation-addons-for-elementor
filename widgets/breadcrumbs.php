<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;
use WPSEO_Breadcrumbs;

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
		return ['animation-addons-for-elementor'];
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

		$this->start_controls_section(
			'section_breadcrumbs_content',
			[
				'label' => esc_html__('Breadcrumbs', 'animation-addons-for-elementor'),
			]
		);

		if (! class_exists('\WPSEO_Breadcrumbs')) {

			$this->add_control(
				'warning_text',
				[
					'type'       => Controls_Manager::ALERT,
					'alert_type' => 'warning',
					'content'    => __('<strong>Yoast SEO</strong> is not installed/activated on your site. Please install and activate <a href="plugin-install.php?s=wordpress-seo&tab=search&type=term" target="_blank">Yoast SEO</a> first.', 'animation-addons-for-elementor'),
				]
			);

			$this->end_controls_section();

			return;
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

		$this->add_control(
			'html_description',
			[
				'raw'             => sprintf(
					/* translators: 1: Link opening tag, 2: Link closing tag. */
					esc_html__('Additional settings are available in the Yoast SEO %1$sBreadcrumbs Panel%2$s', 'animation-addons-for-elementor'),
					sprintf('<a href="%s" target="_blank">', admin_url('admin.php?page=wpseo_titles#top#breadcrumbs')),
					'</a>'
				),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->end_controls_section();

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

		$this->end_controls_section();
	}

	private function get_html_tag()
	{
		$html_tag = $this->get_settings('html_tag');

		if (empty($html_tag)) {
			$html_tag = 'p';
		}

		return Utils::validate_html_tag($html_tag);
	}

	protected function render()
	{
		if (class_exists('\WPSEO_Breadcrumbs')) {
			$html_tag = $this->get_html_tag();
			WPSEO_Breadcrumbs::breadcrumb('<' . $html_tag . ' id="breadcrumbs">', '</' . $html_tag . '>');
		}
	}
}
