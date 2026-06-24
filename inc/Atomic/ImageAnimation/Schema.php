<?php
namespace WCF_ADDONS\Atomic\ImageAnimation;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Animation schema. Applies to e-image and e-svg widgets only.
 *
 * Effect values mirror v3 (reveal / scale / stretch). Each has its own
 * gated fields; visibility is enforced JS-side via predicates.js, the
 * schema just declares the props.
 */
final class Schema {

	/* ---- section anchor ---- */
	const IMG_SECTION_ANCHOR = 'aae_img_section_anchor';

	/* ---- repeater: full interactions list ----
	 * Per-breakpoint array of flat image interaction rows. JS owns the row
	 * shape; PHP round-trips and re-emits to the InteractionsMap as rows[].
	 */
	const IMG_INTERACTIONS = 'aae_img_interactions';

	/* ---- editor toggle (shared across all interactions) ---- */
	const IMG_ENABLE_EDITOR = 'aae_img_enable_editor';

	/** Atomic widget types this section appears on. */
	public static function image_animation_widgets(): array {
		return [ 'e-image', 'e-svg', 'e-aae-a-post-image' ];
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::IMG_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );
		$schema[ self::IMG_INTERACTIONS ]   = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => [] ] );
		$schema[ self::IMG_ENABLE_EDITOR ]  = Boolean_Prop_Type::make()->default( false );

		return $schema;
	}
}
