<?php

namespace WCF_ADDONS\Atomic\ScrollTo;

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

		if (
			empty($config['enabled']['desktop'])
		) {
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

			'scroll-to',

			$id,

			$config
		);
	}

	private function build_config(
		array $settings
	): array {
		return [

			'enabled' =>
			$this->emit_responsive(
				$settings[Schema::ENABLE] ?? [],
				false
			),

			'target' =>
			$this->emit_responsive(
				$settings[Schema::TARGET] ?? [],
				''
			),

			'duration' =>
			$this->emit_responsive(
				$settings[Schema::DURATION] ?? [],
				1
			),

			'ease' =>
			$this->emit_responsive(
				$settings[Schema::EASE] ?? [],
				'power2.out'
			),
		];
	}

	private function emit_responsive(
		$value,
		$fallback = null
	): array {

		$map =
			$this->envelope_to_map(
				$value
			);

		$bps = array_merge(
			['desktop'],
			$this->get_extra_breakpoints()
		);

		$out = [];

		foreach ($bps as $bp) {

			$current =
				$map[$bp] ?? null;

			if (
				null === $current ||
				'' === $current
			) {
				$current =
					$this->cascade_parent(
						$map,
						$bp
					);
			}

			if (
				null === $current ||
				'' === $current
			) {
				$current = $fallback;
			}

			$out[$bp] = $current;
		}

		return $out;
	}

	private function envelope_to_map(
		$value
	): array {

		if (
			is_array($value) &&
			isset($value['value']) &&
			is_array($value['value'])
		) {
			return $value['value'];
		}

		if (is_array($value)) {
			return $value;
		}

		return [];
	}

	private function cascade_parent(
		array $map,
		string $bp
	) {

		$order = array_merge(
			['desktop'],
			$this->get_extra_breakpoints()
		);

		$index =
			array_search(
				$bp,
				$order,
				true
			);

		if (false === $index) {
			return null;
		}

		while ($index > 0) {

			$index--;

			$parent =
				$order[$index];

			if (
				isset($map[$parent]) &&
				'' !== $map[$parent] &&
				null !== $map[$parent]
			) {
				return $map[$parent];
			}
		}

		return null;
	}

	private function get_extra_breakpoints(): array
	{

		$bps = [];

		if (
			! class_exists(
				'\Elementor\Plugin'
			)
		) {
			return [
				'tablet',
				'mobile',
			];
		}

		$manager =
			\Elementor\Plugin::$instance
			->breakpoints;

		if (
			! $manager ||
			! method_exists(
				$manager,
				'get_active_breakpoints'
			)
		) {
			return [
				'tablet',
				'mobile',
			];
		}

		$active =
			$manager->get_active_breakpoints();

		foreach ($active as $bp) {

			$key =
				method_exists($bp, 'get_name')
				? $bp->get_name()
				: null;

			if (
				$key &&
				'desktop' !== $key
			) {
				$bps[] = $key;
			}
		}

		return $bps;
	}
}