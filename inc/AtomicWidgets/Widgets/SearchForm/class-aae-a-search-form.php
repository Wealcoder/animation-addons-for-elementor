<?php
/**
 * AAE Search Form — atomic COMPOSITE / NESTED root element.
 *
 * A full-parity v4 rewrite of the v3 `wcf--blog--search--form` widget, built as a
 * composite nesting tree (like Loop Grid): every visible part is a real, selectable,
 * independently-styleable atomic element — no monolithic markup, and NOT a single
 * line of CSS ships (structural defaults live in define_base_styles(); interaction
 * state — open/close, dropdown/fullscreen positioning, live-result visibility — is
 * driven entirely by the runtime JS toggling inline styles / classes).
 *
 *   e-aae-a-search-form               (this — wrapper, owns mode + ajax/filter config)
 *     ├─ e-aae-a-search-toggle        open/close trigger (dropdown / fullscreen)
 *     └─ e-aae-a-search-panel         the toggling container / fullscreen overlay
 *          ├─ e-aae-a-search-field    <form> — input + filter + submit
 *          │    ├─ e-aae-a-search-input
 *          │    ├─ e-aae-a-search-filter
 *          │    └─ e-aae-a-search-submit
 *          └─ e-aae-a-search-results  live AJAX results container
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-search-toggle.php';
require_once __DIR__ . '/class-aae-a-search-panel.php';

use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Toggle;
use WCF_ADDONS\AtomicWidgets\Widgets\SearchForm\AAE_A_Search_Panel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Search_Form extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-search-form';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-search-form';
	}

	public function get_title() {
		return esc_html__( 'AAE Search Form', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_keywords() {
		return [ 'search', 'form', 'ajax', 'filter', 'atomic', 'composite' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-post'];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// inline (always visible) | dropdown (toggle -> floating panel) |
			// fullscreen (toggle -> viewport overlay). Drives which pieces the JS
			// shows and how it positions the panel.
			'mode'         => String_Prop_Type::make()
				->enum( [ 'inline', 'dropdown', 'fullscreen' ] )
				->default( 'inline' ),

			// Dropdown-only: which edge the floating panel aligns to.
			'position'     => String_Prop_Type::make()
				->enum( [ 'left', 'right' ] )
				->default( 'left' ),

			'enable_ajax'  => Boolean_Prop_Type::make()->default( false ),
			'show_filter'  => Boolean_Prop_Type::make()->default( false ),
			'show_date'    => Boolean_Prop_Type::make()->default( true ),
			'show_cat'     => Boolean_Prop_Type::make()->default( true ),

			// EDITOR-ONLY: in dropdown / fullscreen mode the panel is hidden on the
			// canvas by default (only the search icon shows, like the frontend).
			// Flip this on to reveal the panel for editing. No effect on frontend.
			'editor_show_panel' => Boolean_Prop_Type::make()->default( false ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'aae_search_settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'mode' )
						->set_label( __( 'Mode', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'inline',     'label' => __( 'Inline', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'dropdown',   'label' => __( 'Dropdown', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'fullscreen', 'label' => __( 'Full Screen', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'position' )
						->set_label( __( 'Dropdown Position', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'left',  'label' => __( 'Left', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'right', 'label' => __( 'Right', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'enable_ajax' )
						->set_label( __( 'Enable Ajax Live Search', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_filter' )
						->set_label( __( 'Enable Search Filter', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_cat' )
						->set_label( __( 'Show Category Filter', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_date' )
						->set_label( __( 'Show Date Filter', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'editor_show_panel' )
						->set_label( __( 'Show Panel In Editor', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	/** Wrapper defaults: a relative-positioned inline-flex column. */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
					->add_prop( 'position', String_Prop_Type::generate( 'relative' ) )
					->add_prop( 'gap', Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
			),
		];
	}

	/**
	 * The composition — one locked child per structural role. Each level
	 * self-seeds its own children (Panel -> Field + Results, Field -> Input +
	 * Filter + Submit), mirroring how Loop Grid's Pagination self-seeds its pieces.
	 */
	protected function define_default_children() {
		return [
			AAE_A_Search_Toggle::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Search Toggle' ] )
				->build(),

			AAE_A_Search_Panel::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Search Panel' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-search-toggle', 'e-aae-a-search-panel' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-form' => __DIR__ . '/aae-a-search-form.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-search-form-js' ];
	}

	/**
	 * Publish the runtime config (ajax endpoint + nonce + mode flags) to the Twig
	 * as an inline JSON blob — the JS reads it off the wrapper's data-config, the
	 * same "config travels inline" approach the Loop Grid pagination uses. Keeps
	 * the widget self-contained: no wp_localize_script needed.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['search_config'] = wp_json_encode( [
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'wcf-addons-frontend' ),
			'action'     => 'live_search',
			'mode'       => isset( $settings['mode'] ) ? $settings['mode'] : 'inline',
			'position'   => isset( $settings['position'] ) ? $settings['position'] : 'left',
			'ajax'       => ! empty( $settings['enable_ajax'] ),
			'showFilter' => ! empty( $settings['show_filter'] ),
			'showDate'   => ! empty( $settings['show_date'] ),
			'showCat'    => ! empty( $settings['show_cat'] ),
		] );

		return $settings;
	}
}
