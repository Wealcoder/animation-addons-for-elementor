<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Elementor\Repeater;
use Elementor\Plugin;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use WCF_ADDONS\AAE_Nested_Slider_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_Slider extends Widget_Nested_Base {

	use AAE_Nested_Slider_Trait;

	public $num_of_carousel_items = 0;

	public function get_name() {
		return 'wcf--nested-slider';
	}

	public function get_title() {
		return esc_html__( 'Nested Slider', 'animation-addons-for-elementor' );
	}

    /**
     * Get widget categories.
     *
     * @since 2.4.5
     * @return array
     */
    public function get_categories() {
        return array( 'wcf-wc-addon' );
    }

	public function get_icon() {
		return 'wcf eicon-nested-carousel';
	}

	public function get_keywords() {
		return array( 'Carousel', 'Slides', 'Nested', 'Media', 'Gallery', 'Image' );
	}

	// TODO: Replace this check with `is_active_feature` on 3.28.0 to support is_active_feature second parameter.
	public function show_in_panel() {
		return Plugin::$instance->experiments->is_feature_active( 'nested-elements' ) && Plugin::$instance->experiments->is_feature_active( 'container' );
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Get style dependencies.
	 *
	 * Retrieve the list of style dependencies the widget requires.
	 *
	 * @since 3.24.0
	 * @access public
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends(): array {
		return array( 'swiper' );
	}

	/**
	 * Get script dependencies.
	 *
	 * Retrieve the list of script dependencies the widget requires.
	 *
	 * @since 3.27.0
	 * @access public
	 *
	 * @return array Widget script dependencies.
	 */
	public function get_script_depends(): array {

		return array( 'swiper', 'aae--nested-slider' );
	}

	protected function get_default_children_elements() {
		return array(
			array(
				'elType'   => 'container',
				'settings' => array(
					'_title' => __( 'Slide #1', 'animation-addons-for-elementor' ),
				),
			),
			array(
				'elType'   => 'container',
				'settings' => array(
					'_title' => __( 'Slide #2', 'animation-addons-for-elementor' ),
				),
			),
			array(
				'elType'   => 'container',
				'settings' => array(
					'_title' => __( 'Slide #3', 'animation-addons-for-elementor' ),
				),
			),
		);
	}

	protected function get_default_repeater_title_setting_key() {
		return 'slide_title';
	}

	protected function get_default_children_title() {
		/* translators: %d: Slide number. */
		return esc_html__( 'Slide #%d', 'animation-addons-for-elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.swiper-wrapper';
	}
	protected function get_default_children_container_placeholder_selector() {
		return '.swiper-slide';
	}

	protected function get_html_wrapper_class() {
		return 'elementor-widget-n-carousel';
	}

	protected function register_controls() {
		$low_specificity_slider_container_selector = ':where( {{WRAPPER}} .swiper-slide ) > .e-con';

		$this->start_controls_section(
			'section_slides',
			array(
				'label' => esc_html__( 'Slides', 'animation-addons-for-elementor' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'slide_title',
			array(
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Slide Title', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Slide Title', 'animation-addons-for-elementor' ),
				'dynamic'     => array(
					'active' => true,
				),
				'label_block' => true,
			)
		);

		$this->add_control(
			'carousel_name',
			array(
				'label'       => esc_html__( 'Carousel Name', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Carousel', 'animation-addons-for-elementor' ),
				'render_type' => 'none', // template
			)
		);

		$this->add_control(
			'carousel_items',
			array(
				'label'              => esc_html__( 'Carousel Items', 'animation-addons-for-elementor' ),
				'type'               => Control_Nested_Repeater::CONTROL_TYPE,
				'fields'             => $repeater->get_controls(),
				'default'            => array(
					array(
						'slide_title' => esc_html__( 'Slide #1', 'animation-addons-for-elementor' ),
					),
					array(
						'slide_title' => esc_html__( 'Slide #2', 'animation-addons-for-elementor' ),
					),
					array(
						'slide_title' => esc_html__( 'Slide #3', 'animation-addons-for-elementor' ),
					),
				),
				'frontend_available' => true,
				'title_field'        => '{{{ slide_title }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_slides_style',
			array(
				'label' => esc_html__( 'Slides', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'           => 'content_background',
				'types'          => array( 'classic', 'gradient' ),
				'exclude'        => array( 'image' ),
				'selector'       => $low_specificity_slider_container_selector,
				'fields_options' => array(
					'color' => array(
						'label' => esc_html__( 'Background Color', 'animation-addons-for-elementor' ),
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'           => 'content_border',
				'selector'       => $low_specificity_slider_container_selector,
				'fields_options' => array(
					'color' => array(
						'label' => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
					),
					'width' => array(
						'label' => esc_html__( 'Border Width', 'animation-addons-for-elementor' ),
					),
				),
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					$low_specificity_slider_container_selector => '--border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$logical_dimensions_inline_start = is_rtl() ? '{{RIGHT}}{{UNIT}}' : '{{LEFT}}{{UNIT}}';
		$logical_dimensions_inline_end   = is_rtl() ? '{{LEFT}}{{UNIT}}' : '{{RIGHT}}{{UNIT}}';

		$this->add_responsive_control(
			'content_padding',
			array(
				'label'      => esc_html__( 'Padding', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .aaee-n-carousel .swiper-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();

				// slide controls
				$this->start_controls_section(
					'section_slider_options',
					array(
						'label' => esc_html__( 'Slider Options', 'animation-addons-for-elementor' ),
					)
				);

				$default = array(
					'slides_to_show' => 3,
					'autoplay'       => 'no',
				);
				$this->register_slider_controls( $default );

				$this->end_controls_section();

				// slider navigation style controls
				$this->start_controls_section(
					'section_slider_navigation_style',
					array(
						'label'     => esc_html__( 'Slider Navigation', 'animation-addons-for-elementor' ),
						'tab'       => Controls_Manager::TAB_STYLE,
						'condition' => array( 'navigation' => 'yes' ),
					)
				);

		$this->register_slider_navigation_style_controls();

		$this->end_controls_section();

		// slider pagination style controls
		$this->start_controls_section(
			'section_slider_pagination_style',
			array(
				'label'     => esc_html__( 'Slider Pagination', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'pagination' => 'yes' ),
			)
		);

		$this->register_slider_pagination_style_controls();

		$this->end_controls_section();
	}

	protected function content_template_single_repeater_item() {
		?>
		<#
		const elementUid = view.getIDInt().toString().substr( 0, 3 ),
			numOfSlides = view.collection.length + 1;

		const slideCount = numOfSlides,
			slideUid = elementUid + slideCount,
			slideWrapperKey = slideUid;

		const slideWrapperKeyItem = {
			'class': 'swiper-slide',
			'data-slide': slideCount,
			'role': 'group',
			'aria-roledescription': 'slide',
			'aria-label': slideCount + ' <?php echo esc_attr__( 'of', 'animation-addons-for-elementor' ); ?> ' + numOfSlides,
		};

		view.addRenderAttribute( 'single-slide', slideWrapperKeyItem, null, true );
		#>
		<div {{{ view.getRenderAttributeString( 'single-slide' ) }}}></div>
		<?php
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$slider_settings = $this->get_slider_attributes();

		$this->num_of_carousel_items = count( $settings['carousel_items'] ?? array() );
		$slides                      = $settings['carousel_items'];
		$direction                   = isset( $settings['direction'] ) ? $settings['direction'] : 'rtl';
		$has_autoplay_enabled        = true;
		$outside_wrapper_classes     = array( 'aaee-n-carousel', 'wcf__slider-wrapper' );

		$this->add_render_attribute(
			array(
				'carousel-outside-wrapper' => array(
					'class'                => $outside_wrapper_classes,
					'role'                 => 'region',
					'aria-roledescription' => 'carousel',
					'aria-label'           => $settings['carousel_name'],
				'data-settings'        => json_encode( $slider_settings ),   //phpcs:ignore
				),
				'carousel-inside-wrapper'  => array(
					'class'     => 'swiper-wrapper',
					'aria-live' => $has_autoplay_enabled ? 'off' : 'polite',
				),
			)
		);

		if ( ! empty( $direction ) ) {
			$this->add_render_attribute( 'carousel-outside-wrapper', 'dir', $direction );
		}
		?>
			<div <?php $this->print_render_attribute_string( 'carousel-outside-wrapper' ); ?>>
				<div <?php $this->print_render_attribute_string( 'carousel-wrapper' ); ?>>
					<div <?php $this->print_render_attribute_string( 'carousel-inside-wrapper' ); ?>>
						<?php
						foreach ( $slides as $index => $slide ) {
							$slide_count       = $index + 1;
							$slide_setting_key = $this->get_repeater_setting_key( 'slide_wrapper', 'slide', $index );

							$this->add_render_attribute(
								$slide_setting_key,
								array(
									'class'                => 'swiper-slide',
									'data-slide'           => $slide_count,
									'role'                 => 'group',
									'aria-roledescription' => 'slide',
									'aria-label'           => sprintf(
										/* translators: 1: Slide number. 2: Total amount of slides. */
										esc_attr__( '%1$s of %2$s', 'animation-addons-for-elementor' ),
										$slide_count,
										count( $slides )
									),
								)
							);
							?>
								<div <?php $this->print_render_attribute_string( $slide_setting_key ); ?>>
									<?php $this->print_child( $index ); ?>
								</div>
							<?php
						}
						?>
					</div>	
				</div>				
			</div>
				<!--navigation -->					
			<?php $this->render_slider_navigation(); ?>								
			<?php $this->render_slider_pagination(); ?>
		<?php
	}

	protected function get_initial_config(): array {
		return array_merge(
			parent::get_initial_config(),
			array(
				'support_improved_repeaters' => true,
				'target_container'           => array( '.aaee-n-carousel > .swiper-wrapper' ),
				'node'                       => 'div',
				'is_interlaced'              => true,
			)
		);
	}

	protected function content_template() {
		?>
					<#         
			function buildSliderConfig(settings, elementId, breakpointsConfig) {
				
			const config = {
				loop: settings.loop === 'true',
				speed: parseInt(settings.speed, 10) || 300,
				allowTouchMove: settings.allow_touch_move === 'true',
				slidesPerView: parseInt(settings.slides_to_show, 10) || 1,
				spaceBetween: parseInt(settings.space_between, 10) || 0,
			};

			// Autoplay
			if (settings.autoplay === 'yes') {
				config.autoplay = {
				delay: parseInt(settings.autoplay_delay, 10) || 5000,
				disableOnInteraction: settings.autoplay_interaction === 'yes',
				};
			}
			
			config.grid = {
				rows: parseInt(settings.grid_rows, 10) || 1,
				fill: 'row'		
			};
			

			// Navigation arrows
			if (settings.navigation === 'yes') {
				config.navigation = {
				nextEl: `.elementor-element-${elementId} .wcf-arrow-next`,
				prevEl: `.elementor-element-${elementId} .wcf-arrow-prev`,
				};
			}

			// Pagination bullets
			if (settings.pagination === 'yes') {
				config.pagination = {
				el: `.elementor-element-${elementId} .swiper-pagination`,
				clickable: true,
				type: settings.pagination_type || 'bullets',
				};
			}

			// Mousewheel
			if (settings.mousewheel === 'yes') {
				config.mousewheel = {
				releaseOnEdges: true,
				};
			}
			config.breakpoints = {};
			Object.entries(elementorFrontend.config.responsive.activeBreakpoints).forEach(([device, opts]) => {			
				config.breakpoints[opts.value] = {             
					slidesPerView: settings['slides_to_show'+'_'+device] ? settings['slides_to_show'+'_'+device] : settings['slides_to_show'],
					spaceBetween:  settings['space_between'+'_'+device] ? settings['space_between'+'_'+device] : settings['space_between'],
				}; 
			});
			
			return config;
			}
 
			#>    

			<# if ( settings['carousel_items'] ) {		
			
			const slider_settings = buildSliderConfig(settings,view.$el.attr('data-id'));
			
			const elementUid = view.getIDInt().toString().substr( 0, 3 ),
				carouselOutsideWrapperKey = 'carousel-up' + elementUid,
				carouselInsideWrapperKey = 'carousel-inside-' + elementUid,
				carouselInsideWrapperKeymi = 'carousel-' + elementUid,
				swiperWrapperClass = elementorFrontend.config.swiperClass,
				hasAutoplayEnabled = 'yes' === settings['autoplay'],
				outsideWrapperClasses = ['wcf__slider-wrapper']
				MidWrapperClasses = ['aaee-n-carousel','wcf__slider-wrapper','wcf__slider','swiper']
				shouldRenderPaginationAndArrows = 1 < settings['carousel_items'].length;

			view.addRenderAttribute( carouselOutsideWrapperKey, {
				'class': outsideWrapperClasses,				
				'data-settings' : JSON.stringify(slider_settings),				
			} );

			view.addRenderAttribute( carouselInsideWrapperKey, {
				'class': 'swiper-wrapper',
				'aria-live': hasAutoplayEnabled ? 'off' : 'polite',
			} );

			view.addRenderAttribute( carouselInsideWrapperKeymi, {
				'class': MidWrapperClasses,
				'dir'   : settings['direction'],
				'style' : 'position: static',	
				'aria-roledescription': 'carousel',
				'aria-label': settings['carousel_name'],	
				'role': 'region',		
			} );

			if ( !! settings['direction'] ) {
				view.addRenderAttribute( carouselOutsideWrapperKey, 'dir', settings['direction'] );
			}
			
			#>
				<div {{{ view.getRenderAttributeString( carouselOutsideWrapperKey ) }}}>
					<div {{{ view.getRenderAttributeString( carouselInsideWrapperKeymi ) }}}>
						<div {{{ view.getRenderAttributeString( carouselInsideWrapperKey ) }}}>
							<# _.each( settings['carousel_items'], function( slide, index ) {
								const slideCount = index + 1,
									slideUid = elementUid + slideCount,
									slideWrapperKey = slideUid;

								view.addRenderAttribute( slideWrapperKey, {
									'class': 'swiper-slide',
									'data-slide': slideCount,
									'role': 'group',
									'aria-roledescription': 'slide',
									'aria-label': slideCount + ' <?php echo esc_attr__( 'of', 'animation-addons-for-elementor' ); ?> ' + settings['carousel_items'].length,
								} );
							#>
								<div {{{ view.getRenderAttributeString( slideWrapperKey ) }}}></div>
							<# } ); #>
						</div>		
					</div>					
				</div>	
				<# if ( 'yes' === settings['navigation'] && shouldRenderPaginationAndArrows ) { #> 					
				<?php $this->render_slider_navigation_temp( 'flex' ); ?>
				<# }else{ #>	
				<?php $this->render_slider_navigation_temp( 'none' ); ?>
				<# } #>									
				<# if ( 'yes' === settings['pagination'] && shouldRenderPaginationAndArrows ) { #> 
				<div class="ts-pagination">
					<div class="swiper-pagination"></div>
				</div>
				<# }else{ #>	
				<div class="ts-pagination" style="display:none;">
					<div class="swiper-pagination"></div>
				</div>		
				<# } #>		
			
			<# } #>
		<?php
	}

	protected function render_slider_navigation_temp( $show = 'none' ) {

		?>
		<div class="ts-navigation" style="display:<?php echo esc_attr( $show ); ?>">
			<div class="wcf-arrow wcf-arrow-prev" role="button" tabindex="0">
				<?php $this->render_swiper_button_prev_temp( 'previous' ); ?>
			</div>
			<div class="wcf-arrow wcf-arrow-next" role="button" tabindex="0">
				<?php $this->render_swiper_button_next_temp( 'next' ); ?>
			</div>
		</div>
		<?php
	}

	private function render_swiper_button_prev_temp( $type ) {
		?>
		<#
			const aaenticonSettingsPrev = settings['navigation_previous_icon'],
				aaiconprevHTML = elementor.helpers.renderIcon( view, aaenticonSettingsPrev, { 'aria-hidden': true }, 'i' , 'object' );
			
			if ( '' === aaenticonSettingsPrev['value'] ) { #>
				<?php
				Icons_Manager::render_icon(
					array(
						'library' => 'eicons',
						'value'   => 'eicon-chevron-left',
					)
				);
				?>
			<# } else if ( !! aaenticonSettingsPrev['value'] ) { #>
				{{{ aaiconprevHTML.value }}}
			<# } #>		
		<?php
	}

	private function render_swiper_button_next_temp( $type ) {
		?>
		<#
			const aaenticonSettingsNext = settings['navigation_next_icon'],
				iconNextHTML = elementor.helpers.renderIcon( view, aaenticonSettingsNext, { 'aria-hidden': true }, 'i' , 'object' );

			if ( '' === aaenticonSettingsNext['value'] ) { #>
				<?php
				Icons_Manager::render_icon(
					array(
						'library' => 'eicons',
						'value'   => 'eicon-chevron-right',
					)
				);
				?>
			<# } else if ( !! aaenticonSettingsNext['value'] ) { #>
				{{{ iconNextHTML.value }}}
			<# } #>		
		<?php
	}
}
