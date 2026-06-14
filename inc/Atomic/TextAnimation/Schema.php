<?php

namespace WCF_ADDONS\Atomic\TextAnimation;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Text-animation schema. Visibility for every responsive field is driven by
 * the JS-side config table (src/modules/atomic/extensions/text-animation/
 * config.js + predicates.js) — Schema only registers the props. No
 * set_dependencies on responsive props: their stored value is a breakpoint
 * map and the dep engine compares scalars.
 *
 * Non-responsive props (booleans, single-value strings) keep their normal
 * registration but with deps stripped — those that previously gated on
 * responsive sources (TEXT_EFFECT / TEXT_TRIGGER / TEXT_WRAPPER) can no
 * longer be evaluated reliably, so the JS section is the source of truth
 * for visibility everywhere.
 */
final class Schema
{

	/* ---- section anchor ---- */
	const TEXT_SECTION_ANCHOR = 'aae_text_section_anchor';

	/* ---- text animation prop names ---- */
	const TEXT_EFFECT           = 'aae_text_effect';
	const TEXT_TRIGGER          = 'aae_text_trigger';
	const TEXT_TRIGGER_SELECTOR = 'aae_text_trigger_selector';
	const TEXT_WRAPPER          = 'aae_text_wrapper';
	const TEXT_WRAPPER_SELECTOR = 'aae_text_wrapper_selector';
	const TEXT_DELAY            = 'aae_text_delay';
	const TEXT_DURATION         = 'aae_text_duration';
	const TEXT_STAGGER          = 'aae_text_stagger';
	const TEXT_TRANSLATE_X      = 'aae_text_translate_x';
	const TEXT_TRANSLATE_Y      = 'aae_text_translate_y';
	const TEXT_ROTATION_DIR     = 'aae_text_rotation_dir';
	const TEXT_ROTATION         = 'aae_text_rotation';
	const TEXT_TRANSFORM_ORIGIN = 'aae_text_transform_origin';
	const TEXT_TEXT_SHADOW      = 'aae_text_text_shadow';
	const TEXT_SPIN_COLOR       = 'aae_text_spin_color';
	const TEXT_EASE             = 'aae_text_ease';
	const TEXT_ENABLE_EDITOR    = 'aae_text_enable_editor';

	/* ---- scroll trigger settings ---- */
	const TEXT_START_TRIGGER  = 'aae_text_start_trigger';
	const TEXT_END_TRIGGER    = 'aae_text_end_trigger';
	const TEXT_START_POSITION = 'aae_text_start_position';
	const TEXT_START_CUSTOM   = 'aae_text_start_custom';
	const TEXT_END_POSITION   = 'aae_text_end_position';
	const TEXT_END_CUSTOM     = 'aae_text_end_custom';
	const TEXT_MARKERS        = 'aae_text_markers';

	/* ---- text-invert specific ---- */
	const TEXT_INVERT_START = 'aae_text_invert_start';
	const TEXT_INVERT_END   = 'aae_text_invert_end';

	/* ---- text-spin specific ---- */
	const TEXT_SPIN_START  = 'aae_text_spin_start';
	const TEXT_SPIN_END    = 'aae_text_spin_end';
	const TEXT_SPIN_TOGGLE = 'aae_text_spin_toggle';

	/* ---- text-scale specific ---- */
	const TEXT_SCALE_EASE  = 'aae_text_scale_ease';
	const TEXT_SCALE_NUM   = 'aae_text_scale_num';
	const TEXT_SCALE_BREAK = 'aae_text_scale_break';

	/** Defaults for responsive numeric props. */
	const RESPONSIVE_NUMBER_SETTINGS = [
		self::TEXT_DELAY       => 0.15,
		self::TEXT_DURATION    => 1,
		self::TEXT_STAGGER     => 0.02,
		self::TEXT_TRANSLATE_X => 20,
		self::TEXT_TRANSLATE_Y => 0,
		self::TEXT_ROTATION    => -80,
		self::TEXT_SCALE_NUM   => 1.5,
	];

	/** Effects that expose Duration / Stagger (v3 excludes spin/invert). */
	const TEXT_DURATION_EFFECTS = ['char', 'word', 'text_reveal', 'text_move', 'text_scale'];

	/** Effects that expose Transform-X / Transform-Y. */
	const TEXT_TRANSLATE_EFFECTS = ['char', 'word'];

	/** Effects that expose Rotation. */
	const TEXT_MOVE_EFFECTS = ['text_move'];

	/** Single-effect families — named so Render.php doesn't carry string literals. */
	const TEXT_INVERT_EFFECTS = ['text_invert'];
	const TEXT_SPIN_EFFECTS   = ['text_spin', 'text_spin_color'];
	const TEXT_SCALE_EFFECTS  = ['text_scale'];

	public function register(): void
	{
		add_filter('elementor/atomic-widgets/props-schema', [$this, 'add_animation_props']);
	}

	public function add_animation_props(array $schema): array
	{
		if (! class_exists(String_Prop_Type::class)) {
			return $schema;
		}

		/* ---------- section anchor ---------- */
		$schema[self::TEXT_SECTION_ANCHOR] = Section_Anchor_Prop_Type::make()->default('');

		/* ---------- responsive props (visibility via JS section, no PHP deps) ---------- */

		$schema[self::TEXT_EFFECT]           = Responsive_Json_Prop_Type::make()->default(['desktop' => 'none']);
		$schema[self::TEXT_TRIGGER]          = Responsive_Json_Prop_Type::make()->default(['desktop' => 'on_scroll']);
		$schema[self::TEXT_TRIGGER_SELECTOR] = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);
		$schema[self::TEXT_WRAPPER]          = Responsive_Json_Prop_Type::make()->default(['desktop' => 'default']);
		$schema[self::TEXT_WRAPPER_SELECTOR] = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);

		$schema[self::TEXT_START_TRIGGER]    = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);
		$schema[self::TEXT_END_TRIGGER]      = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);
		$schema[self::TEXT_START_POSITION]   = Responsive_Json_Prop_Type::make()->default(['desktop' => 'top top']);
		$schema[self::TEXT_START_CUSTOM]     = Responsive_Json_Prop_Type::make()->default(['desktop' => 'top top']);
		$schema[self::TEXT_END_POSITION]     = Responsive_Json_Prop_Type::make()->default(['desktop' => 'bottom top']);
		$schema[self::TEXT_END_CUSTOM]       = Responsive_Json_Prop_Type::make()->default(['desktop' => 'bottom top']);


		$schema[self::TEXT_DELAY]       = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_DELAY]]);
		$schema[self::TEXT_DURATION]    = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_DURATION]]);
		$schema[self::TEXT_STAGGER]     = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_STAGGER]]);
		$schema[self::TEXT_TRANSLATE_X] = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_TRANSLATE_X]]);
		$schema[self::TEXT_TRANSLATE_Y] = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_TRANSLATE_Y]]);

		$schema[self::TEXT_ROTATION_DIR]     = Responsive_Json_Prop_Type::make()->default(['desktop' => 'x']);
		$schema[self::TEXT_ROTATION]         = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_ROTATION]]);
		$schema[self::TEXT_TRANSFORM_ORIGIN] = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);
		$schema[self::TEXT_TEXT_SHADOW]      = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);

		$schema[self::TEXT_INVERT_START] = Responsive_Json_Prop_Type::make()->default(['desktop' => 'top 85%']);
		$schema[self::TEXT_INVERT_END]   = Responsive_Json_Prop_Type::make()->default(['desktop' => 'bottom center']);

		$schema[self::TEXT_SPIN_START]  = Responsive_Json_Prop_Type::make()->default(['desktop' => 'top 50%']);
		$schema[self::TEXT_SPIN_END]    = Responsive_Json_Prop_Type::make()->default(['desktop' => 'bottom 30%']);
		$schema[self::TEXT_SPIN_TOGGLE] = Responsive_Json_Prop_Type::make()->default(['desktop' => 'play none none reverse']);

		$schema[self::TEXT_SCALE_EASE]  = Responsive_Json_Prop_Type::make()->default(['desktop' => 'back']);
		$schema[self::TEXT_SCALE_NUM]   = Responsive_Json_Prop_Type::make()->default(['desktop' => self::RESPONSIVE_NUMBER_SETTINGS[self::TEXT_SCALE_NUM]]);
		$schema[self::TEXT_SCALE_BREAK] = Responsive_Json_Prop_Type::make()->default(['desktop' => 'lines']);
		$schema[self::TEXT_SPIN_COLOR] = Responsive_Json_Prop_Type::make()->default(['desktop' => '#000000']);
		$schema[self::TEXT_EASE]        = Responsive_Json_Prop_Type::make()->default(['desktop' => '']);

		/* ---------- non-responsive props (deps dropped — visibility now JS-driven) ---------- */

		$schema[self::TEXT_MARKERS]       = Boolean_Prop_Type::make()->default(false);
		$schema[self::TEXT_ENABLE_EDITOR] = Boolean_Prop_Type::make()->default(false);

		return $schema;
	}

	/* ---------- widget targets ---------- */

	public static function text_animation_widgets(): array
	{
		return ['e-heading', 'e-paragraph', 'e-button', 'e-aae-a-post-title'];
	}

	public static function is_premium_effect( $effect ): bool {
		$core_effects = array_merge(
			self::TEXT_DURATION_EFFECTS,
			self::TEXT_INVERT_EFFECTS,
			self::TEXT_SPIN_EFFECTS,
			['none']
		);
		return ! empty( $effect ) && ! in_array( $effect, $core_effects, true );
	}
}
