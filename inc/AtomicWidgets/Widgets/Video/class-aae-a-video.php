<?php
/**
 * AAE Video — atomic container WIDGET.
 *
 * A single video widget for every source — YouTube, Vimeo, a hosted URL, or
 * a Media Library upload — built entirely on our own engine (Parts\
 * AAE_A_Video_Player, see that class) so the same custom controls bar
 * (play/pause, seek, time, mute, fullscreen) works identically regardless of
 * source, with no dependence on Elementor's own e-youtube/e-self-hosted-video
 * widgets (whose internals aren't reachable from outside — verified directly
 * in youtube-handler.js, its YT.Player instance never leaves that closure).
 *
 * Mirrors the Progress Bar's "container + dedicated Parts widgets" pattern
 * (inc/AtomicWidgets/Widgets/Progressbar/Parts/): the play button is our own
 * Parts\AAE_A_Video_PlayBtn (see that class for why a native e-button
 * couldn't carry the click hook class), independently editable via its own
 * full Style/Content panel; the actual video engine is our own
 * Parts\AAE_A_Video_Player, a single mount point + controls bar that
 * assets/js/video.js drives via a small per-source adapter (native <video>,
 * YT.Player, or Vimeo.Player). The poster is plain `<img>` markup rendered
 * directly by THIS class's own twig from the `poster_image` SETTING (Poster
 * & Play Button panel section) — not a child element at all.
 *
 * This wrapper owns every source/playback SETTING (video_type and everything
 * per-type); the Player part owns none of them — it's a "dumb" rendering
 * surface, and video.js reads all config from THIS element's own
 * data-aae-video-* attributes (same "parent owns the value, JS applies it to
 * the children at runtime" pattern Progress Bar uses for pb_percentage).
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Video;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

require_once __DIR__ . '/Parts/class-aae-a-video-player.php';
require_once __DIR__ . '/Parts/class-aae-a-video-playbtn.php';

use WCF_ADDONS\AtomicWidgets\Widgets\Video\AAE_A_Video_Player;
use WCF_ADDONS\AtomicWidgets\Widgets\Video\AAE_A_Video_PlayBtn;

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

class AAE_A_Video extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-video';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-video';
	}

	public function get_title() {
		return esc_html__( 'AAE Video', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function get_keywords() {
		return [ 'video', 'youtube', 'vimeo', 'player', 'media', 'atomic' ];
	}

	public function get_categories(): array {
		return [ 'aae-atomic-general' ];
	}

	/**
	 * Atomic_Element_Base reads the panel category from HERE, not
	 * get_categories() (Widget_Base's own hook, never called for an element
	 * type) — see AAE_A_Btn's identical note.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		// True exactly when video_type equals $value — verified against
		// Elementor's own evaluateTerm()/isDependencyMet() (editor-props.js):
		// a term describes the state in which the control STAYS VISIBLE;
		// 'effect' => 'hide' fires precisely when the term evaluates false.
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

		// True when video_type is anything other than 'hosted' or 'external'
		// — both play through the same native <video> element, so neither
		// wants the embed-only Lazy Load Player switch.
		$not_hosted = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'video_type' ],
				'value'    => [ 'hosted', 'external' ],
				'effect'   => 'hide',
			] )
			->get();

		// True when video_type is 'hosted' OR 'external' — used for the
		// Preload control, which only makes sense for a real <video> tag.
		$hosted_or_external = Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'video_type' ],
				'value'    => [ 'hosted', 'external' ],
				'effect'   => 'hide',
			] )
			->get();

		// True when controls_enabled is on.
		$controls_on = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'controls_enabled' ],
				'value'    => true,
				'effect'   => 'hide',
			] )
			->get();

		// True when poster_enabled ("Use Thumbnail") is on — everything else
		// in the Poster & Play Button section only means something once the
		// poster itself is switched on.
		$poster_on = Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ 'poster_enabled' ],
				'value'    => true,
				'effect'   => 'hide',
			] )
			->get();

		// Auto-fetch Thumbnail additionally needs poster_enabled on AND the
		// source to not be 'hosted' — RELATION_AND means the control stays
		// visible only while BOTH terms hold, i.e. it hides the moment either
		// one fails.
		$poster_auto_fetch_deps = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'eq',
				'path'     => [ 'poster_enabled' ],
				'value'    => true,
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'nin',
				'path'     => [ 'video_type' ],
				'value'    => [ 'hosted' ],
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Source. Extend the enum + a matching branch in this class'
			// resolve_poster_url()/get_atomic_settings() and video.js's
			// ADAPTERS map to add another provider.
			'video_type' => String_Prop_Type::make()
				->enum( [ 'youtube', 'hosted', 'external', 'vimeo', 'dailymotion', 'videopress' ] )
				->default( 'youtube' ),

			'video_youtube_url' => String_Prop_Type::make()
				->default( 'https://www.youtube.com/watch?v=XHOmBV4js_E' )
				->set_dependencies( $when( 'youtube' ) ),

			// Video_Control gives one field for BOTH "choose from Media
			// Library" and "paste a URL" (id XOR url), same as Elementor's
			// own e-self-hosted-video — no need for two separate props.
			'video_hosted' => Video_Src_Prop_Type::make()
				->set_dependencies( $when( 'hosted' ) ),

			// Plain-text URL to a video file hosted on another site/CDN —
			// same native <video> playback as 'hosted' (see video.js's
			// createNativeAdapter and its cfg.type branch), just a bare
			// Text_Control instead of Video_Src_Prop_Type's Media-Library
			// picker, since there is nothing in this site's Library to pick.
			'video_external_url' => String_Prop_Type::make()
				->default( 'https://crowdytheme.com/assets/wp-content/uploads/2024/06/arolux-branding-agency-video.mp4' )
				->set_dependencies( $when( 'external' ) ),

			'video_vimeo_url' => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $when( 'vimeo' ) ),

			'video_dailymotion_url' => String_Prop_Type::make()
				->default( 'https://www.dailymotion.com/video/xauwnn6' )
				->set_dependencies( $when( 'dailymotion' ) ),

			'video_videopress_url' => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $when( 'videopress' ) ),

			// Playback — shared across all three sources.
			'autoplay' => Boolean_Prop_Type::make()->default( false ),
			'mute'     => Boolean_Prop_Type::make()->default( false ),
			'loop'     => Boolean_Prop_Type::make()->default( false ),

			'preload' => String_Prop_Type::make()
				->enum( [ 'auto', 'metadata', 'none' ] )
				->default( 'metadata' )
				->set_dependencies( $hosted_or_external ),

			'lazyload' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $not_hosted ),

			'youtube_privacy' => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'youtube' ) ),
			'vimeo_dnt'       => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'vimeo' ) ),

			// Poster/play-button overlay. Master on/off switch — when off,
			// aae-a-video.html.twig skips the poster entirely (no
			// placeholder, no auto-fetched thumbnail, nothing behind the
			// play button). Auto-fetch has a real API to hit for YouTube
			// (deterministic thumbnail URL) and Vimeo/Dailymotion/VideoPress
			// (oEmbed); for 'external' there is no such API, so it falls back
			// to the video file's own first frame instead (see the twig's
			// `use_frame_poster` + video.js's primeFramePoster()) — only a
			// Media Library upload ('hosted') has genuinely nothing to fetch.
			'poster_enabled'    => Boolean_Prop_Type::make()->default( true ),
			'poster_auto_fetch' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $poster_auto_fetch_deps ),
			// User-picked fallback/override — wins whenever auto-fetch is off
			// or comes up empty (private video, failed oEmbed lookup, hosted
			// source). Rendered straight from THIS wrapper's own twig now
			// (aae-a-video.html.twig), not a separate e-image child — see
			// define_default_children()'s docblock for why.
			'poster_image' => Image_Prop_Type::make()
				->default_size( 'large' )
				->default_url( \Elementor\Utils::get_placeholder_image_src() )
				->set_dependencies( $poster_on ),
			// Computed server-side in get_atomic_settings() — no panel control.
			'resolved_poster_url' => String_Prop_Type::make()->default( '' ),

			// Custom controls bar.
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
							[ 'value' => 'external',    'label' => __( 'External URL', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'vimeo',       'label' => __( 'Vimeo', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'dailymotion', 'label' => __( 'Dailymotion', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'videopress',  'label' => __( 'VideoPress', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'video_youtube_url' )
						->set_label( __( 'YouTube URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),

					Video_Control::bind_to( 'video_hosted' )
						->set_label( __( 'Video', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'video_external_url' )
						->set_label( __( 'External Video URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Paste a direct video file URL (mp4, webm, ogg)', 'animation-addons-for-elementor' ) ),

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
						->set_label( __( 'Autoplay', 'animation-addons-for-elementor' ) ),

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
	 * Structural styles for THIS element only. The play button carries its
	 * own full Style-tab panel; the Player part carries its own minimal one;
	 * the poster is a plain setting with no Style tab of its own (see
	 * define_default_children()'s docblock). Every key here is verified
	 * against atomic-style-schema-reference.md — one invalid key silently
	 * voids this whole method.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'    => String_Prop_Type::generate( 'block' ),
						'position'   => String_Prop_Type::generate( 'relative' ),
						'overflow'   => String_Prop_Type::generate( 'hidden' ),
						'width'      => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						'background' => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( '#000000' ),
						] ),
					] )
				),
		];
	}

	/**
	 * Fixed structural parts: our own universal video Player
	 * (Parts\AAE_A_Video_Player) and our own play-button trigger
	 * (Parts\AAE_A_Video_PlayBtn — mirrors AAE_A_Btn's icon+label pattern).
	 *
	 * The poster used to be a THIRD default child here (a reused native
	 * e-image, its `aae-a-video-poster` hook class seeded through the
	 * `classes` prop since a reused native widget has no twig of ours to
	 * hardcode a class into — same "Some classes are missing" exposure
	 * AAE_A_Video_PlayBtn's own docblock explains for the button). It's now
	 * the `poster_image` SETTING instead (see define_props_schema() and the
	 * "Poster & Play Button" section below), rendered as plain `<img
	 * class="aae-a-video-poster">` markup directly in THIS class's own twig
	 * (aae-a-video.html.twig) — hook class hardcoded there, same as the
	 * Player/PlayBtn parts, so it can never be flagged or stripped either.
	 */
	protected function define_default_children() {
		return [
			AAE_A_Video_Player::generate()->build(),

			AAE_A_Video_PlayBtn::generate()->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-video-player', 'e-aae-a-video-playbtn' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video' => __DIR__ . '/aae-a-video.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-video-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-video-css' ];
	}

	/**
	 * Resolve the auto-fetched poster thumbnail server-side, since Twig has
	 * no HTTP client and the Vimeo oEmbed lookup needs caching. Same
	 * computed-value hook AAE_A_Site_Logo uses — accepted trade-off: the
	 * editor's client-side twig re-render only refreshes this on an actual
	 * server round-trip, not on every keystroke while editing the video URL.
	 * aae-a-video.html.twig picks between this and the user's own
	 * `poster_image` setting at render time.
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

				// maxresdefault doesn't exist for every video, but this URL
				// is fully deterministic either way — no HTTP call needed here.
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
				// hosted/external: no thumbnail API for an arbitrary file.
				return '';
		}
	}

	/**
	 * Must stay in sync with the identical regex in assets/js/video.js's
	 * getYoutubeIdFromUrl() — both extract the same ID from the same URL
	 * shapes, one server-side (poster resolution), one client-side (player
	 * mount). Ported from Elementor core's own youtube-handler.js.
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
	 * Shared oEmbed thumbnail fetch for every provider with no deterministic
	 * thumbnail URL pattern (Vimeo, Dailymotion, VideoPress — unlike YouTube,
	 * where img.youtube.com/vi/<id>/... needs no HTTP call at all).
	 *
	 * @param string $cache_key_part Unique per URL+provider; caller salts it
	 *                               (e.g. 'vimeo_'.md5($url)) so the three
	 *                               providers can never collide in the cache.
	 * @param string $oembed_url     Full oEmbed request URL, already built.
	 */
	private static function fetch_oembed_thumbnail( string $cache_key_part, string $oembed_url ): string {
		$key    = 'aae_video_thumb_' . $cache_key_part;
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			// '' is itself a valid cached failure — avoid re-hitting the API
			// on every render of a private/unlisted/invalid video URL.
			return $cached;
		}

		$response = wp_remote_get( $oembed_url, [ 'timeout' => 3 ] );

		$thumb = '';

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body  = json_decode( wp_remote_retrieve_body( $response ), true );
			$thumb = esc_url_raw( $body['thumbnail_url'] ?? '' );
		}

		// Shorter TTL on failure so a fixed/newly-public video is picked up
		// again sooner without hammering the endpoint on every page view.
		set_transient( $key, $thumb, $thumb ? DAY_IN_SECONDS : HOUR_IN_SECONDS );

		return $thumb;
	}
}
