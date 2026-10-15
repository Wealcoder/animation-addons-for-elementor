<?php
/**
 * Base Data Tag Class for AAE Dynamic Tags
 *
 * Used for tags that return structured data (images, galleries, etc.)
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Tags;

use Elementor\Core\DynamicTags\Data_Tag as Base_Data_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base Data Tag Class for AAE Dynamic Tags.
 *
 * Used for tags that return structured data (images, galleries, etc.).
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Tags
 */
abstract class Data_Tag_Base extends Base_Data_Tag {

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
	 * Get the current post-ID with template fallback.
	 *
	 * @return int
	 */
	protected function get_post_id() {
		return $this->get_custom_id();
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
	 * Get settings with default value.
	 *
	 * @param string $key     Setting the key.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	protected function get_setting( $key, $default_value = '' ) {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default_value;
	}
}
