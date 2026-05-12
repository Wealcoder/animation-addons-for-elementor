<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Controls {

	const TD = 'animation-addons-for-elementor';

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_controls' ], 10, 2 );
	}

	public function inject_controls( array $controls, $element ) {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return $controls;
		}

		if ( ! class_exists( Section::class ) || ! class_exists( Select_Control::class ) ) {
			return $controls;
		}

		$type = $element->get_element_type();

		if ( in_array( $type, Bootstrap::target_element_types(), true ) ) {
			$controls[] = $this->build_animation_section();
			$controls[] = $this->build_parallax_section();
		}

		return $controls;
	}

	/* ---------- Animation section ---------- */

	private function build_animation_section(): Section {
		$effect_opts     = $this->options_from_map( Schema::effects() );
		$method_opts     = $this->options_from_map( Schema::methods() );
		$trigger_opts    = $this->options_from_map( Schema::triggers() );
		$wrapper_opts    = [
			[ 'value' => 'default', 'label' => __( 'Default', self::TD ) ],
			[ 'value' => 'custom',  'label' => __( 'Custom',  self::TD ) ],
		];
		$scroll_pos_opts = array_map(
			static fn( $v ) => [ 'value' => $v, 'label' => ucwords( str_replace( '_', ' ', $v ) ) ],
			Schema::scroll_positions()
		);
		$fade_dir_opts   = $this->options_from_map( Schema::fade_directions() );
		$rot_dir_opts    = [
			[ 'value' => 'x', 'label' => 'X' ],
			[ 'value' => 'y', 'label' => 'Y' ],
		];
		$ease_opts       = $this->options_from_map( Schema::eases() );

		return Section::make()
			->set_label( __( 'Animation', self::TD ) )
			->set_items( [

				/* Animation effect — responsive */
				...$this->responsive_rows( Schema::ANIM_EFFECT, __( 'Animation', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $effect_opts ) ),

				/* Method (From / To) — responsive */
				...$this->responsive_rows( Schema::ANIM_METHOD, __( 'Method', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $method_opts ) ),

				/* Trigger — responsive */
				...$this->responsive_rows( Schema::ANIM_TRIGGER, __( 'Trigger', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $trigger_opts ) ),

				/* Trigger Selector — responsive */
				...$this->responsive_rows( Schema::ANIM_TRIGGER_SELECTOR, __( 'Trigger Selector', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.my-class' ) ),

				/* Wrapper picker (default / custom) — responsive */
				...$this->responsive_rows( Schema::ANIM_WRAPPER, __( 'Wrapper', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $wrapper_opts ) ),

				/* Scroll trigger custom block — only when wrapper=custom + scroll trigger */
				...$this->responsive_rows( Schema::ANIM_START_TRIGGER, __( 'Start Trigger', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.start_area' ) ),

				...$this->responsive_rows( Schema::ANIM_END_TRIGGER, __( 'End Trigger', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( '.end_area' ) ),

				...$this->responsive_rows( Schema::ANIM_START_POSITION, __( 'Start', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $scroll_pos_opts ) ),

				...$this->responsive_rows( Schema::ANIM_START_CUSTOM, __( 'Custom Start', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'top top+=100' ) ),

				...$this->responsive_rows( Schema::ANIM_END_POSITION, __( 'End', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $scroll_pos_opts ) ),

				...$this->responsive_rows( Schema::ANIM_END_CUSTOM, __( 'Custom End', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'bottom top+=100' ) ),

				Switch_Control::bind_to( Schema::ANIM_MARKERS )
					->set_label( __( 'Markers', self::TD ) ),

				/* Shared numeric controls */
				...$this->responsive_number( Schema::ANIM_DELAY,    __( 'Delay',    self::TD ) ),
				...$this->responsive_number( Schema::ANIM_DURATION, __( 'Duration', self::TD ) ),

				/* Easing — responsive */
				...$this->responsive_rows( Schema::ANIM_EASING, __( 'Ease', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $ease_opts ) ),

				/* Fade-specific */
				...$this->responsive_rows( Schema::ANIM_FADE_FROM, __( 'Fade From', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $fade_dir_opts ) ),

				...$this->responsive_number( Schema::ANIM_FADE_OFFSET, __( 'Fade Offset', self::TD ) ),
				...$this->responsive_number( Schema::ANIM_SCALE,       __( 'Start Scale', self::TD ) ),

				/* 3D Move specific */
				...$this->responsive_rows( Schema::ANIM_ROTATION_DIR, __( 'Rotation Direction', self::TD ),
					fn( $p, $l ) => Select_Control::bind_to( $p )->set_label( $l )->set_options( $rot_dir_opts ) ),

				...$this->responsive_number( Schema::ANIM_ROTATION, __( 'Rotation Value', self::TD ) ),

				...$this->responsive_rows( Schema::ANIM_TRANSFORM_ORIGIN, __( 'Transform Origin', self::TD ),
					fn( $p, $l ) => Text_Control::bind_to( $p )->set_label( $l )->set_placeholder( 'top center -50' ) ),

				/* Custom Properties (effect=custom only) — React-driven single repeater.
				 *
				 * v3 had a SINGLE 2-column repeater: property (SELECT) + value (TEXT).
				 * Atomic Repeatable_Control supports only ONE child control type per row,
				 * so this row is a Switch_Control placeholder; editor-bridge/custom-props.jsx
				 * finds it by label and morphs its editable area into a full property+value
				 * repeater (Select + TextInput per row) using the React toolkit in
				 * src/modules/atomic/ui-builder.jsx.
				 *
				 * The placeholder switch is harmless if the bridge JS isn't loaded — its
				 * value is never read by Render.php; only the two data props (KEYS + VALUES)
				 * matter for output. */
				Switch_Control::bind_to( Schema::ANIM_CUSTOM_PROPS_TRIGGER )
					->set_label( __( 'Custom Properties', self::TD ) )
					->set_description( __( 'GSAP property + value pairs applied when Animation is set to Custom.', self::TD ) ),

				/* Editor + Play button */
				Switch_Control::bind_to( Schema::ANIM_ENABLE_EDITOR )
					->set_label( __( 'Enable On Editor', self::TD ) )
					->set_description( __( 'For better performance in editor mode, keep the setting turned off.', self::TD ) ),

				// Marker control — replaced in the panel by a "Play Now" button via editor-bridge.js.
				Switch_Control::bind_to( Schema::ANIM_PLAY_TOKEN )
					->set_label( __( 'Play Animation', self::TD ) )
					->set_meta( [ 'aaePlayButton' => true ] ),
			] );
	}

	/* ---------- Parallax / Scroll Smoother section ---------- */

	private function build_parallax_section(): Section {
		return Section::make()
			->set_label( __( 'Parallax Effect', self::TD ) )
			->set_items( [
				Switch_Control::bind_to( Schema::PARALLAX_ENABLE )
					->set_label( __( 'Enable Scroll Smoother', self::TD ) )
					->set_description( __( 'If you want to use scroll smooth, please enable global settings first.', self::TD ) ),

				Number_Control::bind_to( Schema::PARALLAX_SPEED )
					->set_label( __( 'Speed', self::TD ) )
					->set_should_force_int( false ),

				Number_Control::bind_to( Schema::PARALLAX_LAG )
					->set_label( __( 'Lag', self::TD ) )
					->set_should_force_int( false ),
			] );
	}

	/* ---------- responsive helpers (mirror TextAnimation\Controls) ---------- */

	/**
	 * Builds an array of Number_Controls for one responsive setting:
	 * one row for desktop + one row per active extra breakpoint.
	 */
	private function responsive_number( string $base, string $label ): array {
		return $this->responsive_rows( $base, $label, function ( $prop, $row_label ) {
			return Number_Control::bind_to( $prop )
				->set_label( $row_label )
				->set_should_force_int( false );
		} );
	}

	/**
	 * Generic responsive-rows builder. Given a control factory $make($prop, $label),
	 * returns rows for desktop + each active extra breakpoint.
	 */
	private function responsive_rows( string $base, string $label, callable $make ): array {
		$rows = [ $make( $base, $label ) ];

		foreach ( Schema::get_extra_breakpoints() as $bp ) {
			$bp_label = Schema::BREAKPOINT_LABELS[ $bp ] ?? ucwords( str_replace( '_', ' ', $bp ) );
			$prop     = Schema::breakpoint_prop( $base, $bp );
			$rows[]   = $make( $prop, $label . ' (' . $bp_label . ')' );
		}

		return $rows;
	}

	private function options_from_map( array $map ): array {
		$out = [];
		foreach ( $map as $value => $label ) {
			$out[] = [ 'value' => $value, 'label' => $label ];
		}
		return $out;
	}
}
