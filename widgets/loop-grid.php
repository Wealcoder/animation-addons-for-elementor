<?php
namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use WCF_ADDONS\WCF_Post_Query_Trait;
use WCF_ADDONS\AAE_Post_Handler_Trait;
use WCF_ADDONS\Widgets\Loop_Builder\Template_Manager;
use WCF_ADDONS\Widgets\Loop_Builder\Query_Manager;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Loop Grid Widget.
 *
 * Displays posts in a customizable grid layout using loop item templates.
 */
class Loop_Grid extends \Elementor\Widget_Base {
	use WCF_Post_Query_Trait;
	use AAE_Post_Handler_Trait;

	/**
	 * Query object.
	 *
	 * @var \WP_Query $query Post Query object.
	 *
	 * @since 2.4.16
	 */
	protected $query;

	/**
	 * Get a widget name.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_name() {
		return 'aae--loop-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_title() {
		return __( 'Loop Grid', 'animation-addons-for-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_icon() {
		return 'wcf eicon-posts-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_categories() {
		return array( 'wcf-wc-addon' );
	}

	/**
	 * Show in a panel.
	 *
	 * @since 2.4.16
	 * @return boolean
	 */
	public function show_in_panel() {
		return true;
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_keywords() {
		return array( 'loop', 'grid', 'posts', 'dynamic', 'template', 'listing', 'archive', 'builder', 'custom', 'elementor', 'pro' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'wcf--loop-grid' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'custom-loop-builder-frontend', 'advanced--adv-posts-pro', 'advanced--aae--features--posts', 'wcf--posts' );
	}

	/**
	 * Register widget controls.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_controls() {
		$this->register_layout_section();
		$this->register_query_controls();
		$this->register_pagination_controls();
		$this->register_additional_options_section();

		// Style sections.
		$this->register_style_layout_section();
		$this->register_style_items_section();
		$this->register_load_more_controls();
		$this->register_style_nothing_found_section();
	}

	/**
	 * Register layout section controls.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_layout_section() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'template_id',
			array(
				'label'              => __( 'Choose Template', 'animation-addons-for-elementor' ),
				'type'               => 'template_query',
				'label_block'        => true,
				'options'            => $this->get_loop_templates_options(),
				'autocomplete'       => array(
					'object' => 'post',
					'query'  => array(
						'post_type'  => 'wcf-addons-template',
						'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
								array(
										'key'   => 'wcf-addons-template-meta_type',
										'value' => 'loop-builder',
								),
						),
					),
				),
				'actions'            => array(
					'new'  => array(
						'visible'         => true,
						'document_config' => array(
							'wcf-addons-template-meta_type' => 'loop-builder',
							'post_type' => 'wcf-addons-template',
						),
					),
					'edit' => array(
						'visible' => true,
					),
				),
				'frontend_available' => true,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'              => __( 'Columns', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => '3',
				'tablet_default'     => '2',
				'mobile_default'     => '1',
				'options'            => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
				),
				'prefix_class'       => 'elementor-grid%s-',
				'frontend_available' => true,
				'condition'          => array(
					'template_id!' => '',
				),
				'selectors'          => array(
					'{{WRAPPER}}.custom-loop-masonry-yes .aae-loop-grid-container' => 'display:block;column-count: {{VALUE}}',
					'{{WRAPPER}}.custom-loop-equal-height-yes .aae-loop-grid-container' => 'display: grid; grid-template-columns: repeat({{VALUE}}, 1fr);',
					'{{WRAPPER}} .aae-loop-grid-container' => 'align-self:flex-start;grid-template-columns: repeat({{VALUE}}, 1fr);',
				),

			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'     => __( 'Items Per Page', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 1,
				'max'       => 100,
				'condition' => array(
					'template_id!' => '',
				),
			)
		);

		$this->add_control(
			'masonry',
			array(
				'label'              => __( 'Masonry', 'animation-addons-for-elementor' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_off'          => __( 'Off', 'animation-addons-for-elementor' ),
				'label_on'           => __( 'On', 'animation-addons-for-elementor' ),
				'condition'          => array(
					'columns!'     => '1',
					'template_id!' => '',
				),
				'frontend_available' => true,
				'prefix_class'       => 'custom-loop-masonry-',
				'render_type'        => 'template',
			)
		);

		$this->add_control(
			'equal_height',
			array(
				'label'        => __( 'Equal Height', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_off'    => __( 'Off', 'animation-addons-for-elementor' ),
				'label_on'     => __( 'On', 'animation-addons-for-elementor' ),
				'condition'    => array(
					'columns!'     => '1',
					'template_id!' => '',
					'masonry'      => '',
				),
				'prefix_class' => 'custom-loop-equal-height-',
				'render_type'  => 'template',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register pagination section controls.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_pagination_controls() {
		$this->start_controls_section(
			'section_pagination',
			array(
				'label' => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'   => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''                      => esc_html__( 'None', 'animation-addons-for-elementor' ),
					'numbers'               => esc_html__( 'Numbers', 'animation-addons-for-elementor' ),
					'prev_next'             => esc_html__( 'Previous/Next', 'animation-addons-for-elementor' ),
					'numbers_and_prev_next' => esc_html__( 'Numbers', 'animation-addons-for-elementor' ) . ' + ' . esc_html__( 'Previous/Next', 'animation-addons-for-elementor' ),
					'load_on_click'         => esc_html__( 'Load On Click', 'animation-addons-for-elementor' ),
				),
			)
		);

		$this->add_control(
			'pagination_page_limit',
			array(
				'label'     => esc_html__( 'Page Limit', 'animation-addons-for-elementor' ),
				'default'   => '5',
				'condition' => array(
					'pagination_type' => array( 'numbers_and_prev_next', 'numbers', 'prev_next' ),
				),
			)
		);

		$this->add_control(
			'pagination_numbers_shorten',
			array(
				'label'     => esc_html__( 'Shorten', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'condition' => array(
					'pagination_type' => array( 'numbers_and_prev_next', 'numbers' ),
				),
			)
		);

		$this->add_control(
			'navigation_prev_icon',
			array(
				'label'         => esc_html__( 'Previous', 'animation-addons-for-elementor' ),
				'type'          => Controls_Manager::ICONS,
				'skin'          => 'inline',
				'label_block'   => false,
				'skin_settings' => array(
					'inline' => array(
						'none' => array(
							'label' => 'Default',
							'icon'  => 'eicon-chevron-left',
						),
						'icon' => array(
							'icon' => 'eicon-star',
						),
					),
				),
				'recommended'   => array(
					'fa-regular' => array(
						'arrow-alt-circle-left',
						'caret-square-left',
					),
					'fa-solid'   => array(
						'angle-double-left',
						'angle-left',
						'arrow-alt-circle-left',
						'arrow-circle-left',
						'arrow-left',
						'caret-left',
						'caret-square-left',
						'chevron-circle-left',
						'chevron-left',
						'long-arrow-alt-left',
					),
				),
				'condition'     => array(
					'pagination_type' => array( 'prev_next', 'numbers_and_prev_next' ),
				),
			)
		);

		$this->add_control(
			'navigation_next_icon',
			array(
				'label'         => esc_html__( 'Next', 'animation-addons-for-elementor' ),
				'type'          => Controls_Manager::ICONS,
				'skin'          => 'inline',
				'label_block'   => false,
				'skin_settings' => array(
					'inline' => array(
						'none' => array(
							'label' => 'Default',
							'icon'  => 'eicon-chevron-right',
						),
						'icon' => array(
							'icon' => 'eicon-star',
						),
					),
				),
				'recommended'   => array(
					'fa-regular' => array(
						'arrow-alt-circle-right',
						'caret-square-right',
					),
					'fa-solid'   => array(
						'angle-double-right',
						'angle-right',
						'arrow-alt-circle-right',
						'arrow-circle-right',
						'arrow-right',
						'caret-right',
						'caret-square-right',
						'chevron-circle-right',
						'chevron-right',
						'long-arrow-alt-right',
					),
				),
				'condition'     => array(
					'pagination_type' => array( 'prev_next', 'numbers_and_prev_next' ),
				),
			)
		);

		$this->add_responsive_control(
			'pagination_align',
			array(
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => array(
					'start'  => array(
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'end'    => array(
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-pagination' => 'justify-content: {{VALUE}};',
					'{{WRAPPER}} .wcf-post-load-more'  => 'align-self: {{VALUE}};',
				),
				'condition' => array(
					'pagination_type!' => array( '', 'infinite_scroll' ),
				),
			)
		);

		$this->add_responsive_control(
			'pagination_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-pagination' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .wcf-post-load-more'  => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array(
					'pagination_type!' => array( '', 'infinite_scroll' ),
				),
			)
		);

		$this->end_controls_section();

		// Pagination style controls for prev/next and numbers pagination.
		$this->start_controls_section(
			'section_pagination_style',
			array(
				'label'     => esc_html__( 'Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'pagination_type' => array( 'numbers_and_prev_next', 'numbers', 'prev_next' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .wcf-post-pagination .page-numbers',
			)
		);

		$this->add_responsive_control(
			'pagination_spacing',
			array(
				'label'      => esc_html__( 'Space Between', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'separator'  => 'before',
				'default'    => array(
					'size' => 10,
				),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-pagination' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tab_pagination' );

		$this->start_controls_tab(
			'tab_pagination_normal',
			array(
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'pagination_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-pagination .page-numbers' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'nabackground',
				'types'    => array( 'classic' ),
				'selector' => '{{WRAPPER}} .wcf-post-pagination .page-numbers',
			)
		);

		$this->add_responsive_control(
			'pagination_number_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-pagination .page-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'border',
				'selector' => '{{WRAPPER}} .wcf-post-pagination .page-numbers',
			)
		);

		$this->add_control(
			'pagination_number_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-pagination .page-numbers' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_pagination_color_hover',
			array(
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'pagination_hover_color',
			array(
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-pagination .page-numbers:not(.dots):hover, {{WRAPPER}} .wcf-post-pagination .page-numbers.current' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_hover_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-pagination .page-numbers:not(.dots):hover, {{WRAPPER}} .wcf-post-pagination .page-numbers.current' => 'background: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Register Load More controls.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_load_more_controls() {
		$this->start_controls_section(
			'section_load_more',
			array(
				'label'     => esc_html__( 'Load More', 'animation-addons-for-elementor' ),
				'condition' => array(
					'pagination_type' => array(
						'load_on_click',
						'infinite_scroll',
					),
				),
			)
		);

		$this->add_control(
			'load_more_spinner',
			array(
				'label'                  => esc_html__( 'Spinner', 'animation-addons-for-elementor' ),
				'type'                   => Controls_Manager::ICONS,
				'default'                => array(
					'value'   => 'fas fa-spinner',
					'library' => 'fa-solid',
				),
				'exclude_inline_options' => array( 'svg' ),
				'recommended'            => array(
					'fa-solid' => array(
						'spinner',
						'cog',
						'sync',
						'sync-alt',
						'asterisk',
						'circle-notch',
					),
				),
				'skin'                   => 'inline',
				'label_block'            => false,
			)
		);

		$this->add_responsive_control(
			'load_more_spinner_size',
			array(
				'label'      => esc_html__( 'Spinner Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .load-more-spinner' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_load_more_button',
			array(
				'label'     => esc_html__( 'Button', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'pagination_type' => 'load_on_click',
				),
			)
		);

		$this->add_control(
			'load_more_btn_text',
			array(
				'label'       => esc_html__( 'Text', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Load More', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Load More', 'animation-addons-for-elementor' ),
				'condition'   => array(
					'pagination_type' => 'load_on_click',
				),
			)
		);

		$this->add_control(
			'load_more_btn_icon',
			array(
				'label'       => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => array(
					'pagination_type' => 'load_on_click',
				),
			)
		);

		$this->add_control(
			'btn_icon_position',
			array(
				'label'     => esc_html__( 'Icon Position', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'row',
				'options'   => array(
					'row'         => esc_html__( 'After', 'animation-addons-for-elementor' ),
					'row-reverse' => esc_html__( 'Before', 'animation-addons-for-elementor' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcf__posts-pro .wcf-post-load-more .load-more-text' => 'flex-direction: {{VALUE}};',
				),
				'condition' => array(
					'pagination_type' => 'load_on_click',
				),
			)
		);

		$this->end_controls_section();

		// style.
		$this->start_controls_section(
			'section_load_more_style',
			array(
				'label'     => esc_html__( 'Load More', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'pagination_type' => array( 'load_on_click' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'load_more_typography',
				'selector' => '{{WRAPPER}} .wcf-post-load-more',
			)
		);

		$this->add_responsive_control(
			'load_more_icon_size',
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
					'{{WRAPPER}} .load-more-text i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .load-more-text svg' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs(
			'tabs_load_more',
		);

		$this->start_controls_tab(
			'tab_load_more_normal',
			array(
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'load_more_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-load-more' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'load_more_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .wcf-post-load-more',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_load_more_hover',
			array(
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'load_more_text_hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-load-more:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'load_more_hover_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .wcf-post-load-more:hover',
			)
		);

		$this->add_control(
			'load_more_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcf-post-load-more:hover' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'load_more_border_border!' => '',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'load_more_border',
				'selector'  => '{{WRAPPER}} .wcf-post-load-more',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'load_more_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-load-more' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'load_more_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-load-more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'load_more_shadow',
				'selector' => '{{WRAPPER}} .wcf-post-load-more',
			)
		);

		$this->add_responsive_control(
			'load_more_margin',
			array(
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcf-post-load-more' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register additional options section.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_additional_options_section() {
		$this->start_controls_section(
			'section_additional_options',
			array(
				'label'     => __( 'Additional Options', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'template_id!' => '',
				),
			)
		);

		$this->add_control(
			'nothing_found_message',
			array(
				'label'     => __( 'Nothing Found Message', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_off' => __( 'Hide', 'animation-addons-for-elementor' ),
				'label_on'  => __( 'Show', 'animation-addons-for-elementor' ),
			)
		);

		$this->add_control(
			'nothing_found_text',
			array(
				'label'     => __( 'Nothing Found Text', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => __( 'It seems we can\'t find what you\'re looking for.', 'animation-addons-for-elementor' ),
				'condition' => array(
					'nothing_found_message' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the style layout section.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_style_layout_section() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => __( 'Column Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'size' => 30,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .custom-loop-container' => 'column-gap: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => __( 'Row Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'condition'  => array(
					'masonry!' => 'yes',
				),
				'default'    => array(
					'size' => 30,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .custom-loop-container' => 'row-gap: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_responsive_control(
			'row_items_margin',
			array(
				'label'      => __( 'Row Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'condition'  => array(
					'masonry' => 'yes',
				),
				'default'    => array(
					'size' => 30,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}}.custom-loop-masonry-yes .custom-loop-container .aae-loop-item' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register style items section.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_style_items_section() {
		$this->start_controls_section(
			'section_style_items',
			array(
				'label' => __( 'Items', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'item_border',
				'selector' => '{{WRAPPER}} .e-loop-item',
			)
		);

		$this->add_control(
			'item_border_radius',
			array(
				'label'      => __( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .e-loop-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'item_box_shadow',
				'selector' => '{{WRAPPER}} .e-loop-item',
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => __( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .e-loop-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register style nothing found a section.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function register_style_nothing_found_section() {
		$this->start_controls_section(
			'section_style_nothing_found',
			array(
				'label'     => __( 'Nothing Found Message', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'nothing_found_message' => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'nothing_found_typography',
				'selector' => '{{WRAPPER}} .custom-loop-nothing-found',
			)
		);

		$this->add_control(
			'nothing_found_color',
			array(
				'label'     => __( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .custom-loop-nothing-found' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'nothing_found_align',
			array(
				'label'     => __( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .custom-loop-nothing-found' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Get loop templates options for the template_id control.
	 *
	 * @since 2.4.16
	 * @return array
	 */
	private function get_loop_templates_options() {
		$templates = get_posts(
			array(
				'post_type'      => 'wcf-addons-template',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
								'key'     => 'wcf-addons-template-meta_type',
								'value'   => 'loop-builder',
								'compare' => '=',
						),
				),
			)
		);

		$options = array( '' => __( 'Select Template', 'animation-addons-for-elementor' ) );

		foreach ( $templates as $template ) {
			$source_type  = get_post_meta( $template->ID, '_elementor_source', true );
			$source_label = '';

			if ( $source_type ) {
				$post_type_obj = get_post_type_object( $source_type );
				$source_label  = $post_type_obj ? ' (' . $post_type_obj->label . ')' : ' (' . ucfirst( $source_type ) . ')';
			}

			$options[ $template->ID ] = $template->post_title . $source_label;
		}

		return $options;
	}

	/**
	 * Render widget output.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['template_id'] ) ) {
			$this->render_empty_view();
			return;
		}

		// Get the current page for pagination.
		$paged             = $this->get_current_page();
		$settings['paged'] = $paged;

		$query_manager = Query_Manager::instance();
		$query         = $query_manager->get_query( $settings );

		// Build base classes.
		$container_classes = array(
			'custom-loop-container',
			'aae-loop-grid-container',
			'custom-loop-container-' . $settings['template_id'],
		);

		$class_settings = array(
			'class'         => $container_classes,
			'data-settings' => wp_json_encode(
				array(
					'template_id'          => $settings['template_id'],
					'posts_per_page'       => $settings['posts_per_page'],
					'pagination_type'      => isset( $settings['pagination_type'] ) ? $settings['pagination_type'] : '',
					'pagination_load_type' => isset( $settings['pagination_load_type'] ) ? $settings['pagination_load_type'] : 'page_reload',
					'source'               => isset( $settings['source'] ) ? $settings['source'] : 'post',
					'orderby'              => isset( $settings['orderby'] ) ? $settings['orderby'] : 'date',
					'order'                => isset( $settings['order'] ) ? $settings['order'] : 'DESC',
					'widget_id'            => $this->get_id(),
					'nonce'                => wp_create_nonce( 'aae_loop_builder_nonce' ),
				)
			),
		);

		if ( empty( $query->have_posts() ) ) {
			unset( $class_settings['data-settings'] );
			$class_settings = array(
				'class' => array(
					'wcf-posts',
				),
			);
		}

		$this->add_render_attribute( 'wrapper', $class_settings );

		?>
		<div class="custom-loop-wrapper aae-loop-builder wcf__posts-pro" data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>">
			<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
				<?php
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$this->render_loop_item( $settings['template_id'] );
					}
					wp_reset_postdata();
				} else {
					$this->render_nothing_found( $settings );
				}
				?>
			</div>
			<?php
			// Render pagination OUTSIDE the container.
			if ( $query->have_posts() && ! empty( $settings['pagination_type'] ) ) {
				$this->render_pagination();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get the current page number for pagination.
	 *
	 * @since 2.4.16
	 * @return int
	 */
	protected function get_current_page() {
		if ( get_query_var( 'paged' ) ) {
			return absint( get_query_var( 'paged' ) );
		}

		if ( isset( $_GET['paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( wp_unslash( $_GET['paged'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( get_query_var( 'page' ) ) {
			return absint( get_query_var( 'page' ) );
		}

		return 1;
	}

	/**
	 * Render single loop item.
	 *
	 * @param int $template_id The template ID.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function render_loop_item( $template_id ) {
		$classes = get_post_class( 'e-loop-item aae-loop-item', get_the_ID() );

		$template_output = Template_Manager::render_template( $template_id, get_the_ID() );

		if ( ! empty( $template_output ) ) :
			?>
			<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-elementor-type="loop-item">
				<?php echo $template_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</article>
			<?php
		endif;
	}

	/**
	 * Render nothing found a message.
	 *
	 * @param array $settings The widget settings.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function render_nothing_found( $settings ) {
		if ( 'yes' !== $settings['nothing_found_message'] ) {
			return;
		}

		$message = $settings['nothing_found_text'] ?? __( 'It seems we can\'t find what you\'re looking for.', 'animation-addons-for-elementor' );
		?>
		<p class="custom-loop-nothing-found">
			<?php echo esc_html( $message ); ?>
		</p>
		<?php
	}

	/**
	 * Render empty view for editor.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	protected function render_empty_view() {
		if ( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		?>
		<div class="custom-loop-empty-view">
			<div class="custom-loop-empty-view__wrapper">
				<div class="custom-loop-empty-view__icon">
					<i class="eicon-posts-grid" aria-hidden="true"></i>
				</div>
				<div class="custom-loop-empty-view__title">
					<?php esc_html_e( 'Choose a Loop Template', 'animation-addons-for-elementor' ); ?>
				</div>
				<div class="custom-loop-empty-view__description">
					<?php esc_html_e( 'Create or select a loop item template to display your content.', 'animation-addons-for-elementor' ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
