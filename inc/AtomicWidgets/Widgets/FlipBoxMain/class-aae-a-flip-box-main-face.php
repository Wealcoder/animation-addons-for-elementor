<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\FlipBoxMain;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Flip_Box_Main_Face extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-flip-box-main-face';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-flip-box-main-face';
	}

	public function get_title(): string {
		return esc_html__( 'Flip Box Main Face', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-inner-section';
	}

	public function get_keywords(): array {
		return [ 'flip', 'face', 'front', 'back', 'atomic' ];
	}

	public function should_show_in_panel(): bool {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'   => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'face_side'  => String_Prop_Type::make()->enum( [ 'front', 'back' ] )->default( 'front' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'background-color' => Color_Prop_Type::generate( '#f2f1f1' ),
					'border-radius'    => String_Prop_Type::generate( '10px' ),
					'overflow'         => String_Prop_Type::generate( 'hidden' ),
					'transition'       => String_Prop_Type::generate( 'transform 0.8s' ),
				] ) ),
		];
	}

	protected function define_default_children(): array {
		return self::build_default_children( 'Title', 'Add your content here.' );
	}

	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading', 'e-image', 'e-button', 'e-aae-a-btn' ];
	}

	public static function build_default_children( string $title = 'Title', string $content = 'Add your content here.' ): array {
		return [
			Atomic_Heading::generate()
				->settings( [
					'title' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $title ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'h2' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( $content ),
						'children' => [],
					] ),
				] )
				->build(),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-flip-box-main-face' => __DIR__ . '/aae-a-flip-box-main-face.html.twig',
		];
	}
}
