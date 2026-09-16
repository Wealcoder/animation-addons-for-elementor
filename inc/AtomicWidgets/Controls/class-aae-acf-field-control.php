<?php
/**
 * AAE ACF Field Control — a dropdown of ACF fields, narrowed to the grid's post type.
 *
 * Binds a plain String prop (the ACF field KEY, `field_…`) exactly as the
 * Text_Control it replaces did, so nothing saved changes shape and the
 * server keeps resolving the key through `Loop_Filter_Auth::resolve_acf()`.
 * What changes is the panel: instead of a box to paste a key into, the
 * builder picks a field by its label, grouped by field group.
 *
 * Which fields are offered is decided in the browser, per ELEMENT — controls
 * are built once per widget TYPE, so PHP cannot know which grid this instance
 * targets. The component (src/modules/atomic/element-controls/AcfFieldControl.jsx)
 * reads the sibling prop named by `post_type_prop` first; when that is empty
 * it finds the grid the filter targets (`grid_prop`, empty = the page's only
 * grid) and reads its `post_type`; a theme-builder document's Preview
 * Settings (`preview_type`) is the last resort. Fields whose group is located
 * on any other post type are hidden — they can never match this grid.
 *
 * `types` limits the ACF field types offered (the Date filter wants only
 * date pickers). A key that is not in the list — typed, or from another post
 * type — is never blanked: the control falls back to a text box holding it.
 *
 *   Aaeaddon_ACF_Field_Control::bind_to( 'acf_field' )
 *       ->set_label( __( 'ACF field', … ) )
 *       ->set_types( [ 'number', 'select', … ] )
 *
 * The catalogue comes from `wp_ajax_aae_loop_query_options` with
 * `kind=acf_field` (class-atomic.php), built by
 * `Loop_Filter_Auth::acf_field_catalogue()`.
 *
 * @package AnimationAddonsForElementor
 */

namespace Wealcoder\AnimationAddons\AtomicWidgets\Controls;

use Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Aaeaddon_ACF_Field_Control extends Atomic_Control_Base {

	/** @var string[] ACF field types to offer; empty = every type. */
	private array $types = array();
	private string $post_type_prop = 'acf_post_type';
	private string $grid_prop = 'target_grid';
	private ?string $placeholder = null;

	public function get_type(): string {
		return 'aae-acf-field';
	}

	public function set_types( array $types ): self {
		$this->types = array_values( array_map( 'strval', $types ) );
		return $this;
	}

	/** The sibling prop holding a builder-chosen post type ('' = from the grid). */
	public function set_post_type_prop( string $prop ): self {
		$this->post_type_prop = $prop;
		return $this;
	}

	/** The sibling prop naming the target grid's element id ('' = the page's grid). */
	public function set_grid_prop( string $prop ): self {
		$this->grid_prop = $prop;
		return $this;
	}

	public function set_placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function get_props(): array {
		return array(
			'types'        => $this->types,
			'postTypeProp' => $this->post_type_prop,
			'gridProp'     => $this->grid_prop,
			'placeholder'  => $this->placeholder,
		);
	}
}
