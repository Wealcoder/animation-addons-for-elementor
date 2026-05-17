<?php
namespace WCF_ADDONS\Atomic\Sticky;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Render {

    public function register(): void {
        add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
    }

    public function maybe_register( $element ): void {
        if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
            return;
        }

        if ( ! in_array( $element->get_element_type(), Bootstrap::target_element_types(), true ) ) {
            return;
        }

        $settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];
        if ( ! $this->is_sticky_enabled( $settings ) ) {
            return;
        }

        $id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
        if ( '' === $id ) {
            return;
        }

        InteractionsMap::register( 'sticky', $id, $this->build_config( $settings ) );
    }

    private function is_sticky_enabled( array $settings ): bool {
        return (bool) ( $settings[ Schema::STICKY_ENABLE ] ?? false );
    }

    private function build_config( array $settings ): array {
        return [
            'enabled' => true,
        ];
    }
}
