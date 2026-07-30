<?php
/**
 * AAE Post Pagination Preview — the hover preview card wrapper.
 *
 * A real, fully customizable atomic container — replaces the earlier
 * JS-templated `.aae-pp-preview-card` div. Nested as an extra child INSIDE
 * both AAE_A_Post_Pagination_Prev and AAE_A_Post_Pagination_Next (each seeds
 * its OWN instance via build_default_inner_children(), same "one class, a
 * `role` prop tells it which side" trick already used by AAE_A_Loop_Arrow's
 * `direction` prop in this same widget family) — so a user can freely
 * add/remove/restyle/reorder the pieces inside (Thumbnail/Category/Title/
 * Date/Author/Excerpt, each its own dedicated widget — see the sibling
 * class-aae-a-post-pagination-preview-*.php files) exactly like they can
 * with Prev/Next's own Icon+Label children.
 *
 * Hidden on the FRONTEND by default (fully opaque/visible in the editor, so
 * it stays selectable/styleable there) — post-pagination.js toggles
 * `.aae-pp-preview-visible` on THIS exact nested node while its parent
 * Prev/Next link is hovered/focused, and positions it near the link via
 * inline top/left (hence `position: fixed` as a real base style below).
 * Being a DESCENDANT of the `<a>`, hovering the card itself still counts as
 * hovering the link (CSS :hover propagates by DOM containment, not visual
 * position) — no separate keep-open handling needed, and clicking anywhere
 * in the card (e.g. the thumbnail) navigates like clicking the link itself.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\PostPagination;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Box_Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-aae-a-post-pagination-preview-image.php';
require_once __DIR__ . '/class-aae-a-post-pagination-preview-category.php';
require_once __DIR__ . '/class-aae-a-post-pagination-preview-title.php';
require_once __DIR__ . '/class-aae-a-post-pagination-preview-date.php';
require_once __DIR__ . '/class-aae-a-post-pagination-preview-author.php';
require_once __DIR__ . '/class-aae-a-post-pagination-preview-excerpt.php';

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Post_Pagination_Preview extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-post-pagination-preview';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-post-pagination-preview';
	}

	public function get_title() {
		return esc_html__( 'Hover Preview Card', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-navigation';
	}

	public function should_show_in_panel() {
		return false;
	}

	protected function define_allowed_child_types() {
		return [
			'e-aae-a-post-pagination-preview-image',
			'e-aae-a-post-pagination-preview-category',
			'e-aae-a-post-pagination-preview-title',
			'e-aae-a-post-pagination-preview-date',
			'e-aae-a-post-pagination-preview-author',
			'e-aae-a-post-pagination-preview-excerpt',
			'e-div-block',
			'e-flexbox',
			'e-paragraph',
			'e-heading',
			'e-svg',
			'e-image',
		];
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
		return [];
	}

	/**
	 * Seeds one Preview wrapper's default pieces for the given side — called
	 * by BOTH AAE_A_Post_Pagination_Prev and AAE_A_Post_Pagination_Next with
	 * 'prev'/'next' respectively, so each leaf piece's `role` prop tells it
	 * which Render_Context key (prev/next) to read.
	 */
	public static function build_default_inner_children( string $role ): array {
		return [
			AAE_A_Post_Pagination_Preview_Image::generate()
				->editor_settings( [ 'title' => 'Thumbnail' ] )
				->settings( [ 'role' => [ '$$type' => 'string', 'value' => $role ] ] )
				->build(),
			AAE_A_Post_Pagination_Preview_Category::generate()
				->editor_settings( [ 'title' => 'Category' ] )
				->settings( [ 'role' => [ '$$type' => 'string', 'value' => $role ] ] )
				->build(),
			AAE_A_Post_Pagination_Preview_Title::generate()
				->editor_settings( [ 'title' => 'Title' ] )
				->settings( [ 'role' => [ '$$type' => 'string', 'value' => $role ] ] )
				->build(),
			AAE_A_Post_Pagination_Preview_Date::generate()
				->editor_settings( [ 'title' => 'Date' ] )
				->settings( [ 'role' => [ '$$type' => 'string', 'value' => $role ] ] )
				->build(),
			AAE_A_Post_Pagination_Preview_Author::generate()
				->editor_settings( [ 'title' => 'Author' ] )
				->settings( [ 'role' => [ '$$type' => 'string', 'value' => $role ] ] )
				->build(),
			AAE_A_Post_Pagination_Preview_Excerpt::generate()
				->editor_settings( [ 'title' => 'Excerpt' ] )
				->settings( [ 'role' => [ '$$type' => 'string', 'value' => $role ] ] )
				->build(),
		];
	}

	/**
	 * Only the constant box (position/size/background/shadow/padding) lives
	 * here — the show/hide opacity+visibility+transform fade and its
	 * `.aae-pp-preview-visible` toggle stay in post-pagination.scss, since
	 * Style_Variant has no way to express "only when this JS-added class is
	 * present" (same reasoning as the Loader's display:none/flex toggle).
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'position', String_Prop_Type::generate( 'fixed' ) )
					->add_prop( 'z-index', Number_Prop_Type::generate( 100000 ) )
					->add_prop( 'width', Size_Prop_Type::generate( [ 'size' => 260, 'unit' => 'px' ] ) )
					->add_prop( 'max-width', Size_Prop_Type::generate( [ 'size' => 'calc(100vw - 20px)', 'unit' => 'custom' ] ) )
					->add_prop( 'background', Background_Prop_Type::generate( [ 'color' => Color_Prop_Type::generate( '#ffffff' ) ] ) )
					->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ) )
					->add_prop( 'padding', Dimensions_Prop_Type::generate( [
						'block-start'  => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
						'inline-end'   => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
						'block-end'    => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
						'inline-start' => Size_Prop_Type::generate( [ 'size' => 12, 'unit' => 'px' ] ),
					] ) )
					->add_prop( 'box-shadow', Box_Shadow_Prop_Type::generate( [
						Shadow_Prop_Type::generate( [
							'hOffset' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'vOffset' => Size_Prop_Type::generate( [ 'size' => 8, 'unit' => 'px' ] ),
							'blur'    => Size_Prop_Type::generate( [ 'size' => 24, 'unit' => 'px' ] ),
							'spread'  => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'color'   => Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.16)' ),
						] ),
					] ) )
					->add_prop( 'text-align', String_Prop_Type::generate( 'left' ) )
			),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-pagination-preview' => __DIR__ . '/aae-a-post-pagination-preview.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-post-pagination-css' ];
	}

	protected function build_template_context(): array {
		return $this->build_base_template_context();
	}
}
