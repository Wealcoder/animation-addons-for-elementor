<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\BtnPro;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Transform_Functions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transform\Functions\Transform_Scale_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * AAE Button Pro — an open atomic container styled like a button.
 */
class AAE_A_Btn_Pro extends Atomic_Element_Base
{
	use Has_Element_Template;

	public function __construct($data = [], $args = null)
	{
		parent::__construct($data, $args);
		$this->meta('is_container', true);
	}

	public static function get_type()
	{
		return 'e-aae-a-btn-pro';
	}

	public static function get_element_type(): string
	{
		return 'e-aae-a-btn-pro';
	}

	public function get_title()
	{
		return esc_html__('Button Pro', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'eicon-button';
	}

	public function get_keywords()
	{
		return ['button', 'aae', 'cta', 'call to action', 'atomic', 'link', 'container', 'pro'];
	}

	public function get_categories(): array
	{
		return ['aae-atomic-general'];
	}

	/**
	 * Panel category for the Elements panel.
	 *
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' ("Atomic Elements") bucket. Delegate so both stay in sync.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array
	{
		return [
			'classes'    => Classes_Prop_Type::make()->default([]),
			'attributes' => Attributes_Prop_Type::make()->meta(Overridable_Prop_Type::ignore()),

			'btn_url'      => String_Prop_Type::make()->default(''),
			'btn_target'   => String_Prop_Type::make()->default('_self'),
			'btn_nofollow' => Boolean_Prop_Type::make()->default(false),
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
				->set_label(__('Button', 'animation-addons-for-elementor'))
				->set_id('content')
				->set_items([
					Text_Control::bind_to('btn_url')
						->set_label(__('URL', 'animation-addons-for-elementor')),

					Select_Control::bind_to('btn_target')
						->set_label(__('Open In', 'animation-addons-for-elementor'))
						->set_options([
							['value' => '_self',  'label' => __('Same Window', 'animation-addons-for-elementor')],
							['value' => '_blank', 'label' => __('New Window',  'animation-addons-for-elementor')],
						]),

					Switch_Control::bind_to('btn_nofollow')
						->set_label(__('Add Nofollow', 'animation-addons-for-elementor')),
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
		$transition_200ms = Transition_Prop_Type::generate([
			Selection_Size_Prop_Type::generate([
				'selection' => Key_Value_Prop_Type::generate([
					'key'   => String_Prop_Type::generate('All properties'),
					'value' => String_Prop_Type::generate('all'),
				]),
				'size' => Size_Prop_Type::generate(['size' => 200, 'unit' => 'ms']),
			]),
		]);

		$transition_300ms = Transition_Prop_Type::generate([
			Selection_Size_Prop_Type::generate([
				'selection' => Key_Value_Prop_Type::generate([
					'key'   => String_Prop_Type::generate('All properties'),
					'value' => String_Prop_Type::generate('all'),
				]),
				'size' => Size_Prop_Type::generate(['size' => 300, 'unit' => 'ms']),
			]),
		]);

		// Basic pill button — solid fill, no border, rounded ends.
		$button_styles = [
			'cursor'      => String_Prop_Type::generate('pointer'),
			'font-weight' => String_Prop_Type::generate('700'),
			'transition'  => $transition_200ms,

			'padding' => Dimensions_Prop_Type::generate([
				'block-start'  => Size_Prop_Type::generate(['size' => 10, 'unit' => 'px']),
				'inline-end'   => Size_Prop_Type::generate(['size' => 20, 'unit' => 'px']),
				'block-end'    => Size_Prop_Type::generate(['size' => 10, 'unit' => 'px']),
				'inline-start' => Size_Prop_Type::generate(['size' => 20, 'unit' => 'px']),
			]),

			'border-radius' => Size_Prop_Type::generate(['size' => 100, 'unit' => 'px']),
			'background'    => Background_Prop_Type::generate([
				'color' => Color_Prop_Type::generate('#cfef00'),
			]),

			'border-width' => Size_Prop_Type::generate(['size' => 1, 'unit' => 'px']),
			'border-style' => String_Prop_Type::generate('solid'),
			'border-color' => Color_Prop_Type::generate('transparent'),

			'display'     => String_Prop_Type::generate('flex'),
			'align-items' => String_Prop_Type::generate('center'),
			'font-size'   => Size_Prop_Type::generate(['size' => 15, 'unit' => 'px']),
		];

		// Hover — a touch darker.
		$button_hover_styles = [
			'background' => Background_Prop_Type::generate([
				'color' => Color_Prop_Type::generate('#c4e201'),
			]),
		];

		// Active/press — a quick shrink.
		$button_active_styles = [
			'transform' => Transform_Prop_Type::generate([
				'transform-functions' => Transform_Functions_Prop_Type::generate([
					Transform_Scale_Prop_Type::generate([
						'x' => Number_Prop_Type::generate(0.95),
						'y' => Number_Prop_Type::generate(0.95),
					]),
				]),
			]),
		];

		// Icon — sits after the label with a 10px gap; the hover slide
		// (translateX on the button's own hover) is a cross-element effect
		// the atomic style schema can't express (a Style_Variant's HOVER
		// state only ever reacts to THIS element's own hover), so it lives
		// in btn-pro.scss instead.
		$icon_styles = [
			'width'  => Size_Prop_Type::generate(['size' => 34, 'unit' => 'px']),
			'margin' => Dimensions_Prop_Type::generate([
				'block-start'  => Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-end'   => Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'block-end'    => Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
				'inline-start' => Size_Prop_Type::generate(['size' => 10, 'unit' => 'px']),
			]),
			'transition' => $transition_300ms,
		];

		return [
			'base' => Style_Definition::make()
				->add_variant(Style_Variant::make()->add_props($button_styles))
				->add_variant(Style_Variant::make()->set_state(Style_States::HOVER)->add_props($button_hover_styles))
				->add_variant(Style_Variant::make()->set_state(Style_States::ACTIVE)->add_props($button_active_styles)),

			// Label carries no props of its own in this design — the key is
			// kept so its class stays a registered style (an unregistered
			// class on the child's `classes` prop shows up as Elementor's
			// "missing classes" panel warning, see class-atomic.php's
			// documented gotcha for this pattern).
			'label' => Style_Definition::make()
				->set_label(__('Label', 'animation-addons-for-elementor'))
				->add_variant(Style_Variant::make()),

			'icon' => Style_Definition::make()
				->set_label(__('Icon', 'animation-addons-for-elementor'))
				->add_variant(Style_Variant::make()->add_props($icon_styles)),
		];
	}

	protected function define_default_children()
	{
		// Same "{element_type}-{key}" naming as define_base_styles() above —
		// see AAE_A_Btn::define_default_children() / Accordion Item's
		// header_icon for the same convention.
		$label_class = static::get_element_type() . '-label';
		$icon_class  = static::get_element_type() . '-icon';

		// Label first, then icon — the icon sits inline after the text with
		// a margin-start gap (see the 'icon' style key's `margin` above), so
		// DOM order is also visual order here.
		return [
			Atomic_Paragraph::generate()
				->settings([
					'classes'   => Classes_Prop_Type::generate([$label_class]),
					'paragraph' => Html_V3_Prop_Type::generate([
						'content'  => String_Prop_Type::generate('Click here'),
						'children' => [],
					]),
					'tag' => String_Prop_Type::generate('span'),
				])
				->build(),
			Atomic_Svg::generate()
				->settings([
					'classes' => Classes_Prop_Type::generate([$icon_class]),
					'svg'     => Svg_Src_Prop_Type::generate([
						'id'  => null,
						'url' => Url_Prop_Type::generate(WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/BtnPro/assets/icons/arrow-right.svg'),
					]),
				])
				->build(),
		];
	}

	protected function define_allowed_child_types()
	{
		return [
			'widget',
			'e-heading',
			'e-paragraph',
			'e-svg',
			'e-button',
			'e-image',
			'e-divider',
			'e-flexbox',
			'e-div-block',
		];
	}

	protected function define_default_html_tag()
	{
		return 'a';
	}

	protected function get_templates(): array
	{
		return [
			'elementor/elements/aae-a-btn-pro' => __DIR__ . '/aae-a-btn-pro.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-btn-pro-js'];
	}

	public function get_style_depends(): array
	{
		return ['aae-a-btn-pro-css'];
	}
}
