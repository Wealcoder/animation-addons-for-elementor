<?php
/**
 * AAE Comment Avatar — "current comment" leaf widget.
 *
 * Renders the avatar of whichever comment AAE_A_Comment_Item is currently
 * repeating over (via `$GLOBALS['comment']`), the same "current post"
 * pattern as AAE_A_Post_Image reading the global $post.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets\Widgets\Comments;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Comment_Avatar extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-comment-avatar';
	}

	public function get_title() {
		return esc_html__( 'AAE Comment Avatar', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_keywords() {
		return [ 'comment', 'avatar', 'gravatar', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'     => Classes_Prop_Type::make()->default( [] ),
			'attributes'  => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'avatar_size' => Number_Prop_Type::make()->default( 60 ),
			'avatar_html' => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Avatar Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Number_Control::bind_to( 'avatar_size' )
						->set_label( __( 'Size (px)', 'animation-addons-for-elementor' ) )
						->set_min( 16 )
						->set_max( 300 ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant(
					Style_Variant::make()
						->add_prop( 'display', String_Prop_Type::generate( 'block' ) )
						->add_prop( 'overflow', String_Prop_Type::generate( 'hidden' ) )
						->add_prop( 'line-height', String_Prop_Type::generate( '0' ) )
						->add_prop( 'border-radius', Size_Prop_Type::generate( [ 'size' => 50, 'unit' => '%' ] ) )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-comment-avatar' => __DIR__ . '/aae-a-comment-avatar.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();
		$size     = ! empty( $settings['avatar_size'] ) ? (int) $settings['avatar_size'] : 60;
		$comment_id = get_comment_ID();

		if ( $comment_id ) {
			$settings['avatar_html'] = (string) get_avatar( $comment_id, $size, '', '', [ 'class' => 'aae-a-comment-avatar-img' ] );
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['avatar_html'] = (string) get_avatar( 'sample@example.com', $size, '', '', [ 'class' => 'aae-a-comment-avatar-img' ] );
		} else {
			$settings['avatar_html'] = '';
		}

		return $settings;
	}
}
