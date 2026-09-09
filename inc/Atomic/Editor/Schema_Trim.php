<?php
/**
 * Editor-side schema trim for AAE's atomic extension props.
 *
 * THE PROBLEM. Elementor's `elementor/atomic-widgets/props-schema` filter
 * carries no element argument (has-atomic-base.php::get_props_schema()), so
 * every AAE extension that hooks it adds its props to EVERY atomic type —
 * Text Animation's props land on e-divider, the slider's 26 props on
 * e-heading, and so on. Core types define 4–7 props of their own; with the
 * extensions on they carry ~150. Measured in the editor on a 1,000-element
 * page: `elementor.widgetsCache` grew from 6 MB to 55 MB, retained heap from
 * 136 MB to 303 MB, and a settings change on one element took 715 ms instead
 * of 355 ms, because every element re-render walks the whole schema of its
 * type (editor-canvas/create-props-resolver.ts iterates the schema, not the
 * saved props).
 *
 * WHAT THIS DOES. It removes each extension's props from the EDITOR'S COPY of
 * the schema for the types that extension does not apply to — the copy that
 * travels in `initial_document.widgets[*].atomic_props_schema` and
 * `elements[*].atomic_props_schema`, plus the two AJAX responses that can
 * refresh those entries later. Nothing else is touched.
 *
 * DANGER — NEVER DO THIS ON THE SERVER SCHEMA. `Props_Parser::validate()`
 * (props-parser.php) iterates the schema on save and silently DROPS any saved
 * prop the schema no longer declares. A per-type filter on the server would
 * erase customer settings on the next save. This class only ever edits the
 * localized config after `get_props_schema()` has returned, and only for the
 * editor; the server, the renderer and the save path all keep the full
 * schema. The editor never drops unknown keys on its side (base-settings.js
 * `self.set(attrs)`, set-settings.js), and create-props-resolver.ts only
 * READS the schema — so a prop absent from the client copy is simply not
 * resolved or rendered by a control, which is exactly the state those types
 * were in before the extension was installed.
 *
 * WHAT KEEPS IT SAFE.
 *  - The type list for each module is the SAME static method or constant its
 *    Controls class tests, so a section can never be offered on a type whose
 *    props were stripped. Belt and braces: any prop bound by a control in the
 *    type's own `atomic_controls` config is never removed, whatever the rule
 *    says — a drifted rule degrades to "not trimmed", never to a broken panel.
 *  - Only keys prefixed `aae_` are ever candidates. Core keys are untouched.
 *  - A module with no rule is never trimmed. Pro modules add their rules via
 *    the `aae/atomic/schema_trim_rules` filter; FlexboxChildHover applies to
 *    every atomic element by design and so registers none.
 *  - `dependencies_per_target_mapping` is pruned to the surviving keys, since
 *    Elementor derives it from the same schema.
 *
 * Switch off with `add_filter( 'aae/atomic/schema_trim', '__return_false' )`.
 *
 * @package WCF_ADDONS\Atomic\Editor
 */

namespace WCF_ADDONS\Atomic\Editor;

use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema_Trim {

	const SCHEMA_FILTER = 'elementor/atomic-widgets/props-schema';
	const RULES_FILTER  = 'aae/atomic/schema_trim_rules';

	/** @var array<string, string[]>|null Schema class => element types that KEEP its props. */
	private ?array $rules = null;

	/** @var array<string, string[]>|null Schema class => prop keys it registers. */
	private ?array $module_keys = null;

	/** @var array<string, string[]> Element type => keys to drop (memo). */
	private array $drop_cache = [];

	public function register(): void {
		if ( ! apply_filters( 'aae/atomic/schema_trim', true ) ) {
			return;
		}

		// Initial editor load — `initial_document.widgets` + `elements`.
		add_filter( 'elementor/editor/localize_settings', [ $this, 'trim_localized' ], 1000 );

		// The two AJAX paths that can replace widgetsCache entries after load.
		add_action( 'elementor/ajax/register_actions', [ $this, 'wrap_ajax_actions' ], 1000 );
	}

	/* ------------------------------------------------------------------ */
	/* Entry points                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * @param mixed $env The localized editor settings.
	 * @return mixed
	 */
	public function trim_localized( $env ) {
		if ( ! is_array( $env ) ) {
			return $env;
		}

		if ( isset( $env['initial_document']['widgets'] ) && is_array( $env['initial_document']['widgets'] ) ) {
			$env['initial_document']['widgets'] = $this->trim_configs( $env['initial_document']['widgets'] );
		}

		if ( isset( $env['elements'] ) && is_array( $env['elements'] ) ) {
			$env['elements'] = $this->trim_configs( $env['elements'] );
		}

		return $env;
	}

	/**
	 * Re-registers the two config-carrying AJAX actions with trimming
	 * wrappers. `register_ajax_action()` keys by tag, so a later registration
	 * replaces the earlier one; the wrappers call Elementor's own public
	 * handlers and trim the `widgets` map in what comes back.
	 *
	 * @param \Elementor\Core\Common\Modules\Ajax\Module $ajax
	 */
	public function wrap_ajax_actions( $ajax ): void {
		if ( ! is_object( $ajax ) || ! method_exists( $ajax, 'register_ajax_action' ) ) {
			return;
		}

		$plugin = \Elementor\Plugin::$instance ?? null;
		if ( ! $plugin ) {
			return;
		}

		if ( isset( $plugin->widgets_manager ) && method_exists( $plugin->widgets_manager, 'ajax_refresh_widgets_config' ) ) {
			$manager = $plugin->widgets_manager;
			$ajax->register_ajax_action(
				'refresh_widgets_config',
				function ( $data ) use ( $manager ) {
					$result = $manager->ajax_refresh_widgets_config( (array) $data );
					return $this->trim_widgets_key( $result );
				}
			);
		}

		if ( isset( $plugin->documents ) && method_exists( $plugin->documents, 'ajax_get_document_config' ) ) {
			$documents = $plugin->documents;
			$ajax->register_ajax_action(
				'get_document_config',
				function ( $request ) use ( $documents ) {
					$result = $documents->ajax_get_document_config( $request );
					return $this->trim_widgets_key( $result );
				}
			);
		}
	}

	/* ------------------------------------------------------------------ */
	/* Trimming                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * @param mixed $result An AJAX result that may carry a `widgets` map.
	 * @return mixed
	 */
	private function trim_widgets_key( $result ) {
		if ( is_array( $result ) && isset( $result['widgets'] ) && is_array( $result['widgets'] ) ) {
			$result['widgets'] = $this->trim_configs( $result['widgets'] );
		}
		return $result;
	}

	/**
	 * @param array<string, mixed> $configs Element type => config.
	 * @return array<string, mixed>
	 */
	private function trim_configs( array $configs ): array {
		foreach ( $configs as $type => $config ) {
			if ( ! is_string( $type ) || ! is_array( $config ) ) {
				continue;
			}
			if ( empty( $config['atomic_props_schema'] ) || ! is_array( $config['atomic_props_schema'] ) ) {
				continue;
			}

			$drop = $this->keys_to_drop( $type, $config );
			if ( ! $drop ) {
				continue;
			}

			$configs[ $type ]['atomic_props_schema'] = array_diff_key( $config['atomic_props_schema'], $drop );

			if ( isset( $config['dependencies_per_target_mapping'] ) && is_array( $config['dependencies_per_target_mapping'] ) ) {
				$configs[ $type ]['dependencies_per_target_mapping'] = $this->prune_dependencies(
					$config['dependencies_per_target_mapping'],
					$drop
				);
			}
		}

		return $configs;
	}

	/**
	 * The set of prop keys to remove from `$type`'s client schema.
	 *
	 * @param string               $type
	 * @param array<string, mixed> $config
	 * @return array<string, true>
	 */
	private function keys_to_drop( string $type, array $config ): array {
		$rules = $this->rules();
		$keys  = $this->module_keys();

		if ( ! $rules || ! $keys ) {
			return [];
		}

		if ( ! isset( $this->drop_cache[ $type ] ) ) {
			$drop = [];
			$keep = [];

			foreach ( $keys as $class => $module_keys ) {
				if ( ! isset( $rules[ $class ] ) ) {
					// No rule — the module is never trimmed.
					foreach ( $module_keys as $key ) {
						$keep[ $key ] = true;
					}
					continue;
				}

				$applies = in_array( $type, $rules[ $class ], true );
				foreach ( $module_keys as $key ) {
					if ( $applies ) {
						$keep[ $key ] = true;
					} else {
						$drop[ $key ] = true;
					}
				}
			}

			// A key two modules share is kept if either keeps it.
			$drop = array_diff_key( $drop, $keep );

			// Only our own keys are ever candidates.
			foreach ( array_keys( $drop ) as $key ) {
				if ( 0 !== strpos( $key, 'aae_' ) ) {
					unset( $drop[ $key ] );
				}
			}

			$this->drop_cache[ $type ] = $drop;
		}

		$drop = $this->drop_cache[ $type ];
		if ( ! $drop ) {
			return [];
		}

		// Belt and braces: never remove a prop a control on THIS type binds.
		if ( ! empty( $config['atomic_controls'] ) && is_array( $config['atomic_controls'] ) ) {
			$bound = [];
			$this->collect_bound_props( $config['atomic_controls'], $bound );
			$drop = array_diff_key( $drop, $bound );
		}

		// Only keys that are actually present.
		return array_intersect_key( $drop, $config['atomic_props_schema'] );
	}

	/**
	 * Walks a serialized `atomic_controls` tree and records every `bind`.
	 *
	 * @param mixed               $node
	 * @param array<string, true> $bound
	 */
	private function collect_bound_props( $node, array &$bound ): void {
		if ( ! is_array( $node ) ) {
			return;
		}

		if ( isset( $node['bind'] ) && is_string( $node['bind'] ) ) {
			$bound[ $node['bind'] ] = true;
		}

		foreach ( $node as $child ) {
			if ( is_array( $child ) ) {
				$this->collect_bound_props( $child, $bound );
			}
		}
	}

	/**
	 * @param array<string, mixed> $mapping Source prop => dependent props.
	 * @param array<string, true>  $drop
	 * @return array<string, mixed>
	 */
	private function prune_dependencies( array $mapping, array $drop ): array {
		$mapping = array_diff_key( $mapping, $drop );

		foreach ( $mapping as $source => $dependents ) {
			if ( ! is_array( $dependents ) ) {
				continue;
			}
			$mapping[ $source ] = array_values( array_filter(
				$dependents,
				static fn( $dependent ) => ! is_string( $dependent ) || ! isset( $drop[ $dependent ] )
			) );
		}

		return $mapping;
	}

	/* ------------------------------------------------------------------ */
	/* Rules + keys                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Schema class => element types that keep its props. Each list is read
	 * from the SAME source the module's Controls class tests, so the two
	 * cannot drift apart.
	 *
	 * @return array<string, string[]>
	 */
	private function rules(): array {
		if ( null !== $this->rules ) {
			return $this->rules;
		}

		$shared = is_callable( [ Bootstrap::class, 'target_element_types' ] )
			? (array) Bootstrap::target_element_types()
			: [];

		$rules = [];

		$shared_modules = [
			'\WCF_ADDONS\Atomic\RegularAnimation\Schema',
			'\WCF_ADDONS\Atomic\Parallax\Schema',
			'\WCF_ADDONS\Atomic\CursorHoverEffect\Schema',
			'\WCF_ADDONS\Atomic\MouseMoveEffect\Schema',
			'\WCF_ADDONS\Atomic\AdvanceTooltip\Schema',
			'\WCF_ADDONS\Atomic\Tilt\Schema',
			'\WCF_ADDONS\Atomic\ScrollTo\Schema',
		];
		foreach ( $shared_modules as $class ) {
			if ( $shared && class_exists( $class ) ) {
				$rules[ ltrim( $class, '\\' ) ] = $shared;
			}
		}

		// Custom CSS: its Controls test the shared list, its Schema publishes a
		// list of its own. Keep on the union so neither reader is starved.
		$custom_css = '\WCF_ADDONS\Atomic\CustomCss\Schema';
		if ( class_exists( $custom_css ) ) {
			$own = is_callable( [ $custom_css, 'target_element_types' ] ) ? (array) $custom_css::target_element_types() : [];
			$rules[ ltrim( $custom_css, '\\' ) ] = array_values( array_unique( array_merge( $shared, $own ) ) );
		}

		$own_list = [
			'\WCF_ADDONS\Atomic\TextAnimation\Schema'        => 'text_animation_widgets',
			'\WCF_ADDONS\Atomic\ImageAnimation\Schema'       => 'image_animation_widgets',
			'\WCF_ADDONS\Atomic\ImageHover\Schema'           => 'image_hover_widgets',
			'\WCF_ADDONS\Atomic\Sticky\Schema'               => 'targeted_elements',
			'\WCF_ADDONS\Atomic\HorizontalScrollAnim\Schema' => 'targeted_elements',
			'\WCF_ADDONS\Atomic\ImageOverlay\Schema'         => 'target_element_types',
		];
		foreach ( $own_list as $class => $method ) {
			if ( class_exists( $class ) && is_callable( [ $class, $method ] ) ) {
				$rules[ ltrim( $class, '\\' ) ] = (array) $class::$method();
			}
		}

		$bgv = '\WCF_ADDONS\Atomic\BackgroundVideo\Schema';
		if ( class_exists( $bgv ) && defined( $bgv . '::TARGET_TYPES' ) ) {
			$rules[ ltrim( $bgv, '\\' ) ] = (array) constant( $bgv . '::TARGET_TYPES' );
		}

		// Nested Slider has no Controls class: its section is built inside the
		// two slider widgets' own define_atomic_controls(), and those are the
		// only types that read `aae_ns_*` (NestedSlider\Render, LoopGridSlider\Render).
		$slider = '\WCF_ADDONS\Atomic\NestedSlider\Schema';
		if ( class_exists( $slider ) ) {
			$rules[ ltrim( $slider, '\\' ) ] = [ 'e-aae-a-slider', 'e-aae-a-loop-grid-slider' ];
		}

		/**
		 * Extension schema classes and the element types that keep their props
		 * in the editor. Pro adds its modules here.
		 *
		 * @param array<string, string[]> $rules Fully-qualified Schema class (no leading backslash) => types.
		 */
		$rules = apply_filters( self::RULES_FILTER, $rules );

		$clean = [];
		foreach ( (array) $rules as $class => $types ) {
			if ( is_string( $class ) && is_array( $types ) ) {
				$clean[ ltrim( $class, '\\' ) ] = array_values( array_filter( $types, 'is_string' ) );
			}
		}

		$this->rules = $clean;
		return $this->rules;
	}

	/**
	 * Schema class => the prop keys it adds, discovered by handing each
	 * registered props-schema callback an empty schema. A module that is not
	 * registered (extension switched off) has no callback and therefore no
	 * keys, so nothing of its is ever stripped; a callback that throws is
	 * skipped the same way.
	 *
	 * @return array<string, string[]>
	 */
	private function module_keys(): array {
		if ( null !== $this->module_keys ) {
			return $this->module_keys;
		}

		$this->module_keys = [];

		global $wp_filter;
		$hook = $wp_filter[ self::SCHEMA_FILTER ] ?? null;
		if ( ! $hook instanceof \WP_Hook ) {
			return $this->module_keys;
		}

		$rules = $this->rules();

		foreach ( $hook->callbacks as $callbacks ) {
			foreach ( (array) $callbacks as $callback ) {
				$fn = $callback['function'] ?? null;
				if ( ! is_array( $fn ) || ! isset( $fn[0] ) || ! is_object( $fn[0] ) ) {
					continue;
				}

				$class = get_class( $fn[0] );
				if ( ! isset( $rules[ $class ] ) || ! is_callable( $fn ) ) {
					continue;
				}

				try {
					$out = call_user_func( $fn, [] );
				} catch ( \Throwable $e ) {
					continue;
				}

				if ( is_array( $out ) && $out ) {
					$this->module_keys[ $class ] = array_merge(
						$this->module_keys[ $class ] ?? [],
						array_map( 'strval', array_keys( $out ) )
					);
				}
			}
		}

		return $this->module_keys;
	}
}
