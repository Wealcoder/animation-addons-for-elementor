<?php
/**
 * AAE Search Query — atomic (v4) LEAF widget.
 *
 * v4 rebuild of the v3 `widgets/search-query.php` (Search_Query). Displays the
 * current search-results heading — "Search Results for: <query>" — plus any
 * active date-range / category filters read from the request ($_GET), exactly
 * like the v3 widget's AAE search addon.
 *
 * DESIGN-LESS + BASE-STYLE-FIRST (see the aae-v4-complex-widget skill):
 * every pure-styling control from v3 (title/query/filter color, typography,
 * text-stroke, text-shadow, blend, alignment) is DROPPED — Elementor v4's
 * native Style tab styles the widget root, and typography/color inherit down
 * to the query <span> and filter rows. Only the two CONTENT props remain
 * (HTML tag + the "Search Text" label). No external SCSS, no JS — the dynamic
 * text is computed server-side in get_atomic_settings() (mirrors Post Title).
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchQuery;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\\Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Atomic_Widget_Base' ) ) {
	return;
}

class AAE_A_Search_Query extends Atomic_Widget_Base {

	use Has_Template;

	const TD = 'animation-addons-for-elementor';

	public static function get_element_type(): string {
		return 'e-aae-a-search-query';
	}

	public function get_title() {
		return esc_html__( 'AAE Search Query', self::TD );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_keywords() {
		return [ 'search', 'query', 'results', 'search query', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Content. The tag is constrained by the Select control's options and
			// re-whitelisted in Twig (matches Post Title — no ->enum()).
			'header_size' => String_Prop_Type::make()->default( 'h2' ),
			'search_text' => String_Prop_Type::make()->default( 'Search Results for:' ),

			// Dynamic — injected in get_atomic_settings() on the frontend; the
			// default is the editor placeholder (no real search query in the editor,
			// mirrors the v3 "Hello World" preview). NOT user-editable (no control).
			'query_term'  => String_Prop_Type::make()->default( 'Hello World' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Content', self::TD ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'header_size' )
						->set_label( __( 'HTML Tag', self::TD ) )
						->set_options( [
							[ 'value' => 'h1',   'label' => 'H1' ],
							[ 'value' => 'h2',   'label' => 'H2' ],
							[ 'value' => 'h3',   'label' => 'H3' ],
							[ 'value' => 'h4',   'label' => 'H4' ],
							[ 'value' => 'h5',   'label' => 'H5' ],
							[ 'value' => 'h6',   'label' => 'H6' ],
							[ 'value' => 'div',  'label' => 'div' ],
							[ 'value' => 'span', 'label' => 'span' ],
							[ 'value' => 'p',    'label' => 'p' ],
						] ),
					Text_Control::bind_to( 'search_text' )
						->set_label( __( 'Search Text', self::TD ) )
						->set_placeholder( __( 'Search Results for:', self::TD ) ),
				] ),
		];
	}

	/**
	 * Design-less: only the structural wrapper defaults live here (block, full
	 * width). All visual styling (typography, color, alignment, spacing) is left
	 * to the native Style tab so the user owns the look. The bare 'base' key must
	 * exist so the Twig root picks up its scope class (base_styles.base).
	 */
	protected function define_base_styles(): array {
		$wrapper = [
			'display' => String_Prop_Type::generate( 'block' ),
			'width'   => String_Prop_Type::generate( '100%' ),
		];

		return [
			'base' => Style_Definition::make()->add_variant( Style_Variant::make()->add_props( $wrapper ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-query' => __DIR__ . '/aae-a-search-query.html.twig',
		];
	}

	public function get_style_depends(): array {
		return []; // Design-less — no external CSS.
	}

	/**
	 * Inject the request-driven dynamic content for the Twig template.
	 *
	 * - query_term: the real search query on the frontend; a placeholder in the
	 *   editor/preview (get_search_query() is empty there — same as v3).
	 * - filters: active date-range / category filters from $_GET, rendered as
	 *   plain rows. Empty in the editor (no request filters).
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$is_editor = ( class_exists( '\\Elementor\\Plugin' ) && (
			\Elementor\Plugin::$instance->editor->is_edit_mode()
			|| \Elementor\Plugin::$instance->preview->is_preview_mode()
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			|| ( isset( $_GET['preview_id'] ) && isset( $_GET['preview_nonce'] ) )
		) );

		if ( $is_editor ) {
			$settings['query_term'] = esc_html__( 'Hello World', self::TD );
			$settings['filters']    = [];
		} else {
			$settings['query_term'] = get_search_query();
			$settings['filters']    = $this->build_filters();
		}

		return $settings;
	}

	/**
	 * Build the active-filter rows from the request. Read-only display of
	 * already-applied search filters (no state change) — values are sanitized;
	 * no nonce needed for reading public search parameters (matches v3).
	 *
	 * @return string[]
	 */
	private function build_filters(): array {
		$filters = [];

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$from_date  = isset( $_GET['from_date'] ) ? sanitize_text_field( wp_unslash( $_GET['from_date'] ) ) : '';
		$to_date    = isset( $_GET['to_date'] ) ? sanitize_text_field( wp_unslash( $_GET['to_date'] ) ) : '';
		$categories = ( isset( $_GET['category'] ) && is_array( $_GET['category'] ) )
			? array_map( 'sanitize_text_field', wp_unslash( $_GET['category'] ) )
			: [];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $from_date && $to_date ) {
			/* translators: 1: start date, 2: end date. */
			$filters[] = sprintf( esc_html__( 'Date: From %1$s to %2$s', self::TD ), $from_date, $to_date );
		} elseif ( $from_date ) {
			/* translators: %s: start date. */
			$filters[] = sprintf( esc_html__( 'Date: From %s', self::TD ), $from_date );
		} elseif ( $to_date ) {
			/* translators: %s: end date. */
			$filters[] = sprintf( esc_html__( 'Date: To %s', self::TD ), $to_date );
		}

		$cat_names = [];
		foreach ( $categories as $cat_id ) {
			$category = get_category( $cat_id );
			if ( ! is_wp_error( $category ) && isset( $category->name ) ) {
				$cat_names[] = $category->name;
			}
		}
		if ( ! empty( $cat_names ) ) {
			/* translators: %s: comma-separated category names. */
			$filters[] = sprintf( esc_html__( 'Category: %s', self::TD ), implode( ', ', $cat_names ) );
		}

		return $filters;
	}
}
