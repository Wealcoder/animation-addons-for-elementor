<?php

namespace WCF_ADDONS\CodeSnippet\listTables;

defined( 'ABSPATH' ) || exit;

// Load WP_List_Table if not loaded.
if ( ! class_exists( '\WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Abstract List table class.
 *
 * @since 1.0.0
 * @package WCF_ADDONS
 */
abstract class AbstractListTable extends \WP_List_Table {

    /**
     * Current page URL.
     *
     * @since 1.0.0
     * @var string
     */
    protected $base_url;

    /**
     * Process bulk action.
     *
     * @param string $doaction Action name.
     *
     * @since 1.0.0
     */
    public function process_bulk_actions( $doaction ) {
        // Clean up URL parameters after processing
        if ( ! empty( $_GET['_wp_http_referer'] ) || ! empty( $_GET['_wpnonce'] ) ) {
            wp_safe_redirect(
                remove_query_arg(
                    array(
                        '_wp_http_referer',
                        '_wpnonce',
                    ),
                    $this->get_current_url()
                )
            );
            exit;
        }
    }

    /**
     * Return the status filter for this request, if any.
     *
     * @param string $fallback Default status.
     *
     * @since 1.0.0
     * @return string
     */
    protected function get_request_status( $fallback = null ) {
        $status = isset( $_GET['code_type'] ) ? sanitize_text_field( wp_unslash( $_GET['code_type'] ) ) : '';
        return empty( $status ) ? $fallback : $status;
    }

    /**
     * Get current URL.
     *
     * @since 1.0.0
     * @return string
     */
    protected function get_current_url() {
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            return esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        }
        return '';
    }

    /**
     * Get views links HTML.
     *
     * @param array $status_links Array of status link data.
     *
     * @since 1.0.0
     * @return array
     */
    protected function get_status_links( $status_links ) {
        $links = array();

        foreach ( $status_links as $key => $link ) {
            $class = $link['current'] ? ' class="current"' : '';
            $links[ $key ] = sprintf(
                '<a href="%s"%s>%s</a>',
                esc_url( $link['url'] ),
                $class,
                $link['label'] // Already escaped in the calling method
            );
        }

        return $links;
    }

    /**
     * Display admin notices/messages.
     *
     * @since 1.0.0
     */
    public function admin_notices() {
        // This method can be overridden by child classes to display notices
        if ( isset( $_GET['message'] ) ) {
            $message = sanitize_text_field( wp_unslash( $_GET['message'] ) );
            $messages = $this->get_admin_messages();

            if ( isset( $messages[ $message ] ) ) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html( $messages[ $message ] )
                );
            }
        }
    }

    /**
     * Get admin messages.
     *
     * @since 1.0.0
     * @return array
     */
    protected function get_admin_messages() {
        return array(
            'deleted'     => __( 'Items deleted successfully.', 'animation-addons-for-elementor' ),
            'activated'   => __( 'Items activated successfully.', 'animation-addons-for-elementor' ),
            'deactivated' => __( 'Items deactivated successfully.', 'animation-addons-for-elementor' ),
        );
    }

    /**
     * Prepare and display the list table.
     *
     * @since 1.0.0
     */
    public function display_table() {
        $this->prepare_items();
        $this->display();
    }

    /**
     * Get the CSS classes for the WP_List_Table table tag.
     *
     * @since 1.0.0
     * @return array
     */
    protected function get_table_classes() {
        return array( 'widefat', 'fixed', 'striped', $this->_args['plural'] );
    }

    /**
     * Message to be displayed when there are no items.
     *
     * @since 1.0.0
     */
    public function no_items() {
        esc_html_e( 'No items found.', 'animation-addons-for-elementor' );
    }

    /**
     * Handles the default column output.
     *
     * @param object $item        The current item.
     * @param string $column_name The current column name.
     *
     * @since 1.0.0
     * @return string
     */
    public function column_default( $item, $column_name ) {
        return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '&mdash;';
    }

    /**
     * Get row actions.
     *
     * @param array  $actions Array of actions.
     * @param bool   $always_visible Whether actions should always be visible.
     *
     * @since 1.0.0
     * @return string
     */
    protected function row_actions( $actions, $always_visible = false ) {
        $action_count = count( $actions );

        if ( ! $action_count ) {
            return '';
        }

        $mode = get_user_setting( 'posts_list_mode', 'list' );

        if ( 'excerpt' === $mode ) {
            $always_visible = true;
        }

        $out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

        $i = 0;
        foreach ( $actions as $action => $link ) {
            ++$i;
            $sep = ( $i < $action_count ) ? ' | ' : '';
            $out .= "<span class='$action'>$link$sep</span>";
        }

        $out .= '</div>';

        $out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' .
            __( 'Show more details', 'animation-addons-for-elementor' ) .
            '</span></button>';

        return $out;
    }

    /**
     * Generate and display row action links.
     *
     * @param object $item        The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     *
     * @since 1.0.0
     * @return string
     */
    protected function handle_row_actions( $item, $column_name, $primary ) {
        return $column_name === $primary ? $this->row_actions( $this->get_row_actions( $item ) ) : '';
    }

    /**
     * Get row actions for an item.
     * This method should be overridden by child classes.
     *
     * @param object $item The item.
     *
     * @since 1.0.0
     * @return array
     */
    protected function get_row_actions( $item ) {
        return array();
    }

    /**
     * Sanitize orderby parameter.
     *
     * @param string $orderby    The orderby parameter.
     * @param array  $allowed    Allowed orderby values.
     * @param string $default    Default orderby value.
     *
     * @since 1.0.0
     * @return string
     */
    protected function sanitize_orderby( $orderby, $allowed = array(), $default = 'ID' ) {
        if ( empty( $allowed ) ) {
            $allowed = array( 'ID', 'title', 'date', 'modified' );
        }

        return in_array( $orderby, $allowed, true ) ? $orderby : $default;
    }

    /**
     * Sanitize order parameter.
     *
     * @param string $order   The order parameter.
     * @param string $default Default order value.
     *
     * @since 1.0.0
     * @return string
     */
    protected function sanitize_order( $order, $default = 'ASC' ) {
        $order = strtoupper( $order );
        return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : $default;
    }

    /**
     * Get items per page from user meta or use default.
     *
     * @param string $option  Option name.
     * @param int    $default Default value.
     *
     * @since 1.0.0
     * @return int
     */
    protected function get_items_per_page( $option, $default = 20 ) {
        $per_page = (int) get_user_option( $option );
        if ( empty( $per_page ) || $per_page < 1 ) {
            $per_page = $default;
        }

        return $per_page;
    }

    /**
     * Add screen options for items per page.
     *
     * @param string $option    Option name.
     * @param string $label     Label for the option.
     * @param int    $default   Default value.
     *
     * @since 1.0.0
     */
    public function add_screen_option( $option, $label, $default = 20 ) {
        $args = array(
            'label'   => $label,
            'default' => $default,
            'option'  => $option,
        );
        add_screen_option( 'per_page', $args );
    }

    /**
     * Handle screen options save.
     *
     * @param mixed  $status Screen option value.
     * @param string $option Option name.
     * @param mixed  $value  Option value.
     *
     * @since 1.0.0
     * @return mixed
     */
    public static function set_screen_option( $status, $option, $value ) {
        if ( false !== strpos( $option, '_per_page' ) ) {
            return $value;
        }
        return $status;
    }

    /**
     * Output hidden fields for maintaining URL parameters.
     *
     * @param array $preserve_params Parameters to preserve.
     *
     * @since 1.0.0
     */
    protected function preserve_url_params( $preserve_params = array() ) {
        $default_params = array( 'page', 'orderby', 'order', 's' );
        $params = array_merge( $default_params, $preserve_params );

        foreach ( $params as $param ) {
            if ( isset( $_GET[ $param ] ) ) {
                printf(
                    '<input type="hidden" name="%s" value="%s" />',
                    esc_attr( $param ),
                    esc_attr( sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) )
                );
            }
        }
    }
}