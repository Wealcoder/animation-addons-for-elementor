<?php

namespace WCF_ADDONS\Atomic\WrapperLink;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use WCF_ADDONS\Atomic\Bootstrap;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if (! defined('ABSPATH')) {
    exit;
}

final class Controls
{

    public function register(): void
    {
        add_filter(
            'elementor/atomic-widgets/controls',
            [$this, 'inject_controls'],
            10,
            2
        );
    }

    public function inject_controls(
        array $controls,
        $element
    ) {
        if (! is_object($element) || ! method_exists($element, 'get_element_type')) {
            return $controls;
        }

        if (! class_exists(Section::class)) {
            return $controls;
        }

        if (
            ! in_array(
                $element->get_element_type(),
                Bootstrap::target_element_types(),
                true
            )
        ) {
            return $controls;
        }

        $controls[] = $this->build_section();

        return $controls;
    }

    private function build_section(): Section
    {
        return Section::make()
            ->set_label(
                Bootstrap::get_label( __( 'Wrapper Link', 'animation-addons-for-elementor' ) )
            )
            ->set_items([
                Text_Control::bind_to(
                    Schema::SECTION_ANCHOR
                ),
            ]);
}

}
