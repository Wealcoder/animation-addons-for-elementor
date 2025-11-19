<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Toggle Switcher
 *
 * Elementor widget for toggle.
 *
 * @since 1.0.0
 */
class Toggle_Switcher extends Widget_Base {


	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--toggle-switch';
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
		return esc_html__( 'Toggle Switch', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-t-letter';
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
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'wcf--toggle-switch' );
	}

	public function get_script_depends() {
		return array( 'aae--switcher-toggle' );
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
			'section_toggle_switch',
			array(
				'label' => esc_html__( 'Toggle Switch', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'element_list',
			array(
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => array(
					'1' => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'switch_title',
			array(
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Monthly', 'animation-addons-for-elementor' ),
			)
		);

		$repeater->add_control(
			'content_type',
			array(
				'label'   => esc_html__( 'Content Type', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'content'  => esc_html__( 'Content', 'animation-addons-for-elementor' ),
					'template' => esc_html__( 'Saved Templates', 'animation-addons-for-elementor' ),
				),
				'default' => 'content',
			)
		);

		$repeater->add_control(
			'elementor_templates',
			array(
				'label'       => esc_html__( 'Save Template', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => false,
				'multiple'    => false,
				'options'     => wcf_addons_get_saved_template_list(),
				'condition'   => array(
					'content_type' => 'template',
				),
			)
		);

		$repeater->add_control(
			'switch_content',
			array(
				'label'     => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'default'   => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::WYSIWYG,
				'condition' => array(
					'content_type' => 'content',
				),
			)
		);

		$this->add_control(
			'toggle_switcher',
			array(
				'label'        => esc_html__( 'Toggle Switcher', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::REPEATER,
				'fields'       => $repeater->get_controls(),
				'item_actions' => array(
					'add'       => false,
					'duplicate' => false,
					'remove'    => false,
					'sort'      => true,
				),
				'default'      => array(
					array( 'switch_title' => 'Monthly' ),
					array( 'switch_title' => 'Yearly' ),
				),
				'title_field'  => '{{{ switch_title }}}',
			)
		);

		$this->add_responsive_control(
			'toggle_gap',
			array(
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'separator'  => 'before',
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'toggle_bottom_space',
			array(
				'label'      => esc_html__( 'Bottom Space', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'separator'  => 'before',
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
					'%'  => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// switcher wrapper
		$this->start_controls_section(
			'section_style_switcher_wrap',
			array(
				'label' => __( 'Switcher Wrapper', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'switcher_wrap_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .slide-toggle-wrapper',
			)
		);

		$this->add_responsive_control(
			'switcher_wrap_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'switcher_wrap_border',
				'selector' => '{{WRAPPER}} .slide-toggle-wrapper',
			)
		);

		$this->add_control(
			'switcher_wrap_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// switcher.
		$this->start_controls_section(
			'section_style_Switcher',
			array(
				'label'     => __( 'Switcher', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'element_list' => '1' ),
			)
		);

		$this->add_responsive_control(
			'switcher_width',
			array(
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 40,
				),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => '--switcher-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .switcher'             => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'switcher_height',
			array(
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'selectors'  => array(
					'{{WRAPPER}} .switcher' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'switcher_background',
			array(
				'label'     => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .switcher' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'switcher_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .switcher' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'switcher_active_background',
			array(
				'label'     => esc_html__( 'Active Background', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} input:checked+.switcher' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'indicator_heading',
			array(
				'label'     => esc_html__( 'Indicator', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'switcher_indicator_width',
			array(
				'label'      => esc_html__( 'Indicator Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => '--switcher-indicator-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'switcher_indicator_space',
			array(
				'label'      => esc_html__( 'Indicator Space', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .slide-toggle-wrapper' => '--switcher-border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'switcher_indicator_background',
			array(
				'label'     => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .switcher::before' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'switcher_indicator_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .switcher::before' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// title.
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => __( 'Title', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .before_label, {{WRAPPER}} .after_label',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .before_label, {{WRAPPER}} .after_label' => 'color: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_active_title',
			array(
				'label' => __( 'Active Title', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_active_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .before_label.active, {{WRAPPER}} .after_label.active' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'      => 'active_title_background',
				'types'     => array( 'classic', 'gradient' ),
				'selector'  => '{{WRAPPER}} .before_label::after, {{WRAPPER}} .after_label::after',
				'condition' => array( 'element_list' => '2' ),
			)
		);

		$this->add_control(
			'active_title_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .before_label, {{WRAPPER}} .after_label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array( 'element_list' => '2' ),
			)
		);

		$this->add_control(
			'active_title_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .before_label::after, {{WRAPPER}} .after_label::after' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array( 'element_list' => '2' ),
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
		$switcher = $settings['toggle_switcher'];

		$this->add_render_attribute(
			'wrapper',
			'class',
			array(
				'wcf__toggle_switcher',
				'style-' . $settings['element_list'],
			)
		);
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>

			<div class="slide-toggle-wrapper">
				<label for="view-<?php echo esc_attr( $this->get_id() ); ?>" class="before_label active">
					<?php echo esc_html( $switcher[0]['switch_title'] ); ?>
				</label>
				<input type="checkbox" id="view-<?php echo esc_attr( $this->get_id() ); ?>">
				<label for="view-<?php echo esc_attr( $this->get_id() ); ?>" class="switcher"></label>
				<label for="view-<?php echo esc_attr( $this->get_id() ); ?>" class="after_label">
					<?php echo esc_html( $switcher[1]['switch_title'] ); ?>
				</label>
			</div>

			<div class="toggle-content">
				<?php
				foreach ( $switcher as $index => $item ) {
					?>
					<div class="toggle-pane <?php echo esc_attr( 0 === $index ? 'show' : '' ); ?>">
						<?php
						if ( 'content' === $item['content_type'] ) {
							$this->print_text_editor( $item['switch_content'] );
						} elseif ( ! empty( $item['elementor_templates'] ) ) {
								echo Plugin::$instance->frontend->get_builder_content( $item['elementor_templates'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						}
						?>
					</div>
					<?php
				}
				?>
			</div>

		</div>
		<?php
	}
}
