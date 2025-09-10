<?php

namespace WCF_ADDONS\CodeSnippet;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers class for CodeSnippet.
 *
 * @since 1.0.0
 * @package WCF_ADDONS
 */
class Helpers {

	/**
	 * Get the status toggle HTML for a snippet.
	 *
	 * @param object $item The current snippet object.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_status_toggle( $item ) {
		$id        = $item->ID;
		$is_active = get_post_meta( $id, 'is_active', true );

		$toggle_html  = '<label class="toggle-switch">';
		$toggle_html .= sprintf(
			'<input type="checkbox" class="snippet-status-toggle" data-id="%d" %s>',
			esc_attr( $id ),
			( 'yes' === $is_active ) ? 'checked' : ''
		);
		$toggle_html .= '<span class="slider"></span>';
		$toggle_html .= '</label>';

		return $toggle_html;
	}

	/**
	 * Get snippet settings.
	 *
	 * @param int $id ID of the snippet post.
	 *
	 * @since 1.0.0
	 * @return array Returns the snippet settings.
	 */
	public static function get_snippet_settings( $id = null ) {
		$defaults = array(
			'code_type'            => 'html',
			'code_content'         => '',
			'load_location'        => 'header',
			'priority'             => 10,
			'is_active'            => 'yes',
			'visibility_page'      => 'all',
			'visibility_page_list' => array(),
		);

		/**
		 * Filter the default snippet settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $defaults The default settings.
		 */
		$defaults = apply_filters( 'wcf_default_snippet_settings', $defaults );

		if ( ! empty( $id ) ) {
			$metadata                  = get_post_meta( $id );
			$settings                  = array();
			$settings['snippet_id']    = $id;
			$settings['snippet_title'] = get_the_title( $id );

			foreach ( $metadata as $key => $value ) {
				$value = maybe_unserialize( is_array( $value ) ? $value[0] : $value );
				if ( ! empty( $value ) ) {
					$settings[ $key ] = $value;
				} else {
					$settings[ $key ] = $defaults[ $key ] ?? '';
				}
			}
		} else {
			$settings                  = array();
			$settings['snippet_id']    = '';
			$settings['snippet_title'] = '';
			foreach ( $defaults as $key => $value ) {
				$settings[ $key ] = $defaults[ $key ];
			}
		}

		/**
		 * Filter the snippet settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings The snippet settings.
		 * @param array $defaults The default settings.
		 */
		$settings = apply_filters( 'wcf_get_snippet_settings', $settings, $defaults );
		return $settings;
	}

	/**
	 * Get page title by ID.
	 *
	 * @param int  $page_id Page ID.
	 * @param bool $as_link Whether to return as link.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_page_title( $page_id, $as_link = false ) {
		$page = get_post( $page_id );
		if ( ! $page ) {
			return '';
		}

		$title = $page->post_title;

		if ( $as_link ) {
			$edit_url = get_edit_post_link( $page_id );
			return sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html( $title ) );
		}

		return $title;
	}

	/**
	 * Get snippet count by type.
	 *
	 * @param string $code_type Code type filter.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public static function get_snippet_count_by_type( $code_type = 'all' ) {
		$args = array(
			'post_type'      => 'wcf-code-snippet',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);

		if ( 'all' !== $code_type ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'code_type',
					'value'   => $code_type,
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Add flash message using the Notices class.
	 *
	 * @param string $message The message text.
	 * @param string $type    The message type.
	 *
	 * @since 1.0.0
	 */
	public static function add_flash_message( $message, $type = 'success' ) {
		Notices::add_flash_message( $message, $type );
	}

	/**
	 * Get list table class.
	 *
	 * @param string $type Type of list table to get.
	 *
	 * @since 2.3.10
	 * @return object
	 */
	public static function aae_get_list_table( $type ) {
		switch ( $type ) {
			case 'wcf-code-snippet':
			default:
				$list_table = new \WCF_ADDONS\CodeSnippet\listTables\CodeSnippetListTable();
				break;
		}

		return $list_table;
	}
}
