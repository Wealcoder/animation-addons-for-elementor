<?php
/**
 * AAE Stack Cards — atomic (v4) COMPOSITE container widget.
 *
 * A scroll-driven card deck: N real, independently-styleable Card child
 * elements (AAE_A_Stack_Card) that the frontend JS stacks and animates with
 * GSAP ScrollTrigger (pin + scrub). Ten animations ship in the registry at
 * assets/js/lib/animations.js; the Motion section retunes any of them.
 *
 * DESIGN-LESS + BASE-STYLE-FIRST: no external stylesheet. Each Card carries its
 * own base style (the "card" look); the deck's structural/editor CSS is a small
 * inline <style> in the Twig (layout mechanics only). The scroll animation is
 * pure runtime JS — the editor renders the cards as a plain vertical list so
 * every card is selectable and stylable, and the Preview control replays the
 * real animation on demand.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\StackCards;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-stack-card.php';

class AAE_A_Stack_Cards extends Atomic_Element_Base {

	use Has_Element_Template;

	/**
	 * The animation registry, mirrored from assets/js/lib/animations.js. Order
	 * is the panel order. Adding a slug here without adding it there falls the
	 * runtime back to 'scroll-stack' rather than breaking.
	 */
	private static function animation_options(): array {
		$animations = [
			'scroll-stack' => __( 'Scroll Stack', 'animation-addons-for-elementor' ),
			'slide-over'   => __( 'Slide Over', 'animation-addons-for-elementor' ),
			'cascade'      => __( 'Cascade', 'animation-addons-for-elementor' ),
			'depth-push'   => __( 'Depth Push', 'animation-addons-for-elementor' ),
			'peel-away'    => __( 'Peel Away', 'animation-addons-for-elementor' ),
			'fan-deck'     => __( 'Fan Deck', 'animation-addons-for-elementor' ),
			'card-flip'    => __( 'Card Flip', 'animation-addons-for-elementor' ),
			'scatter'      => __( 'Scatter', 'animation-addons-for-elementor' ),
			'rotate-stack' => __( 'Rotate Stack', 'animation-addons-for-elementor' ),
			'clip-reveal'  => __( 'Clip Reveal', 'animation-addons-for-elementor' ),
		];

		$options = [];
		foreach ( $animations as $value => $label ) {
			$options[] = [ 'value' => $value, 'label' => $label ];
		}

		return $options;
	}

	private static function animation_values(): array {
		return array_column( self::animation_options(), 'value' );
	}

	/**
	 * Whitelisted eases. GSAP warns and flattens the timeline on an unknown ease
	 * string, so the panel never offers a free-text curve.
	 */
	private static function ease_options(): array {
		$eases = [
			'power2.out'          => __( 'Smooth (default)', 'animation-addons-for-elementor' ),
			'none'                => __( 'Linear', 'animation-addons-for-elementor' ),
			'sine.out'            => __( 'Gentle', 'animation-addons-for-elementor' ),
			'power1.out'          => __( 'Soft', 'animation-addons-for-elementor' ),
			'power3.out'          => __( 'Sharp', 'animation-addons-for-elementor' ),
			'power4.out'          => __( 'Very Sharp', 'animation-addons-for-elementor' ),
			'expo.out'            => __( 'Snap', 'animation-addons-for-elementor' ),
			'circ.out'            => __( 'Circular', 'animation-addons-for-elementor' ),
			'back.out(1.7)'       => __( 'Overshoot', 'animation-addons-for-elementor' ),
			'elastic.out(1, 0.5)' => __( 'Elastic', 'animation-addons-for-elementor' ),
			'power2.inOut'        => __( 'Ease In-Out', 'animation-addons-for-elementor' ),
			'power3.inOut'        => __( 'Ease In-Out Strong', 'animation-addons-for-elementor' ),
		];

		$options = [];
		foreach ( $eases as $value => $label ) {
			$options[] = [ 'value' => $value, 'label' => $label ];
		}

		return $options;
	}

	private static function ease_values(): array {
		return array_column( self::ease_options(), 'value' );
	}

	/**
	 * Show a prop only while `animation` is one of $slugs.
	 *
	 * A term describes when the prop is SHOWN; `effect: hide` is what happens
	 * when it fails (editor-editing-panel.js: `isHidden = !!failingTerm &&
	 * failingTerm.effect === 'hide'`). Reading it the other way round hides
	 * exactly the wrong half of the panel, so don't "correct" it.
	 *
	 * Ten animations share one set of knobs, but no animation reads all of
	 * them — Card Flip ignores every offset, Clip Reveal moves nothing at all.
	 * Showing a slider that provably does nothing is the worst kind of control:
	 * the user drags it, sees no change, and distrusts the rest of the panel.
	 */
	private static function when_animation( array $slugs ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'animation' ],
				'value'    => $slugs,
				'effect'   => 'hide',
			] )
			->get();
	}

	/** Show a prop only while another prop equals $value. */
	private static function when_prop_is( string $prop, $value ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ $prop ],
				'value'    => $value,
				'effect'   => 'hide',
			] )
			->get();
	}

	/** Show a prop only while another prop does NOT equal $value. */
	private static function when_prop_is_not( string $prop, $value ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'ne',
				'path'     => [ $prop ],
				'value'    => $value,
				'effect'   => 'hide',
			] )
			->get();
	}

	/**
	 * Mobile-switch options from Elementor's REAL breakpoints — 767/1024 are
	 * only the defaults and the user can change them. Mirrors the Nav's helper.
	 */
	private static function breakpoint_options(): array {
		$by_px = [];

		if ( class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::$instance->breakpoints )
			&& method_exists( \Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints' ) ) {

			foreach ( \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints() as $key => $breakpoint ) {
				if ( ! is_object( $breakpoint ) || ! method_exists( $breakpoint, 'get_value' ) ) {
					continue;
				}

				$px = (int) $breakpoint->get_value();

				if ( $px <= 0 ) {
					continue;
				}

				$name = ucwords( str_replace( '_', ' ', (string) $key ) );

				$by_px[ $px ] = [
					'value' => (string) $px,
					/* translators: 1: breakpoint name, 2: width in pixels. */
					'label' => sprintf( __( '%1$s (%2$dpx)', 'animation-addons-for-elementor' ), $name, $px ),
				];
			}
		}

		// Legacy fallbacks — also the whole list when Elementor's manager is absent.
		$legacy = [
			767  => __( 'Mobile', 'animation-addons-for-elementor' ),
			1024 => __( 'Tablet', 'animation-addons-for-elementor' ),
		];

		foreach ( $legacy as $px => $name ) {
			if ( ! isset( $by_px[ $px ] ) ) {
				$by_px[ $px ] = [
					'value' => (string) $px,
					/* translators: 1: breakpoint name, 2: width in pixels. */
					'label' => sprintf( __( '%1$s (%2$dpx)', 'animation-addons-for-elementor' ), $name, $px ),
				];
			}
		}

		ksort( $by_px );

		return array_values( $by_px );
	}

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-stack-cards';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-stack-cards';
	}

	public function get_title() {
		return esc_html__( 'Stack Cards', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_keywords() {
		return [ 'stack', 'cards', 'scroll', 'gsap', 'scrolltrigger', 'atomic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	/**
	 * Panel category for the Elements panel.
	 *
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' ("Atomic Elements") bucket. Delegate so both stay in sync.
	 *
	 * Lost when the widget moved over from free (which has always carried it),
	 * so on a Pro-owned site this widget was showing under "Atomic Elements"
	 * instead of "AAE General".
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Editor-only: ON (default) = show ALL cards as a flat, editable list on
			// the canvas; OFF = show only the first card (the rest hidden) for a tidy
			// preview. Has NO effect on the frontend (which always stacks all cards).
			'editor_edit_mode' => Boolean_Prop_Type::make()->default( true ),

			// Which scroll animation drives the deck. See animation_options().
			'animation'  => String_Prop_Type::make()->enum( self::animation_values() )->default( 'scroll-stack' ),

			// WHERE the stack sits in the viewport (0 = top … 50 = centre …
			// 100 = bottom). Positions the deck's centre at that % of the stage.
			'start_offset' => Number_Prop_Type::make()->default( 50 ),

			// Deck box (px). The cards stack inside this box on the frontend; on
			// the canvas the cards render as a plain vertical list for editing.
			'deck_width'    => Number_Prop_Type::make()->default( 620 ),
			'deck_height'   => Number_Prop_Type::make()->default( 400 ),

			// Scroll distance (in viewport-heights) the sticky scene consumes per
			// card, and the ScrollTrigger scrub smoothing (reference uses 0.6).
			'scroll_length' => Number_Prop_Type::make()->default( 100 ),
			'scrub'         => Number_Prop_Type::make()->default( 0.6 ),

			// ── Motion knobs ────────────────────────────────────────────────
			// Every default reproduces the original hardcoded Scroll Stack, so a
			// page saved before these existed renders identically.
			//
			// Each knob is shown only for the animations that actually READ it
			// (see assets/js/lib/animations.js). Keep the two in step: adding a
			// cfg.* read to an animation without adding its slug here leaves the
			// user with a knob they cannot reach.
			'direction'   => String_Prop_Type::make()->enum( [ 'up', 'down', 'left', 'right' ] )->default( 'up' )
				->set_dependencies( self::when_animation( [
					'scroll-stack', 'peel-away', 'cascade', 'scatter', 'slide-over', 'rotate-stack', 'clip-reveal',
				] ) ),
			'offset_step' => Number_Prop_Type::make()->default( 34 )
				->set_dependencies( self::when_animation( [
					'scroll-stack', 'fan-deck', 'cascade', 'depth-push', 'scatter', 'rotate-stack',
				] ) ),
			'scale_step'  => Number_Prop_Type::make()->default( 5 )
				->set_dependencies( self::when_animation( [
					'scroll-stack', 'depth-push', 'scatter', 'slide-over', 'rotate-stack',
				] ) ),
			'rotate_step' => Number_Prop_Type::make()->default( 0 )
				->set_dependencies( self::when_animation( [
					'scroll-stack', 'fan-deck', 'peel-away', 'cascade', 'scatter', 'rotate-stack',
				] ) ),
			'fade_back'   => Number_Prop_Type::make()->default( 0 )
				->set_dependencies( self::when_animation( [
					'scroll-stack', 'depth-push', 'scatter', 'slide-over', 'rotate-stack',
				] ) ),
			// Overlap and easing drive the timeline itself, so every animation
			// uses them — no dependency.
			'overlap'     => Number_Prop_Type::make()->default( 70 ),
			'ease'        => String_Prop_Type::make()->enum( self::ease_values() )->default( 'power2.out' ),
			// Only Card Flip rotates in 3D; nothing else is perspective-sensitive.
			'perspective' => Number_Prop_Type::make()->default( 1200 )
				->set_dependencies( self::when_animation( [ 'card-flip' ] ) ),

			// ── Progress indicator ──────────────────────────────────────────
			'show_progress'  => Boolean_Prop_Type::make()->default( false ),
			'progress_style' => String_Prop_Type::make()->enum( [ 'dots', 'bar', 'counter' ] )->default( 'dots' )
				->set_dependencies( self::when_prop_is( 'show_progress', true ) ),
			'snap_cards'     => Boolean_Prop_Type::make()->default( false ),

			// ── Responsive ──────────────────────────────────────────────────
			// Content props can't be per-breakpoint in the atomic schema, so the
			// runtime does it: matchMedia on the resolved breakpoint px.
			'mobile_behavior'   => String_Prop_Type::make()->enum( [ 'stack', 'list', 'simple-fade' ] )->default( 'stack' ),
			'mobile_breakpoint' => String_Prop_Type::make()->default( '767' )
				->set_dependencies( self::when_prop_is_not( 'mobile_behavior', 'stack' ) ),

			// ── Advanced ────────────────────────────────────────────────────
			// ScrollTrigger's default pinType 'fixed' breaks inside a transformed
			// ancestor (the same reason CSS sticky was unusable here). Default
			// keeps today's behaviour; 'transform' is the escape hatch.
			'pin_type' => String_Prop_Type::make()->enum( [ 'fixed', 'transform' ] )->default( 'fixed' ),
			'markers'  => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		// Preset-picker element control. Presets keyed to `e-aae-a-stack-cards`
		// (see Widgets/StackCards/presets/) show here and replace the selected
		// deck on pick. Each widget carries its own copy of this stub class.
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';
		require_once __DIR__ . '/class-aae-a-stack-items-control.php';
		require_once __DIR__ . '/class-aae-a-stack-preview-control.php';

		return [
			Section::make()
				->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
				->set_id( 'aae_presets' )
				->set_items( [
					AAE_A_Preset_Picker_Control::make()
						->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_id( 'stack_cards' )
				->set_label( __( 'Stack Cards', 'animation-addons-for-elementor' ) )
				->set_items( [
					// Play/scrub sits directly above the animation picker so you can
					// audition a choice without leaving the section.
					AAE_A_Stack_Preview_Control::make()
						->set_label( __( 'Preview Animation', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
					// Presets and Animation look like the same list because each
					// preset is named after the animation it shows off. Say plainly
					// which one is safe on work you have already done.
					Select_Control::bind_to( 'animation' )
						->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Changes only how the cards move — your cards, text and styling stay as they are. To swap the whole design instead, use Apply Preset above.', 'animation-addons-for-elementor' ) )
						->set_options( self::animation_options() ),
					Switch_Control::bind_to( 'editor_edit_mode' )
						->set_label( __( 'Edit Cards (Editor)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Editor only, never the live page. Turn off to show just the first card and keep the canvas tidy.', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'start_offset' )
						->set_label( __( 'Stack Position (viewport %)', 'animation-addons-for-elementor' ) )
						->set_description( __( '0 pins the deck to the top of the screen, 50 centres it, 100 drops it to the bottom.', 'animation-addons-for-elementor' ) )
						->set_min( 0 )->set_max( 100 ),
					Number_Control::bind_to( 'scroll_length' )
						->set_label( __( 'Scroll Length (vh / card)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'How far the visitor scrolls per card. Higher means the deck plays more slowly.', 'animation-addons-for-elementor' ) )
						->set_min( 20 )->set_max( 400 ),
					Number_Control::bind_to( 'scrub' )
						->set_label( __( 'Scrub Smoothing', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Seconds the animation takes to catch up with the scrollbar. 0 locks it to the scroll exactly.', 'animation-addons-for-elementor' ) )
						->set_min( 0 )->set_max( 3 ),
				] ),

			Section::make()
				->set_id( 'stack_cards_items' )
				->set_label( __( 'Cards', 'animation-addons-for-elementor' ) )
				->set_items( [
					AAE_A_Stack_Items_Control::make()
						->set_label( __( 'Cards', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_id( 'stack_cards_motion' )
				->set_label( __( 'Motion', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'direction' )
						->set_label( __( 'Direction', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'up',    'label' => __( 'Up', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'down',  'label' => __( 'Down', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'left',  'label' => __( 'Left', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'right', 'label' => __( 'Right', 'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'ease' )
						->set_label( __( 'Easing', 'animation-addons-for-elementor' ) )
						->set_options( self::ease_options() ),
					Number_Control::bind_to( 'offset_step' )
						->set_label( __( 'Stack Offset (px)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )->set_max( 200 ),
					Number_Control::bind_to( 'scale_step' )
						->set_label( __( 'Shrink per Card (%)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )->set_max( 30 ),
					Number_Control::bind_to( 'rotate_step' )
						->set_label( __( 'Rotate per Card (deg)', 'animation-addons-for-elementor' ) )
						->set_min( -45 )->set_max( 45 ),
					Number_Control::bind_to( 'fade_back' )
						->set_label( __( 'Fade per Card (%)', 'animation-addons-for-elementor' ) )
						->set_min( 0 )->set_max( 50 ),
					Number_Control::bind_to( 'overlap' )
						->set_label( __( 'Card Overlap (%)', 'animation-addons-for-elementor' ) )
						->set_description( __( 'How much each card\'s motion overlaps the next. Lower is snappier, higher is more continuous.', 'animation-addons-for-elementor' ) )
						->set_min( 5 )->set_max( 200 ),
					Number_Control::bind_to( 'perspective' )
						->set_label( __( 'Perspective (px)', 'animation-addons-for-elementor' ) )
						->set_min( 200 )->set_max( 4000 ),
					Number_Control::bind_to( 'deck_width' )
						->set_label( __( 'Deck Width (px)', 'animation-addons-for-elementor' ) )
						->set_min( 200 )->set_max( 1600 ),
					Number_Control::bind_to( 'deck_height' )
						->set_label( __( 'Deck Height (px)', 'animation-addons-for-elementor' ) )
						->set_min( 160 )->set_max( 1200 ),
				] ),

			Section::make()
				->set_id( 'stack_cards_progress' )
				->set_label( __( 'Progress', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'show_progress' )
						->set_label( __( 'Show Indicator', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'progress_style' )
						->set_label( __( 'Indicator Style', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'dots',    'label' => __( 'Dots', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'bar',     'label' => __( 'Bar', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'counter', 'label' => __( 'Counter', 'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'snap_cards' )
						->set_label( __( 'Snap to Card', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Scrolling settles on one card at a time instead of stopping mid-transition.', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'stack_cards_responsive' )
				->set_label( __( 'Responsive', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'mobile_behavior' )
						->set_label( __( 'On Mobile', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Pinned scroll decks are heavy on small screens. Fade or Plain List drops the pin and lets the cards flow down the page instead.', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'stack',       'label' => __( 'Same as Desktop', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'simple-fade', 'label' => __( 'Fade Cards In', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'list',        'label' => __( 'Plain List (no animation)', 'animation-addons-for-elementor' ) ],
						] ),
					Select_Control::bind_to( 'mobile_breakpoint' )
						->set_label( __( 'Switch Below', 'animation-addons-for-elementor' ) )
						->set_options( self::breakpoint_options() ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'pin_type' )
						->set_label( __( 'Pin Method', 'animation-addons-for-elementor' ) )
						->set_description( __( 'Leave on Fixed. Switch to Transform only if the deck jumps or refuses to pin — that happens inside containers that use a CSS transform.', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'fixed',     'label' => __( 'Fixed (default)', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'transform', 'label' => __( 'Transform (fixes broken pin)', 'animation-addons-for-elementor' ) ],
						] ),
					Switch_Control::bind_to( 'markers' )
						->set_label( __( 'Debug Markers', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	/**
	 * The deck root. Only structural defaults live here — `display:block` so the
	 * cards flow as a vertical list on the canvas (each selectable/styleable),
	 * `position:relative` so the frontend JS can absolutely-stack the cards
	 * inside it. The card LOOK is the Card element's own base style; the deck
	 * width/height are applied at runtime from the props.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'  => String_Prop_Type::generate( 'block' ),
						'position' => String_Prop_Type::generate( 'relative' ),
						'width'    => String_Prop_Type::generate( '100%' ),
					] )
				),
		];
	}

	protected function define_default_children() {
		// Seed 4 bare, styleable cards. Users drop their own content into each and
		// restyle it; presets ship richer, pre-styled decks.
		$cards = [];
		for ( $i = 1; $i <= 4; $i++ ) {
			$cards[] = AAE_A_Stack_Card::generate()
				->editor_settings( [ 'title' => 'Card ' . $i ] )
				->build();
		}
		return $cards;
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-stack-card' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-stack-cards' => __DIR__ . '/aae-a-stack-cards.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [];
	}
}
