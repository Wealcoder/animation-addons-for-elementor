<?php
/**
 * AAE Loop Grid Slider — config publisher.
 *
 * Reuses the Nested Slider's schema (NestedSlider\Schema::NS_*) and its exact
 * config shape, publishing it to the SAME InteractionsMap namespace ('ns') and
 * enqueuing the SAME runtime bundle (aae-effect-nested-slider). That means one
 * slider JS drives both the Nested Slider and the Loop Grid Slider — the reason
 * this Render mirrors inc/Atomic/NestedSlider/Render.php almost line for line,
 * differing only in the element type it matches and the extra load-more bridge
 * script it enqueues for AJAX paging.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\Atomic\LoopGridSlider;

use WCF_ADDONS\Atomic\NestedSlider\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		if ( 'e-aae-a-loop-grid-slider' !== $element->get_element_type() ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];

		// Pre-populate all desktop defaults so the config is never empty even when
		// the user hasn't changed any settings (emit_responsive only writes values
		// that differ from their default). Identical to the Nested Slider defaults.
		$config = [
			'effect'            => 'slide',
			'slidesPerView'     => 3,
			'peek'              => 8,
			'cfRotate'          => 45,
			'cfDepth'           => 100,
			'gap'               => 0,
			'slideHeight'       => 0,
			'easing'            => 'power2',
			'centerMode'        => false,
			'centerScale'       => 0.85,
			'enable3d'          => false,
			'perspective'       => 1200,
			'perspectiveRotate' => 50,
			'perspectiveDepth'  => 250,
			'perspectiveOffset' => 18,
			'autoplay'          => false,
			'autoplaySpeed'     => 3000,
			'autoplayDelay'     => 0,
			'autoplayDirection' => 'right',
			'transitionSpeed'   => 680,
			'loop'              => false,
			'pauseOnHover'      => true,
			'cardScale'         => 0.88,
			'cardTilt'          => 20,
		];
		$extra_bps = $this->get_extra_breakpoints();

		$this->emit_responsive( $config, $settings, Schema::NS_EFFECT, 'effect', 'slide', $extra_bps, static fn( $v ) => is_string( $v ) ? $v : 'slide' );
		$this->emit_responsive( $config, $settings, Schema::NS_SLIDES_PER_VIEW, 'slidesPerView', 3, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PEEK, 'peek', 8, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_CF_ROTATE, 'cfRotate', 45, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_CF_DEPTH, 'cfDepth', 100, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_GAP, 'gap', 0, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_SLIDE_HEIGHT, 'slideHeight', 0, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_EASING, 'easing', 'power2', $extra_bps, static fn( $v ) => is_string( $v ) ? $v : 'power2' );
		$this->emit_responsive( $config, $settings, Schema::NS_CENTER_MODE, 'centerMode', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_CENTER_SCALE, 'centerScale', 0.85, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (float) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_ENABLE_3D, 'enable3d', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE, 'perspective', 1200, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE_ROTATE, 'perspectiveRotate', 50, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE_DEPTH, 'perspectiveDepth', 250, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE_OFFSET, 'perspectiveOffset', 18, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (float) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY, 'autoplay', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY_SPEED, 'autoplaySpeed', 3000, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY_DELAY, 'autoplayDelay', 0, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY_DIRECTION, 'autoplayDirection', 'right', $extra_bps, static fn( $v ) => is_string( $v ) ? $v : 'right' );
		$this->emit_responsive( $config, $settings, Schema::NS_TRANSITION_SPEED, 'transitionSpeed', 680, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_LOOP, 'loop', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_PAUSE_ON_HOVER, 'pauseOnHover', true, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_CARD_SCALE, 'cardScale', 0.88, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (float) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_CARD_TILT, 'cardTilt', 20, $extra_bps, static fn( $v ) => is_numeric( $v ) ? (int) $v : null );

		// Responsive slides-per-view fallback. The shared NS schema only defaults
		// desktop (3), so without this a 3-up desktop slider stays 3-up on phones —
		// crushing each post-card to ~100px. Post cards are wider than the Nested
		// Slider's authored slides, so a sensible auto-reduce matters more here.
		// Only fill a breakpoint the user hasn't explicitly overridden (emit_responsive
		// writes slidesPerView_<bp> only when set), so manual values always win.
		$spv_fallbacks = [ 'tablet' => 2, 'mobile' => 1, 'mobile_extra' => 1 ];
		foreach ( $spv_fallbacks as $bp => $value ) {
			if ( in_array( $bp, $extra_bps, true ) && ! isset( $config[ 'slidesPerView_' . $bp ] ) ) {
				$config[ 'slidesPerView_' . $bp ] = $value;
			}
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' !== $id ) {
			\WCF_ADDONS\Atomic\InteractionsMap::register( 'ns', $id, $config );
		}

		if ( ! is_admin() ) {
			// The shared slider runtime — same handle the Nested Slider enqueues.
			wp_enqueue_script( 'aae-effect-nested-slider' );
			// The load-more bridge: paging appends slides then re-binds the slider.
			wp_enqueue_script( 'aae-a-loop-grid-slider-js' );
		}
	}
}
