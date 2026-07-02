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
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
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
            'classes'    => Classes_Prop_Type::make()->default( [] ),
            'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
        ];
    }

    protected function define_atomic_controls(): array {
        // No custom controls needed beyond the base.
        return [];
    }

    protected function define_default_children(): array {
        // Loop Item does not seed any default children.
        return [];
    }

    protected function define_base_styles(): array {
        return [
            'base' => Style_Definition::make()
                ->add_variant(
                    Style_Variant::make()
                        ->add_prop( 'display', String_Prop_Type::generate( 'flex' ) )
                        ->add_prop( 'flex-direction', String_Prop_Type::generate( 'column' ) )
                        ->add_prop( 'height', String_Prop_Type::generate( '100%' ) )
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
            $this->render();
        }
        wp_reset_postdata();
    }
}
?>
