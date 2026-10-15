<?php
/**
 * Elementor V3/V4 dynamic-tag compatibility helpers.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags;

use Wealcoder\AnimationAddons\DynamicTags\Dynamic_Tags_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Keeps legacy dynamic tags compatible with Elementor's Atomic adapter.
 */
trait Atomic_Compatibility_Trait {

	/**
	 * Raised while the v3 stack must see its controls exactly as registered.
	 *
	 * @var bool
	 */
	private $aae_v3_controls_lock = false;

	/**
	 * Controls that the Atomic (v4) editor should get as native `query`
	 * autocomplete controls instead of whatever v3 type they registered with.
	 *
	 * Elementor's Atomic pipeline has no idea what `aae_select2` is:
	 * `Dynamic_Tags_Converter::convert_control_to_prop_type()` returns null for
	 * it, so the control never lands in `props_schema` — the v4 panel renders a
	 * label with no input, and the `condition` (which rides on the prop type, not
	 * on the control) is lost so every branch shows at once. `query` is the one
	 * native type that means "search & select", and Elementor already knows how
	 * to turn it into a `Query_Control` wired to its own REST endpoints.
	 *
	 * Keyed by control name; the value is a v3-style `autocomplete` array
	 * (`object` => post|tax|attachment|<role>, plus an optional `query`), which
	 * is exactly what `Dynamic_Tags_Editor_Config::convert_autocomplete_control_to_atomic()`
	 * reads. Tags opt in by overriding this; returning an empty array is a no-op.
	 *
	 * @return array<string,array>
	 */
	protected function get_atomic_query_controls() {
		return array();
	}

	/**
	 * Atomic tags require one group slug while older AAE tags return an array.
	 *
	 * @return string
	 */
	public function get_atomic_group() {
		$group = $this->get_group();

		if ( is_array( $group ) ) {
			$group = reset( $group );
		}

		return is_string( $group ) ? $group : '';
	}

	/**
	 * Controls as Elementor's Atomic pipeline needs to see them.
	 *
	 * `Dynamic_Tags_Schemas::get()` builds `props_schema` from this method — NOT
	 * from `get_editor_config()` — and that schema is what `Dynamic_Transformer`
	 * resolves saved v4 settings against, on the frontend as well as in the
	 * editor. So the `query` mapping has to live here, or the props stay missing.
	 *
	 * Only the returned array is rewritten. The registered control stack still
	 * holds `aae_select2`, so `add_control_to_stack()` validation, the custom
	 * control object and its AJAX endpoints are all untouched.
	 *
	 * @param string|null $control_id Single control to fetch.
	 * @return array
	 */
	public function get_controls( $control_id = null ) {
		$controls = parent::get_controls( $control_id );

		// A single-control lookup and the v3 settings pass both want the stack
		// verbatim.
		if ( null !== $control_id || $this->aae_v3_controls_lock || ! is_array( $controls ) ) {
			return $controls;
		}

		foreach ( $this->get_atomic_query_controls() as $name => $autocomplete ) {
			if ( ! isset( $controls[ $name ] ) || ! is_array( $controls[ $name ] ) ) {
				continue;
			}

			$controls[ $name ]['type']         = 'query';
			$controls[ $name ]['autocomplete'] = $autocomplete;
		}

		return $controls;
	}

	/**
	 * Resolve v3 settings against the controls as they were actually registered.
	 *
	 * `Controls_Stack::get_init_settings()` looks each control's type up in the
	 * controls manager and normalises the value through that control object. `query`
	 * is an Elementor Pro control, so on a site without Pro the lookup would miss
	 * and the value would stop being normalised. Hand it the real stack instead —
	 * v3 keeps behaving byte for byte as it did.
	 *
	 * @return array
	 */
	protected function get_init_settings() {
		$this->aae_v3_controls_lock = true;

		try {
			return parent::get_init_settings();
		} finally {
			$this->aae_v3_controls_lock = false;
		}
	}

	/**
	 * Expose controls in a format understood by both the V3 editor and V4 Atomic UI.
	 *
	 * @return array
	 */
	public function get_editor_config() {
		$config = parent::get_editor_config();
		$config['atomic_group'] = $this->get_atomic_group();

		if ( empty( $config['controls'] ) || ! is_array( $config['controls'] ) ) {
			return $config;
		}

		// One `get_tags_config()` array feeds two different panels, and the v3
		// panel has no `query` control view to render (that ships with Elementor
		// Pro). Outside the Atomic config pass this is the v3 list, so undo the
		// mapping and give v3 the same `text` control it has always had. Failing
		// closed here means a change in Elementor's filter order can only ever
		// cost v4 the upgrade — it can never break v3.
		if ( ! Dynamic_Tags_Module::is_atomic_config_pass() ) {
			foreach ( array_keys( $this->get_atomic_query_controls() ) as $name ) {
				if ( ! isset( $config['controls'][ $name ] ) || ! is_array( $config['controls'][ $name ] ) ) {
					continue;
				}

				$config['controls'][ $name ]['type'] = 'text';
				unset( $config['controls'][ $name ]['autocomplete'] );
			}
		}

		foreach ( $config['controls'] as $control_name => $control ) {
			if ( ! is_array( $control ) ) {
				continue;
			}

			if ( ! isset( $control['label'] ) || ! is_string( $control['label'] ) ) {
				$control['label'] = ucwords( str_replace( array( '-', '_' ), ' ', (string) $control_name ) );
			}

			if ( 'section' === ( $control['type'] ?? '' ) ) {
				$config['controls'][ $control_name ] = $control;
				continue;
			}

			$control_type = $control['type'] ?? '';

			// Elementor's Atomic converter does not support V3 Select2 controls.
			if ( in_array( $control_type, array( 'select2', 'aae_select2' ), true ) ) {
				$has_options = ! empty( $control['options'] ) || ! empty( $control['groups'] );
				$control['type'] = $has_options ? 'select' : 'text';
			}

			// Empty option controls cannot be converted to Atomic controls; permit manual key entry.
			$is_empty_select = 'select' === $control['type'] && empty( $control['options'] ) && empty( $control['groups'] );
			$is_empty_choose = 'choose' === $control['type'] && empty( $control['options'] );

			if ( $is_empty_select || $is_empty_choose ) {
				$control['type'] = 'text';
			}

			// Atomic conversion requires every non-section control to have a default.
			if ( ! array_key_exists( 'default', $control ) || null === $control['default'] ) {
				$control['default'] = $this->get_atomic_control_default( $control );
			}

			$config['controls'][ $control_name ] = $control;
		}

		return $config;
	}

	/**
	 * Return a valid default for an Atomic-convertible Elementor control.
	 *
	 * @param array $control Control configuration.
	 * @return mixed
	 */
	private function get_atomic_control_default( array $control ) {
		$type = $control['type'] ?? '';

		switch ( $type ) {
			case 'number':
				return 0;
			case 'switcher':
				return false;
			case 'media':
			case 'query':
				return array();
			case 'choose':
				$options = array_keys( $control['options'] ?? array() );
				return $options[0] ?? '';
			default:
				return '';
		}
	}
}
