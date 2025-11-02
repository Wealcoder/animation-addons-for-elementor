<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Control_Media;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WCF_ADDONS\WCF_Button_Trait;

if (! defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

/**
 * Image Accordion
 *
 * Elementor widget for image accordion.
 *
 * @since 1.0.0
 */
class Image_Accordion extends Widget_Base
{

	use  WCF_Button_Trait;

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_name()
	{
		return 'wcf--image-accordion';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_title()
	{
		return esc_html__('Image Accordion', 'animation-addons-for-elementor');
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_icon()
	{
		return 'wcf eicon-accordion';
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
	public function get_categories()
	{
		return ['weal-coder-addon'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['wcf--image-accordion'];
	}

	/**
	 * Requires js files.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'wcf--image-accordion' ];
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
	protected function register_controls()
	{

		// Layout Controls
		$this->start_controls_section(
			'section_image_accordion',
			[
				'label' => esc_html__('Image Accordion', 'animation-addons-for-elementor'),
			]
		);

		$this->register_image_accordions_content();

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'    => 'image_size',
				'exclude' => ['custom'],
				'include' => [],
				'default' => 'full',
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'   => esc_html__('Title HTML Tag', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'default' => 'h4',
			]
		);

		$this->add_control(
			'link_type',
			[
				'label'     => esc_html__('Link Type', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'button',
				'separator' => 'before',
				'options'   => [
					'none'   => esc_html__('None', 'animation-addons-for-elementor'),
					'button' => esc_html__('Button', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'accordion_layout',
			[
				'label'       => esc_html__('Layout', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::SELECT,
				'label_block' => false,
				'options'     => [
					'horizontal' => esc_html__('Horizontal', 'animation-addons-for-elementor'),
					'vertical'   => esc_html__('Vertical', 'animation-addons-for-elementor'),
				],
				'default'     => 'horizontal',
			]
		);

		$dropdown_options = [
			'' => esc_html__('None', 'animation-addons-for-elementor'),
		];

		$excluded_breakpoints = [
			'widescreen',
		];

		foreach (Plugin::$instance->breakpoints->get_active_breakpoints() as $breakpoint_key => $breakpoint_instance) {
			// Exclude the larger breakpoints from the dropdown selector.
			if (in_array($breakpoint_key, $excluded_breakpoints, true)) {
				continue;
			}

			$dropdown_options[$breakpoint_key] = sprintf(
				/* translators: 1: Breakpoint label, 2: `>` character, 3: Breakpoint value. */
				esc_html__('%1$s (%2$s %3$dpx)', 'animation-addons-for-elementor'),
				$breakpoint_instance->get_label(),
				'>',
				$breakpoint_instance->get_value()
			);
		}

		$this->add_control(
			'mobile_breakpoint',
			[
				'label'              => esc_html__('Breakpoint', 'animation-addons-for-elementor'),
				'type'               => Controls_Manager::SELECT,
				'separator'          => 'before',
				'description'        => esc_html__('Note: Choose at which breakpoint it will behave across devices or viewport sizes.', 'animation-addons-for-elementor'),
				'options'            => $dropdown_options,
				'default'            => 'mobile',
				'condition'          => ['accordion_layout' => 'horizontal']
			]
		);

		$this->add_control(
			'expand_style',
			[
				'label'              => esc_html__('Expand On', 'animation-addons-for-elementor'),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'hover',
				'frontend_available' => true,
				'options'            => [
					'hover' => esc_html__('Hover', 'animation-addons-for-elementor'),
					'click' => esc_html__('Click', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_responsive_control(
			'accordion_height',
			[
				'label'      => esc_html__('Height', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
					'{{WRAPPER}} .wcf__image-accordion' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		//button
		$this->start_controls_section(
			'section_button_content',
			[
				'label'     => esc_html__('Button', 'animation-addons-for-elementor'),
				'condition' => [
					'link_type' => 'button',
				],
			]
		);

		//button content
		$this->register_button_content_controls(['btn_text' => 'Reade More '], ['btn_link' => false]);

		$this->end_controls_section();;

		// Content style Controls
		$this->register_content_style_controls();

		//button style
		$this->start_controls_section(
			'section_btn_style',
			[
				'label'     => esc_html__('Button', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'link_type' => 'button',
				],
			]
		);

		$this->register_button_style_controls();

		$this->end_controls_section();
	}

	protected function register_image_accordions_content()
	{
		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			[
				'label'   => esc_html__('Image', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		// Title
		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__('Title', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Siyantika Glory', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your title', 'animation-addons-for-elementor'),
			]
		);

		// Sub Title
		$repeater->add_control(
			'subtitle',
			[
				'label'       => esc_html__('Sub Title', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Modelling - 2012', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your sub title', 'animation-addons-for-elementor'),
			]
		);
		// Description
		$repeater->add_control(
			'description',
			[
				'label'       => esc_html__('Description', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 10,
				'default'     => esc_html__('Hatha yoga built on a harmonious balance between body strength and softness', 'animation-addons-for-elementor'),
				'placeholder' => esc_html__('Type your description', 'animation-addons-for-elementor'),
			]
		);

		$repeater->add_control(
			'details_link',
			[
				'label'       => esc_html__('Link', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [
					'active' => true,
				],
				'placeholder' => 'https://your-link.com',
			]
		);

		$this->add_control(
			'accordions',
			[
				'label'   => esc_html__('Accordions', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [[], [], [], [], []],
			]
		);
	}

	protected function register_content_style_controls()
	{

		$this->start_controls_section(
			'section_content_style',
			[
				'label' => esc_html__('Content', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'overlay_color',
			[
				'label' => esc_html__('Overlay Color', 'animation-addons-for-elementor'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .accordion-item:after' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'content_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Title
		$this->add_control(
			'title_heading',
			[
				'label'     => esc_html__('Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'title_space',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'title_hover_space',
			[
				'label'     => esc_html__('Hover Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--image-box.style-3:hover .title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'image_box_style' => '3',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		// Sub Title
		$this->add_control(
			'subtitle_heading',
			[
				'label'     => esc_html__('Sub Title', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'subtitle_space',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'subtitle_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .subtitle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .subtitle',
			]
		);

		// Description
		$this->add_control(
			'desc_heading',
			[
				'label'     => esc_html__('Description', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'desc_space',
			[
				'label'     => esc_html__('Spacing', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .description',
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
	protected function render()
	{
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute(
			'wrapper',
			'class',
			[
				'wcf__image-accordion',
				'accordion-layout-' . $settings['accordion_layout']
			]
		);
?>
		<?php if ('horizontal' === $settings['accordion_layout'] && ! empty($settings['mobile_breakpoint'])): ?>
			<?php $breakpoint = Plugin::$instance->breakpoints->get_active_breakpoints()[$settings['mobile_breakpoint']]->get_value(); ?>
			<style>
				@media (max-width: <?php echo esc_attr($breakpoint) ?>px) {
					.wcf__image-accordion {
						flex-direction: column !important;
					}
				}
			</style>
		<?php endif; ?>

		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<?php
			foreach ($settings['accordions'] as $index => $item) {
				$this->render_accordion($settings, $item, $index);
			}
			?>
		</div>
	<?php
	}

	protected function render_accordion($settings, $item, $index)
	{
		$image_url = Group_Control_Image_Size::get_attachment_image_src($item['image']['id'], 'image_size', $settings);

		if (! $image_url && isset($item['image']['url'])) {
			$image_url = $item['image']['url'];
		}
	?>
		<div class="accordion-item" style="background-image: url(<?php echo esc_url($image_url); ?>)">
			<div class="content">
				<?php if (! empty($item['subtitle'])) : ?>
					<div class="subtitle"><?php echo esc_html($item['subtitle']); ?></div>
				<?php endif; ?>

				<<?php Utils::print_validated_html_tag($settings['title_tag']); ?> class="title">
					<?php $this->print_unescaped_setting('title', 'accordions', $index); ?>
				</<?php Utils::print_validated_html_tag($settings['title_tag']); ?>>

				<?php if (! empty($item['description'])) : ?>
					<div class="description"><?php echo esc_html($item['description']); ?></div>
				<?php endif; ?>

				<?php
				if ('button' === $settings['link_type']) :
					$this->render_button($settings, 'details_link', 'accordions', $index);
				endif;
				?>
			</div>
		</div>
<?php
	}
}
