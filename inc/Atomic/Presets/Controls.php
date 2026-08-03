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
 * The section is only injected for native types explicitly listed in
 * ALLOWED_NATIVE_TYPES below. It used to be injected unconditionally for
 * every non-AAE element type (relying on the remote preset server to decide,
 * per element type, whether any presets existed) — that surfaced the
 * "Presets" section on widgets that were never meant to have it (e.g.
 * e-paragraph, e-image), because the remote catalog had entries for types no
 * one had actually opted into here. An explicit whitelist is the fix: add a
 * native type here only when you deliberately want it to offer presets.
 *
 * AAE's own widgets (e-aae-a-*) are always skipped here — they place their
 * own Presets section themselves in define_atomic_controls() (see
 * class-aae-a-btn.php, class-aae-a-btn-pro.php, class-aae-a-social-share.php,
 * etc.), so this whitelist never needs to (and must not) list an e-aae-a-*
 * type — doing so would add a second, duplicate "aae_presets" section
 * alongside the one the widget already builds itself.
 */
final class Controls {

	const TD = 'animation-addons-for-elementor';

	// Native (non-AAE) element types allowed to show the Presets section.
	//
	// Empty — so this class currently injects nothing at all. Re-enabling a
	// type takes TWO steps, and one without the other is silent:
	//   1. add the type here, and
	//   2. give it presets — either a remote entry for that element_type, or
	//      a folder of JSONs at inc/AtomicWidgets/Presets/<element-type>/.
	// Whitelisted with no presets = an empty "Presets" section on the widget;
	// presets with no whitelist entry = files nothing can ever reach, which is
	// what the five bundled samples had become before they were deleted.
	const ALLOWED_NATIVE_TYPES = [];

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

		if ( ! in_array( $type, self::ALLOWED_NATIVE_TYPES, true ) ) {
			return $controls;
		}

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
