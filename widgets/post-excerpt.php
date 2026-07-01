<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Post_Excerpt extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--post--excerpt';
	}

	public function get_title() {
		return esc_html__( 'Post Excerpt', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-post-excerpt';
	}

	public function get_categories() {
		return array( 'wcf-single-addon' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => esc_html__( 'Limit', 'animation-addons-for-elementor' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 4,
				'max'     => 900,
				'step'    => 5,
				'default' => 30,
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'    => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => esc_html__( 'Justified', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'default'   => '',
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-excerpt' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-excerpt' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .wcf-post-excerpt',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			array(
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .wcf-post-excerpt',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .wcf-post-excerpt',
			)
		);

		$this->add_control(
			'blend_mode',
			array(
				'label'     => esc_html__( 'Blend Mode', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					''            => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity',
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-excerpt' => 'mix-blend-mode: {{VALUE}}',
				),
				'separator' => 'none',
			)
		);

		$this->end_controls_section();
	}

	protected function switch_post() {
		if ( 'wcf-addons-template' === get_post_type() ) {

			$recent_posts = wp_get_recent_posts(
				array(
					'numberposts' => 1,
					'post_status' => 'publish',
				)
			);

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			Plugin::$instance->db->switch_to_post( $post_id );
		}
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->switch_post();

		$post_id = get_the_id();

		if ( empty( $post_id ) ) {
			Plugin::$instance->db->restore_current_post();
			return;
		}

		$content = get_the_excerpt( $post_id );

		if ( empty( $content ) ) {
			Plugin::$instance->db->restore_current_post();
			return;
		}

		if ( ! empty( $settings['limit'] ) && $settings['limit'] > 0 ) {
			$content = wp_strip_all_tags( $content );
			if ( mb_strlen( $content ) > $settings['limit'] ) {
				$content = mb_substr( $content, 0, $settings['limit'] ) . '...';
			}
		}

		echo '<div class="wcf-post-excerpt">' . wp_kses_post( $content ) . '</div>';

		Plugin::$instance->db->restore_current_post();
	}
}
