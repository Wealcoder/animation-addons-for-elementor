<?php

namespace WCF_ADDONS\Atomic\HorizontalScrollAnim;

use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\HorizontalScrollAnim\Schema;

if (! defined('ABSPATH')) {
	exit;
}

final class Render
{
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void
	{

		add_action(
			'elementor/frontend/before_render',
			[$this, 'maybe_register']
		);
	}

	public function maybe_register( $element ): void {
		if (
			! is_object( $element ) ||
			! method_exists( $element, 'get_element_type' )
		) {
			return;
		}

		if (
			! in_array(
				$element->get_element_type(),
				Schema::targeted_elements(),
				true
			)
		) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::ENABLE ] ?? null );
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$config = [
			'width' => '300%',
			'end'   => 3000,
			'start' => 'top top',
		];

		// Pre-compute which breakpoints have horizontal scroll disabled.
		$disabled_bps = [];
		$enabled_resolved = [ 'desktop' => $enabled_map['desktop'] ?? false ];
		foreach ( $extra_bps as $bp ) {
			$own = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_map['desktop'] ?? false );
			$effective = ( null === $own || '' === $own ) ? $parent_enabled : (bool) $own;
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$this->emit_responsive(
			$config,
			$settings,
			Schema::ENABLE,
			'enabled',
			false,
			$extra_bps,
			static fn( $v ) => (bool) $v,
			$disabled_bps
		);
		$this->emit_responsive(
			$config,
			$settings,
			Schema::WIDTH,
			'width',
			'300%',
			$extra_bps,
			[ $this, 'cast_value' ],
			$disabled_bps
		);
		$this->emit_responsive(
			$config,
			$settings,
			Schema::END,
			'end',
			'3000',
			$extra_bps,
			[ $this, 'cast_value' ],
			$disabled_bps
		);
		$this->emit_responsive(
			$config,
			$settings,
			Schema::START,
			'start',
			'top top',
			$extra_bps,
			[ $this, 'cast_value' ],
			$disabled_bps
		);

		if ( empty( $config ) ) {
			return;
		}

		InteractionsMap::register( 'horizontal', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-horizontal' );
		}
	}

	/** Numeric strings round-trip as numbers; others stay strings. */
	private function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}
}
