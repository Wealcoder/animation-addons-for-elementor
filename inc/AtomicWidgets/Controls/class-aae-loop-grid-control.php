<?php
/**
 * AAE Loop Grid Control — a dropdown of the Loop Grids on this page.
 *
 * Binds the same plain String prop the Text_Control it replaces did (a filter's
 * `target_grid`), so nothing saved changes shape and the server keeps resolving
 * it through `Aaeaddon_A_Loop_Grid::resolve_grid_ref()`. What changes is the
 * panel: instead of copying an element id or remembering a name, the builder
 * picks the grid from a list.
 *
 * Which grids exist is decided in the browser, per DOCUMENT — controls are built
 * once per widget TYPE, so PHP cannot know what is on the page being edited. The
 * component (src/modules/atomic/element-controls/LoopGridControl.jsx) reads the
 * live element tree, so a grid added, renamed or deleted shows up without a
 * reload.
 *
 * Picking a grid stores its ELEMENT id, which survives the grid being renamed.
 * A value the list does not hold — a Grid ID typed by hand, an id from a grid
 * that was deleted — is never blanked: the control shows it in a text box.
 *
 *   Aaeaddon_Loop_Grid_Control::bind_to( 'target_grid' )
 *       ->set_label( __( 'Loop Grid ID', … ) )
 *
 * @package AnimationAddonsForElementor
 */

namespace Wealcoder\AnimationAddons\AtomicWidgets\Controls;

use Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Aaeaddon_Loop_Grid_Control extends Atomic_Control_Base {

	private ?string $placeholder = null;

	public function get_type(): string {
		return 'aae-loop-grid';
	}

	public function set_placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function get_props(): array {
		return array(
			'placeholder' => $this->placeholder,
		);
	}
}
