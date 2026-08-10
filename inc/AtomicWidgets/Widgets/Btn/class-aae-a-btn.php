<?php

namespace WCF_ADDONS\AtomicWidgets\Widgets\Btn;

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
 * AAE Basic Button — an open atomic container styled like a button.
 */
class AAE_A_Btn extends Atomic_Element_Base
{
	use Has_Element_Template;

	/**
	 * Single source of truth for the icon's fixed square size.
	 *
	 * Read by BOTH define_base_styles() (the real default) AND
	 * get_frontend_css_override() (the CSS that must load after Elementor's
	 * base-desktop.css to win the tie against e-svg-base's native 65px
	 * default — see Atomic::fix_frontend_atomic_css_order()). Change ONLY
	 * this constant; a hardcoded duplicate in a .scss file would silently
	 * keep winning forever even after this value changes (see AAE_A_Btn_Pro
	 * for the incident that taught us this the hard way).
	 */
	const ICON_SIZE_PX = 30;

	public function __construct($data = [], $args = null)
	{
		parent::__construct($data, $args);
		$this->meta('is_container', true);
	}

	public static function get_type()
	{
		return 'e-aae-a-btn';
	}

	public static function get_element_type(): string
	{
		return 'e-aae-a-btn';
	}

	public function get_title()
	{
		return esc_html__('Button', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'eicon-button';
	}

	public function get_keywords()
	{
		return ['button', 'aae', 'cta', 'call to action', 'atomic', 'link', 'container'];
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

			// Preset-driven only — no panel control. Each drives its matching
			// 'aae-btn-*' hook class from the twig instead of a preset seeding
			// it into the `classes` prop, so it never shows as a Style-panel
			// chip and can never be flagged/stripped by the "Some classes are
			// missing" alert. See CLAUDE.md's "Never put a functional hook
			// class in the classes prop" for why this pattern exists.
			'aae_btn_text_flip'     => Boolean_Prop_Type::make()->default(false),
			'aae_btn_border_divide' => Boolean_Prop_Type::make()->default(false),
			'aae_btn_mask'          => Boolean_Prop_Type::make()->default(false),
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
		$button_styles = [
			'width'    => Size_Prop_Type::generate(['size' => 'max-content', 'unit' => 'custom']),
			'overflow' => String_Prop_Type::generate('hidden'),
			'position' => String_Prop_Type::generate('relative'),
			'z-index'  => Number_Prop_Type::generate(10),

			'background' => Background_Prop_Type::generate([
				'color' => Color_Prop_Type::generate('#3d405b'),
			]),
			'color' => Color_Prop_Type::generate('#ffffff'),

			'padding' => Dimensions_Prop_Type::generate([
				'block-start'  => Size_Prop_Type::generate(['size' => 12, 'unit' => 'px']),
				'inline-end'   => Size_Prop_Type::generate(['size' => 24, 'unit' => 'px']),
				'block-end'    => Size_Prop_Type::generate(['size' => 12, 'unit' => 'px']),
				'inline-start' => Size_Prop_Type::generate(['size' => 24, 'unit' => 'px']),
			]),

			'border-radius' => Size_Prop_Type::generate(['size' => 8, 'unit' => 'px']),
			'border-width'  => Size_Prop_Type::generate(['size' => 0, 'unit' => 'px']),
			// 'border-color'  => Color_Prop_Type::generate('#000000'),
			'border-style'  => String_Prop_Type::generate('solid'),

			'transition'    => Transition_Prop_Type::generate([
				Selection_Size_Prop_Type::generate([
					'selection' => Key_Value_Prop_Type::generate([
						'key'   => String_Prop_Type::generate('All properties'),
						'value' => String_Prop_Type::generate('all'),
					]),
					'size' => Size_Prop_Type::generate([
						'size' => 600,
						'unit' => 'ms',
					]),
				]),
			]),

			'display'         => String_Prop_Type::generate('inline-flex'),
			'flex-direction'  => String_Prop_Type::generate('row'),
			'gap'             => Size_Prop_Type::generate(['size' => 12, 'unit' => 'px']),
			'align-items'     => String_Prop_Type::generate('center'),

			'font-size'      => Size_Prop_Type::generate(['size' => 12, 'unit' => 'px']),
			'line-height'    => Size_Prop_Type::generate(['size' => 16, 'unit' => 'px']),
			'font-weight'    => String_Prop_Type::generate('700'),
			'text-align'     => String_Prop_Type::generate('center'),
			'text-transform' => String_Prop_Type::generate('uppercase'),
		];

		// Pressed / keyboard-focus — dim slightly.
		$button_pressed_styles = [
			'opacity' => Size_Prop_Type::generate(['size' => 85, 'unit' => '%']),
		];

		$icon_styles = [
			'width'  => Size_Prop_Type::generate(['size' => self::ICON_SIZE_PX, 'unit' => 'px']),
			'height' => Size_Prop_Type::generate(['size' => self::ICON_SIZE_PX, 'unit' => 'px']),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant(Style_Variant::make()->add_props($button_styles))
				->add_variant(Style_Variant::make()->set_state(Style_States::ACTIVE)->add_props($button_pressed_styles))
				->add_variant(Style_Variant::make()->set_state(Style_States::FOCUS)->add_props($button_pressed_styles)),

			'icon' => Style_Definition::make()
				->set_label(__('Icon', 'animation-addons-for-elementor'))
				->add_variant(Style_Variant::make()->add_props($icon_styles)),
		];
	}

	protected function define_default_children()
	{
		// Matches define_base_styles()'s "{element_type}-{key}" naming for the
		// 'icon' style key — same convention Accordion Item uses for its own
		// header_icon class (see that class's define_default_children()).
		$icon_class = static::get_element_type() . '-icon';

		// Icon first, then label — matches the reference design's flex order.
		return [
			Atomic_Svg::generate()
				->settings([
					'classes' => Classes_Prop_Type::generate([$icon_class]),
					'svg'     => Svg_Src_Prop_Type::generate([
						'id'  => null,
						'url' => Url_Prop_Type::generate(WCF_ADDONS_URL . 'inc/AtomicWidgets/Widgets/Btn/assets/icons/add-file.svg'),
					]),
				])
				->build(),
			Atomic_Paragraph::generate()
				->settings([
					'paragraph' => Html_V3_Prop_Type::generate([
						'content'  => String_Prop_Type::generate('Click here'),
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
			'elementor/elements/aae-a-btn' => __DIR__ . '/aae-a-btn.html.twig',
		];
	}

	public function get_script_depends(): array
	{
		return ['aae-a-btn-js'];
	}

	public function get_style_depends(): array
	{
		return ['aae-a-btn-css'];
	}

	/**
	 * Inline CSS that Atomic::fix_frontend_atomic_css_order() injects right
	 * after this widget's own stylesheet, once that stylesheet is guaranteed
	 * to load after Elementor's base-desktop.css.
	 *
	 * WHY: `.e-aae-a-btn-icon` and Elementor core's native `.e-svg-base`
	 * default (65px, atomic-svg.php) share the exact same selector shape
	 * (`.elementor .<class>`) and therefore the same specificity. Elementor
	 * bundles every registered atomic element's base styles into ONE cached
	 * file (base-desktop.css) ordered by registration, and its own native
	 * elements register after ours — so `.e-svg-base` lands later in that
	 * file and wins the tie on the frontend, even though the builder
	 * recomputes styles live per request and shows the correct size.
	 *
	 * Deriving this from ICON_SIZE_PX (rather than a hardcoded value in a
	 * .scss file) means changing that ONE constant is enough — no separate
	 * value to remember to keep in sync.
	 */
	public static function get_frontend_css_override(): string
	{
		return sprintf(
			'.elementor .e-aae-a-btn-icon{width:%1$dpx;height:%1$dpx}',
			self::ICON_SIZE_PX
		);
	}
}
