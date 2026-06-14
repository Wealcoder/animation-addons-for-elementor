<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostTitle;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Title extends Atomic_Widget_Base
{
	use Has_Template;

	public static function get_element_type(): string
	{
		return 'e-aae-a-post-title';
	}

	public function get_title()
	{
		return esc_html__('AAE Post Title', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'eicon-post-title';
	}

	public function get_keywords()
	{
		return ['post', 'title', 'heading', 'atomic', 'dynamic'];
	}

	protected static function define_props_schema(): array
	{
		$post_title = get_the_title();
		if (empty($post_title)) {
			$post_title = __('Default Post Title', 'animation-addons-for-elementor');
		}

		return [
			'classes' => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),
			'tag' => String_Prop_Type::make()->default('h2'),
			'post_title' => String_Prop_Type::make()->default($post_title),
		];
	}

	protected function define_atomic_controls(): array
	{
		return [
			Section::make()
				->set_label(__('Title Settings', 'animation-addons-for-elementor'))
				->set_items([
					Select_Control::bind_to('tag')
						->set_label(__('HTML Tag', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'h1', 'label' => 'H1'],
							['value' => 'h2', 'label' => 'H2'],
							['value' => 'h3', 'label' => 'H3'],
							['value' => 'h4', 'label' => 'H4'],
							['value' => 'h5', 'label' => 'H5'],
							['value' => 'h6', 'label' => 'H6'],
							['value' => 'p', 'label' => 'p'],
							['value' => 'div', 'label' => 'div'],
							['value' => 'span', 'label' => 'span'],
						]),
				]),
		];
	}

	protected function define_base_styles(): array
	{
		$wrapper_styles = [
			'display' => String_Prop_Type::generate('block'),
			'width' => String_Prop_Type::generate('100%'),
			'color' => Color_Prop_Type::generate('#333333'),
			'text-align' => String_Prop_Type::generate('left'),
			'margin' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			]),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant(Style_Variant::make()->add_props($wrapper_styles)),
		];
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-post-title' => __DIR__ . '/aae-a-post-title.html.twig',
		];
	}

	public function get_style_depends(): array
	{
		return []; // No external CSS needed! Handled natively.
	}

	// This makes the title natively available to JS/Twig!
	public function get_atomic_settings(): array
	{
		$settings = parent::get_atomic_settings();

		// Fetch the current post title
		$settings['post_title'] = get_the_title();

		// Fallback for editor if no title exists
		if (empty($settings['post_title']) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
			$settings['post_title'] = __('Sample Post Title', 'animation-addons-for-elementor');
		}

		return $settings;
	}
}
