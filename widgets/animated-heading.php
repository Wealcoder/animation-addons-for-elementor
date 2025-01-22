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
		return [ 'weal-coder-addon' ];
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
		return [ 'wcf--animated-heading' ];
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
			[
				'label' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'heading',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'heading_tag',
			[
				'label'   => esc_html__( 'HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
				'default' => 'h3',
			]
		);

		$this->add_control(
			'heading_link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [
					'url'         => '',
					'is_external' => true,
					'nofollow'    => true,
				],
				'label_block' => false,
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
				'default'   => '',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();


		// Style
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Heading', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label'     => esc_html__( 'Start Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'render_type' => 'template',
				'selectors' => [
					'{{WRAPPER}} .animated--heading' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'heading_typo',
				'selector' => '{{WRAPPER}} .animated--heading',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'heading_color',
			[
				'label' => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'  => Controls_Manager::COLOR,
			]
		);

		$this->add_control(
			'heading_colors',
			[
				'label'       => esc_html__( 'Animation Colors', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'heading_color' => esc_html__( '#F9D371', 'animation-addons-for-elementor' ),
					],
					[
						'heading_color' => esc_html__( '#F47340', 'animation-addons-for-elementor' ),
					],
					[
						'heading_color' => esc_html__( '#EF2F88', 'animation-addons-for-elementor' ),
					],
					[
						'heading_color' => esc_html__( '#8843F2', 'animation-addons-for-elementor' ),
					],
				],
				'title_field' => '{{{ heading_color }}}',
			]
		);

		$this->add_control(
			'heading_color_end',
			[
				'label'   => esc_html__( 'End Color', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#c9f31d',
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

		$colors = [];
		if ( $settings['heading_colors'] ) {
			foreach ( $settings['heading_colors'] as $color ) {
				$colors[] = $color['heading_color'];
			}
		}

		$this->add_render_attribute( 'wrapper', array(
			'class'          => 'animated--heading',
			'data-colors'    => wp_json_encode( $colors ),
			'data-color-end' => $settings['heading_color_end'],
		) );

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
