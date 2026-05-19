<?php

namespace WCF_ADDONS\Atomic\MouseMoveEffect;

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
	'aae_mouse_move_effect_section_anchor';

	const ENABLE =
	'aae_mouse_move_effect_enable';

	const ENABLE_EDITOR =
	'aae_mouse_move_effect_enable_editor';

	const MOVEMENT_WRAPPER =
	'aae_mouse_move_effect_movement_wrapper';

	const MOVE_X =
	'aae_mouse_move_effect_move_x';

	const MOVE_Y =
	'aae_mouse_move_effect_move_y';

	const DURATION =
	'aae_mouse_move_effect_duration';

	const CUSTOMS =
	'aae_mouse_move_effect_customs';

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
		| Enable
		|--------------------------------------------------------------------------
		*/

		$schema[self::ENABLE] =
			Boolean_Prop_Type::make()
			->default(false);

		$schema[self::ENABLE_EDITOR] =
			Boolean_Prop_Type::make()
			->default(false);

		/*
		|--------------------------------------------------------------------------
		| Movement Wrapper
		|--------------------------------------------------------------------------
		*/

		$schema[self::MOVEMENT_WRAPPER] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => 'default',
			]);

		/*
		|--------------------------------------------------------------------------
		| Move X
		|--------------------------------------------------------------------------
		*/

		$schema[self::MOVE_X] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '100',
			]);

		/*
		|--------------------------------------------------------------------------
		| Move Y
		|--------------------------------------------------------------------------
		*/

		$schema[self::MOVE_Y] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '100',
			]);

		/*
		|--------------------------------------------------------------------------
		| Duration
		|--------------------------------------------------------------------------
		*/

		$schema[self::DURATION] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '1',
			]);

		/*
		|--------------------------------------------------------------------------
		| Customs
		|--------------------------------------------------------------------------
		*/

		$schema[self::CUSTOMS] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		return $schema;
	}
}
