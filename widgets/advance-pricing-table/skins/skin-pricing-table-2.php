<?php
namespace WCF_ADDONS\Widgets\Skin;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Skin_Pricing_Table_2 extends Skin_Pricing_Table_Base {

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
		return 'skin-pricing-table-2';
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
		return __( 'Style 2', 'animation-addons-for-elementor-pro' );
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

		// Layout
		$this->start_controls_section(
			'section_pt_layout_2',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor-pro' ),
			]
		);

		$this->add_control(
			'pt_items_order_heading',
			[
				'label' => esc_html__( 'Items Order', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'title_order',
			[
				'label' => esc_html__( 'Title', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'default' => 1,
				'selectors' => [
					'{{WRAPPER}} .title-wrap' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'subtitle_order',
			[
				'label' => esc_html__( 'Sub Title', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'default' => 2,
				'selectors' => [
					'{{WRAPPER}} .sub-title' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'price_order',
			[
				'label' => esc_html__( 'Price', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'default' => 3,
				'selectors' => [
					'{{WRAPPER}} .price-wrap' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'feature_order',
			[
				'label' => esc_html__( 'Features', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'default' => 4,
				'selectors' => [
					'{{WRAPPER}} .pt-feature' => 'order: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_order',
			[
				'label' => esc_html__( 'Button', 'animation-addons-for-elementor-pro' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 5,
				'default' => 5,
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
			<?php
			$this->render_title();
			$this->render_sub_title();
			?>

            <div class="price-wrap">
		        <?php
		        $this->render_original_price();
		        ?>

                <span class="pt-currency"><?php $this->render_currency_symbol(); ?></span>
		        <?php $this->render_price(); ?>

		        <?php
		        $this->render_period();
		        ?>
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
