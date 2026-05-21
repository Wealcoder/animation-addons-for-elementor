<?php

namespace WCF_ADDONS\Atomic\HorizontalScrollAnim;

use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\HorizontalScrollAnim\Schema;

if (! defined('ABSPATH')) {
	exit;
}

final class Render
{

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

		if ( empty( $config ) ) {
			return;
		}

		InteractionsMap::register( 'horizontal', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-horizontal' );
		}
	}
	private function cascade_parent(string $bp, array $resolved, $desktop_value)
	{
		static $cascade = [
			'mobile'       => [ 'mobile_extra', 'tablet', 'tablet_extra', 'laptop' ],
			'mobile_extra' => [ 'tablet', 'tablet_extra', 'laptop' ],
			'tablet'       => [ 'tablet_extra', 'laptop' ],
			'tablet_extra' => [ 'laptop' ],
			'laptop'       => [],
			'widescreen'   => [],
		];
		foreach ($cascade[$bp] ?? [] as $step) {
			if (array_key_exists($step, $resolved)) {
				return $resolved[$step];
			}
		}
		return $desktop_value;
	}

	/** Numeric strings round-trip as numbers; others stay strings. */
	private function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}
	private function emit_responsive(
		array &$config,
		array $settings,
		string $base_key,
		string $cfg_key,
		$default,
		array $extra_bps,
		callable $cast,
		array $disabled_bps = []
	): void {
		$map = $this->envelope_to_map($settings[$base_key] ?? null);

		$desktop_raw   = $map['desktop'] ?? $default;
		$desktop_value = $cast($desktop_raw);

		if ($desktop_value !== $cast($default)) {
			$config[$cfg_key] = $desktop_value;
		}

		$resolved = ['desktop' => $desktop_value];

		foreach ($extra_bps as $bp) {
			$own_raw = $map[$bp] ?? null;
			$parent  = $this->cascade_parent($bp, $resolved, $desktop_value);

			if (null === $own_raw || '' === $own_raw) {
				$resolved[$bp] = $parent;
				continue;
			}

			$own_value = $cast($own_raw);
			$resolved[$bp] = $own_value;

			if ($own_value === $parent) {
				continue;
			}

			// Skip per-bp emission when the user disabled horizontal scroll on this breakpoint.
			// The enabled key itself must still be emitted so the runtime knows it's disabled.
			if ( isset( $disabled_bps[ $bp ] ) && 'enabled' !== $cfg_key ) {
				continue;
			}

			$config[$cfg_key . '_' . $bp] = $own_value;
		}
	}

	private function envelope_to_map($envelope): array
	{
		if (! is_array($envelope) || ! isset($envelope['value']) || ! is_array($envelope['value'])) {
			return [];
		}
		return $envelope['value'];
	}

	/**
	 * Active extra-breakpoint keys (non-desktop), largest→smallest. Falls
	 * back to tablet+mobile when Elementor's Breakpoints manager isn't loaded.
	 */
	private function get_extra_breakpoints(): array
	{
		$active_keys = [];

		if (
			class_exists(\Elementor\Plugin::class)
			&& isset(\Elementor\Plugin::$instance->breakpoints)
			&& method_exists(\Elementor\Plugin::$instance->breakpoints, 'get_active_breakpoints')
		) {
			$active_keys = array_keys(\Elementor\Plugin::$instance->breakpoints->get_active_breakpoints());
		}

		if (empty($active_keys)) {
			$active_keys = ['tablet', 'mobile'];
		}

		static $order = ['widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile'];
		$ordered = [];
		foreach ($order as $bp) {
			if (in_array($bp, $active_keys, true)) {
				$ordered[] = $bp;
			}
		}
		return $ordered;
	}

	/**
	 * True when desktop has parallax enabled OR any extra-bp has its own
	 * enabled=true override. Skip the cascade chain — a child bp inheriting
	 * `enabled=true` from a parent counts too because we walk the parents.
	 */
	private function any_breakpoint_enabled(array $enabled_map, array $extra_bps): bool
	{
		if (($enabled_map['desktop'] ?? false) === true) {
			return true;
		}
		foreach ($extra_bps as $bp) {
			if (($enabled_map[$bp] ?? null) === true) {
				return true;
			}
		}
		return false;
	}

}
