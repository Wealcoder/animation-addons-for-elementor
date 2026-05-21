<?php

namespace WCF_ADDONS\Atomic\AdvanceTooltip;

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
			! in_array(
				$element->get_element_type(),
				Bootstrap::target_element_types(),
				true
			)
		) {
			return;
		}

		$settings =
			$element->get_settings();

		$config =
			$this->build_config(
				$settings
			);

		if (empty($config['enabled'])) {
			return;
		}

		InteractionsMap::register(

			'advance-tooltip',

			$element->get_id(),

			$config
		);
	}

	private function build_config(
		array $settings
	): array {

		$enabled =
			$settings[
				Schema::TOOLTIP_ENABLE
			] ?? false;

		if (
			is_array($enabled) &&
			isset($enabled['value'])
		) {
			$enabled =
				(bool) (
					$enabled['value']['desktop']
					?? false
				);
		}

		if (! $enabled) {
			return [];
		}

		$extra_bps =
			$this->get_extra_breakpoints();

		$config = [
			'enabled' => true,
		];

		$this->emit_responsive(
			$config,
			$settings,
			Schema::TEXT,
			'text',
			'',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::POSITION,
			'position',
			'top',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::TRIGGER,
			'trigger',
			'hover',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::BG,
			'bg',
			'#000000',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::COLOR,
			'color',
			'#ffffff',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::WIDTH,
			'width',
			'200px',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::OFFSET,
			'offset',
			10,
			$extra_bps,
			static fn($v) => $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::ANIMATION,
			'animation',
			'fade',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::DURATION,
			'duration',
			0.3,
			$extra_bps,
			static fn($v) => $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::ARROW_SIZE,
			'arrowSize',
			10,
			$extra_bps,
			static fn($v) => $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::ALIGNMENT,
			'alignment',
			'center',
			$extra_bps,
			static fn($v) => (string) $v
		);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::ARROW_ENABLE,
			'arrow_enable',
			false,
			$extra_bps,
			static fn($v) => (bool) $v
		);

		$this->emit_responsive_object(
			$config,
			$settings,
			Schema::BORDER_RADIUS,
			'borderRadius',
			$extra_bps
		);

		return $config;
	}
}