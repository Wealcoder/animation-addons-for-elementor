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
 *      `style="color:…"` never survives a save. `class` and `id` are allowed
 *      too, so a typed hook class reaches the page and Custom CSS can target
 *      it. `content_html` uses the SAME prop type, for the same reason.
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
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
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
		// and only widens the whitelist — `style`, `class` and `id` included.
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

			// DERIVED, never edited directly and given no control. Holds the
			// interpreted form of `content` — typed tags decoded, block tags
			// unwrapped — because that is what the Twig renders.
			//
			// It exists purely so the editor CANVAS is right: the canvas runs the
			// Twig client-side off the model and never executes PHP, so a
			// PHP-only transform left the preview showing raw angle brackets while
			// the published page showed the heading. InlineTextControl writes this
			// alongside every commit.
			//
			// NOT trusted. get_atomic_settings() recomputes it from `content` on
			// every render and overwrites whatever is stored, so a value crafted in
			// the database — or one left stale by an older build — can never reach
			// the page.
			//
			// MUST be the rich-text prop type, NOT String_Prop_Type. A string prop
			// sanitises with sanitize_text_field(), which strips EVERY tag — so the
			// value the control wrote was saved as bare text and the canvas lost all
			// formatting on the next reload while the front end stayed correct
			// (PHP recomputes, so only the preview could show it). Same wp_kses
			// whitelist as `content`, so the two cannot disagree about what an
			// allowed tag is.
			'content_html' => AAE_Rich_Text_Prop_Type::make()
				->default( [
					'content'  => String_Prop_Type::generate( '' ),
					'children' => [],
				] ),
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
						->set_description( __( 'Switches to div automatically when the Content contains a block-level tag you typed (h1-h6, p, div, ul, blockquote…). A heading cannot legally contain another heading — the browser would close this one early and push the rest of your text outside the element.', 'animation-addons-for-elementor' ) )
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

	/**
	 * One prop only: `margin: 0`, exactly what Elementor's own e-heading ships
	 * (Atomic_Heading::define_base_styles()).
	 *
	 * This widget deliberately carried NO base styles at all, on the principle
	 * that the plugin ships zero CSS for it. That reads well until you drop one:
	 * the element renders as a real h1-h6, so it inherits the THEME's heading
	 * margin, and an Advanced Heading sat with a margin a core Heading next to it
	 * did not have — same panel, same tag, different spacing, and the Margin
	 * fields showed empty rather than the `0` a core heading shows. Matching core
	 * is what makes the two interchangeable.
	 *
	 * Same shape as core: the `margin` schema key is a Union of Dimensions and
	 * Size, and core passes the Size shorthand, which is also what populates all
	 * four Margin placeholders in the panel with `0`.
	 *
	 * A base style is the right home for this rather than the twig or a
	 * stylesheet: it stays fully overridable from the Style tab, and it needs the
	 * `base` key specifically because the twig renders `base_styles.base` onto the
	 * root — without that class in the markup this definition would compile to CSS
	 * that matches nothing.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'margin', Size_Prop_Type::generate( [
							'unit' => 'px',
							'size' => 0,
						] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-advanced-heading' => __DIR__ . '/aae-a-advanced-heading.html.twig',
		];
	}

	/**
	 * Interpret the stored source at RENDER time.
	 *
	 * The panel stores the content EXACTLY as typed, so markup the user wrote by
	 * hand is still sitting there as escaped text (`&lt;h2&gt;`). That is what
	 * keeps the field re-editable. This method turns it into output: tags the user
	 * typed are decoded into real elements and rendered AS TYPED — an `<h2>` comes
	 * out as an `<h2>`, keeping its class and style.
	 *
	 * Only tags on the prop type's whitelist are decoded. Anything else stays
	 * visible as literal text, which TELLS the user it was not applied instead of
	 * silently deleting what they wrote.
	 *
	 * An earlier revision unwrapped block tags into <span> here, so that a typed
	 * `<h2>` could not nest inside the widget's own `<h2>` wrapper. That was
	 * removed on request: the typed tag is now honoured verbatim. The nesting is
	 * real — set the widget's own HTML Tag to `div` to avoid emitting a heading
	 * inside a heading.
	 *
	 * Re-sanitised afterwards: decoding turns text into markup, so the wp_kses pass
	 * that ran on save no longer covers it. Without this a crafted value could
	 * arrive escaped, decode into something the whitelist would have refused, and
	 * reach the page.
	 *
	 * The result is written to `content_html`, which is what the Twig renders —
	 * `content` itself is left untouched so the panel keeps showing the verbatim
	 * source.
	 *
	 * ALWAYS RECOMPUTED, never read. InlineTextControl also writes `content_html`
	 * so the editor canvas (which runs this Twig client-side and never executes
	 * PHP) previews correctly. Overwriting it here is what keeps that a cache
	 * rather than a second source of truth: a stale value from an older build, or
	 * one crafted directly in the database, cannot reach the page.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$source = isset( $settings['content'] ) && is_string( $settings['content'] )
			? $settings['content']
			: '';

		$settings['content_html'] = self::interpret_source( $source );

		return $settings;
	}

	private static function interpret_source( string $html ): string {
		$decoded = self::collapse_spaces( self::decode_typed_tags( $html ) );

		return wp_kses( $decoded, AAE_Rich_Text_Prop_Type::allowed_tags() );
	}

	/**
	 * Collapse every run of spaces to ONE, in the RENDERED value only.
	 *
	 * MUST MIRROR collapseSpaces() in InlineTextControl.jsx — that one feeds the
	 * editor canvas, this one the front end, and the two disagreeing shows up as a
	 * preview that does not match the published page.
	 *
	 * The `content` prop keeps whatever was typed, so the panel field can be laid
	 * out for readability; only this derived value is normalised, so the heading
	 * never inherits those gaps.
	 *
	 * NBSP is collapsed alongside the plain space. Plain runs would fold on their
	 * own — HTML collapses whitespace — but a contenteditable inserts U+00A0 for
	 * consecutive spaces and those DO render, which is what put multiple spaces on
	 * the page in the first place. Both the entity (`&nbsp;`) and the raw UTF-8
	 * character are matched, because the editor produces both.
	 *
	 * Splitting on tags first keeps this out of ATTRIBUTE values: collapsing the
	 * spaces inside `class="a  b"` or a `style` rule would silently change what a
	 * selector matches. PREG_SPLIT_DELIM_CAPTURE puts the tags on the odd indices,
	 * so only the even ones are rewritten.
	 *
	 * @param string $html Decoded markup.
	 * @return string
	 */
	private static function collapse_spaces( string $html ): string {
		$parts = preg_split( '/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( ! is_array( $parts ) ) {
			// A PCRE limit on a very large value. An uncollapsed heading is a far
			// better outcome than an empty one.
			return $html;
		}

		foreach ( $parts as $i => $part ) {
			if ( 0 === $i % 2 ) {
				$parts[ $i ] = preg_replace( '/(?:&nbsp;|[ \t]|\xc2\xa0){2,}/i', ' ', $part );
			}
		}

		return implode( '', $parts );
	}

	/**
	 * `&lt;h2 class="x"&gt;` → `<h2 class="x">`.
	 *
	 * The attribute group is a TEMPERED match — "any character that does not begin
	 * `&gt;`" — not a negated class. `[^&]` was the obvious spelling and is wrong:
	 * it also rejects a legitimate `&amp;` inside an attribute, so
	 * `<a href="/x?a=1&amp;b=2">` would silently fail to decode. Mirrors
	 * TYPED_TAG_RE in InlineTextControl.jsx.
	 */
	private static function decode_typed_tags( string $html ): string {
		$tags = implode( '|', array_map( 'preg_quote', array_keys( AAE_Rich_Text_Prop_Type::allowed_tags() ) ) );

		return preg_replace_callback(
			'/&lt;(\/?)(' . $tags . ')((?:\s(?:(?!&gt;).)*?)?)(\/?)&gt;/i',
			static function ( $m ) {
				// Only ampersands need undoing: inside a text node the browser
				// escapes `&` but leaves quotes alone.
				return '<' . $m[1] . $m[2] . str_replace( '&amp;', '&', $m[3] ) . $m[4] . '>';
			},
			$html
		) ?? $html; // preg_* returns null past a PCRE limit — keep the original.
	}

}
