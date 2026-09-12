<?php
/**
 * AAE Loop Numbers — atomic pagination number list.
 *
 * A structural atomic container holding ONE styleable page-number TEMPLATE
 * (AAE_A_Loop_Number). The template repeats at render, once per page link
 * (1 2 3 … N), so the user styles a single element and every number follows.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-loop-number.php';
// Same namespace, so no import is needed — but page_url() calls it and
// nothing else on this path guarantees it has been loaded.
require_once __DIR__ . '/class-loop-filter-auth.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Numbers extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
		// Holds a single Number template child, which repeats at render.
	}

	public static function get_type() {
		return 'e-aae-a-loop-numbers';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-numbers';
	}

	public function get_title() {
		return esc_html__( 'Page Numbers', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		// Only the single page-number template lives here.
		return [ 'e-aae-a-loop-number' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_default_children() {
		return self::build_number_template();
	}

	/**
	 * Seed the single page-number TEMPLATE. It is one authored atomic element the
	 * user styles once (Normal / Hover / Current); AAE_A_Loop_Number::print_content()
	 * repeats it per page link at render. Locked so it can't be deleted, but its
	 * styles stay fully editable.
	 *
	 * Returns an array (one child) so callers can splice it straight into a
	 * children list.
	 */
	public static function build_number_template(): array {
		return [
			AAE_A_Loop_Number::generate()
				->editor_settings( [ 'title' => __( 'Page Number', 'animation-addons-for-elementor' ) ] )
				->is_locked( true )
				->build(),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'gap', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-numbers' => __DIR__ . '/aae-a-loop-numbers.html.twig',
		];
	}

	/**
	 * This page's URL at page $page, in OUR filter spelling.
	 *
	 * Two things it has to get right, both invisible when wrong:
	 *
	 * The BASE is `request_url()`, not the default. With no base argument
	 * `add_query_arg()` reads `$_SERVER['REQUEST_URI']`, which inside an AJAX
	 * re-render is admin-ajax.php — so every page link would point at the
	 * endpoint instead of at the page.
	 *
	 * And a WooCommerce-spelled arrival (`?min_price=200`) is rewritten into
	 * the canonical keys the rest of the page's links already use. Measured:
	 * without this the page-2 link kept `min_price` while every filter widget
	 * beside it had switched to `price`. Both resolve to the same set today,
	 * because the alias is read on every request — so this is one page
	 * disagreeing with itself about which spelling the site uses, which is the
	 * state that becomes a real bug the day the alias switch is turned off.
	 *
	 * @param array $ctx The grid's render context, when the caller has one.
	 */
	public static function page_url( int $page, array $ctx = [] ): string {
		$url = Loop_Filter_Auth::request_url();
		if ( '' === $url ) {
			// No captured URL (a bare render, or a very early call): the old
			// behaviour, which is right everywhere except inside admin-ajax.
			return 1 === $page ? remove_query_arg( 'aae_page' ) : add_query_arg( 'aae_page', $page );
		}

		$filters = (array) ( $ctx['filters'] ?? [] );
		if ( $filters ) {
			$aliases = Loop_Filter_Auth::consumed_alias_keys(
				Loop_Filter_Auth::declarations_for_document(
					(int) ( $ctx['document_id'] ?? 0 ),
					(string) ( $ctx['grid_id'] ?? '' )
				),
				Loop_Filter_Auth::request_args(),
				false
			);
			if ( $aliases ) {
				$url = remove_query_arg( $aliases, $url );
				// The authorised map, which is already keyed by our own url_key
				// — so this restates exactly what the alias resolved to, never
				// a re-reading of it.
				foreach ( $filters as $key => $value ) {
					if ( '' !== (string) $value ) {
						$url = add_query_arg( (string) $key, (string) $value, $url );
					}
				}
			}
		}

		$url = 1 === $page ? remove_query_arg( 'aae_page', $url ) : add_query_arg( 'aae_page', $page, $url );

		// esc_url_raw, not esc_url: the twig escapes for the attribute itself
		// and esc_url has already turned every `&` into `&#038;`.
		return esc_url_raw( $url );
	}

	/**
	 * Smart-truncated page list: 1 … c-1 c c+1 … N.
	 * Returns an array of ints, or the string '...' for a gap.
	 */
	public static function smart_pages( int $current, int $total ): array {
		if ( $total <= 7 ) {
			return range( 1, max( 1, $total ) );
		}

		$pages   = [];
		$pages[] = 1;

		$start = max( 2, $current - 1 );
		$end   = min( $total - 1, $current + 1 );

		if ( $start > 2 ) {
			$pages[] = '...';
		}
		for ( $i = $start; $i <= $end; $i++ ) {
			$pages[] = $i;
		}
		if ( $end < $total - 1 ) {
			$pages[] = '...';
		}

		$pages[] = $total;

		return $pages;
	}
}
