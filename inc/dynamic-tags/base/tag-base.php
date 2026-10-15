<?php
/**
 * Base Tag Class for AAE Dynamic Tags
 *
 * This abstract class extends Elementor's base tag functionality
 * and provides additional features specific to AAE Pro.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags;

use Elementor\Core\DynamicTags\Tag as Base_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base Tag Class for AAE Dynamic Tags.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags
 */
abstract class Tag_Base extends Base_Tag {

	use Tag_Trait;
	use CustomPostIdTrait;
	use Atomic_Compatibility_Trait;

	/**
	 * Check if tag is editable based on license status.
	 *
	 * @return bool
	 */
	public function is_editable() {
		// Always return true to allow tags to appear in the editor.
		// License check is handled at the widget / control level if needed.
		// Tags should be visible even if not editable.
		return true;
	}

	/**
	 * Get current post-ID with template fallback
	 *
	 * @return int
	 */
	protected function get_post_id() {
		return $this->get_custom_id();
	}

	/**
	 * Get the current post permalink
	 *
	 * @return string|false
	 */
	protected function get_permalink() {
		$post_id = $this->get_post_id();
		return get_permalink( $post_id );
	}

	/**
	 * Get post-object.
	 *
	 * @param int|null $post_id Post ID.
	 *
	 * @return \WP_Post|null
	 */
	protected function get_post( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = $this->get_post_id();
		}
		return get_post( $post_id );
	}

	/**
	 * Check if we're in the editor.
	 *
	 * @return bool
	 */
	protected function is_editor() {
		return \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * Get settings with default value
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	protected function get_setting( $key, $default_value = '' ) {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default_value;
	}
}
