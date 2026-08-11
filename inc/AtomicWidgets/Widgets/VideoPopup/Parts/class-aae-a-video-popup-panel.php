<?php
/**
 * AAE Video Popup — Panel. The popup box: owns every video source/
 * playback/poster/controls setting and renders the same
 * `data-aae-video-*` attribute contract AAE Video's own wrapper does, so
 * video-popup.js's engine (adapters, controls wiring) can bind to it with
 * the same logic AAE Video's video.js already uses — just under entirely
 * renamed hook classes (`aae-a-video-popup-source`, not `aae-a-video`) and
 * a separate script, so the two widgets' document-level delegated click
 * listeners can never collide on the same page.
 *
 * Deliberately duplicates AAE_A_Video's source-prop schema and poster-
 * resolution logic rather than sharing a class with it — and this whole
 * family (Panel/Player/PlayBtn) deliberately never reuses AAE Video's own
 * Parts (AAE_A_Video_Player / AAE_A_Video_PlayBtn) even though those two
 * classes carry no props of their own. Reusing them would require adding
 * this Panel as a SECOND parent in `WIDGET_PARENT_MAP` (class-atomic.php),
 * which only maps one parent per part today; disabling the AAE Video widget
 * on a site that still uses Video Popup would then risk deregistering the
 * Popup's own player. Two small, self-contained copies keep both widget
 * families independently toggleable.
 *
 * Position/size at rest come from THIS class's own base style (a plain
 * box); video-popup.js applies `position: fixed` + placement inline at
 * teleport time, exactly like AAE_A_Offcanvas_Panel.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

require_once __DIR__ . '/class-aae-a-video-popup-close.php';
require_once __DIR__ . '/class-aae-a-video-popup-playbtn.php';
require_once __DIR__ . '/class-aae-a-video-popup-player.php';

use WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup\AAE_A_Video_Popup_Close;
use WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup\AAE_A_Video_Popup_PlayBtn;
use WCF_ADDONS\AtomicWidgets\Widgets\VideoPopup\AAE_A_Video_Popup_Player;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Video_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Image_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Video_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Video_Popup_Panel extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-video-popup-panel';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-video-popup-panel';
	}

	public function get_title() {
		return esc_html__( 'Video Popup Panel', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		$when = function ( string $value ) {
			return Dependency_Manager::make()
				->where( [
					'operator' => 'in',
					'path'     => [ 'video_type' ],
					'value'    => [ $value ],
					'effect'   => 'hide',
				] )
				->get();
		};

		$not_hosted = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'video_type' ],
				'value'    => [ 'hosted' ],
				'effect'   => 'hide',
			] )
			->get();

		$controls_on = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'controls_enabled' ],
				'value'    => true,
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'video_type' => String_Prop_Type::make()
				->enum( [ 'youtube', 'hosted', 'vimeo', 'dailymotion', 'videopress' ] )
				->default( 'youtube' ),

			'video_youtube_url' => String_Prop_Type::make()
				->default( 'https://www.youtube.com/watch?v=XHOmBV4js_E' )
				->set_dependencies( $when( 'youtube' ) ),

			'video_hosted' => Video_Src_Prop_Type::make()
				->set_dependencies( $when( 'hosted' ) ),

			'video_vimeo_url' => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $when( 'vimeo' ) ),

			'video_dailymotion_url' => String_Prop_Type::make()
				->default( 'https://www.dailymotion.com/video/xauwnn6' )
				->set_dependencies( $when( 'dailymotion' ) ),

			'video_videopress_url' => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $when( 'videopress' ) ),

			// Autoplay defaults ON here (unlike AAE Video) — opening the
			// popup already IS the "play" gesture, matching the V3
			// reference button's own `autoplay=1` iframe param.
			'autoplay' => Boolean_Prop_Type::make()->default( true ),
			'mute'     => Boolean_Prop_Type::make()->default( false ),
			'loop'     => Boolean_Prop_Type::make()->default( false ),

			'preload' => String_Prop_Type::make()
				->enum( [ 'auto', 'metadata', 'none' ] )
				->default( 'metadata' )
				->set_dependencies( $when( 'hosted' ) ),

			'lazyload' => Boolean_Prop_Type::make()->default( false )->set_dependencies( $not_hosted ),

			'youtube_privacy' => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'youtube' ) ),
			'vimeo_dnt'       => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'vimeo' ) ),

			'poster_enabled'    => Boolean_Prop_Type::make()->default( true ),
			'poster_auto_fetch' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $not_hosted ),
			'poster_image' => Image_Prop_Type::make()
				->default_size( 'large' )
				->default_url( \Elementor\Utils::get_placeholder_image_src() ),
			'resolved_poster_url' => String_Prop_Type::make()->default( '' ),

			'controls_enabled'  => Boolean_Prop_Type::make()->default( true ),
			'controls_autohide' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $controls_on ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'source' )
				->set_label( __( 'Video Source', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'video_type' )
						->set_label( __( 'Source', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'youtube',     'label' => __( 'YouTube', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'hosted',      'label' => __( 'Hosted Video', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'vimeo',       'label' => __( 'Vimeo', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'dailymotion', 'label' => __( 'Dailymotion', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'videopress',  'label' => __( 'VideoPress', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'video_youtube_url' )
						->set_label( __( 'YouTube URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),

					Video_Control::bind_to( 'video_hosted' )
						->set_label( __( 'Video', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'video_vimeo_url' )
						->set_label( __( 'Vimeo URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'video_dailymotion_url' )
						->set_label( __( 'Dailymotion URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'video_videopress_url' )
						->set_label( __( 'VideoPress URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'playback' )
				->set_label( __( 'Playback', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'autoplay' )
						->set_label( __( 'Autoplay on Open', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'mute' )
						->set_label( __( 'Mute', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'loop' )
						->set_label( __( 'Loop', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'preload' )
						->set_label( __( 'Preload', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'auto',     'label' => __( 'Auto', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'metadata', 'label' => __( 'Metadata', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'none',     'label' => __( 'None (Lazy Load)', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'lazyload' )
						->set_label( __( 'Lazy Load Player', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'youtube_privacy' )
						->set_label( __( 'Privacy-Enhanced Mode', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vimeo_dnt' )
						->set_label( __( 'Do Not Track', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'controls' )
				->set_label( __( 'Player Controls', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'controls_enabled' )
						->set_label( __( 'Show Controls Bar', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'controls_autohide' )
						->set_label( __( 'Auto-hide While Playing', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'poster' )
				->set_label( __( 'Poster & Play Button', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'poster_enabled' )
						->set_label( __( 'Use Thumbnail', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'poster_auto_fetch' )
						->set_label( __( 'Auto-fetch Thumbnail', 'animation-addons-for-elementor' ) ),

					Image_Control::bind_to( 'poster_image' )
						->set_label( __( 'Thumbnail', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	/**
	 * At-rest box look: fixed-size dark box matching the V3 reference's own
	 * `.wcf--popup-video` ~900×520 default. `position` starts `relative`;
	 * video-popup.js switches it to `fixed` + centers it at teleport time
	 * (same split AAE_A_Offcanvas_Panel uses between its own base style and
	 * offcanvas.js's runtime geometry).
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'position'      => String_Prop_Type::generate( 'relative' ),
						'display'       => String_Prop_Type::generate( 'block' ),
						'overflow'      => String_Prop_Type::generate( 'hidden' ),
						'width'         => Size_Prop_Type::generate( [ 'size' => 900, 'unit' => 'px' ] ),
						'max-width'     => Size_Prop_Type::generate( [ 'size' => 92, 'unit' => 'vw' ] ),
						'height'        => Size_Prop_Type::generate( [ 'size' => 520, 'unit' => 'px' ] ),
						'max-height'    => Size_Prop_Type::generate( [ 'size' => 90, 'unit' => 'vh' ] ),
						'border-radius' => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
						'background'    => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( '#000000' ),
						] ),
					] )
				),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Video_Popup_Close::generate()
				->is_locked( true )
				->editor_settings( [ 'title' => 'Close' ] )
				->build(),

			AAE_A_Video_Popup_Player::generate()->build(),

			AAE_A_Video_Popup_PlayBtn::generate()->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-video-popup-close', 'e-aae-a-video-popup-player', 'e-aae-a-video-popup-playbtn' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-popup-panel' => __DIR__ . '/aae-a-video-popup-panel.html.twig',
		];
	}

	/**
	 * Resolve the auto-fetched poster thumbnail server-side, same mechanism
	 * as AAE_A_Video::get_atomic_settings() — see this class's own docblock
	 * for why the logic is duplicated rather than shared.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['resolved_poster_url'] = $this->resolve_poster_url( $settings );

		return $settings;
	}

	private function resolve_poster_url( array $settings ): string {
		if ( empty( $settings['poster_enabled'] ) || empty( $settings['poster_auto_fetch'] ) ) {
			return '';
		}

		switch ( $settings['video_type'] ?? 'youtube' ) {
			case 'youtube':
				$id = self::extract_youtube_id( $settings['video_youtube_url'] ?? '' );

				return $id ? "https://img.youtube.com/vi/{$id}/maxresdefault.jpg" : '';

			case 'vimeo':
				return self::fetch_oembed_thumbnail(
					'vimeo_' . md5( $settings['video_vimeo_url'] ?? '' ),
					'https://vimeo.com/api/oembed.json?url=' . rawurlencode( $settings['video_vimeo_url'] ?? '' )
				);

			case 'dailymotion':
				return self::fetch_oembed_thumbnail(
					'dm_' . md5( $settings['video_dailymotion_url'] ?? '' ),
					'https://www.dailymotion.com/services/oembed?url=' . rawurlencode( $settings['video_dailymotion_url'] ?? '' )
				);

			case 'videopress':
				return self::fetch_oembed_thumbnail(
					'vp_' . md5( $settings['video_videopress_url'] ?? '' ),
					'https://public-api.wordpress.com/oembed/?format=json&url=' . rawurlencode( $settings['video_videopress_url'] ?? '' )
				);

			default:
				return '';
		}
	}

	/**
	 * Must stay in sync with the identical regex in video-popup.js's own
	 * getYoutubeIdFromUrl().
	 */
	public static function extract_youtube_id( string $url ): ?string {
		if ( empty( $url ) ) {
			return null;
		}

		$regex = '#^(?:https?://)?(?:www\.)?(?:m\.)?(?:youtu\.be/|youtube\.com/(?:(?:watch)?\?(?:.*&)?vi?=|(?:embed|v|vi|user|shorts)/))([^?&"\'>]+)#i';

		if ( preg_match( $regex, $url, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Own cache-key prefix (`aae_video_popup_thumb_*`) — deliberately
	 * distinct from AAE Video's `aae_video_thumb_*` transients, even though
	 * both could theoretically cache the same URL's thumbnail, so the two
	 * widgets' caches can never collide or be confused while debugging.
	 */
	private static function fetch_oembed_thumbnail( string $cache_key_part, string $oembed_url ): string {
		$key    = 'aae_video_popup_thumb_' . $cache_key_part;
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $oembed_url, [ 'timeout' => 3 ] );

		$thumb = '';

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body  = json_decode( wp_remote_retrieve_body( $response ), true );
			$thumb = esc_url_raw( $body['thumbnail_url'] ?? '' );
		}

		set_transient( $key, $thumb, $thumb ? DAY_IN_SECONDS : HOUR_IN_SECONDS );

		return $thumb;
	}
}
