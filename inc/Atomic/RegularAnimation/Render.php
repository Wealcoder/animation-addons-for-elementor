<?php

namespace WCF_ADDONS\Atomic\RegularAnimation;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Renders Regular Animation onto atomic widgets.
 *
 * Migrated from per-element data-attrs to the InteractionsMap pattern:
 * one `data-aae-id` attr per element + a single inline JS map at the end
 * of <body> holding every animation config on the page. See
 * `inc/Atomic/InteractionsMap.php` for the rationale.
 */
final class Render
{
	use \WCF_ADDONS\Atomic\Traits\Responsive_Config;

	public function register(): void
	{
		// `elementor/frontend/before_render` fires for EVERY element (widgets
		// AND containers like e-flexbox / e-div-block). The widget-only
		// `elementor/widget/render_content` filter would skip containers — we
		// support both, so we use the universal hook and just call into the
		// InteractionsMap (no HTML transformation needed).
		add_action('elementor/frontend/before_render', [$this, 'maybe_register']);
	}

	public function maybe_register($element): void
	{
		if (! is_object($element) || ! method_exists($element, 'get_element_type')) {
			return;
		}

		$type = $element->get_element_type();
		if (! in_array($type, Bootstrap::target_element_types(), true)) {
			return;
		}

		// Use get_settings() (raw saved props), NOT get_atomic_settings().
		// get_atomic_settings() runs every prop through Render_Props_Resolver
		// which strips any transformable whose $$type has no registered
		// transformer — and aae-rj intentionally doesn't register
		// one. Reading raw lets the trait walk the envelope ourselves.
		$settings = method_exists($element, 'get_settings')
			? $element->get_settings()
			: [];

		$config = $this->build_config($settings);
		if (empty($config)) {
			return;
		}

		// Same id Elementor exposes as data-interaction-id on the rendered tag
		// (universal on atomic widgets, frontend + editor). JS looks up
		// window.AAE_INTERACTIONS_ANIM[interactionId] — no custom attr needed.
		$id = method_exists($element, 'get_id') ? (string) $element->get_id() : '';
		if ('' === $id) {
			return;
		}

		InteractionsMap::register('anim', $id, $config);

		// Enqueue effect bundle on demand. Assets.php declares the dep chain
		// so the core runtime is pulled in automatically.
		if (! is_admin()) {
			wp_enqueue_script('aae-effect-animation');
		}
	}

	/**
	 * Build the JS-side config object. Mirrors the structure animation.js
	 * expects after the data-attr → JS-map migration. Keys are camelCase
	 * (JS-side prefers it; no more `el.dataset.aaeFooBar` kebab→camel hops).
	 */
	private function build_config(array $settings): array
	{
		$effect = $this->unwrap_primitive($settings[Schema::ANIM_EFFECT] ?? null, 'none');

		$config = [];

		if ($effect && 'none' !== $effect) {
			$config['effect'] = $effect;

			// Per-attr table: [ config key, default, effect_family|null ].
			// Mirrors the Schema's responsive registrations. Per-bp variants
			// are emitted by `emit_responsive()` below; values equal to the
			// default are skipped (the JS reader supplies the default when
			// the key is missing).
			$responsive_map = [
				Schema::ANIM_EFFECT           => ['effect',          'none',         null],
				Schema::ANIM_METHOD           => ['method',          'from',         null],
				Schema::ANIM_TRIGGER          => ['trigger',         'on_scroll',    null],
				Schema::ANIM_TRIGGER_SELECTOR => ['triggerSelector', '',             null],
				Schema::ANIM_WRAPPER          => ['wrapper',         'default',      null],
				Schema::ANIM_START_TRIGGER    => ['startTrigger',    '',             null],
				Schema::ANIM_END_TRIGGER      => ['endTrigger',      '',             null],
				Schema::ANIM_START_POSITION   => ['startPosition',   'top top',      null],
				Schema::ANIM_END_POSITION     => ['endPosition',     'bottom bottom',   null],
				Schema::ANIM_DELAY            => ['delay',           Schema::RESPONSIVE_NUMBER_SETTINGS[Schema::ANIM_DELAY]    ?? 0.15, null],
				Schema::ANIM_DURATION         => ['duration',        Schema::RESPONSIVE_NUMBER_SETTINGS[Schema::ANIM_DURATION] ?? 1.5,  null],
				Schema::ANIM_EASING           => ['easing',          'power2.out',   null],
			];

			$trigger_map = $this->envelope_to_map($settings[Schema::ANIM_TRIGGER] ?? null);
			$trigger_desktop = $trigger_map['desktop'] ?? 'on_scroll';
			$is_on_scroll = 'on_scroll' === $trigger_desktop || 'play_with_scroll' === $trigger_desktop;

			// Gate: scroll-trigger custom block requires wrapper=custom.
			$wrapper_is_custom  = $this->unwrap_primitive($settings[Schema::ANIM_WRAPPER] ?? null, 'default') === 'custom';
			
			$scroll_only_keys = [
				Schema::ANIM_START_TRIGGER,
				Schema::ANIM_END_TRIGGER,
				Schema::ANIM_START_POSITION,
				Schema::ANIM_END_POSITION,
			];

			$scroll_custom_only = [
				Schema::ANIM_START_TRIGGER,
				Schema::ANIM_END_TRIGGER,
			];


			$extra_bps = $this->get_extra_breakpoints();

			// Pre-compute which breakpoints have the animation disabled
			// (effect=none after cascade). emit_responsive uses this to
			// skip emitting other per-bp keys for those breakpoints.
			$disabled_bps    = [];
			$effect_resolved = ['desktop' => $effect];
			$effect_map      = $this->envelope_to_map($settings[Schema::ANIM_EFFECT] ?? null);

			foreach ($extra_bps as $bp) {
				$own  = $effect_map[$bp] ?? null;
				$parent_eff = $this->cascade_parent($bp, $effect_resolved, $effect);
				$effective  = (null === $own || '' === $own) ? $parent_eff : $own;
				$effect_resolved[$bp] = $effective;
				if (! $effective || 'none' === $effective) {
					$disabled_bps[$bp] = true;
				}
			}

			foreach ($responsive_map as $base_key => [$cfg_key, $default, $family]) {
				if (null !== $family && ! in_array($effect, $family, true)) {
					continue;
				}
				if (! $is_on_scroll && in_array($base_key, $scroll_only_keys, true)) {
					continue;
				}
				if (in_array($base_key, $scroll_custom_only, true) && ! $wrapper_is_custom) {
					continue;
				}

				$this->emit_responsive($config, $settings, $base_key, $cfg_key, $default, $extra_bps, [$this, 'cast_value'], $disabled_bps);
			}

			// Markers — single-value flag, ships independently of wrapper.
			if ((bool) $this->unwrap_primitive($settings[Schema::ANIM_MARKERS] ?? null, false)) {
				$config['markers'] = true;
			}

			// Unconditionally apply customProps and customPropsTo for the new preset architecture
			$this->emit_custom_pairs($config, $settings, Schema::ANIM_CUSTOM_PROPS, 'customProps', $extra_bps);
			$this->emit_custom_pairs($config, $settings, Schema::ANIM_CUSTOM_PROPS_TO, 'customPropsTo', $extra_bps);

			if ((bool) $this->unwrap_primitive($settings[Schema::ANIM_ENABLE_EDITOR] ?? null, false)) {
				$config['enableEditor'] = true;
			}
		}

		return $config;
	}

	private function emit_custom_pairs(array &$config, array $settings, string $base_key, string $cfg_key, array $extra_bps): void
	{
		$map = $this->envelope_to_map($settings[$base_key] ?? null);
		$desktop_pairs = $this->custom_rows_to_pairs($map['desktop'] ?? []);
		if (! empty($desktop_pairs)) {
			$config[$cfg_key] = $desktop_pairs;
		}

		foreach ($extra_bps as $bp) {
			if (! array_key_exists($bp, $map) || null === $map[$bp]) {
				continue;
			}
			$bp_pairs = $this->custom_rows_to_pairs($map[$bp]);
			if ($bp_pairs === $desktop_pairs) {
				continue;
			}
			$config[$cfg_key . '_' . $bp] = $bp_pairs;
		}
	}

	/**
	 * Filter a per-bp rows array into the emitted runtime contract: an array
	 * of { k, v } pairs. Rows are skipped when enabled=false, property is
	 * empty, or property === 'none'. The JS effect runtime accepts strings
	 * for both — numbers are coerced from strings on the JS side.
	 */
	private function custom_rows_to_pairs($rows): array
	{
		if (! is_array($rows)) {
			return [];
		}
		$pairs = [];
		foreach ($rows as $row) {
			if (! is_array($row)) {
				continue;
			}
			$enabled = $row['enabled'] ?? true;
			if (false === $enabled) {
				continue;
			}
			$k = isset($row['property']) && is_scalar($row['property']) ? trim((string) $row['property']) : '';
			if ('' === $k || 'none' === $k) {
				continue;
			}
			$v = isset($row['value']) && is_scalar($row['value']) ? trim((string) $row['value']) : '';
			$pairs[] = ['k' => $k, 'v' => $v];
		}
		return $pairs;
	}

	/** Numeric strings round-trip as numbers; others stay strings. */
	public function cast_value($v)
	{
		if (is_bool($v) || is_int($v) || is_float($v)) return $v;
		if (is_string($v) && is_numeric($v)) {
			return (false !== strpos($v, '.')) ? (float) $v : (int) $v;
		}
		return $v;
	}
}
