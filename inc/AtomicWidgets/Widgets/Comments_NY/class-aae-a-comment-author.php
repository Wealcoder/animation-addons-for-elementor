<?php
/**
 * AAE Comment Author — "current comment" leaf widget.
 *
 * Outputs the current comment's author name, linked to their website (via
 * core's `get_comment_author_link()`, which already produces the correct
 * `rel="external nofollow ugc"` anchor) unless disabled.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Comment_Author extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-comment-author';
	}

	public function get_title() {
		return esc_html__( 'AAE Comment Author', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_keywords() {
		return [ 'comment', 'author', 'name', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'     => Classes_Prop_Type::make()->default( [] ),
			'attributes'  => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'tag'         => String_Prop_Type::make()->default( 'span' ),
			'show_link'   => Boolean_Prop_Type::make()->default( true ),
			'author_html' => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Author Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'span', 'label' => 'span' ],
							[ 'value' => 'p',    'label' => 'p' ],
							[ 'value' => 'div',  'label' => 'div' ],
							[ 'value' => 'h4',   'label' => 'H4' ],
							[ 'value' => 'h5',   'label' => 'H5' ],
							[ 'value' => 'h6',   'label' => 'H6' ],
						] ),

					Switch_Control::bind_to( 'show_link' )
						->set_label( __( 'Link To Website', 'animation-addons-for-elementor' ) ),
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
			'elementor/elements/aae-a-comment-author' => __DIR__ . '/aae-a-comment-author.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	public function get_atomic_settings(): array {
		$settings   = parent::get_atomic_settings();
		$comment_id = get_comment_ID();
		$show_link  = ! isset( $settings['show_link'] ) || $settings['show_link'];

		if ( $comment_id ) {
			$settings['author_html'] = $show_link
				? (string) get_comment_author_link()
				: esc_html( get_comment_author() );
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['author_html'] = esc_html__( 'Jane Doe', 'animation-addons-for-elementor' );
		} else {
			$settings['author_html'] = '';
		}

		return $settings;
	}
}
