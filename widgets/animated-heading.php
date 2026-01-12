<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Image
 *
 * Elementor widget for image.
 *
 * @since 1.0.0
 */
class Animated_Heading extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--animated-heading';
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
		return esc_html__( 'Animated Heading', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon() {
		return 'wcf eicon-heading';
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
	 */
	public function get_categories() {
		return array( 'weal-coder-addon' );
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
		return array( 'wcf--animated-heading' );
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
			'section_content',
			array(
				'label' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'heading',
			array(
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'heading_tag',
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
				'default' => 'h3',
			)
		);

		$this->add_control(
			'heading_link',
			array(
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'options'     => array( 'url', 'is_external', 'nofollow' ),
				'default'     => array(
					'url'         => '',
					'is_external' => true,
					'nofollow'    => true,
				),
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => false,
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
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
				'default'   => '',
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* ---------------- TRIGGER SETTINGS ---------------- */
		$this->start_controls_section(
			'section_trigger',
			[
				'label' => esc_html__( 'Animation Trigger', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'trigger_type',
			[
				'label'   => esc_html__( 'Trigger', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'viewport',
				'options' => [
					'viewport'       => esc_html__( 'On Viewport Enter', 'animation-addons-for-elementor' ),
					'page_load'      => esc_html__( 'On Page Load', 'animation-addons-for-elementor' ),
					'scroll' => esc_html__( 'OnScroll', 'animation-addons-for-elementor' ),
				],
			]
		);
		

		$this->add_control(
			'trigger_selector',
			[
				'label'       => esc_html__( 'Scroll Trigger Selector', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '.elementor-container',
				'description' => esc_html__( 'CSS selector that controls when the animation starts (e.g. .e-con, .elementor-container)', 'animation-addons-for-elementor' ),
				'default'     => '',
					'condition' => [
					'trigger_type' => 'scroll',
				],
			]
		);


		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_color_mode',
			[
				'label'   => esc_html__( 'Color Mode', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'gradient',
				'options' => [
					'gradient'      => esc_html__( 'Gradient', 'animation-addons-for-elementor' ),
					'gradient_loop' => esc_html__( 'Gradient Loop', 'animation-addons-for-elementor' ),
					'gradient_pingpong' => esc_html__( 'Gradient Ping Pong', 'animation-addons-for-elementor' ),
					'alternate'     => esc_html__( 'Alternate', 'animation-addons-for-elementor' ),
					'edge_fade'     => esc_html__( 'Edge Fade', 'animation-addons-for-elementor' ),
					'repeater'      => esc_html__( 'Custom Colors', 'animation-addons-for-elementor' ),
					'glitch_flash'      => esc_html__( 'Glitch Flash', 'animation-addons-for-elementor' ),
					'random_cycle'  => esc_html__( 'Random Cycle', 'animation-addons-for-elementor' ),
					'center_focus'  => esc_html__( 'Center Focus', 'animation-addons-for-elementor' ),
					'single'        => esc_html__( 'Single Color', 'animation-addons-for-elementor' ),
					'random'        => esc_html__( 'Random Colors', 'animation-addons-for-elementor' ),
					'wave'          => esc_html__( 'Wave Gradient', 'animation-addons-for-elementor' ),
					'hover_reset'   => esc_html__( 'Animate then Reset', 'animation-addons-for-elementor' ),
					'pulse'         => esc_html__( 'Pulse', 'animation-addons-for-elementor' ),
					'spectrum_rotate'         => esc_html__( 'Spectrum Rotate', 'animation-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'heading_color',
			array(
				'label'       => esc_html__( 'Start Color', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::COLOR,
				'render_type' => 'template',
				'selectors'   => array(
					'{{WRAPPER}} .animated--heading' => 'color: {{VALUE}}',
				),
				'condition' => [
					'heading_color_mode' => ['gradient','single','wave','hover_reset', 'gradient_loop','center_focus','edge_fade','pulse','gradient_pingpong'],
				],
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typo',
				'selector' => '{{WRAPPER}} .animated--heading',
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'heading_color',
			array(
				'label' => esc_html__( 'Start Color', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::COLOR,
				 
			)
		);

		$this->add_control(
			'heading_colors',
			array(
				'label'       => esc_html__( 'Animation Colors', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'heading_color' => esc_html__( '#F9D371', 'animation-addons-for-elementor' ),
					),
					array(
						'heading_color' => esc_html__( '#F47340', 'animation-addons-for-elementor' ),
					),
					array(
						'heading_color' => esc_html__( '#EF2F88', 'animation-addons-for-elementor' ),
					),
					array(
						'heading_color' => esc_html__( '#8843F2', 'animation-addons-for-elementor' ),
					),
				),
				'title_field' => '{{{ heading_color }}}',
				'condition' => [
					'heading_color_mode' => ['repeater','random','hover_reset','alternate','random_cycle','glitch_flash'],
				],
			)
		);

		$this->add_control(
			'heading_color_end',
			array(
				'label'   => esc_html__( 'End Color', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#c9f31d',
				 'condition' => [
					'heading_color_mode' => ['gradient','wave', 'gradient_loop','center_focus','edge_fade','pulse','gradient_pingpong'],
				],
			)
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

		$colors = array();
		if ( $settings['heading_colors'] ) {
			foreach ( $settings['heading_colors'] as $color ) {
				$colors[] = $color['heading_color'];
			}
		}

		$this->add_render_attribute(
			'wrapper',
			[
				'class'               => 'animated--heading',
				'data-colormode'     => esc_attr( $settings['heading_color_mode'] ),
				'data-colorstart'    => esc_attr( $settings['heading_color'] ?? '' ),
				'data-colorend'      => esc_attr( $settings['heading_color_end'] ?? '' ),
				'data-colors'          => esc_attr( wp_json_encode( $colors ) ),
				'data-trigger'        => esc_attr( $settings['trigger_type'] ),				
				'data-triggerselector' => esc_attr( $settings['trigger_selector'] ),
			]
		);

		?>
		<<?php Utils::print_validated_html_tag( $settings['heading_tag'] ); ?> <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
		<?php
		if ( ! empty( $settings['heading_link']['url'] ) ) {
			$this->add_link_attributes( 'heading_link', $settings['heading_link'] );
			?>
			<a <?php $this->print_render_attribute_string( 'heading_link' ); ?>>
				<?php echo esc_html( $settings['heading'] ); ?>
			</a>
			<?php
		} else {
			echo esc_html( $settings['heading'] );
		}
		?>
		</<?php Utils::print_validated_html_tag( $settings['heading_tag'] ); ?>>
		<?php
	}
}
