<?php
/**
 * AAE Video — atomic container WIDGET.
 *
 * A video card that delegates actual playback to Elementor's own native
 * atomic elements — e-image (poster), e-youtube, e-self-hosted-video, and
 * e-button (play trigger) — as real, independently editable child elements
 * (same "container + native/fixed children" pattern as Btn's
 * define_default_children()). This wrapper only adds what none of those
 * natively provide: the video-source switch, a unified poster/play-button
 * overlay with auto-thumbnail fallback, a custom hideable controls bar for
 * Hosted/Vimeo, and Vimeo support (which has no native Elementor widget).
 *
 * YouTube is intentionally left AS-IS, full-bleed, with none of our overlay
 * chrome: Elementor's e-youtube handler keeps its YT.Player instance in a
 * private closure (verified directly in youtube-handler.js — never exposed
 * on the DOM or window), so an external custom controls bar cannot drive it.
 * YouTube's own iframe already ships a thumbnail + play button + controls,
 * all editable directly on that child's own panel.
 *
 * Hosted video is a real <video> tag (data-e-type="e-self-hosted-video") and
 * Vimeo is our own mount + Vimeo Player SDK — both fully reachable, so our
 * poster overlay + custom controls bar (play/pause, seek, time, mute,
 * fullscreen) work for those two.
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

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Image\Atomic_Image;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Youtube\Atomic_Youtube;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Self_Hosted_Video\Atomic_Self_Hosted_Video;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Button\Atomic_Button;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
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

		// True when video_type is anything other than 'youtube'.
		$not_youtube = Dependency_Manager::make()
			->where( [
				'operator' => 'nin',
				'path'     => [ 'video_type' ],
				'value'    => [ 'youtube' ],
				'effect'   => 'hide',
			] )
			->get();

		// True when controls_enabled is on AND video_type isn't youtube.
		$controls_on_and_not_youtube = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where( [
				'operator' => 'eq',
				'path'     => [ 'controls_enabled' ],
				'value'    => true,
				'effect'   => 'hide',
			] )
			->where( [
				'operator' => 'nin',
				'path'     => [ 'video_type' ],
				'value'    => [ 'youtube' ],
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Which child element actually plays the video.
			'video_type' => String_Prop_Type::make()
				->enum( [ 'youtube', 'hosted', 'vimeo' ] )
				->default( 'youtube' ),

			// Vimeo has no native Elementor widget, so its source + playback
			// options stay on this wrapper and are handled by our own JS +
			// the Vimeo Player SDK. YouTube/Hosted are edited directly on
			// their own child element — no duplicate props needed here.
			'video_vimeo_url' => String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $when( 'vimeo' ) ),
			'vimeo_autoplay'  => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'vimeo' ) ),
			'vimeo_mute'      => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'vimeo' ) ),
			'vimeo_loop'      => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'vimeo' ) ),
			'vimeo_dnt'       => Boolean_Prop_Type::make()->default( false )->set_dependencies( $when( 'vimeo' ) ),

			// Poster/play-button overlay only applies to Hosted + Vimeo —
			// YouTube's own iframe already ships its own thumbnail + button.
			// Auto-fetch only has somewhere to fetch FROM for Vimeo (YouTube
			// isn't reachable here since it's not our prop anymore; Hosted
			// files have no thumbnail API at all).
			'poster_auto_fetch' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $when( 'vimeo' ) ),
			// Computed server-side in get_atomic_settings() — no panel control.
			'resolved_poster_url'  => String_Prop_Type::make()->default( '' ),
			'placeholder_image_url' => String_Prop_Type::make()->default( '' ),

			// Custom controls bar — Hosted (real <video>) + Vimeo (our own
			// Player instance) only; unreachable for YouTube, see class docblock.
			'controls_enabled'  => Boolean_Prop_Type::make()->default( true )->set_dependencies( $not_youtube ),
			'controls_autohide' => Boolean_Prop_Type::make()->default( true )->set_dependencies( $controls_on_and_not_youtube ),
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
							[ 'value' => 'youtube', 'label' => __( 'YouTube', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'hosted',  'label' => __( 'Hosted Video', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'vimeo',   'label' => __( 'Vimeo', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'video_vimeo_url' )
						->set_label( __( 'Vimeo URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vimeo_autoplay' )
						->set_label( __( 'Autoplay', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vimeo_mute' )
						->set_label( __( 'Mute', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vimeo_loop' )
						->set_label( __( 'Loop', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vimeo_dnt' )
						->set_label( __( 'Do Not Track', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'poster' )
				->set_label( __( 'Poster & Play Button', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'poster_auto_fetch' )
						->set_label( __( 'Auto-fetch Thumbnail (Vimeo)', 'animation-addons-for-elementor' ) ),
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
	 * Structural styles only for THIS element (the wrapper). The four child
	 * elements (image/youtube/self-hosted-video/button) carry their own full
	 * Style-tab panels, independently editable — this plugin never overrides
	 * their look beyond the full-bleed/positioning rules in video.scss.
	 * Every key here is verified against atomic-style-schema-reference.md —
	 * one invalid key silently voids this whole method.
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

			// Bottom controls bar shell (Hosted/Vimeo only) — reveal opacity
			// and gradient are runtime-state-driven, so they live in
			// video.scss; this only sets structural layout.
			'controls_bar' => Style_Definition::make()
				->set_label( __( 'Controls Bar', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'     => String_Prop_Type::generate( 'flex' ),
						'align-items' => String_Prop_Type::generate( 'center' ),
						'gap'         => Size_Prop_Type::generate( [ 'size' => 10, 'unit' => 'px' ] ),
						'width'       => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
					] )
				),

			'progress_track' => Style_Definition::make()
				->set_label( __( 'Progress Track', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'width'         => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						'height'        => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
						'border-radius' => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
						'cursor'        => String_Prop_Type::generate( 'pointer' ),
						'background'    => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( 'rgba(255,255,255,0.35)' ),
						] ),
					] )
				),

			'progress_fill' => Style_Definition::make()
				->set_label( __( 'Progress Fill', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'height'        => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
						'border-radius' => Size_Prop_Type::generate( [ 'size' => 2, 'unit' => 'px' ] ),
						'background'    => Background_Prop_Type::generate( [
							'color' => Color_Prop_Type::generate( '#ffffff' ),
						] ),
					] )
				),

			'control_button' => Style_Definition::make()
				->set_label( __( 'Control Button', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'display'         => String_Prop_Type::generate( 'flex' ),
						'align-items'     => String_Prop_Type::generate( 'center' ),
						'justify-content' => String_Prop_Type::generate( 'center' ),
						'width'           => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
						'height'          => Size_Prop_Type::generate( [ 'size' => 28, 'unit' => 'px' ] ),
						'color'           => Color_Prop_Type::generate( '#ffffff' ),
						'cursor'          => String_Prop_Type::generate( 'pointer' ),
					] )
				),

			'time_text' => Style_Definition::make()
				->set_label( __( 'Time Text', 'animation-addons-for-elementor' ) )
				->add_variant(
					Style_Variant::make()->add_props( [
						'font-size' => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
						'color'     => Color_Prop_Type::generate( '#ffffff' ),
					] )
				),
		];
	}

	/**
	 * Fixed structural parts, mirroring AAE_A_Btn's icon+label pattern —
	 * real, independent, fully-editable native atomic elements rather than
	 * markup this widget owns. Hook classes ('aae-a-video-poster' /
	 * '-playbtn') are how video.js/video.scss find them; per this project's
	 * "never put a functional hook class in classes" rule that makes them
	 * one Style-panel ✕ away from being stripped — same accepted trade-off
	 * Image Compare already carries for the same reason (reusing native
	 * part types leaves no twig seam to hardcode the class instead).
	 */
	protected function define_default_children() {
		return [
			Atomic_Image::generate()
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-video-poster' ] ),
					'image'   => Image_Prop_Type::generate( [
						'src'  => Image_Src_Prop_Type::generate( [
							'id'  => null,
							'url' => Url_Prop_Type::generate( \Elementor\Utils::get_placeholder_image_src() ),
						] ),
						'size' => String_Prop_Type::generate( 'large' ),
					] ),
				] )
				->build(),

			Atomic_Youtube::generate()
				->settings( [
					'source' => String_Prop_Type::generate( 'https://www.youtube.com/watch?v=XHOmBV4js_E' ),
				] )
				->build(),

			Atomic_Self_Hosted_Video::generate()
				->settings( [
					'controls' => Boolean_Prop_Type::generate( true ),
				] )
				->build(),

			Atomic_Button::generate()
				->settings( [
					'classes' => Classes_Prop_Type::generate( [ 'aae-a-video-playbtn' ] ),
					'text'    => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( '▶' ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-image', 'e-youtube', 'e-self-hosted-video', 'e-button' ];
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
	 * Resolve the Vimeo poster thumbnail server-side, since Twig has no HTTP
	 * client and the oEmbed lookup needs caching. Same computed-value hook
	 * AAE_A_Site_Logo uses — accepted trade-off: the editor's client-side
	 * twig re-render only refreshes this on an actual server round-trip, not
	 * on every keystroke while editing the Vimeo URL.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['resolved_poster_url'] = ( 'vimeo' === ( $settings['video_type'] ?? 'youtube' ) && $settings['poster_auto_fetch'] )
			? self::fetch_vimeo_thumbnail( $settings['video_vimeo_url'] ?? '' )
			: '';
		$settings['placeholder_image_url'] = esc_url( \Elementor\Utils::get_placeholder_image_src() );

		return $settings;
	}

	private static function fetch_vimeo_thumbnail( string $url ): string {
		if ( empty( $url ) ) {
			return '';
		}

		$key    = 'aae_vimeo_thumb_' . md5( $url );
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			// '' is itself a valid cached failure — avoid re-hitting the API
			// on every render of a private/unlisted/invalid Vimeo URL.
			return $cached;
		}

		$response = wp_remote_get(
			'https://vimeo.com/api/oembed.json?url=' . rawurlencode( $url ),
			[ 'timeout' => 3 ]
		);

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
