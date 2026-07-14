<?php
namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\Bootstrap;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global Lightbox schema.
 *
 * Registers the shared `lb_*` props onto the atomic elements that get the
 * Lightbox section auto-injected (core `e-image` in Phase 1). Custom AAE
 * widgets that opt in via {@see Lightbox_Manager::register_lightbox_controls()}
 * define these same props locally in their own props schema — the constant
 * names are the single source of truth for both paths.
 *
 * Phase 1 scope: image + gallery. The `type` prop already carries the full
 * enum so future content-types (video/iframe/html/ajax) slot in without a
 * schema migration.
 */
final class Schema {

	/* ---- section anchor ---- */
	const LB_SECTION_ANCHOR = 'aae_lb_section_anchor';

	/* ---- responsive enable switch ---- */
	const LB_ENABLE = 'aae_lb_enable';

	/* ---- content ---- */
	const LB_TYPE    = 'aae_lb_type';    // image | video | iframe | html | ajax | auto
	const LB_GROUP   = 'aae_lb_group';   // gallery id ('' = standalone single item)
	const LB_TITLE   = 'aae_lb_title';
	const LB_CAPTION = 'aae_lb_caption';

	/* ---- chrome ---- */
	const LB_ANIM = 'aae_lb_anim';       // fade | zoom | slide

	/** Atomic element types that get the Lightbox section auto-injected. */
	public static function lightbox_widgets(): array {
		/**
		 * Filter the element types the global Lightbox section is added to.
		 *
		 * @param string[] $types Default target element types.
		 */
		return apply_filters( 'aae/lightbox/target_types', [ 'e-image' ] );
	}

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( Boolean_Prop_Type::class ) ) {
			return $schema;
		}

		// Enable switch — bound to a Switch_Control, so it MUST be a Boolean
		// prop (a Switch emits a bool; a responsive/aae-rj prop would reject it
		// with "invalid value" on save).
		$schema[ self::LB_ENABLE ] = Boolean_Prop_Type::make()->default( false );

		$schema[ self::LB_TYPE ]    = String_Prop_Type::make()->default( 'auto' );
		$schema[ self::LB_GROUP ]   = String_Prop_Type::make()->default( '' );
		$schema[ self::LB_TITLE ]   = String_Prop_Type::make()->default( '' );
		$schema[ self::LB_CAPTION ] = String_Prop_Type::make()->default( '' );
		$schema[ self::LB_ANIM ]    = String_Prop_Type::make()->default( 'zoom' );

		return $schema;
	}
}
