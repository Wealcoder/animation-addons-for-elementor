<?php

namespace WCF_ADDONS\Atomic\MouseMoveEffect;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

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

		$config =
			$this->build_config(
				$settings
			);

		if (empty($config['enabled'])) {
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

		InteractionsMap::register(

			/*
			|--------------------------------------------------------------------------
			| CHANGE THIS
			|--------------------------------------------------------------------------
			*/

			'mouse-move-effect',

			$id,

			$config
		);
	}

	private function build_config(
		array $settings
	): array {

		return [

			'enabled' => (
				$settings[Schema::ENABLE] ?? false
			),

			'enable_editor' => (
				$settings[Schema::ENABLE_EDITOR] ?? false
			),

			'movement_wrapper' => (
				$settings[Schema::MOVEMENT_WRAPPER] ?? []
			),

			'move_x' => (
				$settings[Schema::MOVE_X] ?? []
			),

			'move_y' => (
				$settings[Schema::MOVE_Y] ?? []
			),

			'duration' => (
				$settings[Schema::DURATION] ?? []
			),

			'customs' => (
				$settings[Schema::CUSTOMS] ?? []
			),
		];
	}
}
