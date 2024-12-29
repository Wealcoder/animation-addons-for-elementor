<?php

namespace WCF_ADDONS\Admin;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
} // Exit if accessed directly

class WCF_Plugin_Installer {
	public function __construct() {
		add_action( 'wp_ajax_wcf_install_plugin', [ $this, 'ajax_install_plugin' ] );
		add_action( 'wp_ajax_wcf_active_plugin', [ $this, 'ajax_activate_plugin' ] );
		add_action( 'wp_ajax_wcf_deactive_plugin', [ $this, 'ajax_deactivate_plugin' ] );
	}

	/**
	 * get_local_plugin_data
	 *
	 * @param mixed $basename
	 *
	 * @return array|false
	 */
	public function get_local_plugin_data( $basename = '' ) {
		if ( empty( $basename ) ) {
			return false;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		if ( ! isset( $plugins[ $basename ] ) ) {
			return false;
		}

		return $plugins[ $basename ];
	}

	/**
	 * get_remote_plugin_data
	 *
	 * @param mixed $slug
	 *
	 * @return mixed array|WP_Error
	 */
	public function get_remote_plugin_data( $slug = '', $source = '' ) {
		if ( empty( $slug ) || empty( $source ) ) {
			return new WP_Error( 'empty_arg', esc_html__( 'Argument should not be empty.', 'animation-addons-for-elementor' ) );
		}

		if ( 'wordpress' === $source ) {
			$response = wp_remote_post(
				'http://api.wordpress.org/plugins/info/1.0/',
				[
					'body' => [
						'action'  => 'plugin_information',
						'request' => serialize( (object) [
							'slug'   => $slug,
							'fields' => [
								'version' => false,
							],
						] ),
					],
				]
			);
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( unserialize( wp_remote_retrieve_body( $response ) ) ) {
			return unserialize( wp_remote_retrieve_body( $response ) );
		} else {
			return json_decode( wp_remote_retrieve_body( $response ) );
		}
	}

	/**
	 * install_plugin
	 *
	 * @param mixed $slug
	 * @param bool $active
	 *
	 * @return mixed bool|WP_Error
	 */
	public function install_plugin( $slug = '', $source = '', $active = false ) {
		if ( empty( $slug ) || empty( $source ) ) {
			return new WP_Error( 'empty_arg', esc_html__( 'Argument should not be empty.', 'animation-addons-for-elementor' ) );
		}

		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

		$plugin_data = $this->get_remote_plugin_data( $slug, $source );


		if ( is_wp_error( $plugin_data ) ) {
			return $plugin_data;
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );

		// install plugin
		$install = $upgrader->install( $plugin_data->download_link );


		if ( is_wp_error( $install ) ) {
			return $install;
		}

		// activate plugin
		if ( $install === true && $active ) {
			$active = activate_plugin( $upgrader->plugin_info(), '', false, true );

			if ( is_wp_error( $active ) ) {
				return $active;
			}

			return $active === null;
		}

		return $install;
	}

	public function ajax_install_plugin() {

		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		$slug   = isset( $_POST['action_base'] ) ? sanitize_text_field( $_POST['action_base'] ) : '';
		$source = isset( $_POST['plugin_source'] ) ? sanitize_text_field( $_POST['plugin_source'] ) : '';

		$result = $this->install_plugin( $slug, $source );

		if ( is_null( $result ) ) {
			wp_send_json_error( __( 'Something went wrong', 'animation-addons-for-elementor' ), 404 );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Plugin is installed successfully!', 'animation-addons-for-elementor' ) );
	}

	public function ajax_activate_plugin() {
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		//check user capabilities
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		$basename = isset( $_POST['action_base'] ) ? sanitize_text_field( $_POST['action_base'] ) : '';
		$result   = activate_plugin( $basename, '', false, true );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		if ( $result === false ) {
			wp_send_json_error( __( 'Plugin couldn\'t be activated.', 'animation-addons-for-elementor' ) );
		}
		wp_send_json_success( __( 'Plugin is activated successfully!', 'animation-addons-for-elementor' ) );
	}
	public function ajax_deactivate_plugin() {
		check_ajax_referer( 'wcf_admin_nonce', 'nonce' );

		//check user capabilities
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'you are not allowed to do this action', 'animation-addons-for-elementor' ) );
		}

		$basename = isset( $_POST['action_base'] ) ? sanitize_text_field( $_POST['action_base'] ) : '';
		error_log( $basename );
		$result   = deactivate_plugins( 'animation-addon-for-elementorpro' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		if ( $result === false ) {
			wp_send_json_error( __( 'Plugin couldn\'t be deactivated.', 'animation-addons-for-elementor' ) );
		}
		wp_send_json_success( __( 'Plugin is deactivated successfully!', 'animation-addons-for-elementor' ) );
	}
}

new WCF_Plugin_Installer();
