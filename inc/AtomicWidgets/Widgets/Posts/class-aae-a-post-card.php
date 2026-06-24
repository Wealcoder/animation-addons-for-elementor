<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Posts;

use Elementor\Modules\AtomicWidgets\Elements\Atomic_Heading\Atomic_Heading;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Post_Card extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-card';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-card';
	}

	public function get_title() {
		return esc_html__( 'Post Card', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_keywords() {
		return [ 'post', 'card', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false;
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
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

	protected function define_allowed_child_types(): array {
		return [ 'widget', 'e-image', 'e-heading', 'e-paragraph', 'e-svg', 'e-aae-a-button' ];
	}

	protected function define_default_children(): array {
		return [
			// Thumbnail placeholder — user can swap in e-image from the panel.
			Atomic_Svg::generate()
				->editor_settings( [ 'title' => 'Post Thumbnail' ] )
				->build(),

			Atomic_Heading::generate()
				->editor_settings( [ 'title' => 'Post Title' ] )
				->settings( [
					'title' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Post Title' ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'h3' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Post Excerpt' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Post excerpt goes here...' ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'p' ),
				] )
				->build(),

			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Read More' ] )
				->settings( [
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Read More →' ),
						'children' => [],
					] ),
					'tag' => String_Prop_Type::generate( 'p' ),
				] )
				->build(),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-card' => __DIR__ . '/aae-a-post-card.html.twig',
		];
	}
}
