<?php
namespace WCF_ADDONS\Atomic\NestedSlider;

use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {

	const SLIDER_SECTION_ANCHOR = 'aae_slider_section_anchor';
	const NS_EFFECT = 'aae_ns_effect';
	const NS_SLIDES_PER_VIEW = 'aae_ns_slides_per_view';
	const NS_PEEK = 'aae_ns_peek';
	const NS_CF_ROTATE = 'aae_ns_cf_rotate';
	const NS_CF_DEPTH = 'aae_ns_cf_depth';
	const NS_GAP = 'aae_ns_gap';
	const NS_SLIDE_HEIGHT = 'aae_ns_slide_height';
	const NS_EASING = 'aae_ns_easing';
	const NS_CENTER_MODE = 'aae_ns_center_mode';
	const NS_CENTER_SCALE = 'aae_ns_center_scale';
	const NS_ENABLE_3D = 'aae_ns_enable_3d';
	const NS_PERSPECTIVE = 'aae_ns_perspective';
	const NS_AUTOPLAY = 'aae_ns_autoplay';
	const NS_AUTOPLAY_SPEED = 'aae_ns_autoplay_speed';
	const NS_AUTOPLAY_DELAY = 'aae_ns_autoplay_delay';
	const NS_AUTOPLAY_DIRECTION = 'aae_ns_autoplay_direction';
	const NS_TRANSITION_SPEED = 'aae_ns_transition_speed';
	const NS_LOOP = 'aae_ns_loop';
	const NS_PAUSE_ON_HOVER = 'aae_ns_pause_on_hover';
	const NS_CARD_SCALE = 'aae_ns_card_scale';
	const NS_CARD_TILT = 'aae_ns_card_tilt';
	const NS_PERSPECTIVE_ROTATE = 'aae_ns_perspective_rotate';
	const NS_PERSPECTIVE_DEPTH = 'aae_ns_perspective_depth';
	const NS_PERSPECTIVE_OFFSET = 'aae_ns_perspective_offset';

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		if ( ! class_exists( Responsive_JSON_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::SLIDER_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		$schema[ self::NS_EFFECT ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 'slide' ]);
		$schema[ self::NS_SLIDES_PER_VIEW ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 3 ]);
		$schema[ self::NS_PEEK ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 8 ]);
		$schema[ self::NS_CF_ROTATE ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 45 ]);
		$schema[ self::NS_CF_DEPTH ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 100 ]);
		$schema[ self::NS_GAP ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 0 ]);
		$schema[ self::NS_SLIDE_HEIGHT ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 0 ]);
		$schema[ self::NS_EASING ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 'power2' ]);
		$schema[ self::NS_CENTER_MODE ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => false ]);
		$schema[ self::NS_CENTER_SCALE ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 0.85 ]);
		$schema[ self::NS_ENABLE_3D ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => false ]);
		$schema[ self::NS_PERSPECTIVE ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 1200 ]);
		$schema[ self::NS_AUTOPLAY ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => false ]);
		$schema[ self::NS_AUTOPLAY_SPEED ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 3000 ]);
		$schema[ self::NS_AUTOPLAY_DELAY ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 0 ]);
		$schema[ self::NS_AUTOPLAY_DIRECTION ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 'right' ]);
		$schema[ self::NS_TRANSITION_SPEED ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 680 ]);
		$schema[ self::NS_LOOP ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => false ]);
		$schema[ self::NS_PAUSE_ON_HOVER ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => true ]);
		$schema[ self::NS_CARD_SCALE ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 0.88 ]);
		$schema[ self::NS_CARD_TILT ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 20 ]);
		$schema[ self::NS_PERSPECTIVE_ROTATE ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 50 ]);
		$schema[ self::NS_PERSPECTIVE_DEPTH ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 250 ]);
		$schema[ self::NS_PERSPECTIVE_OFFSET ] = Responsive_JSON_Prop_Type::make()->default([ 'desktop' => 18 ]);

		return $schema;
	}
}
