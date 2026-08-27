<?php
namespace WCF_ADDONS\Atomic\NestedSlider;

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

		$type = $element->get_element_type();
		if ( 'e-aae-a-slider' !== $type ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		// Pre-populate all desktop defaults so the config is never empty even
		// when the user hasn't changed any settings (emit_responsive only writes
		// values that differ from their default, so a fresh slider produces []).
		$config = [
			'effect'            => 'slide',
			'slidesPerView'     => 3,
			'peek'              => 0,
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
			'overflow'          => 'visible',
		];
		$extra_bps  = $this->get_extra_breakpoints();

		$this->emit_responsive( $config, $settings, Schema::NS_EFFECT, 'effect', 'slide', $extra_bps, static fn( $v ) => is_string($v) ? $v : 'slide' );
		$this->emit_responsive( $config, $settings, Schema::NS_SLIDES_PER_VIEW, 'slidesPerView', 3, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PEEK, 'peek', 0, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_CF_ROTATE, 'cfRotate', 45, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_CF_DEPTH, 'cfDepth', 100, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_GAP, 'gap', 0, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_SLIDE_HEIGHT, 'slideHeight', 0, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_EASING, 'easing', 'power2', $extra_bps, static fn( $v ) => is_string($v) ? $v : 'power2' );
		$this->emit_responsive( $config, $settings, Schema::NS_CENTER_MODE, 'centerMode', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_CENTER_SCALE, 'centerScale', 0.85, $extra_bps, static fn( $v ) => is_numeric($v) ? (float) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_ENABLE_3D, 'enable3d', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE, 'perspective', 1200, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE_ROTATE, 'perspectiveRotate', 50, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE_DEPTH, 'perspectiveDepth', 250, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_PERSPECTIVE_OFFSET, 'perspectiveOffset', 18, $extra_bps, static fn( $v ) => is_numeric($v) ? (float) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY, 'autoplay', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY_SPEED, 'autoplaySpeed', 3000, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY_DELAY, 'autoplayDelay', 0, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_AUTOPLAY_DIRECTION, 'autoplayDirection', 'right', $extra_bps, static fn( $v ) => is_string($v) ? $v : 'right' );
		$this->emit_responsive( $config, $settings, Schema::NS_TRANSITION_SPEED, 'transitionSpeed', 680, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_LOOP, 'loop', false, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_PAUSE_ON_HOVER, 'pauseOnHover', true, $extra_bps, static fn( $v ) => (bool) $v );
		$this->emit_responsive( $config, $settings, Schema::NS_CARD_SCALE, 'cardScale', 0.88, $extra_bps, static fn( $v ) => is_numeric($v) ? (float) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_CARD_TILT, 'cardTilt', 20, $extra_bps, static fn( $v ) => is_numeric($v) ? (int) $v : null );
		$this->emit_responsive( $config, $settings, Schema::NS_OVERFLOW, 'overflow', 'visible', $extra_bps, static fn( $v ) => is_string($v) ? $v : 'visible' );

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' !== $id ) {
			\WCF_ADDONS\Atomic\InteractionsMap::register( 'ns', $id, $config );
		}

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-nested-slider' );
		}
	}
}
