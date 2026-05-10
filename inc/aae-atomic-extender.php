<?php
/**
 * AAE — Elementor v4 Atomic Widget Extender (Animation example).
 *
 * Adds an "AAE Animation" section to the v4 Atomic Heading (e-heading) widget
 * with a Select control for fade-in / slide-up / scale-in animations.
 *
 * Architecture:
 *   1. PHP filters register the prop + control (props-schema + controls).
 *   2. A CSS file ships @keyframes for the predefined animations.
 *   3. Frontend render: `elementor/frontend/the_content` walks the document
 *      data and prepends a <style> block scoped by data-interaction-id.
 *   4. Editor live preview: a small JS bridge subscribes to v4 settings
 *      changes and rewrites the same <style> block inside the preview iframe
 *      head so changes show immediately without reload.
 *
 * @package AAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

const AAE_ATOMIC_HEADING    = 'e-heading';
const AAE_ATOMIC_ANIM_PROP  = 'aae_animation';
const AAE_ATOMIC_SECTION_ID = 'aae_animation';

function aae_animation_options(): array {
	return array(
		array( 'value' => '',          'label' => __( 'None',     'animation-addons-for-elementor' ) ),
		array( 'value' => 'fade-in',   'label' => __( 'Fade In',  'animation-addons-for-elementor' ) ),
		array( 'value' => 'slide-up',  'label' => __( 'Slide Up', 'animation-addons-for-elementor' ) ),
		array( 'value' => 'scale-in',  'label' => __( 'Scale In', 'animation-addons-for-elementor' ) ),
	);
}

function aae_animation_allowed_values(): array {
	return array_filter( wp_list_pluck( aae_animation_options(), 'value' ) );
}

add_action(
	'elementor/init',
	function (): void {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return;
		}

		add_filter( 'elementor/atomic-widgets/props-schema', 'aae_inject_atomic_props' );
		add_filter( 'elementor/atomic-widgets/controls', 'aae_inject_atomic_controls', 10, 2 );
		add_filter( 'elementor/frontend/the_content', 'aae_inject_atomic_styles' );
	}
);

function aae_inject_atomic_props( array $schema ): array {
	if ( ! isset( $schema[ AAE_ATOMIC_ANIM_PROP ] ) ) {
		$schema[ AAE_ATOMIC_ANIM_PROP ] = String_Prop_Type::make()->default( '' );
	}
	return $schema;
}

function aae_inject_atomic_controls( array $controls, $element ): array {
	if ( ! is_object( $element ) || ! method_exists( $element, 'get_name' ) ) {
		return $controls;
	}
	if ( AAE_ATOMIC_HEADING !== $element->get_name() ) {
		return $controls;
	}

	$controls[] = Section::make()
		->set_id( AAE_ATOMIC_SECTION_ID )
		->set_label( esc_html__( 'AAE Animation', 'animation-addons-for-elementor' ) )
		->set_items( array(
			Select_Control::bind_to( AAE_ATOMIC_ANIM_PROP )
				->set_label( esc_html__( 'Animation', 'animation-addons-for-elementor' ) )
				->set_options( aae_animation_options() ),
		) );

	return $controls;
}

function aae_inject_atomic_styles( string $content ): string {
	if ( false === strpos( $content, 'data-interaction-id' ) ) {
		return $content;
	}

	$document = \Elementor\Plugin::$instance->documents->get_current();
	if ( ! $document ) {
		return $content;
	}

	$rules = array();
	aae_collect_animation_rules( $document->get_elements_data(), $rules );

	if ( empty( $rules ) ) {
		return $content;
	}

	return '<style id="aae-atomic-anim">' . implode( '', $rules ) . '</style>' . $content;
}

function aae_collect_animation_rules( array $elements, array &$rules ): void {
	$allowed = aae_animation_allowed_values();

	foreach ( $elements as $element ) {
		$type = $element['widgetType'] ?? $element['elType'] ?? '';

		if ( AAE_ATOMIC_HEADING === $type ) {
			$raw = $element['settings'][ AAE_ATOMIC_ANIM_PROP ] ?? '';
			if ( is_array( $raw ) && isset( $raw['value'] ) ) {
				$raw = $raw['value'];
			}
			if ( in_array( $raw, $allowed, true ) ) {
				$rules[] = sprintf(
					'[data-interaction-id="%s"]{animation:aae-%s 0.6s ease-out both;}',
					esc_attr( $element['id'] ),
					esc_attr( $raw )
				);
			}
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			aae_collect_animation_rules( $element['elements'], $rules );
		}
	}
}

/* ───────────── Asset registration ───────────── */

add_action(
	'wp_enqueue_scripts',
	function (): void {
		if ( ! defined( 'WCF_ADDONS_URL' ) ) {
			return;
		}
		wp_enqueue_style(
			'aae-atomic-extender',
			WCF_ADDONS_URL . 'assets/css/aae-atomic-extender.css',
			array(),
			'1.0.0'
		);
	}
);

add_action(
	'elementor/preview/enqueue_styles',
	function (): void {
		if ( ! defined( 'WCF_ADDONS_URL' ) ) {
			return;
		}
		wp_enqueue_style(
			'aae-atomic-extender',
			WCF_ADDONS_URL . 'assets/css/aae-atomic-extender.css',
			array(),
			'1.0.0'
		);
	}
);

add_action(
	'elementor/editor/after_enqueue_scripts',
	function (): void {
		if ( ! defined( 'WCF_ADDONS_URL' ) ) {
			return;
		}
		wp_enqueue_script(
			'aae-atomic-extender',
			WCF_ADDONS_URL . 'assets/js/aae-atomic-extender.js',
			array( 'jquery', 'elementor-editor' ),
			'1.0.0',
			true
		);
	}
);
