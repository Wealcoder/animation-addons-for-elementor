<?php

namespace WCF_ADDONS\Atomic\HorizontalScrollAnim;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{

	/*
	|--------------------------------------------------------------------------
	| CHANGE THESE
	|--------------------------------------------------------------------------
	*/

	const SECTION_ANCHOR =
	'aae_horizontal_scroll_anim_section_anchor';

	const ENABLE =
	'aae_horizontal_scroll_anim_enable';

	const WIDTH =
	'aae_horizontal_scroll_anim_width';

	const WIDTH_CUSTOM =
	'aae_horizontal_scroll_anim_width_custom';

	const END =
	'aae_horizontal_scroll_anim_end';

	const END_CUSTOM =
	'aae_horizontal_scroll_anim_end_custom';

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

		/*
		|--------------------------------------------------------------------------
		| Placeholder Anchor
		|--------------------------------------------------------------------------
		*/

		$schema[self::SECTION_ANCHOR] =
			Section_Anchor_Prop_Type::make()
			->default('');

		/*
		|--------------------------------------------------------------------------
		| Enable Switch
		|--------------------------------------------------------------------------
		*/

		$schema[self::ENABLE] =
			Boolean_Prop_Type::make()
			->default(false);


		/*
		|--------------------------------------------------------------------------
		| Responsive Field
		|--------------------------------------------------------------------------
		*/

		$schema[self::WIDTH] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		$schema[self::WIDTH_CUSTOM] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		$schema[self::END] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		$schema[self::END_CUSTOM] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		return $schema;
	}
}
