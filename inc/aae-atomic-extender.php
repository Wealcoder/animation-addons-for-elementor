<?php
/**
 * AAE — Elementor v4 Atomic Heading Style extender.
 *
 * Adds an "AAE Style" section to e-heading with 4 dropdowns:
 *   - Background Color
 *   - Text Color
 *   - Border
 *   - Border Radius
 *
 * Frontend rendering happens via `the_content` filter. Editor live preview
 * is handled by assets/js/aae-atomic-extender.js using a manual "Re-render"
 * button injected into the section.
 *
 * @package AAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

const AAE_ATOMIC_HEADING = 'e-heading';
const AAE_BG_PROP        = 'aae_color';
const AAE_TEXT_PROP      = 'aae_text_color';
const AAE_BORDER_PROP    = 'aae_border';
const AAE_RADIUS_PROP    = 'aae_radius';

/**
 * Each prop maps to (a) editor dropdown options and (b) the CSS property
 * that the value writes to. Values are emitted as-is into a CSS rule, so
 * the option `value` strings are valid CSS fragments.
 */
function aae_style_props(): array {
	return array(
		AAE_BG_PROP => array(
			'css'     => 'background-color',
			'options' => array(
				array( 'value' => '',        'label' => __( 'None',  'animation-addons-for-elementor' ) ),
				array( 'value' => '#FF3B30', 'label' => __( 'Red',   'animation-addons-for-elementor' ) ),
				array( 'value' => '#007AFF', 'label' => __( 'Blue',  'animation-addons-for-elementor' ) ),
				array( 'value' => '#34C759', 'label' => __( 'Green', 'animation-addons-for-elementor' ) ),
			),
		),
		AAE_TEXT_PROP => array(
			'css'     => 'color',
			'options' => array(
				array( 'value' => '',        'label' => __( 'Default', 'animation-addons-for-elementor' ) ),
				array( 'value' => '#FFFFFF', 'label' => __( 'White',   'animation-addons-for-elementor' ) ),
				array( 'value' => '#000000', 'label' => __( 'Black',   'animation-addons-for-elementor' ) ),
				array( 'value' => '#FFD60A', 'label' => __( 'Yellow',  'animation-addons-for-elementor' ) ),
			),
		),
		AAE_BORDER_PROP => array(
			'css'     => 'border',
			'options' => array(
				array( 'value' => '',                  'label' => __( 'None',         'animation-addons-for-elementor' ) ),
				array( 'value' => '1px solid #000000', 'label' => __( '1px Solid',    'animation-addons-for-elementor' ) ),
				array( 'value' => '2px dashed #FF3B30', 'label' => __( '2px Dashed Red', 'animation-addons-for-elementor' ) ),
				array( 'value' => '3px dotted #007AFF', 'label' => __( '3px Dotted Blue', 'animation-addons-for-elementor' ) ),
			),
		),
		AAE_RADIUS_PROP => array(
			'css'     => 'border-radius',
			'options' => array(
				array( 'value' => '',     'label' => __( 'None',     'animation-addons-for-elementor' ) ),
				array( 'value' => '4px',  'label' => __( '4px',      'animation-addons-for-elementor' ) ),
				array( 'value' => '12px', 'label' => __( '12px',     'animation-addons-for-elementor' ) ),
				array( 'value' => '999px', 'label' => __( 'Pill',    'animation-addons-for-elementor' ) ),
			),
		),
	);
}

add_action(
	'elementor/init',
	function (): void {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return;
		}
		add_filter( 'elementor/atomic-widgets/props-schema', 'aae_inject_style_props' );
		add_filter( 'elementor/atomic-widgets/controls', 'aae_inject_style_controls', 10, 2 );
		add_filter( 'elementor/frontend/the_content', 'aae_inject_style_css' );
	}
);

function aae_inject_style_props( array $schema ): array {
	foreach ( aae_style_props() as $prop_key => $_ ) {
		if ( ! isset( $schema[ $prop_key ] ) ) {
			$schema[ $prop_key ] = String_Prop_Type::make()->default( '' );
		}
	}
	return $schema;
}

function aae_inject_style_controls( array $controls, $element ): array {
	if ( ! is_object( $element ) || ! method_exists( $element, 'get_name' ) ) {
		return $controls;
	}
	if ( AAE_ATOMIC_HEADING !== $element->get_name() ) {
		return $controls;
	}

	$items = array();
	$labels = array(
		AAE_BG_PROP     => __( 'Background Color', 'animation-addons-for-elementor' ),
		AAE_TEXT_PROP   => __( 'Text Color',       'animation-addons-for-elementor' ),
		AAE_BORDER_PROP => __( 'Border',           'animation-addons-for-elementor' ),
		AAE_RADIUS_PROP => __( 'Border Radius',    'animation-addons-for-elementor' ),
	);

	foreach ( aae_style_props() as $prop_key => $config ) {
		$items[] = Select_Control::bind_to( $prop_key )
			->set_label( esc_html( $labels[ $prop_key ] ) )
			->set_options( $config['options'] );
	}

	$controls[] = Section::make()
		->set_id( 'aae_style' )
		->set_label( esc_html__( 'AAE Style', 'animation-addons-for-elementor' ) )
		->set_items( $items );

	return $controls;
}

function aae_inject_style_css( string $content ): string {
	if ( false === strpos( $content, 'data-interaction-id' ) ) {
		return $content;
	}

	$document = \Elementor\Plugin::$instance->documents->get_current();
	if ( ! $document ) {
		return $content;
	}

	$rules = array();
	aae_collect_style_rules( $document->get_elements_data(), $rules );

	if ( empty( $rules ) ) {
		return $content;
	}

	return '<style id="aae-atomic-style">' . implode( '', $rules ) . '</style>' . $content;
}

function aae_collect_style_rules( array $elements, array &$rules ): void {
	$config = aae_style_props();

	foreach ( $elements as $element ) {
		$type = $element['widgetType'] ?? $element['elType'] ?? '';

		if ( AAE_ATOMIC_HEADING === $type ) {
			$decls = array( 'padding:8px 16px', 'display:inline-block' );

			foreach ( $config as $prop_key => $prop_config ) {
				$raw = $element['settings'][ $prop_key ] ?? '';
				if ( is_array( $raw ) && isset( $raw['value'] ) ) {
					$raw = $raw['value'];
				}
				$raw = is_string( $raw ) ? trim( $raw ) : '';
				if ( '' === $raw ) {
					continue;
				}
				// Whitelist: value must match one of the registered options.
				$allowed = array_filter( wp_list_pluck( $prop_config['options'], 'value' ) );
				if ( ! in_array( $raw, $allowed, true ) ) {
					continue;
				}
				$decls[] = $prop_config['css'] . ':' . $raw;
			}

			if ( count( $decls ) > 2 ) { // more than just padding+display
				$rules[] = sprintf(
					'[data-interaction-id="%s"]{%s;}',
					esc_attr( $element['id'] ),
					esc_attr( implode( ';', $decls ) )
				);
			}
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			aae_collect_style_rules( $element['elements'], $rules );
		}
	}
}

/* ───────────── Asset registration ───────────── */

add_action(
	'elementor/editor/after_enqueue_scripts',
	function (): void {
		if ( ! defined( 'WCF_ADDONS_URL' ) ) {
			return;
		}
		wp_enqueue_script(
			'aae-atomic-extender',
			WCF_ADDONS_URL . 'assets/js/aae-atomic-extender.js',
			array( 'elementor-editor' ),
			'1.0.0',
			true
		);
	}
);
