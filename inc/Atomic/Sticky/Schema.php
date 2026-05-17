<?php
namespace WCF_ADDONS\Atomic\Sticky;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Section_Anchor_Prop_Type as Base_Section_Anchor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Schema {

    const STICKY_SECTION_ANCHOR = 'aae_sticky_section_anchor';
    const STICKY_ENABLE = 'aae_sticky_enable';

    public function register(): void {
        add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_sticky_props' ] );
    }

    public function add_sticky_props( array $schema ): array {
        if ( ! class_exists( Boolean_Prop_Type::class ) ) {
            return $schema;
        }

        $schema[ self::STICKY_SECTION_ANCHOR ] = Section_Anchor_Prop_Type::make()->default( '' );
        $schema[ self::STICKY_ENABLE ] = Boolean_Prop_Type::make()->default( false );

        return $schema;
    }
}
