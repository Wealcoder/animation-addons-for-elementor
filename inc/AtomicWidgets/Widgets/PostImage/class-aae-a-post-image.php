<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\PostImage;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Image_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Image extends Atomic_Widget_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-image';
	}

	public function get_title() {
		return esc_html__( 'Post Image', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-featured-image';
	}

	public function get_keywords() {
		return [ 'post', 'image', 'featured', 'atomic', 'dynamic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-post'];
	}

	public function get_initial_config() {
		$config = parent::get_initial_config();

		$config['default_children'] = $this->define_default_children();
		$config['allowed_child_types'] = $this->define_allowed_child_types();
		$config['default_html_tag'] = $this->define_default_html_tag();

		return $config;
	}

	protected static function define_props_schema(): array {
		$image_url = get_the_post_thumbnail_url( null, 'large' );
		$image_alt = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true );

		// Editor preview: the edited page rarely has a featured image, which
		// used to mean the gray placeholder. Sample a random post WITH a
		// featured image instead (shared helper — the Post Title widget
		// previews the SAME post, so the card reads as one real post).
		if ( empty( $image_url ) && class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			if ( $sample ) {
				$image_url = get_the_post_thumbnail_url( $sample, 'large' );
				$image_alt = get_post_meta( get_post_thumbnail_id( $sample ), '_wp_attachment_image_alt', true );
			}
		}

		if ( empty( $image_url ) ) {
			$image_url = \Elementor\Utils::get_placeholder_image_src();
			$image_alt = 'Placeholder Image';
		}

		$has_caption = Dependency_Manager::make()
			->where([
				'operator' => 'eq',
				'path'     => ['show_caption'],
				'value'    => true,
				'effect'   => 'hide',
			])
			->get();

		$is_custom_caption = Dependency_Manager::make( Dependency_Manager::RELATION_AND )
			->where([
				'operator' => 'eq',
				'path'     => ['show_caption'],
				'value'    => true,
				'effect'   => 'hide',
			])
			->where([
				'operator' => 'eq',
				'path'     => ['caption_source'],
				'value'    => 'custom',
				'effect'   => 'hide',
			])
			->get();

		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'object_fit' => String_Prop_Type::make()->default( 'cover' ),
			'aspect_ratio' => String_Prop_Type::make()->default( '16/9' ),
			'image_size' => String_Prop_Type::make()->default( 'large' ),
			'image_url' => String_Prop_Type::make()->default( $image_url ),
			'image_alt' => String_Prop_Type::make()->default( $image_alt ),

			'link' => Link_Prop_Type::make(),

			// Caption Options
			'show_caption' => Boolean_Prop_Type::make()->default( false ),
			'caption_source' => String_Prop_Type::make()->default( 'attachment' )->set_dependencies( $has_caption ),
			'custom_caption' => String_Prop_Type::make()->default( '' )->set_dependencies( $is_custom_caption ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
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

					Select_Control::bind_to( 'image_size' )
						->set_label( __( 'Image Size', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'thumbnail', 'label' => __( 'Thumbnail', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'medium', 'label' => __( 'Medium', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'medium_large', 'label' => __( 'Medium Large', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'large', 'label' => __( 'Large', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'full', 'label' => __( 'Full', 'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_label( __( 'Link Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Link_Control::bind_to( 'link' )
						->set_label( __( 'Link URL', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Caption Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'show_caption' )
						->set_label( __( 'Show Caption', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'caption_source' )
						->set_label( __( 'Caption Source', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'attachment', 'label' => __( 'Attachment Caption', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'custom', 'label' => __( 'Custom Caption', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'custom_caption' )
						->set_label( __( 'Custom Caption Text', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Enter custom caption...', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display' => String_Prop_Type::generate( 'block' ),
					'width' => String_Prop_Type::generate( '100%' ),
					'position' => String_Prop_Type::generate( 'relative' ),
					'overflow' => String_Prop_Type::generate( 'hidden' ),
					'border-radius' => Size_Prop_Type::generate( [
						'size' => 0,
						'unit' => 'px',
					] ),
				] ) )			
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-image' => __DIR__ . '/aae-a-post-image.html.twig',
		];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();
		$size = ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'large';

		$settings['image_url'] = get_the_post_thumbnail_url( null, $size );
		$settings['image_alt'] = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true );

		// Fallback for editor or empty images: preview the shared sample post
		// (random, has a featured image) before resorting to the placeholder.
		if ( empty( $settings['image_url'] ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$sample = \WCF_ADDONS\AtomicWidgets\Atomic::get_sample_post();
			if ( $sample ) {
				$settings['image_url'] = get_the_post_thumbnail_url( $sample, $size );
				$settings['image_alt'] = get_post_meta( get_post_thumbnail_id( $sample ), '_wp_attachment_image_alt', true );
			}
			if ( empty( $settings['image_url'] ) ) {
				$settings['image_url'] = \Elementor\Utils::get_placeholder_image_src();
				$settings['image_alt'] = 'Placeholder Image';
			}
		}

		return $settings;
	}

	protected function define_default_children() {
		return [
			Atomic_Paragraph::generate()
				->editor_settings( [ 'title' => 'Caption' ] )
				->settings( [
					'classes'   => Classes_Prop_Type::generate( [ 'aae-a-post-image-caption' ] ),
					'paragraph' => Html_V3_Prop_Type::generate( [
						'content'  => String_Prop_Type::generate( 'Caption Text' ),
						'children' => [],
					] ),
					'tag'       => String_Prop_Type::generate( 'span' ),
				] )
				->build(),
		];
	}

	/**
	 * Build each child from its OWN type, the way container elements do.
	 *
	 * This widget extends Atomic_Widget_Base — the LEAF base — but declares
	 * default children, and the leaf base assumes it has none:
	 * Widget_Base::_get_default_child_type() ignores the child's data entirely
	 * and returns the V1 `section` element type for anything.
	 *
	 * The caption paragraph was therefore instantiated as Element_Section rather
	 * than Atomic_Paragraph. That class is an Element_Base, not a Widget_Base, so
	 * its get_raw_data() emits `isInner` and — the part that actually breaks —
	 * NO `widgetType`. The editor config handed to the browser carried a model
	 * with `elType: widget` and no widgetType; Elementor's V1 element model sets
	 * remoteRender = true for any elType `widget`, so the view immediately asked
	 * the server to render it, and Elements_Manager::get_element('widget', null)
	 * returns the whole widget-type ARRAY instead of one type — fatal on
	 * ->get_default_args(), one 500 per editor boot on every page using this
	 * widget. Saved data was always correct; only the instantiation was wrong.
	 *
	 * Mirrors Atomic_Element_Base::_get_default_child_type().
	 *
	 * @param array $element_data
	 * @return \Elementor\Element_Base|null
	 */
	protected function _get_default_child_type( array $element_data ) {
		$el_types = array_keys( \Elementor\Plugin::$instance->elements_manager->get_element_types() );

		if ( in_array( $element_data['elType'], $el_types, true ) ) {
			return \Elementor\Plugin::$instance->elements_manager->get_element_types( $element_data['elType'] );
		}

		// Never fall through to a null widget type: get_widget_types( null )
		// returns every registered type, and the caller has no array guard.
		if ( ! isset( $element_data['widgetType'] ) ) {
			return null;
		}

		return \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $element_data['widgetType'] );
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-paragraph', 'e-heading' ];
	}

	protected function define_default_html_tag() {
		return 'div';
	}

	protected function render_children_to_html(): string {
		$caption_text = '';
		$show_caption = (bool) $this->get_settings( 'show_caption' );
		if ( $show_caption ) {
			$caption_source = $this->get_settings( 'caption_source' );
			if ( 'attachment' === $caption_source ) {
				$caption_text = (string) wp_get_attachment_caption( get_post_thumbnail_id() );
			} elseif ( 'custom' === $caption_source ) {
				$caption_text = (string) $this->get_settings( 'custom_caption' );
			}
		}

		$html = '';
		foreach ( $this->get_children() as $child ) {
			// If it's the caption paragraph, check if we should render it, and inject dynamic content if needed
			if ( 'e-paragraph' === $child->get_type() || 'e-heading' === $child->get_type() ) {
				if ( ! $show_caption ) {
					continue;
				}
				if ( 'e-paragraph' === $child->get_type() ) {
					$child->set_settings( 'paragraph', [
						'$$type' => 'html-v3',
						'value' => [
							'content' => [
								'$$type' => 'string',
								'value' => $caption_text,
							],
							'children' => [],
						],
					] );
				} else {
					$child->set_settings( 'title', [
						'$$type' => 'html-v3',
						'value' => [
							'content' => [
								'$$type' => 'string',
								'value' => $caption_text,
							],
							'children' => [],
						],
					] );
				}
			}

			ob_start();
			$child->print_element();
			$html .= ob_get_clean();
		}

		return $html;
	}
}
