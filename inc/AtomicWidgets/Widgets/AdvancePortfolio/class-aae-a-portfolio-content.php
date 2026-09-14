<?php
/**
 * AAE Advanced Portfolio -- Content.
 *
 * The v3 skin's `.content` div: the block under the thumbnail that groups the
 * post title and the date. Kept as its own element (rather than dropping both
 * straight into the item) so the pair can be spaced and aligned as a unit,
 * which is what the skin's CSS did with `.content`.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancePortfolio;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base' ) ) {
	return;
}

class AAE_A_Portfolio_Content extends Atomic_Element_Base {

	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type() {
		return 'e-aae-a-portfolio-content';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-portfolio-content';
	}

	public function get_title() {
		return esc_html__( 'Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-text-align-left';
	}

	public function show_in_panel() {
		return false;
	}

	/**
	 * The one that actually hides an ELEMENT type from the panel.
	 *
	 * show_in_panel() alone is not enough here: it is the Widget_Base hook, so
	 * it works for the leaf parts (Section Title, Date — both Atomic_Widget_Base)
	 * but is never consulted for an Atomic_Element_Base. Verified in the live
	 * editor: with only show_in_panel(), this part still arrived in
	 * elementor.widgetsCache with show_in_panel: true, while Loop Layout and
	 * Loop Item — which declare should_show_in_panel() — arrived false.
	 */
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
		return [];
	}

	/**
	 * A bare flexbox and nothing else — the same two props core `e-flexbox`
	 * ships (elementor/modules/atomic-widgets/elements/flexbox/flexbox.php),
	 * minus its opinionated padding.
	 *
	 * Design-less on purpose. An earlier revision ported v3's `.content` card
	 * wholesale — `position: absolute`, `bottom: 50px`, `width: 175px`,
	 * `background: #000`, `color: #fff`, plus a stylesheet-driven
	 * hover-slide-in reveal. That is a prebuilt look, and it is the wrong stance
	 * for this widget: it decided the design for the user, and the hover was
	 * hard-coded in CSS where nothing in the panel can reach it.
	 *
	 * The user builds that themselves, with more control than the port allowed:
	 * drop whatever children they want in here, set Position / Background /
	 * Size in the Style tab, and use the panel's own `hover` state chip for the
	 * reveal — Elementor generates the hover rule against their own class, so
	 * it composes with everything else instead of fighting a widget stylesheet.
	 *
	 * Keep this minimal. Anything cosmetic added here reappears on every
	 * existing instance of the widget and cannot be removed from the panel,
	 * only overridden.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()->add_variant(
				Style_Variant::make()
					->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
					->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
			),
		];
	}

	/**
	 * Unrestricted, like every core container: `Atomic_Element_Base` reads an
	 * empty list as "no restriction", which is exactly what core `e-flexbox`
	 * and `e-div-block` rely on (neither overrides this method at all).
	 *
	 * The previous allow-list — post title, date, heading, paragraph, flexbox,
	 * div-block — meant a button, an image, an icon or a divider could not be
	 * dropped in here at all. There is no structural reason for that: this
	 * element is a plain flex container, the render loop is the ITEM's job, and
	 * anything the user drops inside simply renders per post.
	 */
	protected function define_allowed_child_types() {
		return [];
	}

	protected function define_default_children() {
		// Seeded by the root.
		return [];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-portfolio-content' => __DIR__ . '/aae-a-portfolio-content.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-advance-portfolio-css' ];
	}
}
