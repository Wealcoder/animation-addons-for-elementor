<?php

namespace WCF_ADDONS;

defined( 'ABSPATH' ) || exit;

use Elementor\Core\Common\Modules\Ajax\Module as Ajax;

class Layout_Import_Api {
	public function __construct() {
		add_action( 'elementor/ajax/register_actions', array( $this, 'register_ajax_actions' ) );
	}

	public function register_ajax_actions( Ajax $ajax ) {
		$ajax->register_ajax_action(
			'get_wcf_template_data',
			function ( $data ) {
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new \Exception( 'Access Denied' );
				}

				if ( ! empty( $data['editor_post_id'] ) ) {
					$editor_post_id = absint( $data['editor_post_id'] );

					if ( ! get_post( $editor_post_id ) ) {
						throw new \Exception( esc_html__( 'Post not found', 'animation-addons-for-elementor' ) );
					}

					\Elementor\Plugin::instance()->db->switch_to_post( $editor_post_id );
				}
		
				if ( empty( $data['template_id'] ) ) {
					throw new \Exception( esc_html__( 'Template id missing', 'animation-addons-for-elementor' ) );
				}

				$result = $this->get_template_data( $data );

				return $result;
			}
		);
	}

	private function get_template_data( array $args ) {
		$source = new Library_Source();
		$data   = $source->get_data( $args );       
		return $data;
	}
}

new Layout_Import_Api();
