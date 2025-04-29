<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Floating Elements
 *
 * Elementor widget for title.
 *
 * @since 1.0.0
 */
class Floating_Elements extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name() {
		return 'wcf--floating-elements';
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
		return esc_html__( 'Floating Elements', 'animation-addons-for-elementor' );
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
		return 'wcf eicon-animation';
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
			'floating_elements_section',
			[
				'label' => esc_html__( 'WCF Floating Elements', 'animation-addons-for-elementor' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'floating_image',
			[
				'label'   => esc_html__( 'Choose Image', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_responsive_control(
			'floating_el_size',
			[
				'label'      => esc_html__( 'Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.floating-element' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$repeater->add_control(
			'floating_el-translate_popover_toggle',
			[
				'label' => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type' => Controls_Manager::POPOVER_TOGGLE,
			]
		);

		$repeater->start_popover();

		//Horizontal Orientation
		$repeater->add_control(
			'floating_el_offset_o_h',
			[
				'label'       => esc_html__( 'Horizontal Orientation', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'toggle'      => false,
				'default'     => 'start',
				'options'     => [
					'start' => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'end'   => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'render_type' => 'ui',
			]
		);

		$repeater->add_responsive_control(
			'floating_el_offset_x',
			[
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => - 1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.floating-element' => 'left: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'floating_el_offset_o_h' => 'start' ]
			]
		);

		$repeater->add_responsive_control(
			'floating_el_offset_x_end',
			[
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => - 1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.floating-element' => 'right: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'floating_el_offset_o_h' => 'end' ]
			]
		);

		//Vertical Orientation
		$repeater->add_control(
			'floating_el_offset_o_v',
			[
				'label'       => esc_html__( 'Vertical Orientation', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'toggle'      => false,
				'default'     => 'start',
				'options'     => [
					'start' => [
						'title' => esc_html__( 'Top', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-top',
					],
					'end'   => [
						'title' => esc_html__( 'Bottom', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'render_type' => 'ui',
			]
		);

		$repeater->add_responsive_control(
			'floating_el_offset_y',
			[
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => - 1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.floating-element' => 'top: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'floating_el_offset_o_v' => 'start' ]
			]
		);

		$repeater->add_responsive_control(
			'floating_el_offset_y_end',
			[
				'label'      => esc_html__( 'Offset', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min' => - 1000,
						'max' => 1000,
					],
					'%'  => [
						'min' => - 100,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.floating-element' => 'bottom: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [ 'floating_el_offset_o_v' => 'end' ]
			]
		);

		$repeater->end_popover();

		$repeater->add_control(
			'floating_el_z_index',
			[
				'label'     => esc_html__( 'Z-Index', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1,
				'separator' => 'after',
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.floating-element' => 'z-index: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'floating_el_live_anim',
			[
				'label'   => esc_html__( 'Live Animation', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''        => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'float'   => esc_html__( 'Float Y', 'animation-addons-for-elementor' ),
					'float-x' => esc_html__( 'Float X', 'animation-addons-for-elementor' ),
					'spin'    => esc_html__( 'Spin', 'animation-addons-for-elementor' ),
					'scale'   => esc_html__( 'Scale', 'animation-addons-for-elementor' ),
					'wiggle'  => esc_html__( 'Wiggle', 'animation-addons-for-elementor' ),
				],
			]
		);

		//smooth scroll animation
		$repeater->add_control(
			'floating_el_enable_scroll_smoother',
			[
				'label'        => esc_html__( 'Enable Scroll Smoother', 'animation-addons-for-elementor' ),
				'description'  => esc_html__( 'If you want to use scroll smooth, please enable global settings first', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'separator'    => 'before',
			]
		);

		$repeater->add_control(
			'floating_el_data_speed',
			[
				'label'     => esc_html__( 'Data Speed', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 0.9,
				'condition' => [ 'floating_el_enable_scroll_smoother' => 'yes' ],
			]
		);

		$repeater->add_control(
			'floating_el_data_lag',
			[
				'label'     => esc_html__( 'Data Lag', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 0.5,
				'condition' => [ 'floating_el_enable_scroll_smoother' => 'yes' ],
			]
		);

		//hide elements
		$repeater->add_control(
			'floating_el_responsive_description',
			[
				'raw'             => sprintf(
				/* translators: 1: Link open tag, 2: Link close tag. */
					esc_html__( 'Responsive visibility will take effect only on %1$s preview mode %2$s or live page, and not while editing in Elementor.', 'animation-addons-for-elementor' ),
					'<a href="javascript: $e.run( \'panel/close\' )">',
					'</a>'
				),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
				'separator' => 'before',
			]
		);

		$active_devices     = Plugin::$instance->breakpoints->get_active_devices_list( [ 'reverse' => true ] );
		$active_breakpoints = Plugin::$instance->breakpoints->get_active_breakpoints();

		foreach ( $active_devices as $breakpoint_key ) {
			$label = 'desktop' === $breakpoint_key ? esc_html__( 'Desktop', 'animation-addons-for-elementor' ) : $active_breakpoints[ $breakpoint_key ]->get_label();
			$repeater->add_control(
				'floating_el_hide_' . $breakpoint_key,
				[
					/* translators: %s: Device name. */
					'label'        => sprintf( esc_html__( 'Hide On %s', 'animation-addons-for-elementor' ), $label ),
					'type'         => Controls_Manager::SWITCHER,
					'default'      => '',
					'label_on'     => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
					'label_off'    => esc_html__( 'Show', 'animation-addons-for-elementor' ),
					'return_value' => 'elementor-hidden-' . $breakpoint_key,
				]
			);
		}

		$this->add_control(
			'wcf_floating_elements',
			[
				'label'   => esc_html__( 'Elements', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [ [] ]
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
		$active_devices     = Plugin::$instance->breakpoints->get_active_devices_list( [ 'reverse' => true ] );
		?>
		<div class="wcf--floating-elements">
			<?php
			foreach ( $settings['wcf_floating_elements'] as $index => $element ) {

				$item_key = 'item_' . $index;

				$this->add_render_attribute( $item_key, 'class', [
					'floating-element',
					'wcf-live-anim-' . $element['floating_el_live_anim'],
					'elementor-repeater-item-' . $element['_id'],
				] );

				foreach ( $active_devices as $breakpoint_key ) {
					$this->add_render_attribute( $item_key, 'class', $element[ 'floating_el_hide_' . $breakpoint_key ] );
				}


				//smooth scroll attr
				if ( ! empty( $element['floating_el_enable_scroll_smoother'] ) ) {

					$attributes = [];

					if ( ! empty( $element['floating_el_data_speed'] ) ) {
						$attributes['data-speed'] = $element['floating_el_data_speed'];
					}
					if ( ! empty( $element['floating_el_data_lag'] ) ) {
						$attributes['data-lag'] = $element['floating_el_data_lag'];
					}
					$this->add_render_attribute( $item_key, $attributes );
				}
				?>
                <div <?php $this->print_render_attribute_string( $item_key ); ?>>
					<?php
					$image_html = '<img src="' . esc_url( $element['floating_image']['url'] ) . '" alt="' . esc_attr( Control_Media::get_image_alt( $element['floating_image'] ) ) . '" />';
					echo wp_kses_post( $image_html );
                    ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}
