<?php

namespace WCF_ADDONS\Atomic\Sticky;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Render {

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
				Bootstrap::target_element_types(),
				true
			)
		) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$config = $this->build_config( $settings );

		if ( empty( $config['enabled'] ) ) {
			return;
		}

		$id = method_exists( $element, 'get_id' )
			? $element->get_id()
			: '';

		if ( empty( $id ) ) {
			return;
		}

		InteractionsMap::register(
			'sticky',
			$id,
			$config
		);
	}

	private function build_config( array $settings ): array {

		$enabled = (bool) (
			$settings[ Schema::STICKY_ENABLE ] ?? false
		);

		return [

			/*
			|--------------------------------------------------------------------------
			| Base
			|--------------------------------------------------------------------------
			*/

			'enabled' => $enabled,

			/*
			|--------------------------------------------------------------------------
			| Pin Trigger
			|--------------------------------------------------------------------------
			*/

			'pinTrigger' => (
				$settings[ Schema::STICKY_PIN_TRIGGER ]
				?? []
			),

			'customPinArea' => (
				$settings[ Schema::STICKY_CUSTOM_PIN_AREA ]
				?? []
			),

			/*
			|--------------------------------------------------------------------------
			| Pin End Trigger
			|--------------------------------------------------------------------------
			*/

			'pinEndTrigger' => (
				$settings[ Schema::STICKY_PIN_END_TRIGGER ]
				?? []
			),

			'customPinEndArea' => (
				$settings[ Schema::STICKY_CUSTOM_PIN_END_AREA ]
				?? []
			),

			/*
			|--------------------------------------------------------------------------
			| Pin
			|--------------------------------------------------------------------------
			*/

			'pin' => (
				$settings[ Schema::STICKY_PIN ]
				?? []
			),

			'customPin' => (
				$settings[ Schema::STICKY_CUSTOM_PIN ]
				?? []
			),

			/*
			|--------------------------------------------------------------------------
			| Pin Start
			|--------------------------------------------------------------------------
			*/

			'pinStart' => (
				$settings[ Schema::STICKY_PIN_START ]
				?? []
			),

			'customPinStart' => (
				$settings[ Schema::STICKY_CUSTOM_PIN_START ]
				?? []
			),

			/*
			|--------------------------------------------------------------------------
			| Pin End
			|--------------------------------------------------------------------------
			*/

			'pinEnd' => (
				$settings[ Schema::STICKY_PIN_END ]
				?? []
			),

			'customPinEnd' => (
				$settings[ Schema::STICKY_CUSTOM_PIN_END ]
				?? []
			),

			/*
			|--------------------------------------------------------------------------
			| Pin Spacing
			|--------------------------------------------------------------------------
			*/

			'pinSpacing' => (
				$settings[ Schema::STICKY_PIN_SPACING ]
				?? []
			),

			/*
			|--------------------------------------------------------------------------
			| Pin Markers
			|--------------------------------------------------------------------------
			*/

			'pinMarkers' => (
				$settings[ Schema::STICKY_PIN_MARKERS ]
				?? false
			),

		];
	}
}