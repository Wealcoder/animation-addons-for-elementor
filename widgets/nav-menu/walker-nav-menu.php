<?php
/**
 * MailChimp api
 */

namespace WCF_ADDONS\Widgets\Nav_Menu;

use WCF_ADDONS\WCF_Theme_Builder;

defined( 'ABSPATH' ) || die();

class WCF_Menu_Walker extends \Walker_Nav_Menu {

	public $elementor_settings = [
        'remove_span'             => false,       
    ]; 

	function __construct($settings = []) {

        if( is_array($settings) ) {
           $this->elementor_settings = wp_parse_args( $settings ,$this->elementor_settings );
        }
      
    }

	public function is_megamenu_enable( $menu_slug ) {
		$menu_obj = wp_get_nav_menu_object( $menu_slug );
		$menu_id  = ( ( ( gettype( $menu_obj ) == 'object' ) && ( isset( $menu_obj->slug ) ) ) ? $menu_obj->term_id : $menu_slug );
		$return   = false;
		$options  = get_option( "wcf_menu_options_" . $menu_id );

		if ( isset( $options['enable_menu'] ) && $options['enable_menu'] == 'on' ) {
			$return = true;
		}

		return $return;
	}


	/**
	 * Starts the element output.
	 *
	 * @since 3.0.0
	 * @since 4.4.0 The {@see 'nav_menu_item_args'} filter was added.
	 * @since 5.9.0 Renamed `$item` to `$data_object` and `$id` to `$current_object_id`
	 *              to match parent class for PHP 8 named parameter support.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string   $output            Used to append additional content (passed by reference).
	 * @param WP_Post  $data_object       Menu item data object.
	 * @param int      $depth             Depth of menu item. Used for padding.
	 * @param stdClass $args              An object of wp_nav_menu() arguments.
	 * @param int      $current_object_id Optional. ID of the current menu item. Default 0.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		// Restores the more descriptive, specific name for use within this method.
		$menu_item = $data_object;

		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}
		$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

		$classes   = empty( $menu_item->classes ) ? array() : (array) $menu_item->classes;
		$classes[] = 'menu-item-' . $menu_item->ID;

		// If Enable MegaMenu
		$icon = $buildercontent = $badge = $styles = '';
		$mega_item_settings = get_post_meta( $menu_item->ID, 'wcf_mega_menu_settings', true );

		if ( $this->is_megamenu_enable( $args->menu ) && isset( $mega_item_settings['menu-item-template'] ) && ! empty( $mega_item_settings['menu-item-template'] ) ) {
			$classes[]      = 'wcf-mega-menu';
			$buildercontent = WCF_Theme_Builder::$_instance->render_build_content( $mega_item_settings['menu-item-template'] );

			if ( 'static' === $mega_item_settings['menu-item-position-type'] && ! empty( $buildercontent ) ) {
				$classes[] = 'mega-position-static';
			}

			if ( 'wp_submenu_list' === $mega_item_settings['mobile-submenu-type'] && ! empty( $buildercontent ) ) {
				$classes[] = 'mobile-wp-submenu';
			}
		}

		if ( $this->is_megamenu_enable( $args->menu ) && isset( $mega_item_settings['menu-item-width-type'] ) && ! empty( $mega_item_settings['menu-item-width-type'] ) ) {
			if ( 'full_width' === $mega_item_settings['menu-item-width-type'] ) {
				$styles .= 'width:100%;';
			} elseif ( 'custom_width' === $mega_item_settings['menu-item-width-type'] && ! empty( $mega_item_settings['menu-item-menucustomwidth'] ) ) {
				$styles .= 'width:' . $mega_item_settings['menu-item-menucustomwidth'];
			} else {
				$styles .= 'width:750px;';
			}
		}

		if ( $this->is_megamenu_enable( $args->menu ) && isset( $mega_item_settings['menu-item-badgetext'] ) && ! empty( $mega_item_settings['menu-item-badgetext'] ) ) {
			$badge_style = '';

			if ( ! empty( $mega_item_settings['menu-item-badgecolor'] ) ) {
				$badge_style .= 'color:#' . $mega_item_settings['menu-item-badgecolor'] . ';';
			}
			if ( ! empty( $mega_item_settings['menu-item-badgebgcolor'] ) ) {
				$badge_style .= '--badge-bg:#' . $mega_item_settings['menu-item-badgebgcolor'] . ';';
			}

			$badge = '<span class="wcf-menu-badge" style="' . $badge_style . '">' . $mega_item_settings['menu-item-badgetext'] . '</span>';
		}

		/**
		 * Filters the arguments for a single nav menu item.
		 *
		 * @since 4.4.0
		 *
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param WP_Post  $menu_item Menu item data object.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$args = apply_filters( 'nav_menu_item_args', $args, $menu_item, $depth );

		/**
		 * Filters the CSS classes applied to a menu item's list item element.
		 *
		 * @since 3.0.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string[] $classes   Array of the CSS classes that are applied to the menu item's `<li>` element.
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $menu_item, $args, $depth ) );

		/**
		 * Filters the ID attribute applied to a menu item's list item element.
		 *
		 * @since 3.0.1
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param string   $menu_item_id The ID attribute applied to the menu item's `<li>` element.
		 * @param WP_Post  $menu_item    The current menu item.
		 * @param stdClass $args         An object of wp_nav_menu() arguments.
		 * @param int      $depth        Depth of menu item. Used for padding.
		 */
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $menu_item->ID, $menu_item, $args, $depth );

		$li_atts          = array();
		$li_atts['id']    = ! empty( $id ) ? $id : '';
		$li_atts['class'] = ! empty( $class_names ) ? $class_names : '';

		/**
		 * Filters the HTML attributes applied to a menu's list item element.
		 *
		 * @since 6.3.0
		 *
		 * @param array $li_atts {
		 *     The HTML attributes applied to the menu item's `<li>` element, empty strings are ignored.
		 *
		 *     @type string $class        HTML CSS class attribute.
		 *     @type string $id           HTML id attribute.
		 * }
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$li_atts       = apply_filters( 'nav_menu_item_attributes', $li_atts, $menu_item, $args, $depth );
		$li_attributes = $this->build_atts( $li_atts );

		$output .= $indent . '<li' . $li_attributes . '>';

		$atts           = array();
		$atts['title']  = ! empty( $menu_item->attr_title ) ? $menu_item->attr_title : '';
		$atts['target'] = ! empty( $menu_item->target ) ? $menu_item->target : '';
		if ( '_blank' === $menu_item->target && empty( $menu_item->xfn ) ) {
			$atts['rel'] = 'noopener';
		} else {
			$atts['rel'] = $menu_item->xfn;
		}

		if ( ! empty( $menu_item->url ) ) {
			if ( get_privacy_policy_url() === $menu_item->url ) {
				$atts['rel'] = empty( $atts['rel'] ) ? 'privacy-policy' : $atts['rel'] . ' privacy-policy';
			}

			$atts['href'] = $menu_item->url;
		} else {
			$atts['href'] = '';
		}

		$atts['aria-current'] = $menu_item->current ? 'page' : '';

		//custom class attribute
		if ( $depth === 0 ){
			$atts['class'] = 'wcf-nav-item';
		}

		/**
		 * Filters the HTML attributes applied to a menu item's anchor element.
		 *
		 * @since 3.6.0
		 * @since 4.1.0 The `$depth` parameter was added.
		 *
		 * @param array $atts {
		 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
		 *
		 *     @type string $title        Title attribute.
		 *     @type string $target       Target attribute.
		 *     @type string $rel          The rel attribute.
		 *     @type string $href         The href attribute.
		 *     @type string $aria-current The aria-current attribute.
		 * }
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$atts       = apply_filters( 'nav_menu_link_attributes', $atts, $menu_item, $args, $depth );
		$attributes = $this->build_atts( $atts );

		/** This filter is documented in wp-includes/post-template.php */
		$title = apply_filters( 'the_title', $menu_item->title, $menu_item->ID );

		/**
		 * Filters a menu item's title.
		 *
		 * @since 4.4.0
		 *
		 * @param string   $title     The menu item's title.
		 * @param WP_Post  $menu_item The current menu item object.
		 * @param stdClass $args      An object of wp_nav_menu() arguments.
		 * @param int      $depth     Depth of menu item. Used for padding.
		 */
		$title = apply_filters( 'nav_menu_item_title', $title, $menu_item, $args, $depth );

		//custom indicator
		$submenu_indicator = '';
		if ( in_array( 'menu-item-has-children', $classes ) || ! empty( $buildercontent ) ) {
			// Use an if statement to conditionally display the submenu indicator icon
			if ( ! empty( $args->submenu_indicator_icon ) ) {
				$submenu_indicator .= '<span class="wcf-submenu-indicator">' . $args->submenu_indicator_icon . '</span>';
			}
		}
		
		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		if( isset($this->elementor_settings['remove_span']) && $this->elementor_settings['remove_span'] == true){		
			$item_output .= $args->link_before . $title . $args->link_after;				
		}else{
			$item_output .= $args->link_before .'<span class="menu-text" data-text="'.$title.'">'. $title .'</span>'. $args->link_after;			
		}
		$item_output .= $badge;
		$item_output .= $submenu_indicator . '</a>';
		$item_output .= $args->after;

		//mega menu content
		if ( ! empty( $buildercontent ) ) {
			$item_output .= sprintf( '<div class="wcf-mega-menu-panel" style="%1s">%2s</div>', $styles, $buildercontent );
		}

		/**
		 * Filters a menu item's starting output.
		 *
		 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
		 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
		 * no filter for modifying the opening and closing `<li>` for a menu item.
		 *
		 * @since 3.0.0
		 *
		 * @param string   $item_output The menu item's starting HTML output.
		 * @param WP_Post  $menu_item   Menu item data object.
		 * @param int      $depth       Depth of menu item. Used for padding.
		 * @param stdClass $args        An object of wp_nav_menu() arguments.
		 */
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $menu_item, $depth, $args );
	}

}
