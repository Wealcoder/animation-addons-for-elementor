<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\Accordion;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Controls\Types\Toggle_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

require_once __DIR__ . '/class-aae-a-accordion-item.php';
require_once __DIR__ . '/class-aae-a-items-control.php';
use WCF_ADDONS\AtomicWidgets\Widgets\Accordion\AAE_A_Accordion_Item;
use WCF_ADDONS\AtomicWidgets\Widgets\Accordion\AAE_A_Items_Control;
use WCF_ADDONS\Atomic\Lightbox\Lightbox_Manager;
use WCF_ADDONS\Atomic\Lightbox\Schema as Lightbox_Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AAE_A_Accordion extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-accordion';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-accordion';
	}

	public function get_title() {
		return esc_html__( 'AAE Accordion', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_keywords() {
		return [ 'accordion', 'tabs', 'toggle', 'atomic', 'gsap' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes' => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'default_state' => String_Prop_Type::make()->enum( [ 'first', 'none' ] )->default( 'first' ),
			'max_items_expanded' => String_Prop_Type::make()->enum( [ 'one', 'multiple' ] )->default( 'one' ),
			'animation_duration' => Size_Prop_Type::make()->default( [ 'size' => 400, 'unit' => 'ms' ] ),
			'faq_schema' => Boolean_Prop_Type::make()->default( false ),

			// Lightbox — the shared props, defined locally so a custom widget
			// carries them without depending on the global props-schema filter
			// (which only targets core element types). LB_ENABLE is a plain
			// Boolean because it binds to a Switch_Control.
			Lightbox_Schema::LB_ENABLE  => Boolean_Prop_Type::make()->default( false ),
			Lightbox_Schema::LB_GROUP   => String_Prop_Type::make()->default( '' ),
			Lightbox_Schema::LB_TITLE   => String_Prop_Type::make()->default( '' ),
			Lightbox_Schema::LB_CAPTION => String_Prop_Type::make()->default( '' ),
			Lightbox_Schema::LB_ANIM    => String_Prop_Type::make()->default( 'zoom' ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			// "Items": a live projection of the accordion's real
			// <e-aae-a-accordion-item> children — one repeater row each, with
			// drag-reorder, duplicate, remove and rename. Mirrors the Nested
			// Slider's "Slides" element-control. Rendered by the React component
			// registered under 'aae-items' (src/modules/atomic/element-controls).
			Section::make()
				->set_id( 'items' )
				->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
				->set_items( [
					AAE_A_Items_Control::make()
						->set_label( __( 'Items', 'animation-addons-for-elementor' ) )
						->set_meta( [ 'layout' => 'custom' ] ),
				] ),

			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Accordion Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Switch_Control::bind_to( 'faq_schema' )
						->set_label( __( 'FAQ Schema', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'behavior_settings' )
				->set_label( __( 'Behavior', 'animation-addons-for-elementor' ) )
				->set_items( [
					Toggle_Control::bind_to( 'default_state' )
						->set_label( __( 'Default State', 'animation-addons-for-elementor' ) )
						->add_options( [
							'first' => [ 'title' => __( 'First Open', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-plus' ],
							'none'  => [ 'title' => __( 'All Closed', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-minus' ],
						] )
						->set_exclusive( true )
						->set_convert_options( true ),

					Toggle_Control::bind_to( 'max_items_expanded' )
						->set_label( __( 'Max Items Expanded', 'animation-addons-for-elementor' ) )
						->add_options( [
							'one'      => [ 'title' => __( 'One', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-number-1' ],
							'multiple' => [ 'title' => __( 'Multiple', 'animation-addons-for-elementor' ), 'atomic-icon' => 'eicon-copy' ],
						] )
						->set_exclusive( true )
						->set_convert_options( true ),

					Number_Control::bind_to( 'animation_duration.size' )
						->set_label( __( 'Animation Speed (ms)', 'animation-addons-for-elementor' ) ),
				] ),

			// ① Shared Lightbox controls — one call, identical to every other
			// widget. Enabling this auto-groups every image inside the accordion
			// into a single navigable gallery.
			Lightbox_Manager::register_lightbox_controls( [
				'label' => __( 'Lightbox (Item Images)', 'animation-addons-for-elementor' ),
			] ),
		];
	}

	protected function define_base_styles(): array {
		$wrapper_styles = [
			'display' => String_Prop_Type::generate( 'flex' ),
			'flex-direction' => String_Prop_Type::generate( 'column' ),
			'width' => String_Prop_Type::generate( '100%' ),
			'gap' => Size_Prop_Type::generate( [
				'size' => 10,
				'unit' => 'px',
			] ),
		];

		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( $wrapper_styles ) ),
		];
	}

	protected function define_default_children() {
		return [
			AAE_A_Accordion_Item::generate()
				->editor_settings( [ 'title' => 'Accordion Item 1' ] )
				->build(),
			AAE_A_Accordion_Item::generate()
				->editor_settings( [ 'title' => 'Accordion Item 2' ] )
				->build(),
			AAE_A_Accordion_Item::generate()
				->editor_settings( [ 'title' => 'Accordion Item 3' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types() {
		return [ 'e-aae-a-accordion-item' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-accordion' => __DIR__ . '/aae-a-accordion.html.twig',
		];
	}

	protected function build_template_context(): array {
		$context = $this->build_base_template_context();
		$settings = $this->get_atomic_settings();

		// ②+③ Lightbox: when enabled, auto-group every image inside the
		// accordion. We publish a config for each descendant image (keyed by
		// that image's own interaction id, so the rendered <img> becomes the
		// trigger) and stamp the group id on the wrapper — the runtime's
		// group-fallback then binds all of them into one gallery.
		if ( Lightbox_Manager::is_enabled( $settings ) ) {
			$group = 'aae-acc-' . $this->get_id();
			$context['lb_group'] = $group;

			$this->publish_lightbox_for_images( $this, $settings, $group );
		}

		if ( ! empty( $settings['faq_schema'] ) ) {
			$faq_data = [
				'@context' => 'https://schema.org',
				'@type' => 'FAQPage',
				'mainEntity' => [],
			];

			$children = $this->get_children();
			foreach ( $children as $child ) {
				$child_settings = $child->get_atomic_settings();
				$title = $child_settings['item_title'] ?? '';
				
				ob_start();
				$grand_children = $child->get_children();
				foreach ( $grand_children as $grandchild ) {
					$grandchild->print_element();
				}
				$answer_html = ob_get_clean();
				
				$answer_text = wp_strip_all_tags( $answer_html );

				if ( $title && $answer_text ) {
					$faq_data['mainEntity'][] = [
						'@type' => 'Question',
						'name' => wp_strip_all_tags( $title ),
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text' => trim( $answer_text ),
						],
					];
				}
			}

			if ( ! empty( $faq_data['mainEntity'] ) ) {
				$context['faq_schema_json'] = wp_json_encode( $faq_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			}
		}

		return $context;
	}

	/**
	 * Recursively walk descendant elements; for every image element, publish a
	 * Lightbox config keyed by that element's id and add it to the shared group.
	 * Runs during render, so InteractionsMap + on-demand enqueue happen exactly
	 * as they do for a standalone core image.
	 *
	 * @param object $element  Element whose children to walk.
	 * @param array  $settings Accordion settings (for title/caption/anim defaults).
	 * @param string $group    Gallery id shared by all images in this accordion.
	 */
	private function publish_lightbox_for_images( $element, array $settings, string $group ): void {
		if ( ! method_exists( $element, 'get_children' ) ) {
			return;
		}

		foreach ( $element->get_children() as $child ) {
			if ( ! is_object( $child ) || ! method_exists( $child, 'get_element_type' ) ) {
				continue;
			}

			$type = $child->get_element_type();
			$is_image = ( 'e-image' === $type || 'e-aae-a-post-image' === $type );

			if ( $is_image ) {
				$cs  = method_exists( $child, 'get_settings' ) ? $child->get_settings() : [];
				$url = $this->image_url_from_settings( $cs );
				// Key by the interaction id the child renders as data-interaction-id
				// (origin_id ?? get_id()), not get_id() — they differ inside
				// components/templates and the runtime looks up by data-interaction-id.
				$cid = '';
				if ( method_exists( $child, 'get_interaction_id' ) ) {
					try { $cid = (string) $child->get_interaction_id(); } catch ( \Throwable $e ) { $cid = ''; }
				}
				if ( '' === $cid && isset( $child->origin_id ) && is_string( $child->origin_id ) ) {
					$cid = $child->origin_id;
				}
				if ( '' === $cid && method_exists( $child, 'get_id' ) ) {
					$cid = (string) $child->get_id();
				}

				if ( '' !== $url && '' !== $cid ) {
					// Reuse the accordion's own settings so the enable gate,
					// title/caption defaults and animation flavour apply.
					Lightbox_Manager::get_attributes(
						$settings,
						[
							'src'     => $url,
							'gallery' => $group,
							'type'    => 'image',
						],
						$cid
					);
				}
			}

			// Recurse into containers (accordion item, div-block, etc.).
			$this->publish_lightbox_for_images( $child, $settings, $group );
		}
	}

	/** Best-effort full-size URL from an atomic image element's settings. */
	private function image_url_from_settings( array $cs ): string {
		$unwrap = static function ( $v ) {
			if ( is_array( $v ) && array_key_exists( 'value', $v ) && array_key_exists( '$$type', $v ) ) {
				return $v['value'];
			}
			return $v;
		};

		// Image_Prop_Type shape: { src: { id, url }, size }.
		$image = $unwrap( $cs['image'] ?? ( $cs['src'] ?? null ) );
		if ( ! is_array( $image ) ) {
			return '';
		}
		$src = $unwrap( $image['src'] ?? null );
		if ( ! is_array( $src ) ) {
			$src = $image;
		}

		// Each leaf is itself a { $$type, value } envelope — unwrap id/url too.
		$id_field = $unwrap( $src['id'] ?? null );
		if ( is_numeric( $id_field ) ) {
			$full = wp_get_attachment_image_url( (int) $id_field, 'full' );
			if ( $full ) {
				return (string) $full;
			}
		}

		$url = $unwrap( $src['url'] ?? null );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
		if ( is_array( $url ) && ! empty( $url['url'] ) ) {
			return (string) $url['url'];
		}
		return '';
	}

	public function get_script_depends(): array {
		return [ 'aae-a-accordion-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-accordion-css' ];
	}
}
