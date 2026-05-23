<?php

namespace WCF_ADDONS\Atomic\ScrollTo;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

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

	public function maybe_register(
		$element
	): void {

		if (
			! is_object($element) ||
			! method_exists(
				$element,
				'get_element_type'
			)
		) {
			return;
		}

		if (
			! in_array(
				$element->get_element_type(),
				Bootstrap::target_element_types(),
				true
			)
		) {
			return;
		}

		$settings = method_exists(
			$element,
			'get_settings'
		)
			? $element->get_settings()
			: [];

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::ENABLE ] ?? null );

		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists(
			$element,
			'get_id'
		)
			? $element->get_id()
			: '';

		if (empty($id)) {
			return;
		}

		$config = [
			'duration' => 1,
			'ease' => 'power2.out',
		];

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
			$config, $settings, Schema::ENABLE, 'enabled', false, $extra_bps,
			static fn( $v ) => (bool) $v,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::TARGET, 'target', '', $extra_bps,
			static fn( $v ) => (string) $v,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::DURATION, 'duration', 1, $extra_bps,
			static fn( $v ) => is_numeric( $v ) ? (float) $v : null,
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::EASE, 'ease', 'power2.out', $extra_bps,
			static fn( $v ) => (string) $v,
			$disabled_bps
		);

		if ( empty( $config ) ) {
			return;
		}

		InteractionsMap::register(

			'scroll-to',

			$id,

			$config
		);

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-scroll-to' );
		}
	}

}