<?php

namespace WCF_ADDONS\Atomic\CustomCss;

use Elementor\Core\DynamicTags\Dynamic_CSS;
use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;
use WCF_ADDONS\Atomic\CustomCss\Schema;

if (! defined('ABSPATH')) {
	exit;
}

final class Render
{
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void
	{
		add_action(
			'elementor/frontend/before_render',
			[$this, 'maybe_register']
		);		
	}

	public function maybe_register($element): void
	{

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

		$raw_data = $element->get_raw_data();
		$settings = $raw_data['settings'] ?? [];
		
		$extra_bps   = $this->get_extra_breakpoints();
		$enabled_map = $this->envelope_to_map($settings[Schema::ENABLE] ?? null);
		if (! $this->any_breakpoint_enabled($enabled_map, $extra_bps)) {
			return;
		}

		$id = method_exists($element, 'get_id') ? $element->get_id() : '';

		if (empty($id)) {
			return;
		}

		$config = $this->build_config($settings, $extra_bps, $enabled_map);

		if (empty($config)) {
			return;
		}

		InteractionsMap::register(
			'custom_css',
			$id,
			$config
		);

		if (! is_admin()) {
			wp_enqueue_script('aae-effect-custom-css');
		}
	}

	private function build_config(
		array $settings,
		array $extra_bps,
		array $enabled_map
	): array {
		$config = [];

		$cast_bool = static fn($v) => is_bool($v) ? $v : ($v === 'yes' || $v === 'true' || $v === 1 || $v === '1');
		$cast_string = static fn($v) => is_string($v) ? $v : (null === $v ? '' : (string) $v);

		$disabled_bps = [];
		$enabled_resolved = ['desktop' => $cast_bool($enabled_map['desktop'] ?? false)];
		foreach ($extra_bps as $bp) {
			$own = $enabled_map[$bp] ?? null;
			$parent_enabled = $this->cascade_parent($bp, $enabled_resolved, $enabled_resolved['desktop']);
			$effective = (null === $own || '' === $own) ? $parent_enabled : $cast_bool($own);
			$enabled_resolved[$bp] = $effective;
			if (! $effective) {
				$disabled_bps[$bp] = true;
			}
		}

		$config['enable_editor'] = (bool) $this->unwrap_primitive($settings[Schema::ENABLE_EDITOR] ?? null, false);

		$this->emit_responsive(
			$config,
			$settings,
			Schema::ENABLE,
			'enabled',
			false,
			$extra_bps,
			$cast_bool,
			$disabled_bps
		);

		$this->emit_responsive($config, $settings, Schema::CSS, 'css', '', $extra_bps, $cast_string, $disabled_bps);

		if (! isset($config['enabled'])) {
			$config['enabled'] = $enabled_resolved['desktop'];
		}

		return $config;
	}
	
}
