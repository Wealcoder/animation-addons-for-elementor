<?php
/**
 * AAE Advanced Heading — atomic leaf widget.
 *
 * A heading whose text is edited as RICH TEXT in the panel: the Content box
 * shows FORMATTED text, never raw markup. That was the whole reason it stopped
 * being a Textarea.
 *
 * THE THREE PIECES, and why each is ours rather than core's:
 *
 *   1. `content` is AAE_Rich_Text_Prop_Type — a subclass of core's
 *      Html_V3_Prop_Type that KEEPS THE `html-v3` KEY and only widens the
 *      wp_kses whitelist. The stock one allows no attributes at all, so
 *      `style="color:…"` never survives a save. `class` is still refused.
 *   2. The panel control is `aae-inline-text`, not core's
 *      `Inline_Editing_Control`. Core's renders the editor with NO toolbar —
 *      its format buttons live on the canvas (see 3).
 *   3. The editor is our own contenteditable, not core's TipTap `InlineEditor`.
 *      A ProseMirror schema is immutable after creation and core's hardcodes
 *      its marks, so a colour mark cannot be added; and TipTap has no generic
 *      `span` mark, so it unwraps coloured spans on parse. Full reasoning in
 *      src/modules/atomic/element-controls/InlineTextControl.jsx.
 *
 * Design-less by intent: the plugin ships NO CSS. Style the heading from the
 * Elementor Style panel; colour individual words from the Content toolbar.
 *
 * HISTORY (2026-08-04) — this widget used to own a raw-HTML string prop
 * (`AAE_Html_Rich_Prop_Type`) plus a hand-rolled toolbar on the CANVAS
 * (`src/modules/atomic/editor-bridge/advanced-heading-inline.js`). Both are
 * deleted. That toolbar's colour button was unfixable in place: it drove
 * `document.execCommand`, which only acts on the focused editable, and the
 * colour picker holds focus the whole time it is open. The replacement wraps a
 * saved Range directly, which needs no focus at all.
 *
 * Legacy content saved under the old string shape is converted on document load
 * by AAE_Advanced_Heading_Migration — do not remove that class, or every heading
 * built before this change loses its text the next time its page is saved
 * (Props_Parser::validate() erases a prop whose value fails the schema).
 *
 * NOTE — core's CANVAS inline editor does NOT attach to this widget, and cannot
 * be made to. `INLINE_EDITING_PROPERTY_PER_TYPE` in editor-canvas is a
 * hardcoded five-entry object (e-button, e-heading, e-paragraph, e-form-label,
 * e-form-submit-button) and neither it nor `registerReplacement` is exported on
 * `elementorV2.editorCanvas`. Editing happens in the panel box.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\AdvancedHeading;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
	return;
}

require_once __DIR__ . '/class-aae-inline-text-control.php';
require_once __DIR__ . '/class-aae-rich-text-prop-type.php';

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

class AAE_A_Advanced_Heading extends Atomic_Widget_Base {

	use Has_Template;

	public static $widget_description = 'Heading with rich inline text editing — bold, italic, underline, strikethrough, super/subscript and links, on any tag from h1 to span.';

	public static function get_element_type(): string {
		return 'e-aae-a-advanced-heading';
	}

	public function get_title() {
		return esc_html__( 'Advanced Heading', 'animation-addons-for-elementor' );
	}

	public function get_keywords() {
		return [ 'atomic', 'heading', 'title', 'highlight', 'html', 'span', 'advanced' ];
	}

	public function get_categories(): array {
		return ['aae-atomic-general'];
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	protected static function define_props_schema(): array {
		// Rich text. NOT the stock Html_V3_Prop_Type: that one's wp_kses pass
		// allows no attributes at all, so `style="color:…"` — the whole point
		// of the colour button — is deleted on save. The subclass keeps the
		// `html-v3` KEY (so the client util and the transformer are unchanged)
		// and only widens the whitelist. `class` stays disallowed.
		$content = AAE_Rich_Text_Prop_Type::make()
			->default( [
				'content'  => String_Prop_Type::generate(
					__( 'Build your <b>Innovate</b> Our Core Solution', 'animation-addons-for-elementor' )
				),
				'children' => [],
			] )
			->description( 'The text content of the heading.' );

		// alias() only exists on Elementor's prop-type meta concern in newer
		// atomic builds (4.2.x+). Guard it — an older Elementor throws
		// "undefined method ...::alias()" while this widget's schema is built,
		// which fatals the whole editor. The aliases are an inline-editor
		// nicety, not required for the widget to render.
		if ( method_exists( $content, 'alias' ) ) {
			$content = $content->alias( 'text', 'content', 'heading' );
		}

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// HTML tag for the heading wrapper. NOT named `tag` on purpose —
			// renaming it now would orphan every saved heading, and core's
			// canvas inline editor (the only thing that reads a `tag` key) can
			// never attach to this type anyway. See the class docblock.
			'ah_tag'  => String_Prop_Type::make()->default( 'h2' ),

			'content' => $content,
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Heading', 'animation-addons-for-elementor' ) )
				->set_id( 'content' )
				->set_items( [
					Select_Control::bind_to( 'ah_tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'h1', 'label' => 'H1' ],
							[ 'value' => 'h2', 'label' => 'H2' ],
							[ 'value' => 'h3', 'label' => 'H3' ],
							[ 'value' => 'h4', 'label' => 'H4' ],
							[ 'value' => 'h5', 'label' => 'H5' ],
							[ 'value' => 'h6', 'label' => 'H6' ],
							[ 'value' => 'div', 'label' => __( 'div', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'p', 'label' => __( 'p', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'span', 'label' => __( 'span', 'animation-addons-for-elementor' ) ],
						] ),

					// NOT core's Inline_Editing_Control: that one renders the
					// TipTap editor with no toolbar, because core's format
					// buttons live on the canvas and the canvas editor is
					// hardcoded to five core types. See the class docblock.
					AAE_Inline_Text_Control::bind_to( 'content' )
						->set_label( __( 'Content', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Type your heading here', 'animation-addons-for-elementor' ) ),
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

	// No define_base_styles() override → inherits the empty default: the plugin
	// ships ZERO CSS for this widget. Style everything via the panel / your own
	// classes.

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-advanced-heading' => __DIR__ . '/aae-a-advanced-heading.html.twig',
		];
	}
}
