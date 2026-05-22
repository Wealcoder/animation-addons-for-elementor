<?php

namespace WCF_ADDONS\Atomic\ScrollTo;

use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{
	const SECTION_ANCHOR =
	'aae_scroll_to_section_anchor';

	const ENABLE =
	'aae_scroll_to_enable';

	const TARGET =
	'aae_scroll_to_target';

	const DURATION =
	'aae_scroll_to_duration';

	const EASE =
	'aae_scroll_to_ease';

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

			self::TARGET,
			self::DURATION,
			self::EASE,
		];

		foreach ($fields as $field) {

			$schema[$field] =
				Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);
		}

		return $schema;
	}
}