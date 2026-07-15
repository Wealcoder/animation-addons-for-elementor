<?php
namespace WCF_ADDONS\Atomic\Lightbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Container-level Lightbox.
 *
 * When a container (e-flexbox / e-div-block / e-grid, or any type added via the
 * `aae/lightbox/container_types` filter) has the Lightbox enabled, we publish a
 * single options bag keyed by the container's interaction id into the 'lbc'
 * interactions map. We do NOT resolve child images server-side — the runtime
 * discovers every eligible child from the DOM at click time (via one delegated
 * document listener), which means dynamically-added content (Loop Grid, AJAX,
 * infinite scroll, nested containers) works with zero re-initialization.
 *
 * The container's data-interaction-id is emitted by Elementor's atomic base on
 * every container wrapper, so the runtime maps a click → nearest ancestor with
 * a matching 'lbc' entry → that container's options + its child gallery.
 *
 * This coexists with the per-element {@see Render} path: a standalone e-image
 * keeps its own 'lb' entry; a container publishes an 'lbc' entry. The runtime
 * supports triggers from either source.
 */
final class Container_Render {

	public function register(): void {
		add_action( 'elementor/frontend/before_render', [ $this, 'maybe_register' ] );
	}

	public function maybe_register( $element ): void {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_element_type' ) ) {
			return;
		}

		if ( ! in_array( $element->get_element_type(), Schema::lightbox_containers(), true ) ) {
			return;
		}

		$settings = method_exists( $element, 'get_settings' ) ? $element->get_settings() : [];

		// TEMP DEBUG — remove after diagnosis.
		if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
			error_log( '[AAE-LBC] container seen: ' . $element->get_element_type()
				. ' enable_raw=' . wp_json_encode( $settings[ Schema::LB_ENABLE ] ?? '(unset)' )
				. ' mode_raw=' . wp_json_encode( $settings[ Schema::LB_CONTAINER_MODE ] ?? '(unset)' ) );
		}

		if ( ! Lightbox_Manager::is_enabled( $settings ) ) {
			if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
				error_log( '[AAE-LBC] BAILED: is_enabled() false' );
			}
			return;
		}

		$iid = $this->interaction_id( $element );
		if ( '' === $iid ) {
			if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
				error_log( '[AAE-LBC] BAILED: empty interaction id' );
			}
			return;
		}

		$mode     = Lightbox_Manager::string( $settings, Schema::LB_CONTAINER_MODE, 'images' );
		$mode     = ( 'content' === $mode ) ? 'content' : 'images';
		$selector = Lightbox_Manager::string( $settings, Schema::LB_CHILD_SELECTOR, '' );
		$group    = Lightbox_Manager::string( $settings, Schema::LB_GROUP, '' );

		// Default selector differs per mode: images mode scans for image nodes;
		// content mode leaves it blank (runtime uses direct children).
		$default_selector = ( 'content' === $mode ) ? '' : Schema::DEFAULT_CHILD_SELECTOR;

		$options = [
			// Discovery.
			'mode'       => $mode,
			'selector'   => '' !== $selector ? $selector : $default_selector,
			// Grouping: explicit id, else auto (runtime derives from container id).
			'group'      => '' !== $group ? $group : ( 'aae-lbc-' . $iid ),
			'captionSrc' => Lightbox_Manager::string( $settings, Schema::LB_CAPTION_SRC, 'none' ),
			// Chrome / behaviour.
			'anim'       => Lightbox_Manager::string( $settings, Schema::LB_ANIM, 'zoom' ),
			'zoom'       => Lightbox_Manager::bool( $settings, Schema::LB_ZOOM, true ),
			'loop'       => Lightbox_Manager::bool( $settings, Schema::LB_LOOP, true ),
			'download'   => Lightbox_Manager::bool( $settings, Schema::LB_DOWNLOAD, false ),
			'counter'    => Lightbox_Manager::bool( $settings, Schema::LB_COUNTER, true ),
		];

		/**
		 * Filter a container's Lightbox options before they're published.
		 *
		 * @param array  $options  The option bag.
		 * @param object $element  The container element.
		 */
		$options = apply_filters( 'aae/lightbox/container_options', $options, $element );

		if ( defined( 'AAE_LB_DEBUG' ) && AAE_LB_DEBUG ) {
			error_log( '[AAE-LBC] OK publishing. iid=' . $iid . ' mode=' . $mode . ' selector=' . $options['selector'] );
		}

		Lightbox_Manager::register_container( $iid, $options );
	}

	/**
	 * Resolve the value Elementor renders as data-interaction-id:
	 * get_interaction_id() === origin_id ?? get_id(). Public on atomic widgets,
	 * protected on the element base — try it, then origin_id, then get_id().
	 */
	private function interaction_id( $element ): string {
		if ( method_exists( $element, 'get_interaction_id' ) ) {
			try {
				$iid = $element->get_interaction_id();
				if ( is_string( $iid ) && '' !== $iid ) {
					return $iid;
				}
			} catch ( \Throwable $e ) {
				// protected in this context — fall through.
			}
		}
		if ( isset( $element->origin_id ) && is_string( $element->origin_id ) && '' !== $element->origin_id ) {
			return $element->origin_id;
		}
		return method_exists( $element, 'get_id' ) ? (string) $element->get_id() : '';
	}
}
