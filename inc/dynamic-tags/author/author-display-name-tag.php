<?php
/**
 * Author Display Name Dynamic Tag
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags\Author;

use Wealcoder\AnimationAddons\DynamicTags\Tags\Tag_Base;
use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Author Display Name Dynamic Tag.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags\Author
 */
class Author_Display_Name_Tag extends Tag_Base {

	/**
	 * Unique tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'aae-author-display-name';
	}

	/**
	 * Tag title for Elementor UI.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Author Display Name', 'animation-addons-for-elementor' );
	}

	/**
	 * Tag group(s).
	 *
	 * @return array<int,string>
	 */
	public function get_group() {
		return array( 'aae-author' );
	}

	/**
	 * Supported categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	/**
	 * Render the author's display name.
	 *
	 * @return void
	 */
	public function render() {
		$author_id = get_the_author_meta( 'ID' );
		
		if(!(bool)get_userdata( absint( $author_id ) )){
			$author_id = 1;
		}
	
		echo esc_html( get_the_author_meta( 'display_name', $author_id ) );
	}
}
