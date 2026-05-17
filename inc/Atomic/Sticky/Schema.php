<?php

namespace WCF_ADDONS\Atomic\Sticky;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Schema {

    const STICKY_ENABLE              = 'aae_sticky_enable';

    const STICKY_PIN_TRIGGER         = 'aae_sticky_pin_trigger';
    const STICKY_CUSTOM_PIN_AREA     = 'aae_sticky_custom_pin_area';

    const STICKY_PIN_END_TRIGGER     = 'aae_sticky_pin_end_trigger';
    const STICKY_CUSTOM_PIN_END_AREA = 'aae_sticky_custom_pin_end_area';

    public function register(): void {

        add_filter(
            'elementor/atomic-widgets/props-schema',
            [ $this, 'add_sticky_props' ]
        );
    }

    public function add_sticky_props( array $schema ): array {

        if ( ! class_exists( Boolean_Prop_Type::class ) ) {
            return $schema;
        }

        /*
        |--------------------------------------------------------------------------
        | Enable Sticky
        |--------------------------------------------------------------------------
        */

        $schema[ self::STICKY_ENABLE ] =
            Boolean_Prop_Type::make()->default( false );

        /*
        |--------------------------------------------------------------------------
        | Sticky Enabled Dependency
        |--------------------------------------------------------------------------
        */

        $sticky_enabled_dependency = Dependency_Manager::make()
            ->where([
                'operator' => 'eq',
                'path'     => [ self::STICKY_ENABLE ],
                'value'    => false,
                'effect'   => 'hide',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pin Trigger
        |--------------------------------------------------------------------------
        */

        $schema[ self::STICKY_PIN_TRIGGER ] =
            String_Prop_Type::make()
                ->default( 'default' )
                ->set_dependencies( $sticky_enabled_dependency );

        /*
        |--------------------------------------------------------------------------
        | Custom Pin Area
        |--------------------------------------------------------------------------
        */

        $custom_pin_dependency = Dependency_Manager::make(
            Dependency_Manager::RELATION_OR
        )
            ->where([
                'operator' => 'eq',
                'path'     => [ self::STICKY_ENABLE ],
                'value'    => false,
                'effect'   => 'hide',
            ])
            ->where([
                'operator' => 'ne',
                'path'     => [ self::STICKY_PIN_TRIGGER ],
                'value'    => 'custom',
                'effect'   => 'hide',
            ])
            ->get();

        $schema[ self::STICKY_CUSTOM_PIN_AREA ] =
            String_Prop_Type::make()
                ->default( '' )
                ->set_dependencies( $custom_pin_dependency );

        /*
        |--------------------------------------------------------------------------
        | Pin End Trigger
        |--------------------------------------------------------------------------
        */

        $schema[ self::STICKY_PIN_END_TRIGGER ] =
            String_Prop_Type::make()
                ->default( 'default' )
                ->set_dependencies( $sticky_enabled_dependency );

        /*
        |--------------------------------------------------------------------------
        | Custom Pin End Area
        |--------------------------------------------------------------------------
        */

        $custom_pin_end_dependency = Dependency_Manager::make(
            Dependency_Manager::RELATION_OR
        )
            ->where([
                'operator' => 'eq',
                'path'     => [ self::STICKY_ENABLE ],
                'value'    => false,
                'effect'   => 'hide',
            ])
            ->where([
                'operator' => 'ne',
                'path'     => [ self::STICKY_PIN_END_TRIGGER ],
                'value'    => 'custom',
                'effect'   => 'hide',
            ])
            ->get();

        $schema[ self::STICKY_CUSTOM_PIN_END_AREA ] =
            String_Prop_Type::make()
                ->default( '' )
                ->set_dependencies( $custom_pin_end_dependency );

        return $schema;
    }
}