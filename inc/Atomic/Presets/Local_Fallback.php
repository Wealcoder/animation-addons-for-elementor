<?php
namespace WCF_ADDONS\Atomic\Presets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads bundled local preset JSON files — extracted verbatim (same directory
 * scan, same parse_preset_file()/detect_primary_widget_type() logic) from
 * class-atomic.php::get_widget_presets(), made per-type-callable since the
 * new remote-first architecture (Cache) fetches per element type on demand
 * instead of building one all-types blob.
 *
 * Every method here is safe to call when NO local preset files exist at all
 * — an absent/empty presets/ folder is a normal empty result, never a
 * warning. This matters because the user plans to delete every bundled
 * local .json file once the remote server is trusted; this class must
 * already behave correctly in that end state, not just today's mixed state.
 */
final class Local_Fallback {

	/**
	 * All local presets for one element type, tagged with `source: 'local'`
	 * and a derived `category` (the owning widget's humanized name — see
	 * humanize_widget_folder()). Returns [] when nothing local exists for
	 * this type; never throws, never emits a warning.
	 *
	 * @return array<int, array{id:string,name:string,model:array,source:string,category:string,thumbnail_url:string,pro:bool}>
	 */
	public function get_presets_for_type( string $type ): array {
		$all = $this->get_all_presets();

		return $all[ $type ] ?? [];
	}

	/**
	 * All local presets, grouped by element type. Cached for the request
	 * (mirrors the original method's one-scan-per-request behavior).
	 *
	 * @var array<string, array>|null
	 */
	private static ?array $cache = null;

	/**
	 * @return array<string, array<int, array>>
	 */
	private function get_all_presets(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$presets      = [];
		$scanned_dirs = [];

		foreach ( $this->get_available_widgets() as $widget_data ) {
			if ( empty( $widget_data['file'] ) ) {
				continue;
			}

			// `file` is normally relative to inc/AtomicWidgets/ in THIS plugin, but a
			// widget owned by another plugin (the families that moved to Pro) gives
			// an absolute path instead. Concatenating that onto WCF_ADDONS_PATH
			// produces a directory that cannot exist, so the widget's presets/
			// folder is never found — and an unreachable preset is invisible rather
			// than broken: the picker simply shows nothing for that widget.
			//
			// Ask path_is_absolute() BEFORE normalising. On Windows it recognises a
			// drive-letter path only with BACKslashes (`#^[a-zA-Z]:\\#`), and
			// wp_normalize_path() turns those into forward slashes — so a normalised
			// absolute Windows path reads as relative and the bug survives the fix.
			$raw_file   = $widget_data['file'];
			$widget_dir = path_is_absolute( $raw_file )
				? wp_normalize_path( dirname( $raw_file ) )
				: wp_normalize_path( dirname( WCF_ADDONS_PATH . 'inc/AtomicWidgets/' . $raw_file ) );
			$preset_dir = $widget_dir . '/presets';

			if ( ! is_dir( $preset_dir ) ) {
				continue;
			}

			// Several sibling widgets can share one folder (e.g. all NestedSlider
			// parts live under Widgets/NestedSlider) — scan each dir once.
			if ( isset( $scanned_dirs[ $preset_dir ] ) ) {
				continue;
			}
			$scanned_dirs[ $preset_dir ] = true;

			$category = $this->humanize_widget_folder( basename( $widget_dir ) );

			$files = glob( $preset_dir . '/*.json' );
			if ( ! is_array( $files ) ) {
				// glob() can return false on a read error; treat identically
				// to "no files" rather than erroring.
				continue;
			}

			foreach ( $files as $file ) {
				$preset = $this->parse_preset_file( $file );
				if ( ! $preset ) {
					continue;
				}

				$type = $this->detect_primary_widget_type( $preset['model'] );
				if ( '' === $type ) {
					continue;
				}

				$presets[ $type ][] = array_merge(
					$preset,
					[
						'source'        => 'local',
						'category'      => $category,
						'thumbnail_url' => '',
						'pro'           => false,
					]
				);
			}
		}

		// Native atomic widgets (e-heading, e-button, …) — one shared root,
		// one sub-folder per element type; folder name IS both the type key
		// and the category label.
		$native_root = wp_normalize_path( WCF_ADDONS_PATH . 'inc/AtomicWidgets/Presets' );

		if ( is_dir( $native_root ) ) {
			$type_dirs = glob( $native_root . '/*', GLOB_ONLYDIR );

			if ( is_array( $type_dirs ) ) {
				foreach ( $type_dirs as $type_dir ) {
					$type     = basename( $type_dir );
					$category = $this->humanize_widget_folder( $type );

					$files = glob( $type_dir . '/*.json' );
					if ( ! is_array( $files ) ) {
						continue;
					}

					foreach ( $files as $file ) {
						$preset = $this->parse_preset_file( $file );

						if ( $preset ) {
							$presets[ $type ][] = array_merge(
								$preset,
								[
									'source'        => 'local',
									'category'      => $category,
									'thumbnail_url' => '',
									'pro'           => false,
								]
							);
						}
					}
				}
			}
		}

		self::$cache = $presets;

		return $presets;
	}

	/**
	 * Same widget registry class-atomic.php uses — kept as a thin proxy so
	 * this class doesn't need to duplicate the (large) widget list.
	 */
	private function get_available_widgets(): array {
		if ( ! class_exists( '\WCF_ADDONS\AtomicWidgets\Atomic' ) ) {
			return [];
		}

		$atomic = \WCF_ADDONS\AtomicWidgets\Atomic::instance();

		if ( ! method_exists( $atomic, 'get_available_widgets_public' ) ) {
			return [];
		}

		return $atomic->get_available_widgets_public();
	}

	/**
	 * Parse one preset .json file into [id, name, model], accepting both the
	 * Elementor native export format ({content:[<model>], title}) and the
	 * plugin format ({name, model}). Returns null when unreadable/invalid —
	 * never throws.
	 */
	private function parse_preset_file( string $file ): ?array {
		$raw = file_get_contents( $file );

		if ( false === $raw ) {
			return null;
		}

		if ( defined( 'WCF_ADDONS_URL' ) ) {
			$raw = str_replace( '{{AAE_ASSET_URL}}', WCF_ADDONS_URL . 'inc/AtomicWidgets/', $raw );
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		$model = null;
		$name  = basename( $file, '.json' );

		if ( ! empty( $data['model'] ) && is_array( $data['model'] ) ) {
			$model = $data['model'];
			if ( isset( $data['name'] ) ) {
				$name = (string) $data['name'];
			}
		} elseif ( ! empty( $data['content'][0] ) && is_array( $data['content'][0] ) ) {
			$model = $data['content'][0];
			if ( ! empty( $data['title'] ) ) {
				$name = (string) $data['title'];
			}
		}

		if ( ! $model ) {
			return null;
		}

		return [
			'id'    => sanitize_key( basename( $file, '.json' ) ),
			'name'  => $name,
			'model' => $model,
		];
	}

	/**
	 * Find the most relevant widget type a preset targets — descends into
	 * container presets to find the first AAE atomic widget inside.
	 */
	private function detect_primary_widget_type( array $model ): string {
		$container_types = [ 'e-flexbox', 'e-div-block', 'e-grid', 'container' ];

		$root_type = $model['elType'] ?? '';
		if ( 'widget' === $root_type && ! empty( $model['widgetType'] ) ) {
			$root_type = $model['widgetType'];
		}

		if ( ! in_array( $root_type, $container_types, true ) ) {
			return $root_type;
		}

		$queue = $model['elements'] ?? [];

		while ( ! empty( $queue ) ) {
			$node = array_shift( $queue );

			if ( ! is_array( $node ) ) {
				continue;
			}

			$type = $node['elType'] ?? '';
			if ( 'widget' === $type && ! empty( $node['widgetType'] ) ) {
				$type = $node['widgetType'];
			}

			if ( is_string( $type ) && 0 === strpos( $type, 'e-aae-a-' ) ) {
				return $type;
			}

			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				foreach ( $node['elements'] as $child ) {
					$queue[] = $child;
				}
			}
		}

		return $root_type;
	}

	/**
	 * Local presets don't carry a real category (that concept didn't exist
	 * before this work) — derive one from the owning widget/folder name:
	 * PascalCase widget dirs ("NestedSlider") become spaced labels
	 * ("Nested Slider"); native element-type dirs ("e-button") drop the
	 * "e-" prefix and title-case the rest ("Button").
	 */
	private function humanize_widget_folder( string $folder ): string {
		if ( 0 === strpos( $folder, 'e-' ) ) {
			$folder = substr( $folder, 2 );
			$folder = str_replace( '-', ' ', $folder );
			return ucwords( $folder );
		}

		// Split PascalCase/camelCase on word boundaries: "NestedSlider" -> "Nested Slider".
		$spaced = preg_replace( '/(?<!^)(?=[A-Z])/', ' ', $folder );

		return ucwords( trim( (string) $spaced ) );
	}
}
