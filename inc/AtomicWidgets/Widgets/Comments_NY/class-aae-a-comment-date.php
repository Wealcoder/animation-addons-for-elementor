<?php
/**
 * AAE Comment Date — "current comment" leaf widget.
 *
 * Outputs the current comment's date (+ optional time), formats
 * configurable, falling back to the site's Settings > General date/time
 * formats when left blank.
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
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Comment_Date extends Atomic_Widget_Base {
	use Has_Template;

	public static function get_element_type(): string {
		return 'e-aae-a-comment-date';
	}

	public function get_title() {
		return esc_html__( 'AAE Comment Date', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_keywords() {
		return [ 'comment', 'date', 'time', 'atomic', 'dynamic' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'       => Classes_Prop_Type::make()->default( [] ),
			'attributes'    => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'date_format'   => String_Prop_Type::make()->default( '' ),
			'time_format'   => String_Prop_Type::make()->default( '' ),
			'show_time'     => Boolean_Prop_Type::make()->default( true ),
			'separator'     => String_Prop_Type::make()->default( ' at ' ),
			'comment_date'  => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
			'comment_time'  => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
			'datetime_attr' => String_Prop_Type::make()->default( '' )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Date Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( 'date_format' )
						->set_label( __( 'Date Format', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Site default', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_time' )
						->set_label( __( 'Show Time', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'time_format' )
						->set_label( __( 'Time Format', 'animation-addons-for-elementor' ) )
						->set_placeholder( __( 'Site default', 'animation-addons-for-elementor' ) ),

					Text_Control::bind_to( 'separator' )
						->set_label( __( 'Date/Time Separator', 'animation-addons-for-elementor' ) ),
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
			'elementor/elements/aae-a-comment-date' => __DIR__ . '/aae-a-comment-date.html.twig',
		];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-comments-css' ];
	}

	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$date_format = ! empty( $settings['date_format'] ) ? $settings['date_format'] : get_option( 'date_format' );
		$time_format = ! empty( $settings['time_format'] ) ? $settings['time_format'] : get_option( 'time_format' );
		$show_time   = ! isset( $settings['show_time'] ) || $settings['show_time'];

		$comment_id = get_comment_ID();

		if ( $comment_id ) {
			$settings['comment_date']  = get_comment_date( $date_format );
			$settings['comment_time']  = $show_time ? get_comment_time( $time_format ) : '';
			$settings['datetime_attr'] = get_comment_date( 'c' );
		} elseif ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$settings['comment_date']  = date_i18n( $date_format );
			$settings['comment_time']  = $show_time ? date_i18n( $time_format ) : '';
			$settings['datetime_attr'] = date_i18n( 'c' );
		} else {
			$settings['comment_date']  = '';
			$settings['comment_time']  = '';
			$settings['datetime_attr'] = '';
		}

		return $settings;
	}
}
