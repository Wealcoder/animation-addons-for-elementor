<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\PostMeta;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Html_V3_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Svg\Atomic_Svg;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Paragraph\Atomic_Paragraph;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Post_Meta_Item extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-meta-item';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-meta-item';
	}

	public function get_title() {
		return esc_html__( 'Post Meta Item', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-info';
	}

	public function get_keywords() {
		return [ 'post', 'meta', 'item', 'info', 'atomic' ];
	}

	public function should_show_in_panel() {
		return false; // Only added via parent
	}

	protected static function define_props_schema(): array {
		$has_custom_key = Dependency_Manager::make()
			->where([
				'operator' => 'eq',
				'path'     => ['meta_type'],
				'value'    => 'custom',
				'effect'   => 'hide',
			])
			->get();

		$has_taxonomy = Dependency_Manager::make()
			->where([
				'operator' => 'eq',
				'path'     => ['meta_type'],
				'value'    => 'terms',
				'effect'   => 'hide',
			])
			->get();

		$has_date_format = Dependency_Manager::make()
			->where([
				'operator' => 'eq',
				'path'     => ['meta_type'],
				'value'    => 'date',
				'effect'   => 'hide',
			])
			->get();

		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'meta_type' => String_Prop_Type::make()->default( 'date' ),
			'date_format' => String_Prop_Type::make()->default( '' )->set_dependencies( $has_date_format ),
			'taxonomy' => String_Prop_Type::make()->default( 'category' )->set_dependencies( $has_taxonomy ),
			'custom_key' => String_Prop_Type::make()->default( '' )->set_dependencies( $has_custom_key ),
			'before_text' => String_Prop_Type::make()->default( '' ),
			'after_text' => String_Prop_Type::make()->default( '' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Meta Item Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'meta_item_settings' )
				->set_items( [
					Select_Control::bind_to( 'meta_type' )
						->set_label( __( 'Type', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date', 'label' => __( 'Date', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'author', 'label' => __( 'Author', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'comments', 'label' => __( 'Comments', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'terms', 'label' => __( 'Terms/Taxonomy', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'custom', 'label' => __( 'Custom Field', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'date_format' )
						->set_label( __( 'Date Format', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'e.g. F j, Y', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'taxonomy' )
						->set_label( __( 'Taxonomy Name', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'category', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'custom_key' )
						->set_label( __( 'Meta Key', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Enter custom meta key...', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'before_text' )
						->set_label( __( 'Before Text', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'after_text' )
						->set_label( __( 'After Text', 'animation-addons-for-elementor' ) ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display'     => String_Prop_Type::generate( 'inline-flex' ),
					'align-items' => String_Prop_Type::generate( 'center' ),
					'gap'         => String_Prop_Type::generate( '6px' ),
					'padding'     => Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
					] ),
				] ) ),
		];
	}

	protected function define_default_children() {
		$svg = Atomic_Svg::generate()
			->settings( [
				'classes' => Classes_Prop_Type::generate( [ 'e-aae-post-meta-item-icon' ] ),
			] )
			->build();

		$paragraph = Atomic_Paragraph::generate()
			->settings( [
				'paragraph' => Html_V3_Prop_Type::generate( [
					'content'  => String_Prop_Type::generate( 'Meta Text' ),
					'children' => [],
				] ),
				'tag'       => String_Prop_Type::generate( 'span' ),
			] )
			->build();

		return [ $svg, $paragraph ];
	}

	protected function define_allowed_child_types() {
		return [ 'widget', 'e-svg', 'e-paragraph', 'e-heading' ];
	}

	protected function define_default_html_tag() {
		return 'li';
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-meta-item' => __DIR__ . '/aae-a-post-meta-item.html.twig',
		];
	}

	protected function get_dynamic_meta_text(): string {
		$meta_type = $this->get_settings( 'meta_type' );
		$before = $this->get_settings( 'before_text' ) ?: '';
		$after = $this->get_settings( 'after_text' ) ?: '';
		$value = '';

		switch ( $meta_type ) {
			case 'date':
				$format = $this->get_settings( 'date_format' ) ?: get_option( 'date_format' );
				$value = get_the_date( $format );
				break;
			case 'author':
				$value = get_the_author();
				break;
			case 'comments':
				$comments_num = get_comments_number();
				if ( 0 === $comments_num ) {
					$value = __( 'No Comments', 'animation-addons-for-elementor' );
				} elseif ( 1 === $comments_num ) {
					$value = __( '1 Comment', 'animation-addons-for-elementor' );
				} else {
					$value = sprintf( _n( '%s Comment', '%s Comments', $comments_num, 'animation-addons-for-elementor' ), number_format_i18n( $comments_num ) );
				}
				break;
			case 'terms':
				$taxonomy = $this->get_settings( 'taxonomy' ) ?: 'category';
				$terms = get_the_terms( get_the_ID(), $taxonomy );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$term_names = wp_list_pluck( $terms, 'name' );
					$value = implode( ', ', $term_names );
				}
				break;
			case 'custom':
				$custom_key = $this->get_settings( 'custom_key' );
				if ( ! empty( $custom_key ) ) {
					$value = get_post_meta( get_the_ID(), $custom_key, true );
					if ( is_array( $value ) ) {
						$value = implode( ', ', $value );
					}
				}
				break;
		}

		if ( empty( $value ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return $before . ucfirst( (string) $meta_type ) . $after;
		}

		return $before . $value . $after;
	}

	protected function render_children_to_html(): string {
		$meta_text = $this->get_dynamic_meta_text();

		$html = '';
		foreach ( $this->get_children() as $child ) {
			if ( 'e-paragraph' === $child->get_type() || 'e-heading' === $child->get_type() ) {
				if ( 'e-paragraph' === $child->get_type() ) {
					$child->set_settings( 'paragraph', [
						'$$type' => 'html-v3',
						'value' => [
							'content' => [
								'$$type' => 'string',
								'value' => $meta_text,
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
								'value' => $meta_text,
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
