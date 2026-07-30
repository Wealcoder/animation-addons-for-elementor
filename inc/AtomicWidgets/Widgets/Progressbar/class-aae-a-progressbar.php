<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Progressbar;

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base')) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/Parts/class-aae-a-progressbar-track.php';
require_once __DIR__ . '/Parts/class-aae-a-progressbar-label.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar_Track;
use WCF_ADDONS\AtomicWidgets\Widgets\Progressbar\AAE_A_Progressbar_Label;

/**
 * AAE Basic Progress Bar — an open atomic container styled like a progress
 * bar. Mirrors the Btn/BtnPro pattern: no `style` select here — the actual
 * look (line / circle / dot) is composed entirely from real child elements
 * (Track+Fill, an SVG ring, or dot spans) supplied by a preset, or by hand.
 * This widget only owns the data that's genuinely per-instance and needs JS
 * (the percentage + whether to show it); everything cosmetic is a native
 * child element the user can restyle with Elementor's own Style tab.
 *
 * The default Track/Fill/Label are each a dedicated small widget type
 * (AAE_A_Progressbar_Track/_Fill/_Label) carrying its own fixed look via its
 * own define_base_styles() — see class-aae-a-progressbar-track.php for why
 * plain Div_Block/e-paragraph reuse can't express that (base styles are
 * owned by the widget TYPE, not a per-instance override).
 *
 * The bundled JS auto-detects which shape is present (.aae-progressbar-fill /
 * .aae-progressbar-path / .aae-progressbar-dot) rather than branching on a
 * stored style setting, so it animates correctly no matter which preset
 * supplied the children.
 */
class AAE_A_Progressbar extends Atomic_Element_Base
{
	use Has_Element_Template;

	const BASE_STYLE_KEY = 'base';

	public function __construct($data = [], $args = null)
	{
		parent::__construct($data, $args);
		$this->meta('is_container', true);
	}

	public static function get_type(): string
	{
		return 'e-aae-a-progressbar';
	}

	public static function get_element_type(): string
	{
		return 'e-aae-a-progressbar';
	}

	public function get_title()
	{
		return esc_html__('Progress Bar', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'eicon-skill-bar';
	}

	public function get_keywords()
	{
		return ['progressbar', 'progress', 'bar', 'basic', 'template', 'container', 'atomic'];
	}

	public function get_categories(): array
	{
		return ['aae-atomic-general'];
	}

	protected static function define_props_schema(): array
	{
		return [
			'classes'    => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			'pb_percentage'         => Number_Prop_Type::make()->default(50),
			'pb_display_percentage' => Boolean_Prop_Type::make()->default(true),
		];
	}

	protected function define_atomic_controls(): array
	{
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

		return [
			Section::make()
				->set_label(__('Presets', 'animation-addons-for-elementor'))
				->set_id('aae_presets')
				->set_items([
					AAE_A_Preset_Picker_Control::make()
						->set_label(__('Apply Preset', 'animation-addons-for-elementor'))
						->set_meta(['layout' => 'custom']),
				]),

			Section::make()
				->set_label(__('Progress Bar', 'animation-addons-for-elementor'))
				->set_id('content')
				->set_items([
					Number_Control::bind_to('pb_percentage')
						->set_label(__('Percentage', 'animation-addons-for-elementor'))
						->set_meta(['min' => 0, 'max' => 100, 'step' => 1]),

					Switch_Control::bind_to('pb_display_percentage')
						->set_label(__('Display Percentage', 'animation-addons-for-elementor')),
				]),

			Section::make()
				->set_label(__('Settings', 'animation-addons-for-elementor'))
				->set_id('settings')
				->set_items([
					Text_Control::bind_to('_cssid')
						->set_label(__('ID', 'animation-addons-for-elementor'))
						->set_meta($this->get_css_id_control_meta()),
				]),
		];
	}

	protected function define_base_styles(): array
	{
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop('display', String_Prop_Type::generate('block'))
						->add_prop('width', String_Prop_Type::generate('100%'))
						->add_prop('max-width', Size_Prop_Type::generate(['size' => 480, 'unit' => 'px']))
						->add_prop('position', String_Prop_Type::generate('relative'))
						->add_prop('margin', Dimensions_Prop_Type::generate([
							'block-start'  => Size_Prop_Type::generate(['size' => 48, 'unit' => 'px']),
							'inline-end'   => Size_Prop_Type::generate(['size' => null, 'unit' => 'auto']),
							'block-end'    => Size_Prop_Type::generate(['size' => 48, 'unit' => 'px']),
							'inline-start' => Size_Prop_Type::generate(['size' => null, 'unit' => 'auto']),
						]))
						->add_prop('padding', Dimensions_Prop_Type::generate([
							'block-start'  => Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
							'inline-end'   => Size_Prop_Type::generate(['size' => 24, 'unit' => 'px']),
							'block-end'    => Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
							'inline-start' => Size_Prop_Type::generate(['size' => 24, 'unit' => 'px']),
						]))
				),
		];
	}

	/**
	 * Out-of-the-box look for a freshly dropped instance: a plain Line bar
	 * (Track containing Fill, each its own dedicated widget type carrying
	 * real base styles) plus the percentage counter span JS animates into.
	 * Circle and Dot looks come from presets that replace these children
	 * entirely.
	 */
	protected function define_default_children()
	{
		return [
			AAE_A_Progressbar_Track::generate()
				->editor_settings(['title' => 'Track'])
				->children(
					AAE_A_Progressbar_Track::build_default_inner_children()
				)
				->build(),

			AAE_A_Progressbar_Label::generate()
				->editor_settings(['title' => 'Percentage'])
				->settings([
					'classes' => Classes_Prop_Type::generate(['aae-pb-pct']),
					'text' => Html_V3_Prop_Type::generate([
						'content'  => String_Prop_Type::generate('0%'),
						'children' => [],
					]),
					'tag' => String_Prop_Type::generate('span'),
				])
				->build(),
		];
	}

	protected function define_allowed_child_types()
	{
		return [
			'widget',
			'e-aae-a-progressbar-track',
			'e-aae-a-progressbar-label',
			'e-heading',
			'e-paragraph',
			'e-svg',
			'e-image',
			'e-divider',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function define_default_html_tag()
	{
		return 'div';
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-progressbar' => __DIR__ . '/aae-a-progressbar.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-progressbar-js'];
	}

	public function get_style_depends(): array
	{
		return ['aae-a-progressbar-css'];
	}
}
