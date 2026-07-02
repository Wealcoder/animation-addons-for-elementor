<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Posts;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Posts extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-posts';
	}

	public function get_title() {
		return esc_html__( 'AAE Posts Grid', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_keywords() {
		return [ 'posts', 'grid', 'blog', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		$args = [
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 6,
			'orderby' => 'date',
			'ignore_sticky_posts' => true,
		];

		$query = new \WP_Query( $args );
		$posts_list = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$image_url = get_the_post_thumbnail_url( null, 'medium_large' );
				if ( empty($image_url) ) {
					$image_url = \Elementor\Utils::get_placeholder_image_src();
				}
				$posts_list[] = [
					'id' => get_the_ID(),
					'title' => get_the_title(),
					'excerpt' => wp_trim_words( get_the_excerpt(), 15 ),
					'url' => get_permalink(),
					'image' => $image_url,
					'date' => get_the_date(),
				];
			}
			wp_reset_postdata();
		}

		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'columns' => Number_Prop_Type::make()->default( 3 ),
			'posts_per_page' => Number_Prop_Type::make()->default( 6 ),
			'order_by' => String_Prop_Type::make()->default( 'date' ),
			'posts_list' => Posts_List_Prop_Type::make()->default( $posts_list ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Grid Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Number_Control::bind_to( 'columns' )
						->set_label( __( 'Columns', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 6 ),

					Number_Control::bind_to( 'posts_per_page' )
						->set_label( __( 'Posts Per Page', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( 20 ),

					Select_Control::bind_to( 'order_by' )
						->set_label( __( 'Order By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'date', 'label' => __( 'Date', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'title', 'label' => __( 'Title', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'rand', 'label' => __( 'Random', 'animation-addons-for-elementor' ) ],
						] ),
				] ),
		];
	}

	protected static function define_styles(): array {
		return [
			Style_Definition::make( 'columns' )
				->add_variant(
					Style_Variant::make( 'grid-template-columns' )
						->set_selector( '& .aae-a-posts-grid' )
						->set_css_property( 'grid-template-columns' )
						->set_css_value( 'repeat({{VALUE}}, 1fr)' )
				),
		];
	}

	protected function set_initial_state(): void {
		parent::set_initial_state();

		$this->add_style_dependencies( [
			$this->get_style_handle('columns'),
		] );
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-posts' => __DIR__ . '/aae-a-posts.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-posts-css' ];
	}



	public function get_script_depends(): array {
		return [ 'aae-a-posts-js' ]; // Needs GSAP and ScrollTrigger via deps
	}

	// Dynamic data injection for Twig JS rendering natively!
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$args = [
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => isset($settings['posts_per_page']) ? (int) $settings['posts_per_page'] : 6,
			'orderby' => isset($settings['order_by']) ? $settings['order_by'] : 'date',
			'ignore_sticky_posts' => true,
		];

		$query = new \WP_Query( $args );
		$posts_list = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				
				// Get image url or placeholder
				$image_url = get_the_post_thumbnail_url( null, 'medium_large' );
				if ( empty($image_url) ) {
					$image_url = \Elementor\Utils::get_placeholder_image_src();
				}

				$posts_list[] = [
					'id' => get_the_ID(),
					'title' => get_the_title(),
					'excerpt' => wp_trim_words( get_the_excerpt(), 15 ),
					'url' => get_permalink(),
					'image' => $image_url,
					'date' => get_the_date(),
				];
			}
			wp_reset_postdata();
		}

		$settings['posts_list'] = $posts_list;

		return $settings;
	}
}

class Post_Item_Prop_Type extends \Elementor\Modules\AtomicWidgets\PropTypes\Base\Object_Prop_Type {
	public static function get_key(): string {
		return 'aae_post_item';
	}
	protected function define_shape(): array {
		return [
			'id' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type::make(),
			'title' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::make(),
			'excerpt' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::make(),
			'url' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::make(),
			'image' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::make(),
			'date' => \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::make(),
		];
	}
	public function validate( $value ): bool {
		return true; // Bypass validation for dynamic internal data
	}
}

class Posts_List_Prop_Type extends \Elementor\Modules\AtomicWidgets\PropTypes\Base\Array_Prop_Type {
	public static function get_key(): string {
		return 'aae_posts_list_array';
	}
	protected function define_item_type(): \Elementor\Modules\AtomicWidgets\PropTypes\Contracts\Prop_Type {
		return Post_Item_Prop_Type::make();
	}
	public function validate( $value ): bool {
		return true; // Bypass validation for dynamic internal data
	}
}
