<?php
namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\PropTypes\Responsive_Json_Prop_Type;
use WCF_ADDONS\Atomic\Lightbox\Section_Anchor_Prop_Type;
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

	/* ================================================================
	 * Style controls (responsive React section — "Lightbox Style").
	 *
	 * Every value below is a Responsive_Json_Prop_Type: a { desktop, tablet,
	 * mobile, … } object. The runtime resolves the active breakpoint at open
	 * time and writes the result as CSS custom properties on the overlay root,
	 * so each container can style the ONE shared overlay independently.
	 *
	 * Only sizing/spacing props are meaningfully responsive; colors/borders
	 * carry a desktop value that cascades to every breakpoint.
	 * ============================================================== */
	const LB_STYLE_ANCHOR = 'aae_lb_style_anchor';

	/* overlay backdrop */
	const LB_OVERLAY_COLOR   = 'aae_lb_overlay_color';   // rgba/hex
	const LB_OVERLAY_OPACITY = 'aae_lb_overlay_opacity'; // 0–100

	/* content container */
	const LB_CONTENT_FULLWIDTH = 'aae_lb_content_fullwidth'; // bool
	const LB_CONTENT_WIDTH     = 'aae_lb_content_width';      // px (responsive)
	const LB_CONTENT_MAXWIDTH  = 'aae_lb_content_maxwidth';   // vw/px (responsive)
	const LB_CONTENT_PADDING   = 'aae_lb_content_padding';    // px (responsive)
	const LB_CONTENT_RADIUS    = 'aae_lb_content_radius';     // px (responsive)
	const LB_CONTENT_BG        = 'aae_lb_content_bg';         // color
	const LB_CONTENT_SHADOW    = 'aae_lb_content_shadow';     // css box-shadow string

	/* nav arrows (prev / next) */
	const LB_ARROW_SIZE        = 'aae_lb_arrow_size';        // icon px (responsive)
	const LB_ARROW_BOX         = 'aae_lb_arrow_box';         // button px (responsive)
	const LB_ARROW_COLOR       = 'aae_lb_arrow_color';
	const LB_ARROW_BG          = 'aae_lb_arrow_bg';
	const LB_ARROW_RADIUS      = 'aae_lb_arrow_radius';      // px
	const LB_ARROW_BORDER_W    = 'aae_lb_arrow_border_w';    // px
	const LB_ARROW_BORDER_C    = 'aae_lb_arrow_border_c';
	const LB_ARROW_COLOR_HOVER = 'aae_lb_arrow_color_hover';
	const LB_ARROW_BG_HOVER    = 'aae_lb_arrow_bg_hover';
	const LB_ARROW_OFFSET      = 'aae_lb_arrow_offset';      // px from edge (responsive)

	/* close button */
	const LB_CLOSE_SIZE        = 'aae_lb_close_size';        // icon px (responsive)
	const LB_CLOSE_BOX         = 'aae_lb_close_box';         // button px (responsive)
	const LB_CLOSE_COLOR       = 'aae_lb_close_color';
	const LB_CLOSE_BG          = 'aae_lb_close_bg';
	const LB_CLOSE_RADIUS      = 'aae_lb_close_radius';      // px
	const LB_CLOSE_BORDER_W    = 'aae_lb_close_border_w';    // px
	const LB_CLOSE_BORDER_C    = 'aae_lb_close_border_c';
	const LB_CLOSE_COLOR_HOVER = 'aae_lb_close_color_hover';
	const LB_CLOSE_BG_HOVER    = 'aae_lb_close_bg_hover';

	/**
	 * All Responsive_Json style props, so add_props() and Container_Render can
	 * loop them instead of repeating the list. bind key (without the aae_lb_
	 * prefix) => desktop default.
	 */
	public static function style_props(): array {
		return [
			self::LB_OVERLAY_COLOR   => '',
			self::LB_OVERLAY_OPACITY => '',
			self::LB_CONTENT_FULLWIDTH => false,
			self::LB_CONTENT_WIDTH   => '',
			self::LB_CONTENT_MAXWIDTH => '',
			self::LB_CONTENT_PADDING => '',
			self::LB_CONTENT_RADIUS  => '',
			self::LB_CONTENT_BG      => '',
			self::LB_CONTENT_SHADOW  => '',
			self::LB_ARROW_SIZE      => '',
			self::LB_ARROW_BOX       => '',
			self::LB_ARROW_COLOR     => '',
			self::LB_ARROW_BG        => '',
			self::LB_ARROW_RADIUS    => '',
			self::LB_ARROW_BORDER_W  => '',
			self::LB_ARROW_BORDER_C  => '',
			self::LB_ARROW_COLOR_HOVER => '',
			self::LB_ARROW_BG_HOVER  => '',
			self::LB_ARROW_OFFSET    => '',
			self::LB_CLOSE_SIZE      => '',
			self::LB_CLOSE_BOX       => '',
			self::LB_CLOSE_COLOR     => '',
			self::LB_CLOSE_BG        => '',
			self::LB_CLOSE_RADIUS    => '',
			self::LB_CLOSE_BORDER_W  => '',
			self::LB_CLOSE_BORDER_C  => '',
			self::LB_CLOSE_COLOR_HOVER => '',
			self::LB_CLOSE_BG_HOVER  => '',
		];
	}

	/** Default child selector used when a container leaves the override blank. */
	const DEFAULT_CHILD_SELECTOR = 'img, a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".webp"], a[href$=".gif"], .gallery-item img';

	/** Atomic element types that get the Lightbox section auto-injected. */
	public static function lightbox_widgets(): array {
		/**
		 * Filter the element types the global Lightbox section is added to.
		 *
		 * @param string[] $types Default target element types.
		 */
		return apply_filters( 
				'aae/lightbox/target_types',
				 [],//[ 'e-image' ] 
				 
				);
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

		// Style section (responsive React). The anchor gives the section its
		// replaceable row; every style value is a permissive responsive-json
		// object resolved to CSS vars at open time.
		if ( class_exists( Responsive_Json_Prop_Type::class ) ) {
			$schema[ self::LB_STYLE_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

			foreach ( self::style_props() as $key => $default ) {
				$schema[ $key ] = Responsive_Json_Prop_Type::make()->default( [ 'desktop' => $default ] );
			}
		}

		return $schema;
	}
}
