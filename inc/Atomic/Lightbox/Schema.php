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

	/* ---- responsive enable switch ---- */
	const LB_ENABLE = 'aae_lb_enable';

	/* ---- content ---- */
	const LB_TYPE    = 'aae_lb_type';    // image | video | iframe | html | ajax | auto
	const LB_GROUP   = 'aae_lb_group';   // gallery id ('' = standalone single item)
	const LB_TITLE   = 'aae_lb_title';
	const LB_CAPTION = 'aae_lb_caption';

	/* ---- chrome ---- */
	const LB_ANIM     = 'aae_lb_anim';       // fade | zoom | slide
	const LB_ZOOM     = 'aae_lb_zoom';       // bool — show zoom toolbar button
	const LB_LOOP     = 'aae_lb_loop';       // bool — loop at ends
	const LB_DOWNLOAD = 'aae_lb_download';   // bool — show download button
	const LB_COUNTER  = 'aae_lb_counter';    // bool — show "n / N" counter

	/* ---- container mode ---- */
	// When enabled on a container, its children become grouped triggers.
	//   'images'  → only child images become slides (default).
	//   'content' → each DIRECT child becomes an image OR video slide (its first
	//               image, or a video it links/embeds); children with neither
	//               are skipped. Clicking anywhere on a child opens it.
	//   'full'    → each DIRECT child becomes a slide showing its WHOLE markup
	//               (heading + text + button + image, whatever it contains),
	//               cloned into the lightbox. Clicking anywhere on a child opens it.
	const LB_CONTAINER_MODE = 'aae_lb_container_mode'; // images | content | full
	const LB_CHILD_SELECTOR = 'aae_lb_child_selector'; // CSS selector override ('' = default)
	const LB_CAPTION_SRC    = 'aae_lb_caption_src';    // none | alt | title | caption

	/** Default child selector used when a container leaves the override blank. */
	const DEFAULT_CHILD_SELECTOR = 'img, a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".webp"], a[href$=".gif"], .gallery-item img';

	/** Atomic element types that get the Lightbox section auto-injected. */
	public static function lightbox_widgets(): array {
		/**
		 * Filter the element types the global Lightbox section is added to.
		 *
		 * @param string[] $types Default target element types.
		 */
		return apply_filters( 'aae/lightbox/target_types', [ 'e-image' ] );
	}

	/**
	 * Atomic CONTAINER types that get the container-level Lightbox section —
	 * enabling it turns every eligible child image into a grouped trigger.
	 */
	public static function lightbox_containers(): array {
		/**
		 * Filter the container element types the container Lightbox section is
		 * added to.
		 *
		 * @param string[] $types Default container element types.
		 */
		return apply_filters(
			'aae/lightbox/container_types',
			[ 'e-flexbox', 'e-div-block', 'e-grid' ]
		);
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

		// Toolbar / behaviour toggles (shared by element + container modes).
		$schema[ self::LB_ZOOM ]     = Boolean_Prop_Type::make()->default( true );
		$schema[ self::LB_LOOP ]     = Boolean_Prop_Type::make()->default( true );
		$schema[ self::LB_DOWNLOAD ] = Boolean_Prop_Type::make()->default( false );
		$schema[ self::LB_COUNTER ]  = Boolean_Prop_Type::make()->default( true );

		// Container mode.
		$schema[ self::LB_CONTAINER_MODE ] = String_Prop_Type::make()->default( 'images' );
		$schema[ self::LB_CHILD_SELECTOR ] = String_Prop_Type::make()->default( '' );
		$schema[ self::LB_CAPTION_SRC ]    = String_Prop_Type::make()->default( 'none' );

		return $schema;
	}
}
