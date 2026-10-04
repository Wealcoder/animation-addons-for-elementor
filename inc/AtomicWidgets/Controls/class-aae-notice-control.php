<?php
/**
 * AAE Notice — an instruction card inside an atomic widget's panel.
 *
 * An element-control (no stored value) that the editing panel routes to the
 * React component registered as `aae-notice` in
 * src/modules/atomic/element-controls/NoticeControl.jsx. It exists so a widget
 * can TELL the builder something a control cannot: why a list is empty, what a
 * setting depends on, how to fix the site so a feature works.
 *
 * Static copy goes in through set_text() and friends. Copy that depends on the
 * ELEMENT (its own settings, the page it is on) cannot be written here —
 * define_atomic_controls() runs once per widget TYPE, not per instance — so a
 * dynamic notice passes a `source` the component resolves against the element
 * at render time (see NoticeControl.jsx; ProNoticeControl does the same with
 * `elementor.config.v4Promotions`).
 *
 * BOTH set_label() and set_meta() must be called before this is serialised:
 * Element_Control_Base types get_label(): string and get_meta(): array while
 * defaulting both to null, so jsonSerialize() throws a TypeError and the editor
 * fails to boot. make() sets safe defaults for that reason.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Controls;

use Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Controls\Base\Element_Control_Base' ) ) {
	return;
}

class AAE_Notice_Control extends Element_Control_Base {

	/** 'info' | 'warning' | 'success'. */
	private string $tone = 'info';
	private string $title = '';
	private string $text = '';
	private string $link_url = '';
	private string $link_label = '';
	/** A key the component may resolve dynamically (e.g. 'loop-grid-taxonomies'). */
	private string $source = '';

	public static function make(): self {
		$control = new self();
		$control->set_label( '' );
		$control->set_meta( [] );
		return $control;
	}

	public function get_type(): string {
		return 'aae-notice';
	}

	public function set_tone( string $tone ): self {
		$this->tone = in_array( $tone, [ 'info', 'warning', 'success' ], true ) ? $tone : 'info';
		return $this;
	}

	public function set_title( string $title ): self {
		$this->title = $title;
		return $this;
	}

	public function set_text( string $text ): self {
		$this->text = $text;
		return $this;
	}

	public function set_link( string $url, string $label ): self {
		$this->link_url   = $url;
		$this->link_label = $label;
		return $this;
	}

	public function set_source( string $source ): self {
		$this->source = $source;
		return $this;
	}

	public function get_props(): array {
		return [
			'tone'      => $this->tone,
			'title'     => $this->title,
			'text'      => $this->text,
			'linkUrl'   => $this->link_url,
			'linkLabel' => $this->link_label,
			'source'    => $this->source,
		];
	}
}
