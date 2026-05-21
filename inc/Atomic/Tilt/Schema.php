<?php

namespace WCF_ADDONS\Atomic\Tilt;

use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{
	const SECTION_ANCHOR =
	'aae_tilt_section_anchor';

	const ENABLE =
	'aae_tilt_enable';

	const MAX =
	'aae_tilt_max';

	const SPEED =
	'aae_tilt_speed';

	const SCALE =
	'aae_tilt_scale';

	const PERSPECTIVE =
	'aae_tilt_perspective';

	const GLARE =
	'aae_tilt_glare';

	const MAX_GLARE =
	'aae_tilt_max_glare';

	const RESET =
	'aae_tilt_reset';

	const TRANSITION =
	'aae_tilt_transition';

	const GYROSCOPE =
	'aae_tilt_gyroscope';

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

		$schema[self::ENABLE] =
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

			self::MAX,
			self::SPEED,
			self::SCALE,
			self::PERSPECTIVE,
			self::MAX_GLARE,
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
		| Boolean Responsive Fields
		|------------------------------------------------------------------
		*/

		$boolean_fields = [

			self::GLARE,
			self::RESET,
			self::TRANSITION,
			self::GYROSCOPE,
		];

		foreach ($boolean_fields as $field) {

			$schema[$field] =
				Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => false,
				]);
		}

		return $schema;
	}
}