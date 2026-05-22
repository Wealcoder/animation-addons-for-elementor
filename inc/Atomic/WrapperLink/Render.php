<?php
namespace WCF_ADDONS\Atomic\WrapperLink;

use WCF_ADDONS\Atomic\Bootstrap;
use WCF_ADDONS\Atomic\InteractionsMap;
use Elementor\Modules\AtomicWidgets\PropsResolver\Render_Props_Resolver;

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

		$type = $element->get_element_type();
		if ( ! in_array( $type, Bootstrap::target_element_types(), true ) ) {
			return;
		}

		if ( ! class_exists( Render_Props_Resolver::class ) ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' )
			? $element->get_settings()
			: [];

		$enabled = $settings[ Schema::ENABLE ] ?? false;
		if ( is_array( $enabled ) && isset( $enabled['value'] ) ) {
			$enabled = (bool) $enabled['value'];
		} else {
			$enabled = (bool) $enabled;
		}

		if ( ! $enabled ) {
			return;
		}

		$id = method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
		if ( '' === $id ) {
			return;
		}

		$url = $settings[ Schema::LINK ] ?? '';
		if ( is_array( $url ) && isset( $url['value'] ) ) {
			$url = (string) $url['value'];
		} else {
			$url = (string) $url;
		}

		$is_external_raw = $settings[ Schema::IS_EXTERNAL ] ?? false;
		if ( is_array( $is_external_raw ) && isset( $is_external_raw['value'] ) ) {
			$is_external = (bool) $is_external_raw['value'];
		} else {
			$is_external = (bool) $is_external_raw;
		}

		if ( empty( $url ) ) {
			return;
		}

		$config = [
			'url' => $url,
			'isExternal' => $is_external,
		];

		InteractionsMap::register( 'wrapper_link', $id, $config );

		if ( ! is_admin() ) {
			wp_enqueue_script( 'aae-effect-wrapper-link' );
		}
	}
}
