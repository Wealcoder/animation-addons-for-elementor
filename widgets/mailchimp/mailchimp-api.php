<?php
/**
 * MailChimp api
 */

namespace WCF_ADDONS\Widgets\Mailchimp;

defined('ABSPATH') || die();

class Mailchimp_Api {

    /** Core HTTP helper (uses Basic auth + keeps raw body for debugging) */
    private static function request($method, $url, $api_key, $body = null, $timeout = 45) {
        $args = [
            'method'  => $method,
            'timeout' => $timeout,
            'headers' => [
                // Mailchimp recommends Basic (or Bearer). Basic is simplest for API keys.
                'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
                'Content-Type'  => 'application/json; charset=utf-8',
                'Accept'        => 'application/json',
            ],
        ];

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $resp = wp_remote_request($url, $args);
        if (is_wp_error($resp)) {
            return ['http_code' => 0, 'body' => ['title' => $resp->get_error_message()], 'raw' => null];
        }
        $code = wp_remote_retrieve_response_code($resp);
        $raw  = wp_remote_retrieve_body($resp);
        $json = json_decode($raw, true) ?: [];

        return ['http_code' => $code, 'body' => $json, 'raw' => $raw];
    }

    /** Extract DC ("usX") from API key */
    private static function dc_from_key($api_key) {
        $parts = explode('-', (string) $api_key);
        return $parts[1] ?? null;
    }

    /** Keep only merge tags that exist in the audience */
    private static function filter_merge_fields(array $input, array $allowed_tags) {
        $clean = [];
        foreach ($allowed_tags as $tag) {
            if (isset($input[$tag])) {
                $clean[$tag] = is_string($input[$tag]) ? trim((string)$input[$tag]) : $input[$tag];
            }
        }
        return $clean;
    }

    /** Make sure an array encodes to JSON object ({}) instead of [] when empty */
    private static function as_json_object(array $arr) {
        return $arr ? (object) $arr : new \stdClass();
    }

    /** Pretty-print Mailchimp field-level errors */
    private static function pretty_mailchimp_error(array $body): string {
        if (!empty($body['errors']) && is_array($body['errors'])) {
            $lines = [];
            foreach ($body['errors'] as $e) {
                $f = isset($e['field']) ? (string)$e['field'] : '';
                $m = isset($e['message']) ? (string)$e['message'] : '';
                $lines[] = trim(($f ? "{$f}: " : '') . $m);
            }
            return implode(' | ', $lines);
        }
        return (string)($body['detail'] ?? $body['title'] ?? __('Mailchimp error', 'animation-addons-for-elementor'));
    }

    /** Normalize + validate email (returns lowercased email or WP_Error) */
    private static function normalize_and_validate_email($raw) {
        $email = sanitize_email((string)$raw);
        if (!$email || !is_email($email)) {
            return new \WP_Error('invalid_email', __('Please provide a valid email address.', 'animation-addons-for-elementor'), [
                'raw'       => $raw,
                'sanitized' => $email,
            ]);
        }
        return strtolower($email);
    }

    /**
     * Add/Update a subscriber (idempotent)
     */
    public static function insert_subscriber_to_mailchimp($submitted_data) {

        // 0) Basic nonce check
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';

        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($nonce , 'wcf-addons-frontend')) {
            wp_send_json_error('Invalid nonce');
        }
  
        // 1) Decode API key and basic inputs
        $api_key = '';
        if (!empty($_POST['key'])) {
            $api_key = str_replace('w1c2f', '', base64_decode( sanitize_text_field( wp_unslash($_POST['key']) )));
        }
        $list_id = isset($_POST['listId']) ? trim((string) sanitize_text_field( wp_unslash($_POST['listId']))) : '';
        $double  = (isset($_POST['doubleOpt']) && $_POST['doubleOpt'] === 'yes');

        if (!$api_key || !$list_id) {
            return ['status' => 0, 'msg' => esc_html__('Missing API key or List ID.', 'animation-addons-for-elementor')];
        }

        $dc = self::dc_from_key($api_key);
        if (!$dc) {
            return ['status' => 0, 'msg' => esc_html__('Invalid API key.', 'animation-addons-for-elementor')];
        }

        // 2) Email: sanitize, validate, lowercase + hash
        $email_lc_or_error = self::normalize_and_validate_email($submitted_data['email'] ?? '');
        if (is_wp_error($email_lc_or_error)) {          
            return ['status' => 0, 'msg' => esc_html__('Please provide a valid email address.', 'animation-addons-for-elementor')];
        }
        $email           = $email_lc_or_error;
        $subscriber_hash = md5($email); // Mailchimp requires md5(lowercase email)

        // 3) Optional tags (array of strings)
        $tags = [];
        if (!empty($_POST['listTags'])) {
            $tags = array_filter(array_map('trim', preg_split('/\s*,\s*/', sanitize_text_field(wp_unslash($_POST['listTags'])))));
        }

        // 4) Build merge_fields safely
        // Read allowed merge tags from API so we never send invalid keys
        $merge_fields_allowed = self::get_merge_tags($api_key, $list_id); // ['FNAME','LNAME','PHONE',...]
        $default_merge        = [
            'FNAME' => isset($submitted_data['fname']) ? trim((string)$submitted_data['fname']) : '',
            'LNAME' => isset($submitted_data['lname']) ? trim((string)$submitted_data['lname']) : '',
            'PHONE' => isset($submitted_data['phone']) ? trim((string)$submitted_data['phone']) : '',
        ];

        $candidate_merge = $default_merge;

        // If user enabled advanced mapping, only copy keys that are valid merge tags
        if (isset($submitted_data['advanced-mailchimp'])) {
            $candidate_merge = self::filter_merge_fields($submitted_data, $merge_fields_allowed);
             
        }
      
        // Ensure JSON object for merge_fields ({} when empty)
        $merge_fields_obj = self::as_json_object(
            self::filter_merge_fields($candidate_merge, $merge_fields_allowed)
        );

        $payload = [
            'email_address' => $email,                          // correct key
            'status_if_new' => $double ? 'pending' : 'subscribed',
            'status'        => $double ? 'pending' : 'subscribed',
            'merge_fields'  => $merge_fields_obj,               // must be object
        ];

        // 5) PUT add-or-update
        $member_url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";
        $res = self::request('PUT', $member_url, $api_key, $payload);    
	
        // 6) If tags requested, apply via the dedicated endpoint (reliable for both new & existing)
        if (!empty($tags) && $res['http_code'] >= 200 && $res['http_code'] < 300) {
            $tag_ops = array_map(fn($t) => ['name' => $t, 'status' => 'active'], $tags);
            $tags_url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}/tags";
            self::request('POST', $tags_url, $api_key, ['tags' => $tag_ops]);
        }

        // 7) Normalize response
        if ($res['http_code'] >= 400) {
            $msg = self::pretty_mailchimp_error($res['body']);
            return ['status' => 0, 'msg' => sanitize_text_field($msg), 'body' => $res['body']];
        }

        $member_status = $res['body']['status'] ?? '';
        if ($member_status === 'pending') {
            return ['status' => 'confirmation_message', 'msg' => esc_html__('Confirm your subscription from your email.', 'animation-addons-for-elementor'), 'body' => $res['body']];
        }
        return ['status' => 'success_message', 'msg' => esc_html__('Your subscription updated.', 'animation-addons-for-elementor'), 'body' => $res['body']];
    }

    /** Get audience lists (unchanged but use GET helper) */
    public static function get_mailchimp_lists($api = null) {
        $dc = self::dc_from_key($api);
        if (!$dc) return 0;

        $url = "https://{$dc}.api.mailchimp.com/3.0/lists";
        $res = self::request('GET', $url, $api);
        if ($res['http_code'] >= 200 && !empty($res['body']['lists'])) {
            $options = [];
            foreach ($res['body']['lists'] as $item) {
                // preserve Elementor control ordering
                $options[$item['id']] = $item['name'];
            }
            return $options;
        }
        return [];
    }

    /** Fetch merge tags (returns list of tag strings like ['FNAME','LNAME']) */
    public static function get_merge_tags($api, $list_id) {
        $dc = self::dc_from_key($api);
        if (!$dc) return [];

        $url = "https://{$dc}.api.mailchimp.com/3.0/lists/".trim($list_id)."/merge-fields?count=100";
        $res = self::request('GET', $url, $api);
        if ($res['http_code'] >= 200 && !empty($res['body']['merge_fields'])) {
            return array_values(array_map(fn($mf) => $mf['tag'], $res['body']['merge_fields']));
        }
        return ['FNAME','LNAME','PHONE']; // sensible fallbacks
    }

    /** Back-compat alias (kept for your existing UI) */
    public static function get_form_fields($api = null, $list_id = null) {
        $dc = self::dc_from_key($api);
        if (!$dc) return [];
        $url = "https://{$dc}.api.mailchimp.com/3.0/lists/".trim($list_id)."/merge-fields?count=30";
        $res = self::request('GET', $url, $api);
        if ($res['http_code'] >= 200 && !empty($res['body']['merge_fields'])) {
            update_option('aae_addon_mailchimp_form_field', $res['body']['merge_fields']);
            return $res['body']['merge_fields'];
        }
        return [];
    }
}
