<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use WCF_ADDONS\WCF_Button_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Elementor Services Tab widget.
 *
 * Elementor widget that displays vertical or horizontal tabs with different
 * pieces of content.
 *
 * @since 1.0.0
 */
class Services_Tab extends Widget_Base {
	use  WCF_Button_Trait;

	/**
	 * Get widget name.
	 *
	 * Retrieve tabs widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_name() {
		return 'wcf--services-tab';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve tabs widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_title() {
		return esc_html__( 'Services Tab', 'animation-addons-for-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve tabs widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_icon() {
		return 'wcf eicon-tabs';
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
		return [ 'wcf--tabs' ];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'wcf--services-tab', 'wcf--button'];
	}

	/**
	 * Register tabs widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function register_controls() {
		$start = is_rtl() ? 'right' : 'left';
		$end   = is_rtl() ? 'left' : 'right';

		$this->start_controls_section(
			'section_tabs',
			[
				'label' => esc_html__( 'Tabs', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'element_list',
			[
				'label'   => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => esc_html__( 'One', 'animation-addons-for-elementor' ),
					'2' => esc_html__( 'Two', 'animation-addons-for-elementor' ),
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'tab_number',
			[
				'label'       => esc_html__( 'Number', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '01', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( '01', 'animation-addons-for-elementor' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'tab_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Tab Title', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Tab Title', 'animation-addons-for-elementor' ),
				'label_block' => true,
				'dynamic'     => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'tab_content',
			[
				'label'       => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'default'     => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Tab Content', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::WYSIWYG,
			]
		);

		$repeater->add_control(
			'tab_image',
			[
				'label'   => esc_html__( 'Choose Image', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => esc_html__( 'Link', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => esc_html__( 'https://your-link.com', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'tabs',
			[
				'label'       => esc_html__( 'Tabs Items', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[ 'tab_number' => esc_html__( '01', 'animation-addons-for-elementor' ) ],
					[ 'tab_number' => esc_html__( '02', 'animation-addons-for-elementor' ) ],
					[ 'tab_number' => esc_html__( '03', 'animation-addons-for-elementor' ) ],
				],
				'title_field' => '{{{ tab_title }}}',
			]
		);

		$this->add_control(
			'view',
			[
				'label'   => esc_html__( 'View', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'traditional',
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'image',
				'default'   => 'full',
				'separator' => 'none',
			]
		);

		$this->end_controls_section();

		//button
		$this->start_controls_section(
			'section_button_content',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'condition' => [ 'element_list' => '1' ]
			]
		);

		$this->register_button_content_controls( [ 'btn_text' => 'Get ticket ' ], [ 'btn_link' => false ] );

		$this->end_controls_section();

		//settings
		$this->start_controls_section( 'section_tabs_setting', [
			'label' => esc_html__( 'Settings', 'animation-addons-for-elementor' ),
		] );

		$dropdown_options     = [
			'none' => esc_html__( 'None', 'animation-addons-for-elementor' ),
		];
		$excluded_breakpoints = [
			'laptop',
			'tablet_extra',
			'widescreen',
		];

		foreach ( Plugin::$instance->breakpoints->get_active_breakpoints() as $breakpoint_key => $breakpoint_instance ) {
			// Exclude the larger breakpoints from the dropdown selector.
			if ( in_array( $breakpoint_key, $excluded_breakpoints, true ) ) {
				continue;
			}

			$dropdown_options[ $breakpoint_key ] = sprintf(
			/* translators: 1: Breakpoint label, 2: `>` character, 3: Breakpoint value. */
				esc_html__( '%1$s (%2$s %3$dpx)', 'animation-addons-for-elementor' ),
				$breakpoint_instance->get_label(),
				'>',
				$breakpoint_instance->get_value()
			);
		}

		$this->add_control(
			'breakpoint_selector',
			[
				'label'        => esc_html__( 'Breakpoint', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SELECT,
				'description'  => esc_html__( 'Note: Choose at which breakpoint tabs will automatically switch to a vertical (“accordion”) layout.', 'animation-addons-for-elementor' ),
				'options'      => $dropdown_options,
				'default'      => 'mobile',
				'prefix_class' => 'wcf-tabs-',
			]
		);

		$this->end_controls_section();

		//style tabs
		$this->start_controls_section(
			'section_tabs_style',
			[
				'label' => esc_html__( 'Tabs', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'navigation_width',
			[
				'label'     => esc_html__( 'Tabs Width', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'default'   => [
					'unit' => '%',
				],
				'range'     => [
					'%' => [
						'min' => 5,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--services-tab' => '--image-width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();

		//image
		$this->start_controls_section(
			'section_tab_image_style',
			[
				'label' => esc_html__( 'Image', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'em' => [
						'min' => 1,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .image',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_box_shadow',
				'selector' => '{{WRAPPER}} .image',
			]
		);

		$this->end_controls_section();

		//style tabs title
		$this->start_controls_section(
			'section_tab_title_style',
			[
				'label' => esc_html__( 'Tab Title', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'tab_number_heading',
			[
				'label'     => esc_html__( 'Tab Number', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'tab_number',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .number' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tab_number_typography',
				'selector' => '{{WRAPPER}} .number',
			]
		);

		$this->add_responsive_control( 'tabs_number_space',
			[
				'label'      => esc_html__( 'Space', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 400,
					],
				],
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .number' => 'margin-right: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'tab_title_heading',
			[
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->start_controls_tabs( 'tabs_title_style' );

		$this->start_controls_tab(
			'tab_title_normal',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'tab_title',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tab-title' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tab_title_typography',
				'selector' => '{{WRAPPER}} .tab-title',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_hover',
			[
				'label' => esc_html__( 'Hover/Active', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'tab_title_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tab-title:hover, {{WRAPPER}} .tab-title.active' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'tab_title_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tab-title:hover, {{WRAPPER}} .tab-title.active' => 'border-color: {{VALUE}}',
				],
				'condition' => [ 'title_border_border!' => '' ]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();


		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'title_border',
				'selector'  => '{{WRAPPER}} .tab-title',
				'separator' => 'before',
			]
		);


		$this->add_responsive_control(
			'title_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .tab-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 300,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .tab-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//style tab content
		$this->start_controls_section(
			'section_tab_content_style',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'tab_content_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .content' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tab_content_typography',
				'selector' => '{{WRAPPER}} .content',
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'em' => [
						'min' => 1,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'content_margin',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 500,
					],
					'em' => [
						'min' => 1,
						'max' => 10,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//button style
		$this->start_controls_section(
			'section_btn_style',
			[
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'element_list' => '1' ]
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => esc_html__( 'Button Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'separator'  => 'after',
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 100,
					]
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf__btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->register_button_style_controls();

		$this->end_controls_section();
	}

	/**
	 * Render tabs widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$tabs     = $this->get_settings_for_display( 'tabs' );
		$id_int   = substr( $this->get_id_int(), 0, 3 );

		$this->add_render_attribute( 'wrapper', 'class', [
			'wcf--services-tab',
			'style-' . $settings['element_list'],
		] );

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<div class="tabs-wrapper" role="tablist">
				<?php
				foreach ( $tabs as $index => $item ) :
					$tab_count             = $index + 1;
					$tab_title_setting_key = $this->get_repeater_setting_key( 'tab_title', 'tabs', $index );

					$this->add_render_attribute( $tab_title_setting_key, [
						'id'            => 'tab-title-' . $id_int . $tab_count,
						'class'         => [ 'tab-title', 'tab-desktop-title' ],
						'aria-selected' => 1 === $tab_count ? 'true' : 'false',
						'data-tab'      => $tab_count,
						'role'          => 'tab',
						'tabindex'      => 1 === $tab_count ? '0' : '-1',
						'aria-controls' => 'tab-content-' . $id_int . $tab_count,
					] );
					?>
					<div <?php $this->print_render_attribute_string( $tab_title_setting_key ); ?>>
						<?php
						if ( ! empty( $item['tab_number'] ) ) {
							?><span class="number"><?php
							$this->print_unescaped_setting( 'tab_number', 'tabs', $index );
							?></span><?php
						}
						$this->print_unescaped_setting( 'tab_title', 'tabs', $index );
						?>

						<?php if ( '2' === $settings['element_list'] ): ?>
                            <div class="content">
								<?php $this->print_text_editor( $item['tab_content'] ); ?>
                            </div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="tabs-content-wrapper" role="tablist">
				<?php
				foreach ( $tabs as $index => $item ) :
					$tab_count               = $index + 1;
					$hidden                  = 1 === $tab_count ? 'false' : 'hidden';
					$tab_content_setting_key = $this->get_repeater_setting_key( 'tab_content', 'tabs', $index );

					$tab_title_mobile_setting_key = $this->get_repeater_setting_key( 'tab_title_mobile', 'tabs', $tab_count );

					$this->add_render_attribute( $tab_content_setting_key, [
						'id'              => 'tab-content-' . $id_int . $tab_count,
						'class'           => 'tab-content',
						'data-tab'        => $tab_count,
						'role'            => 'tabpanel',
						'aria-labelledby' => 'tab-title-' . $id_int . $tab_count,
						'tabindex'        => '0',
					] );

					$this->add_render_attribute( $tab_title_mobile_setting_key, [
						'class'         => [ 'tab-title', 'tab-mobile-title' ],
						'aria-selected' => 1 === $tab_count ? 'true' : 'false',
						'data-tab'      => $tab_count,
						'role'          => 'tab',
						'tabindex'      => 1 === $tab_count ? '0' : '-1',
						'aria-controls' => 'tab-content-' . $id_int . $tab_count,
						'aria-expanded' => 'false',
					] );


					$link_key = 'link_p' . $index;
					if ( ! empty( $item['link']['url'] ) ) {
						$this->add_link_attributes( $link_key, $item['link'] );
					}

					?>
					<div <?php $this->print_render_attribute_string( $tab_title_mobile_setting_key ); ?>>
						<?php
						if ( ! empty( $item['tab_number'] ) ) {
							?><span class="number"><?php
							$this->print_unescaped_setting( 'tab_number', 'tabs', $index );
							?></span><?php
						}
						$this->print_unescaped_setting( 'tab_title', 'tabs', $index );
						?>
						<?php if ( '2' === $settings['element_list'] ): ?>
                            <div class="content">
								<?php $this->print_text_editor( $item['tab_content'] ); ?>
                            </div>
						<?php endif; ?>
					</div>
					<div <?php $this->print_render_attribute_string( $tab_content_setting_key ); ?>>
                        <div class="image">
                            <?php
							$image_url = Group_Control_Image_Size::get_attachment_image_src( $item['tab_image']['id'], 'image', $settings );

							if ( ! $image_url && isset( $item['tab_image']['url'] ) ) {
								$image_url = $item['tab_image']['url'];
							}
							$image_html = '<img  src="' . esc_url( $image_url ) . '" alt="' . esc_attr( Control_Media::get_image_alt( $item['tab_image'] ) ) . '" />';

							if ( ! empty( $item['link']['url'] ) ) :
								$image_html = '<a ' . $this->get_render_attribute_string( $link_key ) . '>' . $image_html . '</a>';
							endif;

							echo wp_kses_post( $image_html );
							?>
                        </div>

						<?php if ( '1' === $settings['element_list'] ): ?>
                            <div class="content">
								<?php
								$this->print_text_editor( $item['tab_content'] );

								$this->render_button( $settings, 'link', 'tabs', $index );
								?>
                            </div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
