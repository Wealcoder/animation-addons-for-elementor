<?php

namespace WCF_ADDONS\Atomic\Animation;

use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\Animation\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_settings' ) ) {
			return;
		}

		$settings = $element->get_settings();
		$id       = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';

		if ( '' === $id ) {
			return;
		}

		$extra_bps = $this->get_extra_breakpoints();

		$this->register_regular_animation( $id, $settings, $extra_bps );
		$this->register_text_animation( $id, $settings, $extra_bps, $element );
	}

	private function register_regular_animation( string $id, array $settings, array $extra_bps ): void {
		$effect_map = $this->envelope_to_map( $settings[ Schema::ANIM_EFFECT ] ?? null );
		if ( ! $this->any_breakpoint_active( $effect_map, $extra_bps, 'none' ) ) {
			return;
		}

		$config = [];
		$disabled_bps = $this->compute_disabled_bps( $effect_map, $extra_bps, 'none' );

		$map = [
			Schema::ANIM_EFFECT             => [ 'effect', 'none' ],
			Schema::ANIM_TRIGGER            => [ 'trigger', 'on_page_load' ],
			Schema::ANIM_DURATION           => [ 'duration', 1.5 ],
			Schema::ANIM_DELAY              => [ 'delay', 0.15 ],
			Schema::ANIM_EASING             => [ 'easing', 'power2.out' ],
			Schema::ANIM_REPEAT             => [ 'repeat', 0 ],
			'aae_anim_method'               => [ 'method', 'from' ],
			'aae_anim_trigger_selector'     => [ 'triggerSelector', '' ],
			'aae_anim_wrapper'              => [ 'wrapper', 'default' ],
			'aae_anim_start_trigger'        => [ 'startTrigger', '' ],
			'aae_anim_end_trigger'          => [ 'endTrigger', '' ],
			'aae_anim_start_position'       => [ 'startPosition', 'top top' ],
			'aae_anim_start_custom'         => [ 'startCustom', 'top top' ],
			'aae_anim_end_position'         => [ 'endPosition', 'bottom top' ],
			'aae_anim_end_custom'           => [ 'endCustom', 'bottom top' ],
			'aae_anim_fade_from'            => [ 'fadeFrom', 'bottom' ],
			'aae_anim_fade_offset'          => [ 'fadeOffset', 50 ],
			'aae_anim_scale'                => [ 'scale', 0.7 ],
			'aae_anim_rotation_dir'         => [ 'rotationDir', 'x' ],
			'aae_anim_rotation'             => [ 'rotation', -80 ],
			'aae_anim_transform_origin'     => [ 'transformOrigin', 'top center -50' ],
		];

		foreach ( $map as $base_key => [ $config_key, $default ] ) {
			$this->emit_responsive(
				$config,
				$settings,
				$base_key,
				$config_key,
				$default,
				$extra_bps,
				[ $this, 'cast_value' ],
				$disabled_bps
			);
		}

		// custom props is an array, we just pass the raw value directly
		if ( isset( $settings['aae_anim_custom_props'] ) ) {
			$this->emit_responsive_object(
				$config,
				$settings,
				'aae_anim_custom_props',
				'customProps',
				[],
				$extra_bps,
				null,
				$disabled_bps
			);
		}

		if ( isset( $settings['aae_anim_markers'] ) ) {
			$m_val = $settings['aae_anim_markers'];
			$config['markers'] = is_array( $m_val ) && isset( $m_val['value'] ) ? (bool) $m_val['value'] : (bool) $m_val;
		}

		// Default fallback for desktop if empty
		if ( ! isset( $config['effect'] ) ) {
			$config['effect'] = $effect_map['desktop'] ?? 'none';
		}

		InteractionsMap::register( 'anim', $id, $config );
	}

	private function register_text_animation( string $id, array $settings, array $extra_bps, $element ): void {
		if ( ! in_array( $element->get_element_type(), Schema::text_animation_widgets(), true ) ) {
			return;
		}

		$effect_map = $this->envelope_to_map( $settings[ Schema::TEXT_EFFECT ] ?? null );
		if ( ! $this->any_breakpoint_active( $effect_map, $extra_bps, 'none' ) ) {
			return;
		}

		$config = [];
		$disabled_bps = $this->compute_disabled_bps( $effect_map, $extra_bps, 'none' );

		$map = [
			Schema::TEXT_EFFECT             => [ 'effect', 'none' ],
			Schema::TEXT_TRIGGER            => [ 'trigger', 'in-view' ],
			Schema::TEXT_TRIGGER_SELECTOR   => [ 'triggerSelector', '' ],
			Schema::TEXT_WRAPPER            => [ 'wrapper', 'default' ],
			Schema::TEXT_DELAY              => [ 'delay', 0.15 ],
			Schema::TEXT_DURATION           => [ 'duration', 1 ],
			Schema::TEXT_STAGGER            => [ 'stagger', 0.02 ],
			Schema::TEXT_TRANSLATE_X        => [ 'translateX', 20 ],
			Schema::TEXT_TRANSLATE_Y        => [ 'translateY', 0 ],
			Schema::TEXT_ROTATION_DIR       => [ 'rotationDir', 'x' ],
			Schema::TEXT_ROTATION           => [ 'rotation', -80 ],
			Schema::TEXT_TRANSFORM_ORIGIN   => [ 'transformOrigin', 'top center -50' ],
			'aae_text_start_trigger'        => [ 'startTrigger', '' ],
			'aae_text_end_trigger'          => [ 'endTrigger', '' ],
			'aae_text_start_position'       => [ 'startPosition', 'top top' ],
			'aae_text_end_position'         => [ 'endPosition', 'bottom top' ],
			'aae_text_invert_start'         => [ 'invertStart', 'top 85%' ],
			'aae_text_invert_end'           => [ 'invertEnd', 'bottom center' ],
			'aae_text_spin_start'           => [ 'spinStart', 'top 85%' ],
			'aae_text_spin_end'             => [ 'spinEnd', 'bottom 30%' ],
			'aae_text_spin_toggle'          => [ 'spinToggle', 'play none none reverse' ],
			'aae_text_scale_ease'           => [ 'scaleEase', 'back' ],
			'aae_text_scale_num'            => [ 'scaleNum', 1.5 ],
			'aae_text_scale_break'          => [ 'scaleBreak', 'lines' ],
			'aae_text_spin_color'           => [ 'spinColor', '' ],
		];

		foreach ( $map as $base_key => [ $config_key, $default ] ) {
			$this->emit_responsive(
				$config,
				$settings,
				$base_key,
				$config_key,
				$default,
				$extra_bps,
				[ $this, 'cast_value' ],
				$disabled_bps
			);
		}

		if ( isset( $settings['aae_text_markers'] ) ) {
			$m_val = $settings['aae_text_markers'];
			$config['markers'] = is_array( $m_val ) && isset( $m_val['value'] ) ? (bool) $m_val['value'] : (bool) $m_val;
		}

		// Default fallback
		if ( ! isset( $config['effect'] ) ) {
			$config['effect'] = $effect_map['desktop'] ?? 'none';
		}

		InteractionsMap::register( 'text', $id, $config );
	}

	private function any_breakpoint_active( array $map, array $extra_bps, $off_value = 'none' ): bool {
		$desktop = $map['desktop'] ?? $off_value;
		if ( $desktop !== $off_value ) {
			return true;
		}
		foreach ( $extra_bps as $bp ) {
			if ( isset( $map[ $bp ] ) && $map[ $bp ] !== '' && $map[ $bp ] !== $off_value ) {
				return true;
			}
		}
		return false;
	}

	private function compute_disabled_bps( array $map, array $extra_bps, $off_value = 'none' ): array {
		$disabled_bps = [];
		$resolved = [ 'desktop' => $map['desktop'] ?? $off_value ];
		foreach ( $extra_bps as $bp ) {
			$own = $map[ $bp ] ?? null;
			$parent = $this->cascade_parent( $bp, $resolved, $map['desktop'] ?? $off_value );
			$effective = ( null === $own || '' === $own ) ? $parent : $own;
			$resolved[ $bp ] = $effective;
			if ( $effective === $off_value ) {
				$disabled_bps[ $bp ] = true;
			}
		}
		return $disabled_bps;
	}

	private function cast_value( $v ) {
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) return $v;
		if ( is_string( $v ) && is_numeric( $v ) ) {
			return ( false !== strpos( $v, '.' ) ) ? (float) $v : (int) $v;
		}
		return $v;
	}
}
