<?php

namespace WCF_ADDONS\Atomic\WrapperLink;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if (! defined('ABSPATH')) {
    exit;
}

final class Schema
{
    const LINK = 'aae_wrapper_link';
    const IS_EXTERNAL = 'aae_wrapper_link_is_external';
    const ENABLE = 'aae_wrapper_link_enable';
    const SOURCE = 'aae_wrapper_link_source';
    const SECTION_ANCHOR = 'aae_wrapper_link_anchor';

    /**
     * Elements the Wrapper Link extension applies to: the shared extension
     * targets plus the Loop Grid's per-post card. On the loop item the "Link
     * Source: Current Post" mode links each repeated card to its own post
     * (the per-instance URL rides the card's data-aae-post-url attribute,
     * since the wrapper-link runtime config is element-id-keyed and loop
     * repeats share one id).
     */
    public static function target_element_types(): array
    {
        return array_merge(
            \WCF_ADDONS\Atomic\Bootstrap::target_element_types(),
            ['e-aae-a-loop-item']
        );
    }

    public function register(): void
    {
        add_filter(
            'elementor/atomic-widgets/props-schema',
            [$this, 'add_props']
        );       
    }

    public function add_props(
        array $schema
    ): array {
        $schema[self::SECTION_ANCHOR] = Section_Anchor_Prop_Type::make()->default('');
        $schema[self::ENABLE] = Boolean_Prop_Type::make()->default(false);
        $schema[self::SOURCE] = String_Prop_Type::make()->default('custom');
        $schema[self::LINK] = String_Prop_Type::make()->default('');
        $schema[self::IS_EXTERNAL] = Boolean_Prop_Type::make()->default(false);
        
        return $schema;
    }

    
}
