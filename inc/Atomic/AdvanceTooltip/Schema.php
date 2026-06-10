<?php

namespace WCF_ADDONS\Atomic\AdvanceTooltip;

use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{
	const SECTION_ANCHOR =
	'aae_advance_tooltip_section_anchor';

	const TOOLTIP_ENABLE =
	'aae_advance_tooltip_enable';

	const TEXT =
	'aae_advance_tooltip_text';

	const POSITION =
	'aae_advance_tooltip_position';

	const TRIGGER =
	'aae_advance_tooltip_trigger';

	const BG =
	'aae_advance_tooltip_bg';

	const COLOR =
	'aae_advance_tooltip_color';

	const WIDTH =
	'aae_advance_tooltip_width';

	const OFFSET =
	'aae_advance_tooltip_offset';

	const ARROW_ENABLE =
	'aae_advance_tooltip_arrow_enable';

	const ANIMATION =
	'aae_advance_tooltip_animation';

	const ARROW_SIZE =
	'aae_advance_tooltip_arrow_size';

	const BORDER =
	'aae_advance_tooltip_border';

	const ALIGNMENT =
	'aae_advance_tooltip_alignment';

	const TOOLTIP_ENABLE_EDITOR =
	'aae_advance_tooltip_enable_editor';

	const SHOW_DELAY =
	'aae_advance_tooltip_show_delay';

	const HIDE_DELAY =
	'aae_advance_tooltip_hide_delay';

	const PADDING =
	'aae_advance_tooltip_padding';

	const FONT_SIZE =
	'aae_advance_tooltip_font_size';

	const LINE_HEIGHT =
	'aae_advance_tooltip_line_height';

	public function register(): void
	{
		add_filter(
			'elementor/atomic-widgets/props-schema',
			[$this, 'add_props']
		);
	}

	public function add_props(
		array $schema
	): array {

		$schema[self::SECTION_ANCHOR] =
			Section_Anchor_Prop_Type::make()
			->default('');

		/*
		|------------------------------------------------------------------
		| Enable
		|------------------------------------------------------------------
		*/

		$schema[self::TOOLTIP_ENABLE] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => false,
			]);

		/*
		|------------------------------------------------------------------
		| Primitive Responsive Fields
		|------------------------------------------------------------------
		*/

		$fields = [

			self::TEXT,
			self::POSITION,
			self::TRIGGER,
			self::BG,
			self::COLOR,
			self::WIDTH,
			self::OFFSET,
			self::ANIMATION,
			self::ARROW_SIZE,
			self::ALIGNMENT,
		];

		foreach ($fields as $field) {

			$schema[$field] =
				Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);
		}

		/*
		|------------------------------------------------------------------
		| Arrow Enable
		|------------------------------------------------------------------
		*/

		$schema[self::ARROW_ENABLE] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => true,
			]);

		/*
		|------------------------------------------------------------------
		| Border
		|------------------------------------------------------------------
		*/

		$schema[self::BORDER] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => [
					'style'  => '',
					'width'  => ['top' => '', 'right' => '', 'bottom' => '', 'left' => ''],
					'color'  => '',
					'radius' => '8px',
				],
			]);

		$schema[self::TOOLTIP_ENABLE_EDITOR] =
			Boolean_Prop_Type::make()
			->default(false);

		/*
		|------------------------------------------------------------------
		| New interactive controls
		|------------------------------------------------------------------
		*/

		$schema[self::SHOW_DELAY] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => 0,
			]);

		$schema[self::HIDE_DELAY] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => 0,
			]);

		$schema[self::PADDING] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => [
					'top' => '8',
					'right' => '12',
					'bottom' => '8',
					'left' => '12',
					'unit' => 'px',
				],
			]);

		$schema[self::FONT_SIZE] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => [
					'size' => 14,
					'unit' => 'px',
				],
			]);

		$schema[self::LINE_HEIGHT] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => [
					'size' => 1.5,
					'unit' => '',
				],
			]);

		return $schema;
	}
}