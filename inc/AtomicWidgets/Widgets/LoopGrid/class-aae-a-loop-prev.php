<?php
/**
 * AAE Loop Previous — pagination "Previous" container (Pro replica).
 *
 * Structural container seeding a paragraph label. Static UI for now.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-loop-arrow.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Loop_Prev extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-loop-prev';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-loop-prev';
	}

	public function get_title() {
		return esc_html__( 'Previous', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-chevron-left';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [ 'e-paragraph', 'e-button', 'e-svg', 'e-aae-a-loop-arrow' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [];
	}

	protected function define_default_children() {
		$children = [];

		// Arrow icon — our custom Loop Arrow widget (inline chevron). Its 20px
		// size + colour come from the widget's OWN base styles (reliable, not
		// stripped like a seeded e-svg local style) and stay editable.
		if ( self::child_type_registered( 'e-aae-a-loop-arrow' ) ) {
			$children[] = self::build_arrow( 'prev', 'Prev Icon' );
		}

		// Text label.
		$children[] = [
			'elType'          => 'widget',
			'widgetType'      => 'e-paragraph',
			'settings'        => [
				'paragraph' => [
					'$$type' => 'html-v3',
					'value'  => [
						'content'  => [ '$$type' => 'string', 'value' => __( 'Prev', 'animation-addons-for-elementor' ) ],
						'children' => [],
					],
				],
				'tag'       => [ '$$type' => 'string', 'value' => 'span' ],
			],
			'editor_settings' => [ 'title' => 'Prev Label' ],
			'elements'        => [],
		];

		return $children;
	}

	private static function child_type_registered( string $type ): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		try {
			return (bool) \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $type );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Build a seeded Loop Arrow child ('prev' | 'next'). Size/colour come from
	 * the arrow widget's own base styles, so nothing here can be stripped.
	 */
	public static function build_arrow( string $direction, string $title ): array {
		return [
			'elType'          => 'widget',
			'widgetType'      => 'e-aae-a-loop-arrow',
			'settings'        => [
				'direction' => [ '$$type' => 'string', 'value' => $direction ],
			],
			'editor_settings' => [ 'title' => $title ],
			'elements'        => [],
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'inline-flex' ) )
					->add_prop( 'align-items', String_Prop_Type::generate( 'center' ) )
					->add_prop( 'gap', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 6, 'unit' => 'px' ] ) )
					->add_prop( 'cursor', String_Prop_Type::generate( 'pointer' ) )
					->add_prop( 'padding', \Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type::generate( [
						'block-start'  => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
						'block-end'    => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
						'inline-start' => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
						'inline-end'   => \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 14, 'unit' => 'px' ] ),
					] ) )
					->add_prop( 'border-width', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 1, 'unit' => 'px' ] ) )
					->add_prop( 'border-style', String_Prop_Type::generate( 'solid' ) )
					->add_prop( 'border-color', \Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type::generate( '#d5d8dc' ) )
					->add_prop( 'border-radius', \Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type::generate( [ 'size' => 4, 'unit' => 'px' ] ) )
					->add_prop( 'color', \Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type::generate( '#1a1a1a' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-loop-prev' => __DIR__ . '/aae-a-loop-nav.html.twig',
		];
	}

	protected function build_template_context(): array {
		return array_merge( $this->build_base_template_context(), [ 'nav_role' => 'prev' ] );
	}
}
