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
    const SECTION_ANCHOR = 'aae_wrapper_link_anchor';

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
        $schema[self::LINK] = String_Prop_Type::make()->default('');       
        $schema[self::IS_EXTERNAL] = Boolean_Prop_Type::make()->default(false);     
        
        return $schema;
    }

    
}
