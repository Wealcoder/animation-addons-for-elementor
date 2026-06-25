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
	 * Build the JS-side config object — REPEATER architecture.
	 *
	 * The whole section is now an interactions repeater. Output shape:
	 *   {
	 *     rows:        [ <interaction>, ... ],   // desktop
	 *     rows_tablet: [ ... ],                  // per-bp override (optional)
	 *     enableEditor: true                     // optional
	 *   }
	 * Each <interaction> is the runtime config for one row (effect, method,
	 * trigger, customProps, …). The runtime binds N triggers / tweens, one
	 * per row.
	 *
	 * Exclusive-trigger rule (first-row-wins) is enforced per breakpoint:
	 *   - on_page_load                  → only the first such row survives
	 *   - on_scroll / play_with_scroll  → only the first scroll-type row survives
	 *   - mouseover / click             → unlimited
	 */
	private function build_config(array $settings): array
	{
		$map = $this->envelope_to_map($settings[Schema::ANIM_INTERACTIONS] ?? null);

		$desktop_rows = $this->rows_to_runtime($map['desktop'] ?? []);
		if (empty($desktop_rows)) {
			// Nothing at desktop — check whether any breakpoint has rows; if
			// the whole prop is empty there's no animation to register.
			$any = false;
			foreach ($map as $rows) {
				if (is_array($rows) && ! empty($rows)) { $any = true; break; }
			}
			if (! $any) {
				return [];
			}
		}

		$config = [];
		if (! empty($desktop_rows)) {
			$config['rows'] = $desktop_rows;
		}

		$extra_bps = $this->get_extra_breakpoints();
		foreach ($extra_bps as $bp) {
			if (! array_key_exists($bp, $map) || null === $map[$bp]) {
				continue;
			}
			$bp_rows = $this->rows_to_runtime($map[$bp]);
			// Skip emitting when identical to desktop (runtime cascades).
			if ($bp_rows === $desktop_rows) {
				continue;
			}
			$config['rows_' . $bp] = $bp_rows;
		}

		if ((bool) $this->unwrap_primitive($settings[Schema::ANIM_ENABLE_EDITOR] ?? null, false)) {
			$config['enableEditor'] = true;
		}

		return $config;
	}

	/**
	 * Convert a saved per-breakpoint rows array (flat row objects from the
	 * editor) into runtime interaction configs, applying the exclusive-trigger
	 * dedupe. Rows with effect=none/empty are dropped.
	 */
	private function rows_to_runtime($rows): array
	{
		if (! is_array($rows)) {
			return [];
		}

		$out = [];
		// Two independent exclusive groups, each capped at one row (first-wins):
		//   A) page-load   B) scroll / play-scroll / slide-change.
		// So one page-load row AND one scroll-type row may coexist.
		$exclusive_groups = [
			['on_page_load'],
			['on_scroll', 'play_with_scroll', 'on_slide_change'],
		];
		$used_groups = [];

		foreach ($rows as $row) {
			if (! is_array($row)) {
				continue;
			}
			$effect = isset($row['effect']) ? (string) $row['effect'] : 'none';
			if ('' === $effect || 'none' === $effect) {
				continue;
			}

			$trigger = isset($row['trigger']) ? (string) $row['trigger'] : 'on_scroll';

			// Exclusive-trigger enforcement (first-row-wins PER group): the first
			// row in each group fills that group's slot; later rows of the same
			// group are dropped. The two groups don't block each other.
			$gi = null;
			foreach ($exclusive_groups as $idx => $group) {
				if (in_array($trigger, $group, true)) {
					$gi = $idx;
					break;
				}
			}
			if (null !== $gi) {
				if (isset($used_groups[$gi])) {
					continue;
				}
				$used_groups[$gi] = true;
			}

			$out[] = $this->row_to_config($row, $effect, $trigger);
		}

		return $out;
	}

	/** One editor row → one runtime interaction config (camelCase keys). */
	private function row_to_config(array $row, string $effect, string $trigger): array
	{
		$str = function ($key, $default = '') use ($row) {
			$v = $row[$key] ?? null;
			return (is_scalar($v) && '' !== $v) ? $v : $default;
		};
		$num = function ($key, $default) use ($row) {
			$v = $row[$key] ?? null;
			return (is_numeric($v)) ? $this->cast_value($v) : $default;
		};

		$cfg = [
			'effect'          => $effect,
			'method'          => $str('method', 'from'),
			'trigger'         => $trigger,
			'triggerSelector' => $str('trigger_selector', ''),
			'wrapper'         => $str('wrapper', 'default'),
			'startTrigger'    => $str('start_trigger', ''),
			'endTrigger'      => $str('end_trigger', ''),
			'startPosition'   => $str('start_position', 'top center'),
			'endPosition'     => $str('end_position', 'bottom bottom'),
			'delay'           => $num('delay', 0.15),
			'duration'        => $num('duration', 1.5),
			'easing'          => $str('easing', 'power2.out'),
			'customProps'     => $this->custom_rows_to_pairs($row['custom_props'] ?? []),
			'customPropsTo'   => $this->custom_rows_to_pairs($row['custom_props_to'] ?? []),
		];

		if (! empty($row['markers'])) {
			$cfg['markers'] = true;
		}

		return $cfg;
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
