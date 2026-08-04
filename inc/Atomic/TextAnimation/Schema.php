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

	/* ---- repeater: full interactions list ----
	 * Per-breakpoint array of flat text interaction rows. JS owns the row
	 * shape; PHP round-trips and re-emits to the InteractionsMap as rows[].
	 */
	const TEXT_INTERACTIONS = 'aae_text_interactions';

	/* ---- editor toggle (shared across all interactions) ---- */
	const TEXT_ENABLE_EDITOR = 'aae_text_enable_editor';

	public function register(): void
	{
		add_filter('elementor/atomic-widgets/props-schema', [$this, 'add_animation_props']);
	}

	public function add_animation_props(array $schema): array
	{
		if (! class_exists(String_Prop_Type::class)) {
			return $schema;
		}

		$schema[self::TEXT_SECTION_ANCHOR] = Section_Anchor_Prop_Type::make()->default('');
		$schema[self::TEXT_INTERACTIONS]   = Responsive_Json_Prop_Type::make()->default(['desktop' => []]);
		$schema[self::TEXT_ENABLE_EDITOR]  = Boolean_Prop_Type::make()->default(false);

		return $schema;
	}

	/* ---------- widget targets ---------- */

	public static function text_animation_widgets(): array
	{
		return ['e-heading', 'e-paragraph', 'e-button', 'e-aae-a-post-title','e-aae-a-advanced-heading'];
	}
}
