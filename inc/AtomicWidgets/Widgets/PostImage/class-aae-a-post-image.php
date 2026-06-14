<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\PostImage;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Image extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-post-image';
	}

	public function get_title() {
		return esc_html__( 'AAE Post Image', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-featured-image';
	}

	public function get_keywords() {
		return [ 'post', 'image', 'featured', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		$image_url = get_the_post_thumbnail_url( null, 'large' );
		$image_alt = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true );

		if ( empty( $image_url ) ) {
			$image_url = \Elementor\Utils::get_placeholder_image_src();
			$image_alt = 'Placeholder Image';
		}

		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'object_fit' => String_Prop_Type::make()->default( 'cover' ),
			'aspect_ratio' => String_Prop_Type::make()->default( '16/9' ),
			'image_url' => String_Prop_Type::make()->default( $image_url ),
			'image_alt' => String_Prop_Type::make()->default( $image_alt ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Image Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'object_fit' )
						->set_label( __( 'Object Fit', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'cover', 'label' => 'Cover' ],
							[ 'value' => 'contain', 'label' => 'Contain' ],
							[ 'value' => 'fill', 'label' => 'Fill' ],
						] ),

					Select_Control::bind_to( 'aspect_ratio' )
						->set_label( __( 'Aspect Ratio', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => '16/9', 'label' => '16:9' ],
							[ 'value' => '4/3', 'label' => '4:3' ],
							[ 'value' => '1/1', 'label' => '1:1 (Square)' ],
							[ 'value' => 'auto', 'label' => 'Auto' ],
						] ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'block' ),
			'width' => String_Prop_Type::generate( '100%' ),
			'border-radius' => Size_Prop_Type::generate( [
				'size' => 0,
				'unit' => 'px',
			] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-image' => __DIR__ . '/aae-a-post-image.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-image-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['image_url'] = get_the_post_thumbnail_url( null, 'large' );
		$settings['image_alt'] = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true );

		// Fallback for editor or empty images
		if ( empty( $settings['image_url'] ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['image_url'] = \Elementor\Utils::get_placeholder_image_src();
			$settings['image_alt'] = 'Placeholder Image';
		}

		return $settings;
	}
}
