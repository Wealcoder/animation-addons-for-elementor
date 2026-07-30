<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\VideoMask;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-video-mask-btn.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Video_Mask extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-video-mask';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-video-mask';
	}

	public function get_title(): string {
		return esc_html__( 'Video Mask', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-youtube';
	}

	public function get_keywords(): array {
		return [ 'video', 'mask', 'play', 'atomic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	/**
	 * Panel category for the Elements panel.
	 *
	 * Atomic_Element_Base reads the panel category from HERE — get_categories()
	 * is Widget_Base's hook and is never called for an element type, so a
	 * category declared only there silently falls back to Elementor's own
	 * 'v4-elements' ("Atomic Elements") bucket. Delegate so both stay in sync.
	 */
	protected function define_panel_categories(): array {
		return $this->get_categories();
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Video
			'vm_video_link'        => String_Prop_Type::make()->default( 'https://wealcoder.com/dev/video/dancer.mp4' ),
			'vm_video_autoplay'    => Boolean_Prop_Type::make()->default( true ),
			'vm_video_mute'        => Boolean_Prop_Type::make()->default( true ),
			'vm_video_playsinline' => Boolean_Prop_Type::make()->default( false ),
			'vm_video_loop'        => Boolean_Prop_Type::make()->default( false ),

			// Mask
			'vm_mask_shape' => String_Prop_Type::make()
				->enum( [ 'circle', 'flower', 'sketch', 'triangle', 'blob' ] )
				->default( 'circle' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'video' )
				->set_label( __( 'Video', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'vm_video_link' )
						->set_label( __( 'Video Link (MP4)', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vm_video_autoplay' )
						->set_label( __( 'Autoplay', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vm_video_mute' )
						->set_label( __( 'Mute', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vm_video_playsinline' )
						->set_label( __( 'Plays Inline', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'vm_video_loop' )
						->set_label( __( 'Loop', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'vm_mask_shape' )
						->set_label( __( 'Mask Shape', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'circle',   'label' => __( 'Circle',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'flower',   'label' => __( 'Flower',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'sketch',   'label' => __( 'Sketch',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'triangle', 'label' => __( 'Triangle', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'blob',     'label' => __( 'Blob',     'animation-addons-for-elementor' ) ],
						] ),
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

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display'  => String_Prop_Type::generate( 'grid' ),
					'position' => String_Prop_Type::generate( 'relative' ),
				] ) ),
		];
	}

	// The inner AAE_A_Video_Mask_Btn element is the click-trigger and
	// open-label container. The user positions it freely via the Style panel.
	protected function define_default_children(): array {
		return [
			AAE_A_Video_Mask_Btn::generate()
				->editor_settings( [ 'title' => 'Button' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'e-aae-a-video-mask-btn' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-video-mask' => __DIR__ . '/aae-a-video-mask.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-video-mask-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-video-mask-css' ];
	}
}
