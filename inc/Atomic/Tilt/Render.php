<?php

namespace WCF_ADDONS\Atomic\Tilt;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\Traits\Responsive_Config;

if (! defined('ABSPATH')) {
	exit;
}

final class Render
{
	use Responsive_Config;
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
		
		// If it's not enabled on any breakpoint, don't register it
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$config =
			$this->build_config(
				$settings,
				$extra_bps,
				$enabled_map
			);

		if ( ! empty($settings[Schema::ENABLE_EDITOR]) ) {
			$config['enableEditor'] = true;
		}

		$id = method_exists(
			$element,
			'get_id'
		)
			? $element->get_id()
			: '';

		if (empty($id) || empty($config)) {
			return;
		}

		InteractionsMap::register(

			'tilt',

			$id,

			$config
		);

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-tilt' );
		}
	}

	private function build_config(
		array $settings, array $extra_bps, array $enabled_map
	): array {
		$config = [];

		$cast_bool = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );
		$cast_num  = static fn( $v ) => is_numeric( $v ) ? (float) $v : $v;

		// Pre-compute which breakpoints have tilt disabled.
		$disabled_bps = [];
		$enabled_resolved = [ 'desktop' => $cast_bool( $enabled_map['desktop'] ?? false ) ];
		foreach ( $extra_bps as $bp ) {
			$own = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_resolved['desktop'] );
			$effective = ( null === $own || '' === $own ) ? $parent_enabled : $cast_bool( $own );
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$this->emit_responsive(
			$config, $settings, Schema::ENABLE, 'enabled', false, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::MAX, 'max', 15, $extra_bps,
			$cast_num,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::SPEED, 'speed', 300, $extra_bps,
			$cast_num,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::SCALE, 'scale', 1, $extra_bps,
			$cast_num,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::PERSPECTIVE, 'perspective', 1000, $extra_bps,
			$cast_num,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::GLARE, 'glare', false, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::MAX_GLARE, 'maxGlare', 0.5, $extra_bps,
			$cast_num,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::RESET, 'reset', true, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::TRANSITION, 'transition', true, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::GYROSCOPE, 'gyroscope', true, $extra_bps,
			$cast_bool,
			$disabled_bps
		);

		return $config;
	}

}