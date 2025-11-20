<?php

namespace WCF_ADDONS;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

trait WCF_Button_Trait {
	protected function register_button_content_controls( $default_value = array(), $conditions = array() ) {
		$default = array(
			'btn_text' => esc_html__( 'Click here', 'animation-addons-for-elementor' ),
		);

		$default = array_merge( $default, $default_value );

		$default_conditions = array(
			'btn_link' => true,
		);

		$default_conditions = array_merge( $default_conditions, $conditions );

		$this->add_control(
			'btn_element_list',
			array(
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default'   => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'square'    => esc_html__( 'Square', 'animation-addons-for-elementor' ),
					'underline' => esc_html__( 'Underline', 'animation-addons-for-elementor' ),
					'mask'      => esc_html__( 'Mask', 'animation-addons-for-elementor' ),
					'oval'      => esc_html__( 'Oval', 'animation-addons-for-elementor' ),
					'circle'    => esc_html__( 'Circle', 'animation-addons-for-elementor' ),
					'ellipse'   => esc_html__( 'Ellipse', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_control(
			'btn_hover_list',
			array(
				'label'     => esc_html__( 'Hover Style', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'hover-none',
				'options'   => array(
					'hover-none'      => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'hover-divide'    => esc_html__( 'Divided', 'animation-addons-for-elementor' ),
					'hover-cross'     => esc_html__( 'Cross', 'animation-addons-for-elementor' ),
					'hover-cropping'  => esc_html__( 'Cropping', 'animation-addons-for-elementor' ),
					'rollover-top'    => esc_html__( 'Rollover Top', 'animation-addons-for-elementor' ),
					'rollover-left'   => esc_html__( 'Rollover Left', 'animation-addons-for-elementor' ),
					'parallal-border' => esc_html__( 'Parallel Border', 'animation-addons-for-elementor' ),
					'rollover-cross'  => esc_html__( 'Rollover Cross', 'animation-addons-for-elementor' ),
				),
				'condition' => array(
					'btn_element_list' => array( 'default', 'primary', 'square' ),
				),
			)
		);

		$this->add_control(
			'btn_text',
			array(
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'default'     => $default['btn_text'],
				'placeholder' => $default['btn_text'],
			)
		);

		$this->add_control(
			'current_link',
			array(
				'label'        => esc_html__( 'Current Link', 'animation-addons-for-elementor' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Off', 'animation-addons-for-elementor' ),
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		if ( $default_conditions['btn_link'] ) {
			$this->add_control(
				'btn_link',
				array(
					'label'   => esc_html__( 'Link', 'animation-addons-for-elementor' ),
					'type'    => Controls_Manager::URL,
					'dynamic' => array(
						'active' => true,
					),
					'default' => array(
						'url' => '#',
					),
				)
			);
		}

		$this->add_control(
			'button_icon',
			array(
				'label'            => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin'             => 'inline',
				'label_block'      => false,
			)
		);

		$this->add_control(
			'button_icon_align',
			array(
				'label'   => esc_html__( 'Icon Position', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
					'left'  => esc_html__( 'Before', 'animation-addons-for-elementor' ),
					'right' => esc_html__( 'After', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_responsive_control(
			'button_icon_direction',
			array(
				'label'     => esc_html__( 'Direction', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'row'    => array(
						'title' => esc_html__( 'Row - horizontal', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-right',
					),
					'column' => array(
						'title' => esc_html__( 'Column - vertical', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-arrow-down',
					),
				),
				'toggle'    => true,
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_icon_indend',
			array(
				'label'     => esc_html__( 'Icon Spacing', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);
	}

	protected function register_button_style_controls() {
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .wcf__btn a',
			)
		);

		$this->add_responsive_control(
			'load_more_spacing',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pf-load-more' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'pagination_type' => 'load_more',
				),
			)
		);

		$this->add_responsive_control(
			'button_icon_size',
			array(
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 100,
						'step' => 1,
					),
					'%'  => array(
						'min' => 1,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf__btn a i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcf__btn a svg' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a' => 'fill: {{VALUE}}; color: {{VALUE}};',
					'{{WRAPPER}} .wcf__btn a.wcf-btn-underline:after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wcf__btn a.wcf-btn-mask:after' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'      => 'btn_background',
				'types'     => array( 'classic', 'gradient' ),
				'exclude'   => array( 'image' ),
				'selector'  => '{{WRAPPER}} .wcf__btn a:not(.wcf-btn-mask, .wcf-btn-ellipse), {{WRAPPER}} .wcf__btn a.wcf-btn-mask:after, {{WRAPPER}} .wcf__btn a.wcf-btn-ellipse:before',
				'condition' => array( 'btn_element_list!' => 'underline' ),
			)
		);

		$this->add_control(
			'ellipse_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a.wcf-btn-ellipse' => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'btn_element_list' => 'ellipse' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'button_text_hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a:hover, {{WRAPPER}} .wcf__btn a:focus' => 'color: {{VALUE}};fill: {{VALUE}};',
					'{{WRAPPER}} .wcf__btn a.wcf-btn-underline:hover:after'                  => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'      => 'btn_hover_background',
				'types'     => array( 'classic', 'gradient' ),
				'exclude'   => array( 'image' ),
				'selector'  => '{{WRAPPER}} .wcf__btn a:not(.wcf-btn-mask, .btn-item, .btn-parallal-border, .btn-rollover-cross, .wcf-btn-ellipse):after, {{WRAPPER}} .wcf__btn a.wcf-btn-mask, {{WRAPPER}} .wcf__btn .btn-hover-bgchange span, {{WRAPPER}} .wcf__btn .btn-rollover-cross:hover, {{WRAPPER}} .wcf__btn .btn-parallal-border:hover, {{WRAPPER}} .wcf__btn a.wcf-btn-ellipse:hover:before,{{WRAPPER}} .wcf__btn a.btn-hover-none:hover',
				'condition' => array( 'btn_element_list!' => 'underline' ),
			)
		);

		$this->add_control(
			'ellipse_border_hover_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a.wcf-btn-ellipse:hover' => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'btn_element_list' => 'ellipse' ),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf__btn a:hover, {{WRAPPER}} .wcf__btn a:focus, {{WRAPPER}} .wcf__btn a:hover.btn-parallal-border:before, {{WRAPPER}} .wcf__btn a:hover.btn-parallal-border:after, {{WRAPPER}} .wcf__btn a:hover.btn-rollover-cross:before, {{WRAPPER}} .wcf__btn a:hover.btn-rollover-cross:after, {{WRAPPER}} .wcf__btn a.btn-hover-none:hover' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'btn_border_border!' => '',
					'btn_element_list!'  => array( 'underline', 'ellipse' ),
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'btn_border',
				'selector'  => '{{WRAPPER}} .wcf__btn a, {{WRAPPER}} .wcf__btn a.btn-parallal-border:before, {{WRAPPER}} .wcf__btn a.btn-parallal-border:after, {{WRAPPER}} .wcf__btn a.btn-rollover-cross:before, {{WRAPPER}} .wcf__btn a.btn-rollover-cross:after',
				'separator' => 'before',
				'condition' => array( 'btn_element_list!' => array( 'underline', 'ellipse' ) ),
			)
		);

		$this->add_responsive_control(
			'btn_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf__btn a:not(.wcf-btn-ellipse, .wcf-btn-circle, .wcf-btn-oval)'               => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf__btn a.btn-parallal-border:before, {{WRAPPER}} .wcf__btn a.btn-parallal-border:after' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf__btn a.btn-rollover-cross:before, {{WRAPPER}} .wcf__btn a.btn-rollover-cross:after'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'btn_element_list!' => array( 'underline', 'circle', 'ellipse', 'oval' ),
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf__btn a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf__btn a.wcf-btn-mask:after' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'      => 'button_shadow',
				'selector'  => '{{WRAPPER}} .wcf__btn a.btn-hover-none',
				'condition' => array(
					'btn_hover_list' => 'hover-none',
				),
			)
		);

		$this->add_responsive_control(
			'button_size',
			array(
				'label'      => esc_html__( 'Button Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 20,
						'max'  => 500,
						'step' => 5,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf__btn a' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'btn_element_list' => array( 'circle', 'square' ),
				),
			)
		);
	}

	protected function render_button( $settings = array(), $setting = null, $repeater_name = null, $index = null ) {
		if ( ! empty( $settings ) ) {
			$settings = $this->get_settings_for_display();
		}

		$link_key = 'link_';

		if ( $repeater_name ) {
			$repeater = $this->get_settings_for_display( $repeater_name );
			$link     = $repeater[ $index ][ $setting ];

			$link_key = 'link_' . $index;
			if ( ! empty( $link['url'] ) ) {
				$this->add_link_attributes( $link_key, $link );
			}
		} elseif ( isset( $settings['current_link'] ) && 'yes' === $settings['current_link'] ) {
			$this->add_link_attributes(
				$link_key,
				array(
					'url'         => $this->get_current_context_url(),
					'is_external' => $settings['btn_link']['is_external'],
					'nofollow'    => $settings['btn_link']['nofollow'],
				)
			);
		} elseif ( ! empty( $settings['btn_link']['url'] ) ) {
			$this->add_link_attributes( $link_key, $settings['btn_link'] );
		} else {
			$this->add_render_attribute( $link_key, 'role', 'button' );
		}

		$this->add_render_attribute( 'button_wrapper', 'class', 'wcf__btn' );
		$this->add_render_attribute( $link_key, 'class', 'wcf-btn-' . $settings['btn_element_list'] );

		if ( 'right' === $settings['button_icon_align'] ) {
			$this->add_render_attribute( 'button_wrapper', 'class', 'icon-position-after' );
		}

		if ( ! empty( $settings['btn_hover_list'] ) ) {
			$this->add_render_attribute( $link_key, 'class', 'btn-' . $settings['btn_hover_list'] );
		}

		if ( 'mask' === $settings['btn_element_list'] ) {
			$this->add_render_attribute( $link_key, 'data-text', $settings['btn_text'] );
		}

		$ext_wrap = in_array( $settings['btn_element_list'], array( 'oval', 'circle', 'ellipse' ) );

		if ( $ext_wrap ) {
			$this->add_render_attribute( $link_key, 'class', 'btn-item' );

			if ( 'ellipse' !== $settings['btn_element_list'] ) {
				$this->add_render_attribute( $link_key, 'class', 'btn-hover-bgchange' );
			}
		}

		$migrated = isset( $settings['__fa4_migrated']['button_icon'] );
		$is_new   = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();
		?>
		<div <?php $this->print_render_attribute_string( 'button_wrapper' ); ?>>
			<?php if ( $ext_wrap ) : ?>
			<div class="btn-wrapper">
				<?php endif; ?>
				<a <?php $this->print_render_attribute_string( $link_key ); ?>>
					<?php
					if ( $is_new || $migrated ) :
						Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) );
					else :
						?>
						<i class="<?php echo esc_attr( $settings['icon'] ); ?>" aria-hidden="true"></i>
					<?php endif; ?>
					<?php $this->print_unescaped_setting( 'btn_text' ); ?>
				</a>
				<?php if ( $ext_wrap ) : ?>
			</div>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get Current Page Link.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_current_context_url() {

		if ( is_singular() ) {
			return get_permalink();
		}

		if ( is_category() || is_tag() || is_tax() || is_post_type_archive() || is_author() || is_date() ) {
			return get_term_link( get_queried_object() );
		}

		if ( is_home() ) {
			return home_url();
		}

		if ( is_search() ) {
			return get_search_link();
		}

		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}
}
