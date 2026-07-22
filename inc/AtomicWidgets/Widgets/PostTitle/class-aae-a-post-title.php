<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostTitle;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Title extends Atomic_Widget_Base
{
	use Has_Template;

	private const LINK_BASE_STYLE_KEY = 'link-base';

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
		// Editor preview: sample a random post WITH a featured image (shared
		// helper — the Post Image widget previews the SAME post) instead of the
		// edited page's own title, which reads as broken in a loop preview.
		$post_title = '';
		if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			if ($sample) {
				$post_title = get_the_title($sample);
			}
		}
		if (empty($post_title)) {
			$post_title = get_the_title();
		}
		if (empty($post_title)) {
			$post_title = __('Default Post Title', 'animation-addons-for-elementor');
		}

		$has_limit = Dependency_Manager::make()
			->where([
				'operator' => 'ne',
				'path'     => ['limit_by'],
				'value'    => 'none',
				'effect'   => 'hide',
			])
			->get();

		return [
			'classes' => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),
			'link' => Link_Prop_Type::make(),
			'tag' => String_Prop_Type::make()->default('h2'),
			'post_title' => String_Prop_Type::make()->default($post_title),
			// Default: CSS line-clamp at 2 lines — uniform card heights out of the
			// box, works identically in editor + frontend (pure CSS, no trim).
			'limit_by' => String_Prop_Type::make()->default('line'),
			'title_limit' => Number_Prop_Type::make()->default(2)->set_dependencies($has_limit),
		];
	}

	protected function define_atomic_controls(): array
	{
		return [
			Section::make()
				->set_id('settings')
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

					Select_Control::bind_to('limit_by')
						->set_label(__('Limit By', 'animation-addons-for-elementor'))
						->set_options([
							['value' => 'none', 'label' => __('None', 'animation-addons-for-elementor')],
							['value' => 'word', 'label' => __('Word Count', 'animation-addons-for-elementor')],
							['value' => 'char', 'label' => __('Character Count', 'animation-addons-for-elementor')],
							['value' => 'line', 'label' => __('Line Clamp (CSS)', 'animation-addons-for-elementor')],
						]),

					Number_Control::bind_to('title_limit')
						->set_label(__('Limit', 'animation-addons-for-elementor'))
						->set_min(1)
						->set_max(1000),

					Link_Control::bind_to('link')
						->set_label(__('Link', 'animation-addons-for-elementor'))
						->set_placeholder(__('Type or paste your URL', 'animation-addons-for-elementor')),
				]),
		];
	}

	protected function define_base_styles(): array
	{
		$wrapper_styles = [
			'display' => String_Prop_Type::generate('block'),
			'width' => String_Prop_Type::generate('100%'),
			'margin' => \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate([
				'block-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'block-end' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			]),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant(Style_Variant::make()->add_props($wrapper_styles)),
			self::LINK_BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop('all', 'unset')
						->add_prop('cursor', 'pointer')
						->add_prop('display', 'block')
						->add_prop('width', '100%')
				),
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

		// Fallback for editor if no title exists: preview the shared sample
		// post (random, has a featured image) so the card looks real.
		if (empty($settings['post_title']) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			$settings['post_title'] = $sample
				? get_the_title($sample)
				: __('Sample Post Title', 'animation-addons-for-elementor');
		}

		// Apply limit if set
		$limit_by = ! empty( $settings['limit_by'] ) ? $settings['limit_by'] : 'none';
		$limit_val = isset( $settings['title_limit'] ) ? (int) $settings['title_limit'] : 10;

		if ( 'word' === $limit_by ) {
			$settings['post_title'] = wp_trim_words( $settings['post_title'], $limit_val, '...' );
		} elseif ( 'char' === $limit_by ) {
			if ( mb_strlen( $settings['post_title'] ) > $limit_val ) {
				$settings['post_title'] = mb_substr( $settings['post_title'], 0, $limit_val ) . '...';
			}
		}

		return $settings;
	}
}
