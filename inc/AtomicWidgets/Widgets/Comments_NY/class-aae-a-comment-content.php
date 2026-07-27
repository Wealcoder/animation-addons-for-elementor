<?php
/**
 * AAE Comment Content — "current comment" leaf widget.
 *
 * Outputs the current comment's text, run through core's `comment_text`
 * filter chain (wpautop, make_clickable, convert_smilies, …) via
 * `get_comment_text()` — same filtered output core themes render.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Comment_Content extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-comment-content';
	}

	public function get_title() {
		return esc_html__( 'AAE Comment Content', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-text-align-left';
	}

	public function get_keywords() {
		return [ 'comment', 'content', 'text', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'      => Classes_Prop_Type::make()->default( [] ),
			'attributes'   => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'tag'          => String_Prop_Type::make()->default( 'div' ),
			'content_html' => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Content Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'div', 'label' => 'div' ],
							[ 'value' => 'p',   'label' => 'p' ],
						] ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_prop( 'display', String_Prop_Type::generate( 'block' ) ) ),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-comment-content' => __DIR__ . '/aae-a-comment-content.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	public function get_atomic_settings(): array {
		$settings   = parent::get_atomic_settings();
		$comment_id = get_comment_ID();

		if ( $comment_id ) {
			$settings['content_html'] = get_comment_text( $comment_id );
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['content_html'] = '<p>' . esc_html__( 'This is a sample comment used to preview the layout in the editor.', 'animation-addons-for-elementor' ) . '</p>';
		} else {
			$settings['content_html'] = '';
		}

		return $settings;
	}
}
