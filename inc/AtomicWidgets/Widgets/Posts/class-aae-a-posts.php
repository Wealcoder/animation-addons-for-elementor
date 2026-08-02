<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Posts;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

require_once __DIR__ . '/class-aae-a-post-card.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Posts extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-posts';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-posts';
	}

	public function get_title() {
		return esc_html__( 'Posts', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_keywords() {
		return [ 'posts', 'grid', 'blog', 'atomic', 'dynamic' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-post'];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'        => Classes_Prop_Type::make()->default( [] ),
			'attributes'     => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			'posts_layout'   => String_Prop_Type::make()->default( 'grid' ),
			'columns'        => Number_Prop_Type::make()->default( 3 ),
			'posts_per_page' => Number_Prop_Type::make()->default( 6 ),
			'order_by'       => String_Prop_Type::make()->default( 'date' ),
			'order'          => String_Prop_Type::make()->default( 'DESC' ),
			'post_type'      => String_Prop_Type::make()->default( 'post' ),
			'excerpt_length' => Number_Prop_Type::make()->default( 15 ),

			'show_date'      => Boolean_Prop_Type::make()->default( false ),
			'show_excerpt'   => Boolean_Prop_Type::make()->default( true ),
			'show_read_more' => Boolean_Prop_Type::make()->default( true ),
			'read_more_text' => String_Prop_Type::make()->default( 'Read More' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Posts Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'posts_layout' )
						->set_label( __( 'Layout', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'grid', 'label' => __( 'Grid', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'list', 'label' => __( 'List', 'animation-addons-for-elementor' ) ],
						] ),

					Number_Control::bind_to( 'columns' )
						->set_label( __( 'Columns', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 1, 'max' => 6, 'step' => 1 ] ),

					Number_Control::bind_to( 'posts_per_page' )
						->set_label( __( 'Posts Per Page', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 1, 'max' => 24, 'step' => 1 ] ),

					Number_Control::bind_to( 'excerpt_length' )
						->set_label( __( 'Excerpt Length (words)', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'min' => 5, 'max' => 100, 'step' => 1 ] ),

					Switch_Control::bind_to( 'show_date' )
						->set_label( __( 'Show Date', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_excerpt' )
						->set_label( __( 'Show Excerpt', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_read_more' )
						->set_label( __( 'Read More Button', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'read_more_text' )
						->set_label( __( 'Read More Text', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_label( __( 'Query', 'animation-addons-for-elementor' ) )
				->set_id( 'query' )
				->set_items( [
					Select_Control::bind_to( 'post_type' )
						->set_label( __( 'Post Type', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'post', 'label' => __( 'Posts', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'page', 'label' => __( 'Pages', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'order_by' )
						->set_label( __( 'Order By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date',  'label' => __( 'Date',   'animation-addons-for-elementor' ) ],
							[ 'value' => 'title', 'label' => __( 'Title',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'rand',  'label' => __( 'Random', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'order' )
						->set_label( __( 'Order', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'DESC', 'label' => __( 'Descending', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'ASC',  'label' => __( 'Ascending',  'animation-addons-for-elementor' ) ],
						] ),
				] ),

			Section::make()
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_id( 'settings' )
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
					'display' => String_Prop_Type::generate( 'block' ),
					'width'   => String_Prop_Type::generate( '100%' ),
				] ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Post_Card::generate()
				->editor_settings( [ 'title' => 'Post Card' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-post-card' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$args = [
			'post_type'           => $settings['post_type'] ?? 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) ( $settings['posts_per_page'] ?? 6 ),
			'orderby'             => $settings['order_by'] ?? 'date',
			'order'               => $settings['order'] ?? 'DESC',
			'ignore_sticky_posts' => true,
		];

		$query      = new \WP_Query( $args );
		$posts_list = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$image_url = get_the_post_thumbnail_url( null, 'medium_large' );
				if ( empty( $image_url ) ) {
					$image_url = \Elementor\Utils::get_placeholder_image_src();
				}

				// Intrinsic dimensions for the thumb. The <img> is
				// loading="lazy", so without width/height the browser
				// reserves NO box and everything below shifts when the bytes
				// land — mid-scroll, under every ScrollTrigger measured
				// before it (see CLAUDE.md → cache compat, step 2). The
				// attachment metadata knows the size; ship it.
				$image_w  = 0;
				$image_h  = 0;
				$thumb_id = get_post_thumbnail_id();
				if ( $thumb_id ) {
					$meta = wp_get_attachment_image_src( $thumb_id, 'medium_large' );
					if ( $meta ) {
						$image_w = (int) $meta[1];
						$image_h = (int) $meta[2];
					}
				}

				$posts_list[] = [
					'id'      => get_the_ID(),
					'title'   => get_the_title(),
					'excerpt' => wp_trim_words( get_the_excerpt(), (int) ( $settings['excerpt_length'] ?? 15 ) ),
					'url'     => get_permalink(),
					'image'   => $image_url,
					'image_w' => $image_w,
					'image_h' => $image_h,
					'date'    => get_the_date(),
				];
			}
			wp_reset_postdata();
		}

		$settings['posts_list'] = $posts_list;

		return $settings;
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-posts' => __DIR__ . '/aae-a-posts.html.twig',
		];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-posts-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-posts-css' ];
	}
}
