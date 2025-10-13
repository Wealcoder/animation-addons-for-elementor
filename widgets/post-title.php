<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Post_Title extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--post--title';
	}

	public function get_title() {
		return esc_html__( 'Post Title', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-post-title';
	}

	public function show_in_panel() {
		// By default don't show.
		return true;
	}

	public function get_categories() {
		return array( 'wcf-single-addon' );
	}

	public function get_keywords() {
		return array( 'post title', 'title', 'post header' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'current_link',
			array(
				'label'        => esc_html__( 'Current Link', 'animation-addons-for-elementor' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Off', 'animation-addons-for-elementor' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'link',
			array(
				'label'     => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::URL,
				'dynamic'   => array(
					'active' => true,
				),
				'default'   => array(
					'url' => '',
				),
				'condition' => array(
					'current_link!' => 'yes',
				),
			)
		);

		$this->add_control(
			'header_size',
			array(
				'label'   => esc_html__( 'HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				),
				'default' => 'h1',
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
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				),
			)
		);

		// Word Trimming Controls
		$this->add_control(
			'enable_word_trim',
			array(
				'label'        => esc_html__( 'Trim Words', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'separator'    => 'before',
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'word_count',
			array(
				'label'     => esc_html__( 'Word Count', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 10,
				'min'       => 1,
				'max'       => 100,
				'condition' => array(
					'enable_word_trim' => 'yes',
				),
			)
		);

		$this->add_control(
			'ellipsis_type',
			array(
				'label'     => esc_html__( 'Ellipsis Type', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'dots',
				'options'   => array(
					'dots' => esc_html__( 'Dots (...)', 'animation-addons-for-elementor' ),
					'text' => esc_html__( 'Custom Text', 'animation-addons-for-elementor' ),
					'icon' => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
					'none' => esc_html__( 'None', 'animation-addons-for-elementor' ),
				),
				'condition' => array(
					'enable_word_trim' => 'yes',
				),
			)
		);

		$this->add_control(
			'ellipsis_text',
			array(
				'label'     => esc_html__( 'Custom Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '...',
				'condition' => array(
					'enable_word_trim' => 'yes',
					'ellipsis_type'    => 'text',
				),
			)
		);

		$this->add_control(
			'ellipsis_icon',
			array(
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-ellipsis-h',
					'library' => 'solid',
				),
				'condition' => array(
					'enable_word_trim' => 'yes',
					'ellipsis_type'    => 'icon',
				),
			)
		);

		$this->add_control(
			'show_title_highlight',
			array(
				'label'              => esc_html__( 'Show Highlight', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SWITCHER,
				'separator'          => 'before',
				'label_on'           => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'          => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value'       => 'yes',
				'frontend_available' => true,
			)
		);

		$this->add_control(
			'highlight_title_length',
			array(
				'label'              => esc_html__( 'Highlight Length', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::NUMBER,
				'default'            => 5,
				'min'                => 2,
				'max'                => 100,
				'condition'          => array(
					'show_title_highlight' => 'yes',
				),
				'frontend_available' => true,
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
					'{{WRAPPER}} .wcf--title'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .wcf--title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => esc_html__( 'Hover Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--title:hover'   => 'color: {{VALUE}};',
					'{{WRAPPER}} .wcf--title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .wcf--title',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			array(
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .wcf--title',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .wcf--title',
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
					'{{WRAPPER}} .wcf--title' => 'mix-blend-mode: {{VALUE}}',
				),
				'separator' => 'none',
			)
		);

		// Ellipsis Style Controls
		$this->add_control(
			'heading_ellipsis',
			array(
				'label'     => esc_html__( 'Ellipsis Style', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'enable_word_trim' => 'yes',
					'ellipsis_type!'   => 'none',
				),
			)
		);

		$this->add_control(
			'ellipsis_color',
			array(
				'label'     => esc_html__( 'Ellipsis Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--title .wcf-ellipsis' => 'color: {{VALUE}};',
				),
				'condition' => array(
					'enable_word_trim' => 'yes',
					'ellipsis_type!'   => 'none',
				),
			)
		);

		$this->add_control(
			'ellipsis_size',
			array(
				'label'     => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 6,
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf--title .wcf-ellipsis i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf--title .wcf-ellipsis svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition' => array(
					'enable_word_trim' => 'yes',
					'ellipsis_type'    => 'icon',
				),
			)
		);

		$this->add_control(
			'ellipsis_spacing',
			array(
				'label'     => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'default'   => array(
					'size' => 5,
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf--title .wcf-ellipsis' => 'margin-left: {{SIZE}}{{UNIT}};',
				),
				'condition' => array(
					'enable_word_trim' => 'yes',
					'ellipsis_type!'   => 'none',
				),
			)
		);

		$this->add_control(
			'heading_highlight',
			array(
				'label'     => esc_html__( 'Highlight Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'show_title_highlight' => 'yes',
				),
			)
		);

		$this->add_control(
			'title_h_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf--title .highlight' => 'color: {{VALUE}};',
				),
				'condition' => array(
					'show_title_highlight' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'title_h_typography',
				'selector'  => '{{WRAPPER}} .wcf--title .highlight',
				'condition' => array(
					'show_title_highlight' => 'yes',
				),
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
		$post_id   = get_the_id();
		$title     = get_the_title( $post_id );
		$permalink = get_the_permalink( $post_id );

		if ( '' === $title ) {
			return;
		}

		// Apply word trimming if enabled
		$original_title = $title;
		$ellipsis_html  = '';

		if ( 'yes' === $settings['enable_word_trim'] ) {
			$word_count = ! empty( $settings['word_count'] ) ? absint( $settings['word_count'] ) : 10;

			// Get the ellipsis based on type
			$ellipsis = $this->get_ellipsis_html( $settings );

			// Trim the title
			$title = wp_trim_words( $title, $word_count, '' );

			// Check if title was actually trimmed
			if ( $title !== $original_title && ! empty( $ellipsis ) ) {
				$ellipsis_html = $ellipsis;
			}
		}

		// Apply highlight if enabled
		if ( 'yes' === $settings['show_title_highlight'] ) {
			$highlight_title_length = (int) $settings['highlight_title_length'];
			$title                  = $this->wcf_wrap_first_n_words( $title, $highlight_title_length );
		}

		// Add ellipsis to the title
		if ( ! empty( $ellipsis_html ) ) {
			$title .= $ellipsis_html;
		}

		// Add link if needed
		if ( ! empty( $settings['link']['url'] ) ) {
			$this->add_link_attributes( 'url', $settings['link'] );
			$title = sprintf( '<a %1$s>%2$s</a>', $this->get_render_attribute_string( 'url' ), $title );
		} elseif ( $settings['current_link'] ) {
			$this->add_link_attributes(
				'url',
				array(
					'url'         => $permalink,
					'is_external' => false,
					'nofollow'    => false,
				)
			);
			$title = sprintf( '<a %1$s>%2$s</a>', $this->get_render_attribute_string( 'url' ), $title );
		}

		$this->add_render_attribute( 'title', 'class', 'wcf--title' );

		$title_html = sprintf( '<%1$s %2$s>%3$s</%1$s>', Utils::validate_html_tag( $settings['header_size'] ), $this->get_render_attribute_string( 'title' ), $title );

		// PHPCS - the variable $title_html holds safe data.
		echo wp_kses_post( $title_html );

		Plugin::$instance->db->restore_current_post();
	}

	/**
	 * Get ellipsis HTML based on settings.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function get_ellipsis_html( $settings ) {
		$ellipsis_type = ! empty( $settings['ellipsis_type'] ) ? $settings['ellipsis_type'] : 'dots';

		switch ( $ellipsis_type ) {
			case 'dots':
				return '<span class="wcf-ellipsis">...</span>';

			case 'text':
				$text = ! empty( $settings['ellipsis_text'] ) ? $settings['ellipsis_text'] : '...';
				return '<span class="wcf-ellipsis">' . esc_html( $text ) . '</span>';

			case 'icon':
				if ( ! empty( $settings['ellipsis_icon'] ) ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon(
						$settings['ellipsis_icon'],
						array(
							'aria-hidden' => 'true',
							'class'       => 'wcf-ellipsis',
						)
					);
					return ob_get_clean();
				}
				return '';

			case 'none':
			default:
				return '';
		}
	}

	/**
	 * Wrap the first N words of a string with a span tag.
	 *
	 * @param string $text The text to wrap.
	 * @param int    $n The number of words to wrap.
	 * @param string $class_name string The class to apply to the span tag.
	 *
	 * @return string
	 */
	private function wcf_wrap_first_n_words( $text, $n, $class_name = 'highlight' ) {
		// Handle plain text (no HTML tags)
		$words = preg_split( '/\s+/', strip_tags( $text ) );

		if ( count( $words ) <= $n ) {
			return '<span class="' . esc_attr( $class_name ) . '">' . esc_html( $text ) . '</span>';
		}

		$wrapped_words   = array_slice( $words, 0, $n );
		$remaining_words = array_slice( $words, $n );

		return '<span class="' . esc_attr( $class_name ) . '">' . esc_html( implode( ' ', $wrapped_words ) ) . '</span> ' . esc_html( implode( ' ', $remaining_words ) );
	}
}