<?php

/**
 * MailChimp api
 */

namespace WCF_ADDONS\Widgets\Mailchimp;

defined( 'ABSPATH' ) || die();

class Mailchimp_Api {

	/**
	 * request
	 *
	 * @param array $submitted_data
	 * @return array | int error
	 */
	public static function insert_subscriber_to_mailchimp( $submitted_data ) {

		if ( ! isset( $_REQUEST['nonce'] ) || empty( $_REQUEST['nonce'] ) ) {
			wp_send_json_error( 'Missing nonce' );
		}

		// Verify nonce
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce , 'wcf-addons-frontend' ) ) {
			exit( 'No naughty business please' );
		}

		$return = [];

		$api = '';
		if ( isset( $_POST['key'] ) ) {
			$ke = sanitize_text_field( wp_unslash( $_POST['key'] ) );
			$api = str_replace( 'w1c2f', '', base64_decode( $ke ) );
		}

		$tags = '';
		if ( isset( $_POST['listTags'] ) && ! empty( $_POST['listTags'] ) ) {
			$lst = sanitize_text_field( wp_unslash($_POST['listTags']) );	
			$tags = explode( ', ', $lst );
		}

		$auth = [
			'api_key' => $api,
			'list_id' => isset($_POST['listId']) ? sanitize_text_field( wp_unslash($_POST['listId']) ) : '',
		];

		$data = [
			'email_address' => ( isset( $submitted_data['email'] ) ? $submitted_data['email'] : '' ),
			'status'        => ( ( isset( $_POST['doubleOpt'] ) && $_POST['doubleOpt'] == 'yes' ) ? 'pending' : 'subscribed' ),
			'status_if_new' => ( ( isset( $_POST['doubleOpt'] ) && $_POST['doubleOpt'] == 'yes' ) ? 'pending' : 'subscribed' ),
			'merge_fields'  => [
				'FNAME' => ( isset( $submitted_data['fname'] ) ? $submitted_data['fname'] : '' ),
				'LNAME' => ( isset( $submitted_data['lname'] ) ? $submitted_data['lname'] : '' ),
				'PHONE' => ( isset( $submitted_data['phone'] ) ? $submitted_data['phone'] : '' ),
			],
		];
		
		if(isset($submitted_data['advanced-mailchimp'])){
			$data['merge_fields'] = $submitted_data;
		}

		if ( ! empty( $tags ) ) {
			$data['tags'] = $tags;
		}

		$server = explode( '-', $auth['api_key'] );

		if ( ! isset( $server[1] ) ) {
			return [
				'status' => 0,
				'msg'    => esc_html__( 'Invalid API key.', 'animation-addons-for-elementor' ),
			];
		}

		$url = 'https://' . $server[1] . '.api.mailchimp.com/3.0/lists/' . $auth['list_id'] . '/members/';

		$response = wp_remote_post(
			$url,
			[
				'method'      => 'POST',
				'data_format' => 'body',
				'timeout'     => 45,
				'headers'     => [
					'Authorization' => 'apikey ' . $auth['api_key'],
					'Content-Type'  => 'application/json; charset=utf-8',
				],
				'body'        => wp_json_encode( $data ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$error_message    = $response->get_error_message();
			$return['status'] = 0;
			$return['msg']    = 'Something went wrong: ' . esc_html( $error_message );
		} else {
			$body           = (array) json_decode( $response['body'] );
			$return['body'] = $body;
			if ( $body['status'] > 399 && $body['status'] < 600 ) {
				$return['status'] = 0;
				$return['msg']    = $body['title'];
			} elseif ( $body['status'] == 'subscribed' ) {
				$return['status'] = 1;
				$return['msg']    = esc_html__( 'Your data has been inserted on Mailchimp.', 'animation-addons-for-elementor' );
			} elseif ( $body['status'] == 'pending' ) {
				$return['status'] = 1;
				$return['msg']    = esc_html__( 'Confirm your subscription from your email.', 'animation-addons-for-elementor' );
			} else {
				$return['status'] = 0;
				$return['msg']    = esc_html__( 'Something went wrong. Try again later.', 'animation-addons-for-elementor' );
			}
		}

		return $return;
	}

	/**
	 * Get request
	 *
	 * @return array all list
	 */
	public static function get_mailchimp_lists( $api = null ) {
		$options = [];

		$server = explode( '-', $api );

		if ( ! isset( $server[1] ) ) {
			return 0;
		}

		$url = 'https://' . $server[1] . '.api.mailchimp.com/3.0/lists';

		$response = wp_remote_post(
			$url,
			[
				'method'      => 'GET',
				'data_format' => 'body',
				'timeout'     => 45,
				'headers'     => [

					'Authorization' => 'apikey ' . $api,
					'Content-Type'  => 'application/json; charset=utf-8',
				],
				'body'        => '',
			]
		);

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$body   = (array) json_decode( $response['body'] );
			$listed = isset( $body['lists'] ) ? $body['lists'] : [];

			if ( is_array( $listed ) && count( $listed ) > 0 ) {
				$options = array_reduce(
					$listed,
					function ( $result, $item ) {
						// extra space is needed to maintain order in elementor control
						$result[ $item->id ] = $item->name;

						return $result;
					},
					array()
				);
			}
		}

		return $options;
	}
	public static function get_form_fields( $api = null , $list_id = null ) {
		$options = [];

		$server = explode( '-', $api );

		if ( ! isset( $server[1] ) ) {
			return 0;
		}

		$url = 'https://' . $server[1] . '.api.mailchimp.com/3.0/lists/'.trim($list_id).'/merge-fields?count=30';

		$response = wp_remote_post(
			$url,
			[
				'method'      => 'GET',
				'data_format' => 'body',
				'timeout'     => 45,
				'headers'     => [
					'Authorization' => 'apikey ' . $api,
					'Content-Type'  => 'application/json; charset=utf-8',
				],
				'body'        => '',
			]
		);

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$body   = (array) json_decode( $response['body'] );
			$listed = isset( $body['merge_fields'] ) ? $body['merge_fields'] : [];
			update_option('aae_addon_mailchimp_form_field', $listed);
			return $listed;			
		}

		return $options;
	}
}