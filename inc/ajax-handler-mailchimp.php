<?php

namespace WCF_ADDONS;

use Elementor\Plugin;

defined('ABSPATH') || die();

class Ajax_Handler_Mailchimp
{

	public static function init()
	{
		if (class_exists('\WCFAddonsPro\Ajax_Handler')) {
			return;
		}
		add_action('wp_ajax_mailchimp_api', [__CLASS__, 'mailchimp_lists']);
		add_action('wp_ajax_nopriv_mailchimp_api', [__CLASS__, 'mailchimp_lists']);

		add_action('wp_ajax_wcf_mailchimp_ajax', [__CLASS__, 'mailchimp_prepare_ajax']);
		add_action('wp_ajax_nopriv_wcf_mailchimp_ajax', [__CLASS__, 'mailchimp_prepare_ajax']);

		add_action('wp_ajax_wcf_mailchimp_list_fields', [__CLASS__, 'wcf_mailchimp_list_fields']);
		add_action('wp_ajax_nopriv_wcf_mailchimp_list_fields', [__CLASS__, 'wcf_mailchimp_list_fields']);

		//popup action
		add_action('wp_ajax_wcf_load_popup_content', [__CLASS__, 'wcf__popup_content']);
		add_action('wp_ajax_nopriv_wcf_load_popup_content', [__CLASS__, 'wcf__popup_content']);
	}

	/**
	 * Mailchimp subscriber all list handler Ajax call
	 */
	public static function mailchimp_lists()
	{

		if(isset($_REQUEST['nonce']) && !empty($_REQUEST['nonce']) ) {
			$nonce = sanitize_text_field(wp_unslash( $_REQUEST['nonce'] ));
		} else {
			wp_send_json_error('Missing nonce');
		}

		if (! wp_verify_nonce($nonce, 'wcf-addons-editor')) {
			exit('No naughty business please');
		}

		$api = ! empty($_REQUEST['api']) ? sanitize_text_field( wp_unslash($_REQUEST['api']) ) : '';
		update_option('aae_mailchimp_api', $api);
		$response = Widgets\Mailchimp\Mailchimp_Api::get_mailchimp_lists($api);

		wp_send_json($response);
	}

	public static function wcf_mailchimp_list_fields()
	{

		 if(isset($_REQUEST['nonce']) && !empty($_REQUEST['nonce']) ) {
			$nonce = sanitize_text_field(wp_unslash( $_REQUEST['nonce'] ));
		} else {
			wp_send_json_error('Missing nonce');
		}

		if (! wp_verify_nonce($nonce, 'wcf-addons-editor')) {
			exit('No naughty business please');
		}

		$api = ! empty($_REQUEST['api']) ? sanitize_text_field( wp_unslash($_REQUEST['api']) ) : '';
		$list_id = ! empty($_REQUEST['list_id']) ? sanitize_text_field( wp_unslash( $_REQUEST['list_id'] ) ) : '';

		$response = Widgets\Mailchimp\Mailchimp_Api::get_form_fields($api, $list_id);

		wp_send_json($response);
	}

	/**
	 * Mailchimp subscriber handler Ajax call
	 */
	public static function mailchimp_prepare_ajax()
	{
		if(isset($_REQUEST['nonce']) && !empty($_REQUEST['nonce']) ) {
			$nonce = sanitize_text_field(wp_unslash( $_REQUEST['nonce'] ));
		} else {
			wp_send_json_error('Missing nonce');
		}

		if (! wp_verify_nonce($nonce, 'wcf-addons-frontend')) {
			exit('No naughty business please');
		}
		$subscriber_info = isset( $_POST['subscriber_info'] ) ?  sanitize_text_field( wp_unslash( $_POST['subscriber_info'] ) ) : '';
		parse_str($subscriber_info, $subscriber);

		$response = Widgets\Mailchimp\Mailchimp_Api::insert_subscriber_to_mailchimp($subscriber);

		wp_send_json($response);
	}

	/**
	 * wcf popup content Ajax call
	 */
	public static function wcf__popup_content()
	{
		if(isset($_REQUEST['nonce']) && !empty($_REQUEST['nonce']) ) {
			$nonce = sanitize_text_field(wp_unslash( $_REQUEST['nonce'] ));
		} else {
			wp_send_json_error('Missing nonce');
		}	

		if (! wp_verify_nonce($nonce, 'wcf-addons-frontend')) {
			exit('No naughty business please');
		}

		$post_id    = isset( $_REQUEST['post_id'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) ) ) : 0;
		$element_id = isset( $_REQUEST['element_id'] ) ? absint( sanitize_text_field( wp_unslash( $_REQUEST['element_id'] ) ) ) : 0;
		$settings   = wcf_addons_get_widget_settings($post_id, $element_id);
		ob_start();

		if ('template' === $settings['popup_content_type']) {
			echo wp_kses_post( Plugin::$instance->frontend->get_builder_content($settings['popup_elementor_templates']) );
		} else {

			$content = $settings['popup_content'];
			$content = shortcode_unautop($content);
			$content = do_shortcode($content);
			$content = wptexturize($content);

			if ($GLOBALS['wp_embed'] instanceof \WP_Embed) {
				$content = $GLOBALS['wp_embed']->autoembed($content);
			}

			echo wp_kses_post( $content );
		}
		$html = ob_get_contents();
		ob_end_clean();

		wp_send_json(
			array(
				'html'      => $html,
				'widget_attr' => 'test attr',
			)
		);

		die();
	}
}

Ajax_Handler_Mailchimp::init();
