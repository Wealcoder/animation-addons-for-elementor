<?php
/**
 * AAE Advanced Portfolio -- Date.
 *
 * The v3 base skin's `render_date()`, which is simply `get_the_date()`. No
 * post has to be passed in: the repeating item calls `the_post()` before each
 * render pass, so the global post is already the right one by the time this
 * resolves -- the same reason the Post Image and Post Title parts need no
 * wiring either.
 *
 * In the editor there is no loop, so a formatted today's date stands in rather
 * than rendering an empty element the user cannot see or select.
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
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
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

class AAE_A_Portfolio_Date extends Atomic_Widget_Base {

	use Has_Template;

	public static function get_type() {
		return 'e-aae-a-portfolio-date';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-portfolio-date';
	}

	public function get_title() {
		return esc_html__( 'Date', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		// The DEFAULT is what the editor shows.
		//
		// get_atomic_settings() below is the authority on the frontend, but the
		// editor's element render does not go through it — it renders from the
		// model, so a prop left defaulting to '' arrives empty and this element
		// showed as a blank box in the canvas even though the frontend was
		// correct. AAE_A_Post_Title solves the same problem the same way: it
		// resolves its title into the schema default (via Atomic::get_sample_post()
		// in the editor) rather than relying on get_atomic_settings() alone.
		//
		// Inside a loop this default is irrelevant — get_atomic_settings()
		// overwrites it with the real post's date.
		$date = '';
		if ( function_exists( 'get_the_date' ) ) {
			$date = (string) get_the_date();
		}
		if ( '' === $date && class_exists( '\WCF_ADDONS\AtomicWidgets\Atomic' ) ) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			if ( $sample ) {
				$date = (string) get_the_date( '', $sample );
			}
		}
		if ( '' === $date && function_exists( 'date_i18n' ) ) {
			$date = date_i18n( get_option( 'date_format' ) );
		}

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'text'       => String_Prop_Type::make()->default( $date ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
					->add_prop( 'font-size', Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ) )
					->add_prop( 'color', Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.6)' ) )
			),
		];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		// Inside the repeating item the global post is already set, so this is
		// simply the current post's date.
		$date = function_exists( 'get_the_date' ) ? (string) get_the_date() : '';

		// Outside a loop there is no date to show, and an empty string makes the
		// twig emit nothing at all — which is how this element disappeared from
		// the editor canvas completely (verified: no e-aae-a-portfolio-date node
		// in the preview DOM), leaving it unselectable and unstylable. A stand-in
		// keeps it visible; on the frontend, inside a real loop, this branch
		// cannot be reached because get_the_date() always has a post.
		//
		// The previous version gated this on editor->is_edit_mode(), which is
		// false while the PREVIEW IFRAME renders, so the fallback never fired
		// where it was actually needed.
		if ( '' === $date && function_exists( 'date_i18n' ) ) {
			$date = date_i18n( get_option( 'date_format' ) );
		}

		$settings['text'] = $date;

		return $settings;
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-portfolio-date' => __DIR__ . '/aae-a-portfolio-date.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advance-portfolio-css' ];
	}
}
