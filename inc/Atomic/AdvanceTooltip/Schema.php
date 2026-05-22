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

	const DURATION =
	'aae_advance_tooltip_duration';

	const ARROW_SIZE =
	'aae_advance_tooltip_arrow_size';

	const BORDER_RADIUS =
	'aae_advance_tooltip_borderRadius';

	const ALIGNMENT =
	'aae_advance_tooltip_alignment';

	const TOOLTIP_ENABLE_EDITOR =
	'aae_advance_tooltip_enable_editor';

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
			self::DURATION,
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
		| Border Radius
		|------------------------------------------------------------------
		*/

		$schema[self::BORDER_RADIUS] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
					'unit' => 'px',
					'isLinked' => true,
				],
			]);

		$schema[self::TOOLTIP_ENABLE_EDITOR] =
			Boolean_Prop_Type::make()
			->default(false);

		return $schema;
	}
}