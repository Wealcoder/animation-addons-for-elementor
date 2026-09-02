<?php
/**
 * AAE Loop Item — atomic container widget.
 *
 * Provides a default flexbox container with column direction for Loop Grid items.
 * This widget is used as the wrapper for each loop iteration, ensuring a consistent
 * layout that can be styled via the Elementor UI.
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Link_Control;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( '\\Elementor\\Modules\\AtomicWidgets\\Elements\\Base\\Atomic_Element_Base' ) ) {
    return;
}

class AAE_A_Loop_Item extends Atomic_Element_Base {
    use Has_Element_Template;

    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data, $args );
        $this->meta( 'is_container', true );
    }

    public static function get_type() {
        return 'e-aae-a-loop-item';
    }

    public static function get_element_type(): string {
        return 'e-aae-a-loop-item';
    }

    public function get_title() {
        return esc_html__( 'Loop Item', 'animation-addons-for-elementor' );
    }

    public function get_icon() {
        // Use a generic container icon.
        return 'eicon-container';
    }

    public function show_in_panel() {
        return false;
    }

    public function should_show_in_panel() {
        return false;
    }

    protected static function define_props_schema(): array {
        return [
            // Snapshot of this element's own full model (JSON), captured by the
            // JS preset-apply engine the first time a preset is applied — see
            // preset-apply.js's SNAPSHOT_REVERT_TYPES / "Reset to Default".
            'aae_preset_snapshot' => String_Prop_Type::make()->default( '' ),

            'classes'    => Classes_Prop_Type::make()->default( [] ),
            'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
            'link'       => Link_Prop_Type::make(),
        ];
    }

    protected function define_atomic_controls(): array {
        // Preset-picker element control. Presets keyed to `e-aae-a-loop-item`
        // (see Widgets/LoopGrid/presets/) show here and replace the selected
        // item on pick. Each widget carries its own copy of this stub class.
        require_once __DIR__ . '/class-aae-a-preset-picker-control.php';

        return [
            Section::make()
                ->set_label( __( 'Presets', 'animation-addons-for-elementor' ) )
                ->set_id( 'aae_presets' )
                ->set_items( [
                    AAE_A_Preset_Picker_Control::make()
                        ->set_label( __( 'Apply Preset', 'animation-addons-for-elementor' ) )
                        ->set_meta( [ 'layout' => 'custom' ] ),
                ] ),

            Section::make()
                ->set_id( 'settings' )
                ->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
                ->set_items( [
                    Link_Control::bind_to( 'link' )
                        ->set_label( __( 'Wrapper Link', 'animation-addons-for-elementor' ) )
                        ->set_placeholder( __( 'Type or paste your URL', 'animation-addons-for-elementor' ) ),
                ] ),
        ];
    }

    protected function define_default_children(): array {
        // Loop Item does not seed any default children.
        return [];
    }

    /**
     * Default card sizing: each Loop Item is now a CSS-grid child of the Loop
     * Layout (display:grid; grid-template-columns), so column sizing is owned
     * by the parent's grid-template-columns rather than a per-item flex-basis.
     * Still a flex column internally so the card's own content (image/title/
     * etc.) stacks top-to-bottom.
     *
     * IMPORTANT: the atomic style schema has NO `flex-grow` / `flex-shrink` /
     * `flex-basis` keys — only the `flex` shorthand (Flex_Prop_Type, see
     * elementor style-schema.php). Likewise `height` must be a Size, not a
     * String. Unknown/mistyped keys make the whole Style_Definition fail
     * validation, so NONE of the props emit ("define_base_styles kaj kore na").
     */
    protected function define_base_styles(): array {
        return [
            'base' => Style_Definition::make()
                ->add_variant(
                    Style_Variant::make()
                        ->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
                        ->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
                        ->add_prop( 'height', Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ) )
                        ->add_prop( 'padding', Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ) )
                )
        ];
    }

    protected function set_initial_state(): void {
        parent::set_initial_state();
    }

    protected function get_templates(): array {
        return [
            'elementor/elements/aae-a-loop-item' => __DIR__ . '/aae-a-loop-item.html.twig',
        ];
    }

    /**
     * Repeat the WHOLE loop-item card once per queried post.
     *
     * The Loop Grid root publishes its query on the Render_Context stack (keyed
     * by AAE_A_Loop_Grid::class). Here we read it, run the WP_Query, and render
     * this element's full twig once per post — each Loop Item div becomes a
     * direct flex child of the Loop Layout, so the atomic current-post widgets
     * resolve per post. Repeating at THIS level (not the root) means
     * non-repeating siblings of the layout (e.g. Pagination) render only once.
     *
     * In the editor / when there's no query context (item edited in isolation),
     * fall back to a single normal render so the card is still editable.
     */
    public function print_content() {
        $ctx = \Elementor\Modules\AtomicWidgets\Elements\Base\Render_Context::get(
            \WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid\AAE_A_Loop_Grid::class
        );

        // No query context (item edited in isolation) — render once via the
        // trait's Twig pipeline so the item's own div + atomic style class emit.
        if ( empty( $ctx ) || empty( $ctx['query_args'] ) ) {
            $this->render();
            return;
        }

        $query = new \WP_Query( $ctx['query_args'] );

        if ( ! $query->have_posts() ) {
            echo '<div class="aae-a-loop-grid-empty">'
                . esc_html__( 'No posts found.', 'animation-addons-for-elementor' )
                . '</div>';
            return;
        }

        // Repeat the WHOLE card once per post. Each iteration runs the Loop Item's
        // Twig template (render()) — NOT parent::print_content(), which is the bare
        // Element_Base loop-children method and would skip the item's own wrapper
        // div, dropping its atomic style class (background etc.) on the frontend.
        // The Loop Item's own Twig div is the direct flex child of Loop Layout —
        // no intermediary wrapper is needed.
        while ( $query->have_posts() ) {
            $query->the_post();

            ob_start();
            $this->render();
            echo self::ensure_image_alt( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput -- re-emitting Elementor's own rendered output, only an alt attribute is added.
        }
        wp_reset_postdata();
    }

    /**
     * Fill in `alt` on any `<img>` in $html that has NO alt attribute at all —
     * e.g. a native e-image widget inside a card design (local or remote
     * preset) whose src is bound to this post's featured image but whose own
     * `alt` prop was left empty. AAE_A_Post_Image already resolves a real alt
     * from attachment meta on every render (see get_atomic_settings()); this
     * is the safety net for every OTHER image type a design can drop into a
     * Loop Item, since we do not control what a (possibly remote) preset's
     * model contains. Falls back to the post title — the one thing true of
     * every image inside this specific card, whatever the design.
     *
     * `alt=""` is left completely alone: an explicitly empty alt is the
     * correct, deliberate way to mark an image as decorative (and is not
     * what axe's image-alt rule flags) — only a MISSING attribute is fixed.
     *
     * Regex rather than DOMDocument, on purpose: DOMDocument needs a full
     * parse-and-serialise of a fragment, which normalises markup and mangles
     * HTML5 void elements — a destructive amount of change for adding one
     * attribute. Mirrors Skip_Lazy::mark_images() in the Pro plugin, which
     * solves the identical "add one attribute to every <img> in this
     * fragment" problem the same way.
     */
    private static function ensure_image_alt( string $html ): string {
        if ( '' === $html || false === stripos( $html, '<img' ) ) {
            return $html;
        }

        $title = get_the_title();
        if ( '' === $title ) {
            $title = __( 'Post image', 'animation-addons-for-elementor' );
        }
        $alt = esc_attr( $title );

        $out = preg_replace_callback(
            '/<img\b[^>]*>/i',
            static function ( array $m ) use ( $alt ): string {
                $tag = $m[0];

                if ( preg_match( '/\balt\s*=/i', $tag ) ) {
                    return $tag; // Already has one, empty or not — leave it.
                }

                // Plain string surgery, NOT preg_replace, for this half: $alt
                // is untrusted (the post title) and preg_replace's REPLACEMENT
                // argument treats a literal "$1" or a trailing backslash in it
                // as a backreference/escape — a title like "Save $1 Today"
                // would silently corrupt the inserted attribute. Detecting the
                // void-tag `/>` is a plain string check, so no regex is needed
                // for that part either.
                $is_void   = '/>' === substr( $tag, -2 );
                $close_len = $is_void ? 2 : 1;
                $insert    = ' alt="' . $alt . '"';

                return substr( $tag, 0, -$close_len ) . $insert . substr( $tag, -$close_len );
            },
            $html
        );

        // preg_replace_callback returns null on failure (catastrophic
        // backtracking, PCRE limits on a very large card). Returning null
        // here would blank the whole card — emit the original instead. A
        // missing alt is a11y debt; an empty card is a broken page.
        return null === $out ? $html : $out;
    }
}
?>
