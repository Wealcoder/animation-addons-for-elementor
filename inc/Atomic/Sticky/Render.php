<?php

namespace WCF_ADDONS\Atomic\Sticky;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void {

		add_action(
			'elementor/frontend/before_render',
			[ $this, 'maybe_register' ]
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

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map( $settings[ Schema::STICKY_ENABLE ] ?? null );
		if ( ! $this->any_breakpoint_enabled( $enabled_map, $extra_bps ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' )
			? $element->get_id()
			: '';

		if ( empty( $id ) ) {
			return;
		}

		$config = $this->build_config( $settings, $extra_bps, $enabled_map );

		if ( empty( $config ) ) {
			return;
		}

		InteractionsMap::register(
			'sticky',
			$id,
			$config
		);

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-sticky' );
		}
	}

	private function build_config( array $settings, array $extra_bps, array $enabled_map ): array {
		$config = [];

		$cast = static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' );

		// Pre-compute which breakpoints have sticky disabled.
		$disabled_bps = [];
		$enabled_resolved = [ 'desktop' => $cast( $enabled_map['desktop'] ?? false ) ];
		foreach ( $extra_bps as $bp ) {
			$own = $enabled_map[ $bp ] ?? null;
			$parent_enabled = $this->cascade_parent( $bp, $enabled_resolved, $enabled_resolved['desktop'] );
			$effective = ( null === $own || '' === $own ) ? $parent_enabled : $cast( $own );
			$enabled_resolved[ $bp ] = $effective;
			if ( ! $effective ) {
				$disabled_bps[ $bp ] = true;
			}
		}

		$this->emit_responsive(
			$config, $settings, Schema::STICKY_ENABLE, 'enable', false, $extra_bps,
			$cast,
			$disabled_bps
		);

		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_TRIGGER, 'pinTrigger', 'default', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'default',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN_AREA, 'customPinArea', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : '',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_END_TRIGGER, 'pinEndTrigger', 'default', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'default',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN_END_AREA, 'customPinEndArea', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : '',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN, 'pin', true, $extra_bps,
			static fn( $v ) => $v === 'custom' ? 'custom' : ( is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' ) ),
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_CUSTOM_PIN, 'customPin', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : '',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_START, 'pinStart', 'top top', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'top top',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_END, 'pinEnd', 'bottom bottom', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : 'bottom bottom',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_PIN_SPACING, 'pinSpacing', false, $extra_bps,
			static fn( $v ) => is_bool( $v ) ? $v : ( $v === 'yes' || $v === 'true' || $v === 1 || $v === '1' ),
			$disabled_bps
		);
		$pin_markers_val = $this->read_primitive( $settings, Schema::STICKY_PIN_MARKERS, false );
		$pin_markers_bool = is_bool( $pin_markers_val ) ? $pin_markers_val : ( $pin_markers_val === 'yes' || $pin_markers_val === 'true' || $pin_markers_val === 1 || $pin_markers_val === '1' );
		if ( $pin_markers_bool ) {
			$config['pinMarkers'] = true;
		}
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_TOGGLE_CLASS, 'toggleClass', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : '',
			$disabled_bps
		);
		$this->emit_responsive(
			$config, $settings, Schema::STICKY_BG_COLOR, 'bgColor', '', $extra_bps,
			static fn( $v ) => is_string( $v ) ? $v : '',
			$disabled_bps
		);

		// Emit border object
		$border_envelope = $settings[ Schema::STICKY_BORDER ] ?? null;
		$border_map      = $this->envelope_to_map( $border_envelope );

		$border_desktop = $border_map['desktop'] ?? null;
		if ( $border_desktop && is_array( $border_desktop ) && ! empty( $border_desktop['style'] ) && $border_desktop['style'] !== 'none' ) {
			$config['border'] = $border_desktop;
		}

		foreach ( $extra_bps as $bp ) {
			if ( isset( $disabled_bps[ $bp ] ) ) {
				continue;
			}
			$bp_border = $border_map[ $bp ] ?? null;
			if ( $bp_border && is_array( $bp_border ) && ! empty( $bp_border['style'] ) && $bp_border['style'] !== 'none' ) {
				$config[ 'border_' . $bp ] = $bp_border;
			}
		}

		return $config;
	}
}