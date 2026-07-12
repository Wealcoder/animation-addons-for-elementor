<?php
namespace WCF_ADDONS\Atomic\Presets;

use Elementor\Modules\AtomicWidgets\Controls\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects a "Presets" section (the Apply Preset picker) into NATIVE atomic
 * widgets — e-heading, e-button, e-image, … — whose classes we don't own, so
 * the section can't be placed in define_atomic_controls() the way AAE widgets
 * do it (see AdvancedHeading).
 *
 * A native widget gets the section only when presets are actually bundled for
 * it: one folder per element type under inc/AtomicWidgets/Presets/, the FOLDER
 * NAME being the element type key. Drop e.g.
 * inc/AtomicWidgets/Presets/e-heading/my-design.json and the section appears
 * on every selected e-heading — no code change needed.
 *
 * The preset JSONs are scanned and localised by
 * class-atomic.php::get_widget_presets(); the shared React control
 * (element-controls/PresetPickerControl.jsx) lists them for the selected
 * element's type and applies on pick.
 *
 * AAE's own widgets (e-aae-a-*) are skipped here — they place the section
 * themselves in define_atomic_controls().
 */
final class Controls {

	const TD = 'animation-addons-for-elementor';

	/**
	 * Element types that have at least one bundled preset, as
	 * [ type => true ]. Populated on first use — one directory scan per request.
	 *
	 * @var array<string, true>|null
	 */
	private static $types_with_presets = null;

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_controls' ], 10, 2 );
	}

	public function inject_controls( array $controls, $element ) {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return $controls;
		}

		if ( ! class_exists( Section::class ) ) {
			return $controls;
		}

		$type = $element->get_element_type();

		// AAE widgets place their own Presets section in define_atomic_controls().
		if ( ! is_string( $type ) || 0 === strpos( $type, 'e-aae-a-' ) ) {
			return $controls;
		}

		if ( ! $this->has_presets( $type ) ) {
			return $controls;
		}

		// Presets first — the section reads as a starting point, per convention.
		array_unshift( $controls, $this->build_presets_section() );

		return $controls;
	}

	private function build_presets_section(): Section {
		return Section::make()
			->set_label( __( 'Presets', self::TD ) )
			->set_id( 'aae_presets' )
			->set_items( [
				AAE_A_Preset_Picker_Control::make()
					->set_label( __( 'Apply Preset', self::TD ) )
					->set_meta( [ 'layout' => 'custom' ] ),
			] );
	}

	/**
	 * Whether any preset JSON is bundled for the given element type. Checks the
	 * same folders class-atomic.php::get_widget_presets() scans
	 * (inc/AtomicWidgets/Presets/<type>/*.json) — keep the path in sync.
	 */
	private function has_presets( string $type ): bool {
		if ( null === self::$types_with_presets ) {
			self::$types_with_presets = [];

			$root = wp_normalize_path( WCF_ADDONS_PATH . 'inc/AtomicWidgets/Presets' );
			if ( is_dir( $root ) ) {
				foreach ( glob( $root . '/*/*.json' ) as $file ) {
					self::$types_with_presets[ basename( dirname( $file ) ) ] = true;
				}
			}
		}

		return isset( self::$types_with_presets[ $type ] );
	}
}
