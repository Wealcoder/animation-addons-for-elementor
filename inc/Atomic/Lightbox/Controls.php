<?php

namespace WCF_ADDONS\Atomic\Lightbox;

use WCF_ADDONS\Atomic\Bootstrap;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightbox panel section.
 *
 * `build_section()` is the single definition of the section. It is used two ways:
 *   - auto-injected onto core elements (e-image) via inject_controls()
 *   - returned to custom AAE widgets by Lightbox_Manager::register_lightbox_controls()
 *
 * so both paths get an identical editing experience with one code path.
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

		if ( in_array( $type, Schema::lightbox_widgets(), true ) ) {
			$controls[] = $this->build_section();
		}

		if ( in_array( $type, Schema::lightbox_containers(), true ) ) {
			$controls[] = $this->build_container_section();
		}

		return $controls;
	}

	/**
	 * Container-level "Lightbox (Children)" section. Enabling it turns every
	 * eligible child image inside the container into a grouped trigger — no
	 * per-image configuration. Discovery + grouping happen client-side.
	 *
	 * @param array $args Optional overrides (e.g. [ 'label' => '…' ]).
	 */
	public function build_container_section( array $args = [] ): Section {
		$label = $args['label'] ?? __( 'Lightbox (Children)', self::TD );

		return Section::make()
			->set_id( 'aae_lightbox_container' )
			->set_label( Bootstrap::get_label( $label ) )
			->set_items( [
				Switch_Control::bind_to( Schema::LB_ENABLE )
					->set_label( __( 'Enable Lightbox', self::TD ) ),

				Select_Control::bind_to( Schema::LB_CONTAINER_MODE )
					->set_label( __( 'Content Mode', self::TD ) )
					->set_options( [
						// 'images'  — scan the container for image nodes.
						// 'content' — one slide per direct child; each child opens
						//             its image OR video (children with neither are
						//             skipped).
						[ 'value' => 'images',  'label' => __( 'Images (scan)', self::TD ) ],
						[ 'value' => 'content', 'label' => __( 'Per Child (Image / Video)', self::TD ) ],
					] ),

				Text_Control::bind_to( Schema::LB_GROUP )
					->set_label( __( 'Gallery / Group ID', self::TD ) )
					->set_placeholder( __( 'blank = auto (this container)', self::TD ) ),

				// Images mode → matches child images. Per-child mode → picks which
				// direct children qualify (blank = all direct children).
				Text_Control::bind_to( Schema::LB_CHILD_SELECTOR )
					->set_label( __( 'Child Selector', self::TD ) )
					->set_placeholder( __( 'blank = default', self::TD ) ),

				Select_Control::bind_to( Schema::LB_CAPTION_SRC )
					->set_label( __( 'Caption Source', self::TD ) )
					->set_options( [
						[ 'value' => 'none',    'label' => __( 'None', self::TD ) ],
						[ 'value' => 'alt',     'label' => __( 'Image Alt', self::TD ) ],
						[ 'value' => 'title',   'label' => __( 'Image Title', self::TD ) ],
						[ 'value' => 'caption', 'label' => __( 'Attachment Caption', self::TD ) ],
					] ),

				Select_Control::bind_to( Schema::LB_ANIM )
					->set_label( __( 'Open Animation', self::TD ) )
					->set_options( [
						[ 'value' => 'zoom',  'label' => __( 'Zoom', self::TD ) ],
						[ 'value' => 'fade',  'label' => __( 'Fade', self::TD ) ],
						[ 'value' => 'slide', 'label' => __( 'Slide', self::TD ) ],
					] ),

				Switch_Control::bind_to( Schema::LB_ZOOM )
					->set_label( __( 'Zoom Button', self::TD ) ),
				Switch_Control::bind_to( Schema::LB_COUNTER )
					->set_label( __( 'Counter', self::TD ) ),
				Switch_Control::bind_to( Schema::LB_LOOP )
					->set_label( __( 'Loop', self::TD ) ),
				Switch_Control::bind_to( Schema::LB_DOWNLOAD )
					->set_label( __( 'Download Button', self::TD ) ),
			] );
	}

	/**
	 * The shared "Lightbox" section. Native controls are used for Phase 1 so
	 * the section works without a React replacement; the anchor is kept so a
	 * richer responsive panel can be swapped in later exactly like the other
	 * effects (ImageHover, Tilt, …).
	 *
	 * @param array $args Reserved for per-widget overrides (label, defaults).
	 */
	public function build_section( array $args = [] ): Section {
		$label = $args['label'] ?? __( 'Lightbox', self::TD );

		return Section::make()
			->set_id( 'aae_lightbox' )
			->set_label( Bootstrap::get_label( $label ) )
			->set_items( [
				// NOTE: no Section_Anchor here. The anchor pattern is only for
				// effects that swap in a React ResponsiveSection replacement; the
				// lightbox uses native controls, and an unpaired custom-layout
				// anchor makes the atomic panel fail save validation.
				Switch_Control::bind_to( Schema::LB_ENABLE )
					->set_label( __( 'Enable Lightbox', self::TD ) ),

				Text_Control::bind_to( Schema::LB_GROUP )
					->set_label( __( 'Gallery / Group ID', self::TD ) )
					->set_placeholder( __( 'e.g. my-gallery (leave blank for single)', self::TD ) ),

				Text_Control::bind_to( Schema::LB_TITLE )
					->set_label( __( 'Title', self::TD ) ),

				Text_Control::bind_to( Schema::LB_CAPTION )
					->set_label( __( 'Caption', self::TD ) ),

				Select_Control::bind_to( Schema::LB_ANIM )
					->set_label( __( 'Open Animation', self::TD ) )
					->set_options( [
						[ 'value' => 'zoom', 'label' => __( 'Zoom', self::TD ) ],
						[ 'value' => 'fade', 'label' => __( 'Fade', self::TD ) ],
						[ 'value' => 'slide', 'label' => __( 'Slide', self::TD ) ],
					] ),
			] );
	}
}
