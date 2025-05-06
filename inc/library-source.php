<?php

namespace WCF_ADDONS;

defined( 'ABSPATH' ) || exit;

use Elementor\TemplateLibrary\Source_Base;

class Library_Source extends Source_Base {

	public function get_id() {
		return 'wcf-layout-manager';
	}

	public function get_title() {
		return __( 'AAE Layout Manager', 'animation-addons-for-elementor' );
	}

	public function register_data() {
	}

	public function save_item( $template_data ) {
		return new \WP_Error( 'invalid_request', 'Cannot save template to a WCF layout manager' );
	}

	public function update_item( $new_data ) {
		return new \WP_Error( 'invalid_request', 'Cannot update template to a WCF layout manager' );
	}

	public function delete_template( $template_id ) {
		return new \WP_Error( 'invalid_request', 'Cannot delete template from a WCF layout manager' );
	}

	public function export_template( $template_id ) {
		return new \WP_Error( 'invalid_request', 'Cannot export template from a WCF layout manager' );
	}

	public function get_items( $args = array() ) {
		return array();
	}

	public function get_item( $template_id ) {
		$templates = $this->get_items();

		return $templates[ $template_id ];
	}

	public function request_template_data( $template_id ) {
		if ( empty( $template_id ) ) {
			return;
		}

		$request_url = plugin::instance()->api_url . '/' . $template_id;
		
		$response    = wp_remote_get(
			$request_url,
			array(
				'timeout' => 30,
				'body'    => [
					// Which API version is used.
					'api_version' => 1.1,
					'is_pro'      => false,
					// Which language to return.
					'site_lang'   => get_bloginfo( 'language' ),
				],
			)
		);

		return wp_remote_retrieve_body( $response );
	}

	public function get_data( array $args, $context = 'display' ) {
		$data = $this->request_template_data( $args['template_id'] );

		$data = json_decode( $data, true );

		if ( empty( $data ) || empty( $data['content'] ) ) {
			throw new \Exception( esc_html__( 'Template does not have any content', 'animation-addons-for-elementor' ) );
		}

		$data['content'] = $this->replace_elements_ids( $data['content'] );
		$data['content'] = $this->process_export_import_content( $data['content'], 'on_import' );

		$post_id  = $args['editor_post_id'];
		$document = \Elementor\Plugin::instance()->documents->get( $post_id );

		if ( $document ) {
			$data['content'] = $document->get_elements_raw_data( $data['content'], true );
		}

		return $data;
	}
}

new Library_Source();
