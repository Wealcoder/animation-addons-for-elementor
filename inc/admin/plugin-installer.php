<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound
namespace WCF_ADDONS\Admin;
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

if (!defined('ABSPATH')) {
    exit();
} // Exit if accessed directly

class WCF_Plugin_Installer
{

    /**
     * The only plugin these endpoints may activate.
     *
     * The dashboard and the editor both send this exact basename, and the
     * buttons that send it only render while Pro is installed but inactive.
     * Accepting anything else would make this a general "activate any
     * plugin" endpoint, which is not what the user was shown a button for.
     */
    const PRO_BASENAME = 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php';

    public function __construct($reload = false)
    {
        if (!$reload) {

            add_action('wp_ajax_wcf_active_plugin', [$this, 'ajax_activate_plugin']);
            add_action('wp_ajax_activate_from_editor_plugin', [$this, 'activate_from_editor_plugin']);
            add_action('wp_ajax_aaeaddon_template_dependency_status', [$this, 'dependency_status']);
            add_action('wp_ajax_aaeaddon_atomic_import_status', [$this, 'atomic_import_status']);
        }
    }

    /**
     * Can a V4 (atomic) starter template be imported here, and is one already in?
     *
     * Asked by the starter-template grids the moment Import is pressed on a V4
     * card -- not read from the page payload -- because the answer changes
     * during a session: the first V4 import makes every later one a "second
     * import", and that is the case the dialog exists for. Reports, never
     * writes; the importer snapshots the same signal itself at step 1.
     *
     * @return void Sends JSON {available: bool, in_use: bool}.
     */
    public function atomic_import_status()
    {
        check_ajax_referer('wcf_admin_nonce', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        // The atomic registry only loads on Elementor 4+; without it there is
        // nothing a V4 template could render with, so "not available" is the
        // honest answer rather than an error.
        if (!class_exists('\WCF_ADDONS\AtomicWidgets\Atomic')) {
            wp_send_json_success(['available' => false, 'in_use' => false]);
        }

        wp_send_json_success(\WCF_ADDONS\AtomicWidgets\Atomic::import_signal());
    }

    public function ajax_activate_plugin()
    {

        check_ajax_referer('wcf_admin_nonce', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        $basename = isset($_POST['action_base']) ? sanitize_text_field(wp_unslash($_POST['action_base'])) : '';

        if (self::PRO_BASENAME !== $basename) {
            wp_send_json_error(__('Invalid plugin.', 'animation-addons-for-elementor'));
        }

        // Not silent: a silent activation skips activate_{$plugin}, which is
        // where Pro's own register_activation_hook() flushes rewrite rules.
        $result = activate_plugin($basename, '', false, false);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(['message' => __('Plugin activated successfully!', 'animation-addons-for-elementor')]);
    }

    public function activate_from_editor_plugin()
    {

        check_ajax_referer('wcf-template-library', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        $basename = isset($_POST['action_base']) ? sanitize_text_field(wp_unslash($_POST['action_base'])) : '';

        if (self::PRO_BASENAME !== $basename) {
            wp_send_json_error(__('Invalid plugin.', 'animation-addons-for-elementor'));
        }

        // Not silent: a silent activation skips activate_{$plugin}, which is
        // where Pro's own register_activation_hook() flushes rewrite rules.
        $result = activate_plugin($basename, '', false, false);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Plugin activated successfully!', 'animation-addons-for-elementor'));
    }

    function check_plugin_status($base_path)
    {

        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (file_exists(WP_PLUGIN_DIR . '/' . $base_path)) {
            return is_plugin_active($base_path) ? 'Active' : 'Inactive';
        }

        return __('Not Installed', 'animation-addons-for-elementor');
    }

    function check_theme_status($theme_slug)
    {

        $theme = wp_get_theme($theme_slug);

        if ($theme->exists()) {
            return (get_template() === $theme_slug) ? 'Active' : 'Installed';
        }

        return __('Not Installed', 'animation-addons-for-elementor');
    }

    /**
     * Dependancy Check    
     * @return mixed Json | bool
     */
    public function dependency_status()
    {

        check_ajax_referer('wcf_admin_nonce', 'nonce');

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(__('You are not allowed to do this action', 'animation-addons-for-elementor'));
        }

        delete_option('aaeaddon_template_import_progress');
        delete_option('aaeaddon_template_import_state');

        // Ensure $_POST['dependencies'] exists
        if (!isset($_POST['dependencies'])) {
            wp_send_json_error(__('Missing dependencies data', 'animation-addons-for-elementor'));
        }

        $dependencies = sanitize_text_field(wp_unslash($_POST['dependencies']));
        $dependencies = json_decode($dependencies, true);

        $plugins = isset($dependencies['plugins']) && is_array($dependencies['plugins'])  ? $dependencies['plugins'] : [];
        $themes = isset($dependencies['themes']) && is_array($dependencies['themes'])  ? $dependencies['themes'] : [];

        // This plugin ships no installer. Something has to be listening on the
        // starter-template hooks for a missing dependency to be installable at
        // all, so when nothing is, the import screen says so per row instead of
        // offering a checkbox that would quietly do nothing.
        $not_installed       = __('Not Installed', 'animation-addons-for-elementor');
        $can_install_plugins = (bool) has_action('aae/starter_template/install_plugin');

        // Check plugin dependencies
        foreach ($plugins as &$dep) {
            $dep['status']    = $this->check_plugin_status($dep['Base_Slug']);
            $dep['needs_pro'] = ($not_installed === $dep['status']) && !$can_install_plugins;
        }
        // Themes are reported, never installed and never activated. Changing a
        // site's theme is the user's decision and belongs to Appearance >
        // Themes, so these rows carry a status and nothing selectable.
        foreach ($themes as &$tm) {
            $tm['status'] = $this->check_theme_status($tm['slug']);
        }

        wp_send_json_success(['dependencies' => ['plugins' => $plugins, 'themes' => $themes]]);
    }
}

new WCF_Plugin_Installer();
