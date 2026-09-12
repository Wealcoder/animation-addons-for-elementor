<?php
/**
 * AAE Advanced Portfolio — Section Title.
 *
 * The v3 skin's `render_section_title()`: the "WORK" heading above the grid.
 *
 * The text and tag the user EDITS live on the root (`section_title` /
 * `section_title_tag`) and arrive here through the Render_Context stack,
 * because that is where the v3 skin read them from
 * (`$this->get_instance_value('section_title')` on the parent widget) — so the
 * two controls stay in the parent's Layout section exactly as they appear in
 * the v3 panel. AAE_A_Post_Pagination_Preview_Date resolves its own text the
 * same way.
 *
 * This element does carry `text`/`tag` props of its own, but only as the
 * fallback for when that context is not on the stack — the editor renders each
 * element in isolation, where the parent's define_render_context() never ran.
 * See get_atomic_settings().
 *
 * The tag is validated against a fixed list rather than printed as given: it
 * reaches the twig as an element name, so an unchecked value would be an
 * injection point. h2 is both the schema default and the fallback.
 *
 * Renders nothing at all when the title is empty, matching the v3 skin's early
 * return — an empty heading would still take vertical space in the grid.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancePortfolio;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Portfolio_Title extends Atomic_Widget_Base {

	use Has_Template;

	/** Tags the section title may render as — mirrors the v3 select. */
	const ALLOWED_TAGS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

	public static function get_type() {
		return 'e-aae-a-portfolio-title';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-portfolio-title';
	}

	public function get_title() {
		return esc_html__( 'Section Title', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-heading';
	}

	public function show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			// Both filled in by get_atomic_settings() from the root's context;
			// never edited on this element.
			// Default doubles as the editor fallback when the root's context is
			// not on the stack — see get_atomic_settings().
			'text'       => String_Prop_Type::make()->default( 'WORK' ),
			'tag'        => String_Prop_Type::make()->default( 'h2' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	/**
	 * v3's own rule for this element is exactly two declarations:
	 *
	 *   .wcf--advance-portfolio.skin-portfolio-three .section-title {
	 *     padding: 50px 20px; text-align: center;
	 *   }
	 *
	 * The 50px block padding is what gives the pinned heading room to swell to
	 * 3x without colliding with the grid, so it is part of the animation, not
	 * decoration.
	 *
	 * The font-size is deliberately NOT ported from the live reference. There
	 * the heading computes to 175px in a licensed display face, and none of
	 * that comes from the widget — it is that template's own typography. Baking
	 * it in here would hard-code one site's design into every drop of the
	 * widget. 48px stays as a sane starting point the user raises in the Style
	 * tab; at 3x it is the SAME multiple, just from a smaller base.
	 */
	protected function define_base_styles(): array {
		$pad_inline = Size_Prop_Type::generate( [ 'size' => 20, 'unit' => 'px' ] );
		$pad_block  = Size_Prop_Type::generate( [ 'size' => 50, 'unit' => 'px' ] );

		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
					->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 48, 'unit' => 'px' ] ) )
					->add_prop( 'margin', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
					->add_prop( 'text-align', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'padding', Dimensions_Prop_Type::generate( [
						'block-start'  => $pad_block,
						'inline-end'   => $pad_inline,
						'block-end'    => $pad_block,
						'inline-start' => $pad_inline,
					] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-portfolio-title' => __DIR__ . '/aae-a-portfolio-title.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advance-portfolio-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$ctx = Render_Context::get( AAE_A_Advance_Portfolio::class );

		// Two DIFFERENT situations that both look like "no title", and they must
		// not be treated the same:
		//
		//   context present, value empty  -> the user cleared Section Title, so
		//                                    render nothing (the v3 skin's early
		//                                    return).
		//   context absent                -> the editor is rendering this element
		//                                    on its own, so the parent's
		//                                    define_render_context() never ran.
		//                                    Falling through to '' here made the
		//                                    heading vanish from the canvas
		//                                    entirely (verified: no
		//                                    e-aae-a-portfolio-title node in the
		//                                    preview DOM at all), leaving nothing
		//                                    to click and style. Use this
		//                                    element's own default instead.
		$has_ctx = is_array( $ctx ) && array_key_exists( 'section_title', $ctx );

		$text = $has_ctx
			? (string) $ctx['section_title']
			: (string) ( $settings['text'] ?? '' );

		$tag = $has_ctx && ! empty( $ctx['section_title_tag'] )
			? (string) $ctx['section_title_tag']
			: (string) ( $settings['tag'] ?? 'h2' );

		$settings['text'] = $text;
		$settings['tag']  = in_array( strtolower( $tag ), self::ALLOWED_TAGS, true )
			? strtolower( $tag )
			: 'h2';

		return $settings;
	}
}
