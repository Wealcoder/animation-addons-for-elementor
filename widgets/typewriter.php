<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Typewriter
 *
 * Elementor widget for typewriter.
 *
 * @since 1.0.0
 */
class Typewriter extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--typewriter';
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
		return esc_html__( 'Typewriter', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-animation-text';
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
		return [ 'wcf--typewriter' ];
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
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'typewriter_normal_text',
			[
				'label'   => esc_html__( 'Non-Animated Text', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'ai'      => [
					'type' => 'text',
				],
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'A Web', 'animation-addons-for-elementor' ),
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'list_text',
			[
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Designer', 'animation-addons-for-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'typewriter_animated_text',
			[
				'label'       => esc_html__( 'Animated Text List', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[
						'list_text' => esc_html__( 'Designer', 'animation-addons-for-elementor' ),
					],
					[
						'list_text' => esc_html__( 'Developer', 'animation-addons-for-elementor' ),
					],
					[
						'list_text' => esc_html__( 'programmer', 'animation-addons-for-elementor' ),
					],
				],
				'title_field' => '{{{ list_text }}}', //phpcs:ignore
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => esc_html__( 'HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
					'p'   => 'p',
				],
				'default' => 'h2',
			]
		);

		$this->add_responsive_control(
			'align',
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
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'typewriter_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .typed_title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .typed_list' => '--line-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .typed_title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .typed_title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .typed_title',
			]
		);

		$this->add_control(
			'line_color',
			[
				'label'     => esc_html__( 'Line Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .typed_list' => '--line-color: {{VALUE}};',
				],
                'separator' => 'before',
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

		if ( empty( $settings['typewriter_normal_text'] ) && empty( $settings['typewriter_animated_text'] ) ) {
			return;
		}

		$this->add_render_attribute( 'typewriter-attr', 'class', 'wcf--typewriter' );


		$typewriter_text = '';

		if ( ! empty( $settings['typewriter_normal_text'] ) ) {
			$typewriter_text = $settings['typewriter_normal_text'];
		}

		$typewriter_animated_text = array();
		if ( ! empty( $settings['typewriter_animated_text'] ) ) {
			foreach ( $settings['typewriter_animated_text'] as $animated_text ) {
				$typewriter_animated_text[] = $animated_text['list_text']; // Add each list_text to the array
			}
		}

		?>

        <style>
            @keyframes typeWriterLine {
                from {
                    border-color: var(--line-color, #000);
                }
                to {
                    border-color: transparent;
                }
            }

            .wcf--typewriter .typed_list {
                display: inline-block;
                border-right: 3px solid var(--line-color, #000);
                animation: typeWriterLine 1s infinite;
            }
        </style>

        <div <?php $this->print_render_attribute_string( 'typewriter-attr' ); ?>>
            <<?php Utils::print_validated_html_tag( $settings['html_tag'] ); ?> class="typed_title">
                <?php echo wp_kses_post( $typewriter_text ); ?>
                <span class="typed_list" data-text='<?php echo json_encode($typewriter_animated_text); ?>'></span>
            </<?php Utils::print_validated_html_tag( $settings['html_tag'] ); ?>>
        </div>

		<?php
	}
}
