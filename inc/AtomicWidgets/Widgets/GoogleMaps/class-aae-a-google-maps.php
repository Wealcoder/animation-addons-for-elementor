<?php
/**
 * AAE Google Maps — atomic WIDGET (v4 port of Elementor's v3 `google_maps`).
 *
 * Source: elementor/includes/widgets/google-maps.php.
 *
 * A LEAF widget (`Atomic_Widget_Base` + `Has_Template`), not a composite
 * container: the embed is a single cross-origin <iframe> with no styleable
 * sub-parts, so there is nothing for a child atomic element to own. Spawning
 * children here would only add an unselectable wrapper (see the
 * complex-widget skill's C1-vs-C2 rule — composite is for widgets whose
 * pieces the user genuinely styles one by one).
 *
 * WHAT MOVED TO THE STYLE TAB (and is therefore NOT a control here):
 *  - v3 `height` (responsive slider)     -> `height` is first-class in v4's
 *    style schema; the 300px default below is the same value core's own
 *    widget-google_maps.css hardcoded for the iframe, and any Style-tab
 *    Height overrides it.
 *  - v3 `css_filters` / `css_filters_hover` (Group_Control_Css_Filter)
 *    -> `filter` is first-class in v4's style schema, and Style_States::HOVER
 *    gives the hover half. A CSS `filter` on this root applies to the whole
 *    subtree, so it reaches the iframe exactly as v3's `{{WRAPPER}} iframe`
 *    selector did.
 *  - v3 `hover_transition` (Transition Duration slider) -> replaced by the
 *    `transition` base-style default below, itself editable in the Style tab.
 *    NOTE: core's Transition_Transformer only whitelists the property `all`
 *    (see its get_allowed_properties()), so a narrower `filter 300ms` would
 *    be silently DROPPED — `all` is the only value that actually emits.
 *
 * WHY THE URL IS BUILT IN TWIG, NOT IN PHP:
 * `get_atomic_settings()` runs server-side only, but the editor canvas
 * renders this same .twig CLIENT-SIDE through Twing — so a `map_src` computed
 * in PHP would simply be undefined on the canvas and the widget would preview
 * blank (the constraint that forced AAE_A_Menu's `rendered_menu` through an
 * AJAX fallback). Building the URL from plain string concatenation + the
 * `url_encode` filter — both present in Twing, verified against
 * assets/js/packages/twing/twing.js — makes the editor and the frontend
 * produce identical markup, and the map now re-renders live as the address is
 * typed (v3's `content_template()` was empty, so it had no live preview).
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\GoogleMaps;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Key_Value_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Selection_Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Transition_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Google_Maps extends Atomic_Widget_Base {

	use Has_Template;

	/**
	 * Elementor's OWN Integrations-settings option, reused deliberately: a site
	 * that already configured the key for the v3 widget keeps working with zero
	 * setup, and there is only ever one key to rotate. Do NOT fork this into an
	 * `aae_*` option — that would silently strand every existing install on the
	 * keyless maps.google.com fallback.
	 */
	const API_KEY_OPTION = 'elementor_google_maps_api_key';

	/** Same default address v3 shipped. */
	const DEFAULT_ADDRESS = 'London Eye, London, United Kingdom';

	/** Same height core's widget-google_maps.css hardcoded for the iframe. */
	const DEFAULT_HEIGHT_PX = 300;

	const DEFAULT_ZOOM = 10;

	public static function get_element_type(): string {
		return 'e-aae-a-google-maps';
	}

	public function get_title() {
		return esc_html__( 'Google Maps', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-google-maps';
	}

	public function get_keywords() {
		return [ 'google', 'map', 'maps', 'embed', 'location', 'address', 'atomic', 'aae' ];
	}

	public function get_categories(): array {
		return [ 'aae-atomic-general' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'address' => String_Prop_Type::make()->default( self::DEFAULT_ADDRESS ),

			// v3 used Controls_Manager::SLIDER for zoom, but a Size_Prop_Type +
			// `Number_Control::bind_to('zoom.size')` is the exact combination
			// the complex-widget skill lists as a hard "do NOT": that `.size`
			// sub-path silently fails to RENDER in Elementor 4.1.x — no error,
			// the control is just absent from the panel. A plain top-level
			// Number_Prop_Type is what actually works, and zoom is an integer
			// step anyway, so the slider bought nothing.
			'zoom' => Number_Prop_Type::make()->default( self::DEFAULT_ZOOM ),

			// No control by design — this is the site-wide key, edited in
			// Elementor > Settings > Integrations, not per widget. It lives in
			// the SCHEMA (rather than only in get_atomic_settings()) so the
			// editor's client-side Twing render can read it too; the
			// get_atomic_settings() override below re-reads the option so the
			// frontend is never served a stale key from a cached schema.
			'api_key' => String_Prop_Type::make()->default( self::get_api_key() ),

			// Also control-less. The empty-Location placeholder text lives in
			// the schema rather than as a literal in the .twig (which is what
			// AAE_A_Menu does) purely so it goes through __() and stays
			// translatable — the twig is also parsed client-side by Twing,
			// which has no `trans` filter, so PHP is the only place that can
			// translate it.
			'placeholder_text' => String_Prop_Type::make()->default(
				__( 'Enter a location to display the map.', 'animation-addons-for-elementor' )
			),
		];
	}

	private static function get_api_key(): string {
		return (string) get_option( self::API_KEY_OPTION, '' );
	}

	protected function define_atomic_controls(): array {
		// v3 rendered a Controls_Manager::ALERT here when no key was set. There
		// is no atomic Alert control, so the guidance rides the Section's own
		// description instead — always visible, which is fine: it is a pointer,
		// not an error, and the widget works keyless via the maps.google.com
		// fallback exactly as v3 did.
		$key_hint = __( 'Set your Google Maps API key in Elementor > Settings > Integrations. Without a key the map falls back to the keyless maps.google.com embed.', 'animation-addons-for-elementor' );

		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Google Maps', 'animation-addons-for-elementor' ) )
				->set_description( $key_hint )
				->set_items( [
					Text_Control::bind_to( 'address' )
						->set_label( __( 'Location', 'animation-addons-for-elementor' ) )
						->set_placeholder( self::DEFAULT_ADDRESS ),

					Number_Control::bind_to( 'zoom' )
						->set_label( __( 'Zoom', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 20 )
						->set_step( 1 )
						->set_should_force_int( true ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		// Everything here is a plain style prop with a static value on the
		// element or a descendant, so ALL of it belongs in the base style
		// rather than a stylesheet (base-style-first). Only the editor-only
		// pointer-events rule — ancestor-scoped, hence inexpressible here —
		// survives in google-maps.scss.
		$root_styles = [
			'display'  => String_Prop_Type::generate( 'block' ),
			'position' => String_Prop_Type::generate( 'relative' ),
			'width'    => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
			'height'   => Size_Prop_Type::generate( [ 'size' => self::DEFAULT_HEIGHT_PX, 'unit' => 'px' ] ),

			// Core's own widget-google_maps.css set this so a Style-tab border
			// radius actually clips the iframe's square corners.
			'overflow' => String_Prop_Type::generate( 'hidden' ),

			// Stands in for v3's `hover_transition` control, so a Style-tab
			// hover filter eases instead of snapping. 'all' is the only
			// property core's Transition_Transformer whitelists.
			'transition' => Transition_Prop_Type::generate( [
				Selection_Size_Prop_Type::generate( [
					'selection' => Key_Value_Prop_Type::generate( [
						'key'   => String_Prop_Type::generate( 'All properties' ),
						'value' => String_Prop_Type::generate( 'all' ),
					] ),
					'size' => Size_Prop_Type::generate( [ 'size' => 300, 'unit' => 'ms' ] ),
				] ),
			] ),
		];

		// Compound descendant key -> `.e-aae-a-google-maps-base .aae-a-google-maps-frame`
		// (same shape as AAE_A_Form's 'base .aae-form-checkbox-row'). Structural,
		// not cosmetic: the iframe fills whatever box the user's Style-tab
		// Width/Height gives the root. `display:block` also kills the inline
		// descender gap core patched with `line-height: 0`.
		$frame_styles = [
			'display'      => String_Prop_Type::generate( 'block' ),
			'width'        => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
			'height'       => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
			'border-width' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
		];

		// Only ever visible with an empty Location, i.e. never in a finished
		// design — so this is a fallback affordance, not an opinionated visual
		// default, and does not need the design-less `:where()` treatment.
		$placeholder_styles = [
			'display'         => String_Prop_Type::generate( 'flex' ),
			'align-items'     => String_Prop_Type::generate( 'center' ),
			'justify-content' => String_Prop_Type::generate( 'center' ),
			'width'           => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
			'height'          => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
			'text-align'      => String_Prop_Type::generate( 'center' ),
			'font-size'       => Size_Prop_Type::generate( [ 'size' => 13, 'unit' => 'px' ] ),
			'color'           => Color_Prop_Type::generate( '#69727d' ),
			'background'      => Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( '#f1f2f3' ),
			] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $root_styles ) ),

			'base .aae-a-google-maps-frame' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $frame_styles ) ),

			'base .aae-a-google-maps-placeholder' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $placeholder_styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-google-maps' => __DIR__ . '/aae-a-google-maps.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-google-maps-css' ];
	}

	/**
	 * Re-read the API key from the live option on every server render.
	 *
	 * The schema default above is resolved once per request and is cacheable,
	 * so rotating the key in Elementor > Settings > Integrations could
	 * otherwise leave the frontend embedding the old one until the schema
	 * cache is invalidated. Cheap single get_option(), and it keeps the
	 * frontend authoritative.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['api_key'] = self::get_api_key();

		return $settings;
	}

	public function render_markdown(): string {
		$settings = $this->get_atomic_settings();
		$address  = isset( $settings['address'] ) ? trim( (string) $settings['address'] ) : '';

		if ( '' === $address ) {
			return '';
		}

		return '[Map: ' . $address . '](https://maps.google.com/maps?q=' . rawurlencode( $address ) . ')';
	}
}
