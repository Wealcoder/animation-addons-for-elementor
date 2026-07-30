<?php
/**
 * AAE Table of Content — atomic leaf WIDGET.
 *
 * Native Atomic 4 port of the Pro `wcf--table-of-contents` widget
 * (animation-addons-for-elementor-pro/widgets/table-of-contents.php).
 *
 * Renders the box shell (header + collapsible body) server-side via Twig; the
 * heading scan, nested/flat list build, active-heading highlighting, smooth
 * scroll, collapse/expand toggle and responsive minimize behaviour all run in
 * assets/js/table-of-contents.js (register() API, GSAP ScrollTrigger +
 * ScrollToPlugin, mirroring the Pro widget's frontend handler).
 *
 * Every behavioural setting is emitted as a data-aae-toc-* attribute so the
 * JS reads its config off the element (no wp_localize / frontend settings for
 * atomic widgets). Style-panel styling (box/header/list/marker colours,
 * typography, spacing, borders) rides the named base-style definitions below
 * plus a handful of CSS custom properties bridged from Content-tab colour
 * inputs — the same pattern as ToggleSwitcher.
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\TableOfContents;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Svg_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Styles\Style_States;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Table_Of_Contents extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Auto-generated Table of Contents from the page headings — nested hierarchy, active-heading highlighting, smooth scroll, collapsible + responsive minimize box.';

	public static function get_element_type(): string {
		return 'e-aae-a-toc';
	}

	public function get_title() {
		return esc_html__( 'AAE Table of Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-table-of-contents';
	}

	public function get_keywords() {
		return [ 'toc', 'table', 'content', 'contents', 'anchor', 'heading', 'atomic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'         => Classes_Prop_Type::make()->default( [] ),
			'attributes'      => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Header.
			'title'           => String_Prop_Type::make()->default( 'Table of Contents' ),
			'html_tag'        => String_Prop_Type::make()
				->enum( [ 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ] )
				->default( 'h4' ),

			// Include: which heading tags to collect, and the container to scan.
			// headings_by_tags is stored as a comma-separated string ("h2,h3,…")
			// because the atomic controls have no multi-select tag input; the
			// panel exposes it via individual level switches (see below), which
			// the twig recomposes into this data attribute for the JS.
			'inc_h1'          => Boolean_Prop_Type::make()->default( false ),
			'inc_h2'          => Boolean_Prop_Type::make()->default( true ),
			'inc_h3'          => Boolean_Prop_Type::make()->default( true ),
			'inc_h4'          => Boolean_Prop_Type::make()->default( true ),
			'inc_h5'          => Boolean_Prop_Type::make()->default( true ),
			'inc_h6'          => Boolean_Prop_Type::make()->default( true ),
			'container'       => String_Prop_Type::make()->default( '' ),

			// Exclude.
			'exclude_selector' => String_Prop_Type::make()->default( '' ),

			// Marker.
			'marker_view'     => String_Prop_Type::make()
				->enum( [ 'numbers', 'bullets' ] )
				->default( 'numbers' ),
			// Uploaded SVG bullet icon; empty → built-in dot glyph fallback in twig.
			'marker_icon'     => Svg_Src_Prop_Type::make(),

			// Additional options.
			'word_wrap'       => Boolean_Prop_Type::make()->default( false ),
			'minimize_box'    => Boolean_Prop_Type::make()->default( true ),
			'expand_icon'     => Svg_Src_Prop_Type::make(),
			'collapse_icon'   => Svg_Src_Prop_Type::make(),
			'minimized_on'    => String_Prop_Type::make()
				->enum( [ 'mobile', 'mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'desktop' ] )
				->default( 'tablet' ),
			'hierarchical_view' => Boolean_Prop_Type::make()->default( true ),
			'collapse_subitems' => Boolean_Prop_Type::make()->default( false ),

			// Content-tab colour bridges → CSS custom properties (Style tab can't
			// reach every one of these list-item state colours generically, so
			// they ride inline vars, exactly like ToggleSwitcher's colours).
			'item_text_color'        => String_Prop_Type::make()->default( '' ),
			'item_text_hover_color'  => String_Prop_Type::make()->default( '' ),
			'item_text_active_color' => String_Prop_Type::make()->default( '' ),
			'marker_color'           => String_Prop_Type::make()->default( '' ),
			'toggle_button_color'    => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Table of Contents', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'title' )
						->set_label( __( 'Title', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'html_tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'h2',  'label' => 'H2' ],
							[ 'value' => 'h3',  'label' => 'H3' ],
							[ 'value' => 'h4',  'label' => 'H4' ],
							[ 'value' => 'h5',  'label' => 'H5' ],
							[ 'value' => 'h6',  'label' => 'H6' ],
							[ 'value' => 'div', 'label' => 'div' ],
						] ),

					Select_Control::bind_to( 'marker_view' )
						->set_label( __( 'Marker View', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'numbers', 'label' => __( 'Numbers', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'bullets', 'label' => __( 'Bullets', 'animation-addons-for-elementor' ) ],
						] ),

					Svg_Control::bind_to( 'marker_icon' )
						->set_label( __( 'Bullet Icon', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'include' )
				->set_label( __( 'Include', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'inc_h1' )->set_label( __( 'H1', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'inc_h2' )->set_label( __( 'H2', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'inc_h3' )->set_label( __( 'H3', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'inc_h4' )->set_label( __( 'H4', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'inc_h5' )->set_label( __( 'H5', 'animation-addons-for-elementor' ) ),
					Switch_Control::bind_to( 'inc_h6' )->set_label( __( 'H6', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'container' )
						->set_label( __( 'Container', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'placeholder' => 'body' ] ),
				] ),

			Section::make()
				->set_id( 'exclude' )
				->set_label( __( 'Exclude', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'exclude_selector' )
						->set_label( __( 'Anchors By Selector', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'placeholder' => '.no-toc, .elementor-heading-title' ] ),
				] ),

			Section::make()
				->set_id( 'additional_options' )
				->set_label( __( 'Additional Options', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'word_wrap' )
						->set_label( __( 'Word Wrap (ellipsis)', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'minimize_box' )
						->set_label( __( 'Minimize Box', 'animation-addons-for-elementor' ) ),

					Svg_Control::bind_to( 'expand_icon' )
						->set_label( __( 'Expand Icon', 'animation-addons-for-elementor' ) ),

					Svg_Control::bind_to( 'collapse_icon' )
						->set_label( __( 'Minimize Icon', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'minimized_on' )
						->set_label( __( 'Minimized On', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'mobile',       'label' => __( 'Mobile Portrait', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'mobile_extra',  'label' => __( 'Mobile Landscape', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'tablet',        'label' => __( 'Tablet Portrait', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'tablet_extra',  'label' => __( 'Tablet Landscape', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'laptop',        'label' => __( 'Laptop', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'desktop',       'label' => __( 'Desktop (or smaller)', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'hierarchical_view' )
						->set_label( __( 'Hierarchical View', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'collapse_subitems' )
						->set_label( __( 'Collapse Subitems', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'colors' )
				->set_label( __( 'Colors', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'item_text_color' )
						->set_label( __( 'Item Text', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'item_text_hover_color' )
						->set_label( __( 'Item Text (Hover)', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'item_text_active_color' )
						->set_label( __( 'Item Text (Active)', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'marker_color' )
						->set_label( __( 'Marker', 'animation-addons-for-elementor' ) ),
					Text_Control::bind_to( 'toggle_button_color' )
						->set_label( __( 'Toggle Icon', 'animation-addons-for-elementor' ) ),
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
	 * Base styles — named definitions so the Style tab exposes editable
	 * sections (Box / Header / Title / Body / List Item) with real cached
	 * classes and no separate stylesheet needed for the structural defaults.
	 * Behavioural / state CSS (active item, ellipsis, counters, collapse
	 * transition) lives in the on-demand stylesheet.
	 */
	protected function define_base_styles(): array {
		$px = static function ( $n ) {
			return Size_Prop_Type::generate( [ 'size' => $n, 'unit' => 'px' ] );
		};

		return [
			// Outer box.
			'base' => Style_Definition::make()
				->set_label( __( 'Box', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'       => String_Prop_Type::generate( 'block' ),
						'width'         => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						'overflow'      => String_Prop_Type::generate( 'hidden' ),
						'border-width'  => $px( 1 ),
						'border-style'  => String_Prop_Type::generate( 'solid' ),
						'border-color'  => Color_Prop_Type::generate( '#9da5ae' ),
						'border-radius' => $px( 3 ),
					] )
				),

			// Header row.
			'header' => Style_Definition::make()
				->set_label( __( 'Header', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'         => String_Prop_Type::generate( 'flex' ),
						'align-items'     => String_Prop_Type::generate( 'center' ),
						'justify-content' => String_Prop_Type::generate( 'space-between' ),
						'padding'         => Dimensions_Prop_Type::generate( [
							'block-start'  => $px( 20 ),
							'inline-end'   => $px( 20 ),
							'block-end'    => $px( 20 ),
							'inline-start' => $px( 20 ),
						] ),
					] )
				),

			// Header title.
			'header_title' => Style_Definition::make()
				->set_label( __( 'Title', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'margin'    => Dimensions_Prop_Type::generate( [
							'block-start'  => $px( 0 ),
							'inline-end'   => $px( 0 ),
							'block-end'    => $px( 0 ),
							'inline-start' => $px( 0 ),
						] ),
						'font-size' => $px( 18 ),
					] )
				),

			// Scrollable body.
			'body' => Style_Definition::make()
				->set_label( __( 'Body', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'padding' => Dimensions_Prop_Type::generate( [
							'block-start'  => $px( 20 ),
							'inline-end'   => $px( 20 ),
							'block-end'    => $px( 20 ),
							'inline-start' => $px( 20 ),
						] ),
					] )
				),

			// List item text (normal + hover) — Style tab gets a "List Item"
			// section; the runtime :active-item colour still rides a CSS var.
			'list_item' => Style_Definition::make()
				->set_label( __( 'List Item', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'text-decoration' => String_Prop_Type::generate( 'none' ),
					] )
				)
				->add_variant(
					Style_Variant::make()
						->set_state( Style_States::HOVER )
						->add_props( [
							'text-decoration' => String_Prop_Type::generate( 'underline' ),
						] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-toc' => __DIR__ . '/aae-a-table-of-contents.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-toc-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-toc-css' ];
	}
}
