<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\LoopGrid;

use Elementor\Modules\AtomicWidgets\Controls\Base\Atomic_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AAE Query Chips — AJAX-searchable MULTI select, bound to a String_Array prop.
 *
 * Elementor 4.1.x atomic controls only ship a SINGLE-value ajax select
 * (`query`) and a static-options multi select (`chips`); the multi ajax
 * `query-chips` control exists only in the 4.3-dev packages. This control
 * fills the gap: it serialises as { type: 'aae-query-chips' } and the editing
 * panel routes it to the React component registered under the same id in
 * src/modules/atomic/element-controls/QueryChipsControl.jsx (an MUI
 * Autocomplete, multiple, fed by the `aae_loop_query_options` admin-ajax
 * search endpoint).
 *
 * Each selected option is stored as ONE string of the bound String_Array:
 * a JSON object `{"id":123,"label":"Hello"}` — the id feeds the WP_Query,
 * the label re-hydrates the chip when the panel reopens.
 */
class AAE_Query_Chips_Control extends Atomic_Control_Base {

	/** 'post' (search posts by title/ID) or 'term' (search taxonomy terms). */
	private string $kind = 'post';

	/** Taxonomy name — only used when $kind === 'term'. */
	private string $taxonomy = '';

	private ?string $placeholder = null;

	public function get_type(): string {
		return 'aae-query-chips';
	}

	public function set_kind( string $kind ): self {
		$this->kind = $kind;
		return $this;
	}

	public function set_taxonomy( string $taxonomy ): self {
		$this->taxonomy = $taxonomy;
		return $this;
	}

	public function set_placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function get_props(): array {
		return [
			'kind'        => $this->kind,
			'taxonomy'    => $this->taxonomy,
			'placeholder' => $this->placeholder,
		];
	}
}
