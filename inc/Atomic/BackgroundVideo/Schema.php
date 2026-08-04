<?php

namespace WCF_ADDONS\Atomic\BackgroundVideo;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background Video — props.
 *
 * v4 has no video option in the atomic Background style control (its overlay
 * union is colour / image / gradient only — see
 * Elementor\Modules\AtomicWidgets\PropTypes\Background_Overlay_Prop_Type), so
 * this extension adds one for the three container types.
 *
 * Modelled on v3's Group_Control_Background video fields, with two deliberate
 * differences:
 *
 *   - v3 had no enable switch: picking "Video" as the Background Type WAS the
 *     switch. There is no such tab here, so ENABLE exists.
 *   - v3 offered one Video Link text field. Here SOURCE splits it into a media
 *     picker (upload / pick an mp4) and a URL field, because "paste a link" and
 *     "choose a file" in one control is exactly the ambiguity that makes people
 *     paste an attachment page URL and wonder why nothing plays.
 *
 * Every field except ENABLE is responsive (Responsive_JSON_Prop_Type), so a
 * different clip — or none — can be set per breakpoint. ENABLE is a plain
 * Boolean: it is the on/off for the whole feature, and PLAY_ON_MOBILE already
 * covers the one per-device case v3 supported.
 */
final class Schema {

	const SECTION_ANCHOR = 'aae_bgv_section_anchor';

	const ENABLE = 'aae_bgv_enable';
	const SOURCE = 'aae_bgv_source';
	const FILE = 'aae_bgv_file';
	const LINK = 'aae_bgv_link';
	const POSTER = 'aae_bgv_poster';
	const PLAY_ONCE = 'aae_bgv_play_once';
	const PLAY_ON_MOBILE = 'aae_bgv_play_on_mobile';

	const SOURCE_FILE = 'file';
	const SOURCE_URL = 'url';

	/**
	 * Only the three container types can host a background video.
	 *
	 * Deliberately NOT Bootstrap::target_element_types() — that list is every
	 * animatable atomic widget (headings, images, buttons…), and a background
	 * layer behind a heading is meaningless. Mirrors the narrow list
	 * FlexboxChildHover keeps for the same reason.
	 */
	const TARGET_TYPES = [ 'e-flexbox', 'e-div-block', 'e-grid' ];

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		$schema[ self::SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );

		$schema[ self::ENABLE ] = Boolean_Prop_Type::make()->default( false );

		$schema[ self::SOURCE ] = Responsive_JSON_Prop_Type::make()->default( [
			'desktop' => self::SOURCE_FILE,
		] );

		// Media props store the whole attachment object the picker returns
		// ({ id, url, sizes, … }); the runtime only reads `url`.
		$schema[ self::FILE ] = Responsive_JSON_Prop_Type::make()->default( [
			'desktop' => null,
		] );

		$schema[ self::POSTER ] = Responsive_JSON_Prop_Type::make()->default( [
			'desktop' => null,
		] );

		$schema[ self::LINK ] = Responsive_JSON_Prop_Type::make()->default( [
			'desktop' => '',
		] );

		$schema[ self::PLAY_ONCE ] = Responsive_JSON_Prop_Type::make()->default( [
			'desktop' => false,
		] );

		$schema[ self::PLAY_ON_MOBILE ] = Responsive_JSON_Prop_Type::make()->default( [
			'desktop' => false,
		] );

		return $schema;
	}
}
