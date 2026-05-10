<?php
namespace WCF_ADDONS\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {

	public function register(): void {
		add_filter( 'elementor/frontend/element/attributes', [ $this, 'inject_attributes' ], 10, 2 );
	}

	public function inject_attributes( array $attrs, $element ): array {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return $attrs;
		}

		if ( ! in_array( $element->get_element_type(), Bootstrap::target_element_types(), true ) ) {
			return $attrs;
		}

		$settings = method_exists( $element, 'get_atomic_settings' )
			? $element->get_atomic_settings()
			: [];

		$animation = $settings[ Schema::PROP_KEY ] ?? [];

		if ( empty( $animation['effect'] ) || 'none' === $animation['effect'] ) {
			return $attrs;
		}

		$attrs['data-aae-anim']     = esc_attr( $animation['effect'] );
		$attrs['data-aae-trigger']  = esc_attr( $animation['trigger']  ?? 'in-view' );
		$attrs['data-aae-duration'] = esc_attr( $animation['duration'] ?? '600' );
		$attrs['data-aae-delay']    = esc_attr( $animation['delay']    ?? '0' );
		$attrs['data-aae-easing']   = esc_attr( $animation['easing']   ?? 'power2.out' );
		$attrs['data-aae-repeat']   = esc_attr( $animation['repeat']   ?? '0' );

		return $attrs;
	}
}
