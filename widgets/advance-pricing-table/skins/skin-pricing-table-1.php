<?php

namespace WCF_ADDONS\Widgets\Skin;

use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Skin_Pricing_Table_1 extends Skin_Pricing_Table_Base {

	/**
	 * Get skin ID.
	 *
	 * Retrieve the skin ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @abstract
	 */
	public function get_id() {
		return 'skin-pricing-table-1';
	}

	/**
	 * Get skin title.
	 *
	 * Retrieve the skin title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @abstract
	 */
	public function get_title() {
		return __( 'Style 1', 'animation-addons-for-elementor-pro' );
	}

	/**
	 * Register skin controls actions.
	 *
	 * Run on init and used to register new skins to be injected to the widget.
	 * This method is used to register new actions that specify the location of
	 * the skin in the widget.
	 *
	 * Example usage:
	 * `add_action( 'elementor/element/{widget_id}/{section_id}/before_section_end', [ $this, 'register_controls' ] );`
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _register_controls_actions() {
		parent::_register_controls_actions();

		add_action( 'elementor/element/wcf--a-pricing-table/section_header/before_section_end', [
			$this,
			'inject_header_controls'
		] );

		add_action( 'elementor/element/wcf--a-pricing-table/section_header_style/before_section_end', [
			$this,
			'injects_header_style'
		] );

	}

	public function inject_header_controls() {
		$this->parent->start_injection( [
			'at' => 'before',
			'of' => 'title',
		] );

		$this->add_control(
			'pt-icon',
			[
				'label' => esc_html__( 'Icon', 'animation-addons-for-elementor-pro' ),
				'default'          => [
					'value'   => 'far fa-paper-plane',
					'library' => 'fa-solid',
				],
				'type'  => Controls_Manager::ICONS,
			]
		);

		$this->parent->end_injection();
	}

	public function injects_header_style() {
		$this->parent->start_injection( [
			'at' => 'after',
			'of' => 'subtitle_typography',
		] );

		$this->add_control(
			'heading_icon_style',
			[
				'label'     => esc_html__( 'Icon', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'hading_icon_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .skin-pricing-table-1 .icon i'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .skin-pricing-table-1 .icon svg' => 'fill: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'hading_icon_size',
			[
				'label'     => esc_html__( 'Size', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .skin-pricing-table-1 .icon' => 'font-size: {{SIZE}}px;',
				],
			]
		);

		$this->add_responsive_control(
			'heading_icon_padding',
			[
				'label' => esc_html__( 'Margin', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors' => [
					'{{WRAPPER}} .skin-pricing-table-1 .icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'header_icon_align',
			[
				'label' => esc_html__( 'Alignment', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor-pro' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor-pro' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor-pro' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}} .skin-pricing-table-1 .icon' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_bg_color',
			[
				'label'     => esc_html__( 'Header Top Background', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}} .skin-pricing-table-1 .header-top' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->parent->end_injection();
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
	public function register_controls( Widget_Base $widget ) {
		$this->parent = $widget;

		$this->start_controls_section(
			'section_pt_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor-pro' ),
			]
		);
		// Items Order
		$this->add_control(
			'pt_items_order_heading',
			[
				'label' => esc_html__( 'Items Order', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'header_price_order',
			[
				'label' => esc_html__( 'Header & Pricing', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 3,
				'default' => 1,
				'selectors' => [
					'{{WRAPPER}} .pt-header' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'feature_list_order',
			[
				'label' => esc_html__( 'Feature', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 3,
				'default' => 2,
				'selectors' => [
					'{{WRAPPER}} .pt-feature' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'btn_order',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 3,
				'default'   => 3,
				'selectors' => [
					'{{WRAPPER}} .wcf__btn' => 'order: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render button widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function render() {

		$this->parent->add_render_attribute( 'wrapper', 'class', 'wcf__priceTable '. $this->get_id() );

		?>
        <div <?php $this->parent->print_render_attribute_string( 'wrapper' ); ?>>
            <div class="pt-header">
                <div class="header-top">
                    <div class="icon"><span>
                        <?php Icons_Manager::render_icon( $this->get_instance_value( 'pt-icon' ), [ 'aria-hidden' => 'true' ] ); ?>
                    </span></div>
		            <?php $this->render_title(); ?>
		            <?php $this->render_sub_title(); ?>
                </div>
                <div class="price-wrap">
		            <?php
		            $this->render_original_price();
		            $this->render_price();
		            ?>
                    <span class="pt-currency"><?php $this->render_currency_symbol(); ?></span>
		            <?php
		            $this->render_period();
		            ?>
                </div>
            </div>
			<?php
			$this->render_feature_list();
			$this->render_button();
			$this->render_ribbon();
			?>
        </div>
		<?php
	}

}
