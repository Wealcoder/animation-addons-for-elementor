<?php

namespace WCF_ADDONS\Atomic\CustomCss;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

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
            ->set_label( Bootstrap::get_label( 'Custom CSS' ) )
            ->set_items([
                Text_Control::bind_to(
                    Schema::SECTION_ANCHOR
                ),
            ]);
    }
}
