<?php
/**
 * AAE Stack Cards — atomic (v4) COMPOSITE container widget.
 *
 * A scroll-driven card deck: N real, independently-styleable Card child
 * elements (AAE_A_Stack_Card) that the frontend JS stacks and animates with
 * GSAP ScrollTrigger (pin + scrub). This first release ships the "Scroll Stack"
 * animation; the other reference animations (Fan, Peel, Cascade, Spiral, Depth,
 * Reveal, Flip, Scatter, Timeline) arrive later as presets that set the
 * `animation` prop + styled card content.
 *
 * DESIGN-LESS + BASE-STYLE-FIRST: no external stylesheet. Each Card carries its
 * own base style (the "card" look); the deck's structural/editor CSS is a small
 * inline <style> in the Twig (layout mechanics only). The scroll animation is
 * pure runtime JS — the editor renders the cards as a plain vertical list so
 * every card is selectable and stylable.
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
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-stack-card.php';

class AAE_A_Stack_Cards extends Atomic_Element_Base {

	use Has_Element_Template;

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

			// Which scroll animation drives the deck. First release ships
			// 'scroll-stack'; the rest arrive as presets that set this value.
			'animation'  => String_Prop_Type::make()->enum( [ 'scroll-stack' ] )->default( 'scroll-stack' ),

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
		];
	}

	protected function define_atomic_controls(): array {
		// Preset-picker element control. Presets keyed to `e-aae-a-stack-cards`
		// (see Widgets/StackCards/presets/) show here and replace the selected
		// deck on pick. Each widget carries its own copy of this stub class.
		require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

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
					Switch_Control::bind_to( 'editor_edit_mode' )
						->set_label( __( 'Edit Cards (Editor)', 'animation-addons-for-elementor' ) ),
					Select_Control::bind_to( 'animation' )
						->set_label( __( 'Animation', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'scroll-stack', 'label' => __( 'Scroll Stack', 'animation-addons-for-elementor' ) ],
						] ),
					Number_Control::bind_to( 'start_offset' )
						->set_label( __( 'Stack Position (viewport %)', 'animation-addons-for-elementor' ) ),
					// deck_width / deck_height stay as props (twig/JS read their
					// defaults, 620×400) but have NO panel control — the deck size is
					// fixed; the cards are styled via their own Style tab.
					Number_Control::bind_to( 'scroll_length' )
						->set_label( __( 'Scroll Length (vh / card)', 'animation-addons-for-elementor' ) ),
					Number_Control::bind_to( 'scrub' )
						->set_label( __( 'Scrub Smoothing', 'animation-addons-for-elementor' ) ),
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
