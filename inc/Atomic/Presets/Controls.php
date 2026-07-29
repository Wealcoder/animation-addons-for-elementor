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
 * The section is injected unconditionally for every non-AAE element type —
 * presets can now come from the remote preset server (see Atomic\Presets\Rest
 * + Cache + Remote_Client) as well as bundled local JSON, so a static
 * per-request local-folder glob can no longer decide whether a type "has"
 * presets. The shared React control (element-controls/PresetPickerControl.jsx)
 * fetches per-type on demand from this plugin's own REST proxy and renders
 * nothing when the resolved (remote + local merged) list is empty.
 *
 * AAE's own widgets (e-aae-a-*) are skipped here — they place the section
 * themselves in define_atomic_controls().
 */
final class Controls {

	const TD = 'animation-addons-for-elementor';

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

		// The section is now always injected (no local-only glob gate): the
		// React control fetches per-type from the remote-preset proxy route
		// on demand and renders nothing when the resolved list is empty, so
		// a type with remote-only presets (no local .json bundled at all)
		// still gets its "Presets" section instead of silently having none.
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
}
