<?php

namespace WCF_ADDONS\Atomic\WrapperLink;

use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;

if (! defined('ABSPATH')) {
    exit;
}

final class Schema
{
    const LINK =
    'aae_wrapper_link';

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

        $schema[self::LINK] =
            Link_Prop_Type::make();

        return $schema;
    }
}
