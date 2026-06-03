<?php

namespace WCF_ADDONS\Atomic\HorizontalScrollAnim;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{
	
	const SECTION_ANCHOR = 'aae_horizontal_section_anchor';

	const ENABLE = 'aae_horizontal_enable';

	const START = 'aae_horizontal_start';
	const MARKERS = 'aae_horizontal_markers';
	const SPEED = 'aae_horizontal_speed';

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
			Responsive_JSON_Prop_Type::make()
			->default(false);


		/*
		|--------------------------------------------------------------------------
		| Responsive Field
		|--------------------------------------------------------------------------
		*/

		$schema[self::START] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => 'top top',
			]);

		$schema[self::MARKERS] =
			Boolean_Prop_Type::make()
			->default(false);

		$schema[self::SPEED] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => 1,
			]);

		return $schema;
	}
	public static function targeted_elements(): array {
		return [ 'e-flexbox', 'e-grid' ];
	}
}
