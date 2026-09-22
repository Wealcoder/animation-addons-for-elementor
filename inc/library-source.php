<?php

namespace Wealcoder\AnimationAddons;

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
		
		$response    = wp_safe_remote_get(
			$request_url,
			array(
				'timeout' => 15,
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

	/**
	 * Fresh element ids for a tree that is about to be inserted, WITHOUT the
	 * controls walk get_data() does after it.
	 *
	 * Source_Base::replace_elements_ids() is a pure array walk; every new id
	 * goes through `elementor/document/element/replace_id`, which Elementor's
	 * Styles_Ids_Modifier answers by regenerating each atomic element's LOCAL
	 * style ids (`e-<newId>-…`) and rewriting its `classes` references. Global
	 * `g-…` ids pass through untouched — those are the design system's and are
	 * resolved by process_global_styles on the client side.
	 *
	 * That is the whole of what a V4 (atomic) block needs from PHP before
	 * `document/elements/import`: no process_export_import_content(), no
	 * get_elements_raw_data() — both instantiate every element and silently
	 * DROP any type that is not registered on this site, which for a block
	 * means every AAE widget the user has switched off.
	 *
	 * @param array $content Elements array.
	 * @return array
	 */
	public function replace_ids( array $content ): array {
		return $this->replace_elements_ids( $content );
	}

	public function get_data( array $args, $context = 'display' ) {	
	
		if(isset($args['json_data'])){
			$data = $args['json_data'];
		}else{
			$data = $this->request_template_data( $args['template_id'] );
			$data = json_decode( $data, true );
		}		
	
		if ( empty( $data ) || empty( $data['content'] ) ) {
			throw new \Exception( esc_html__( 'Template does not have any content', 'animation-addons-for-elementor' ) );
		}
		add_filter('import_allow_fetch_attachments', '__return_false', 99);
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
