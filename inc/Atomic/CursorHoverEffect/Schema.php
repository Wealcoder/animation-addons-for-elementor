<?php

namespace WCF_ADDONS\Atomic\CursorHoverEffect;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{

	/*
	|--------------------------------------------------------------------------
	| Section Anchor
	|--------------------------------------------------------------------------
	*/

	const SECTION_ANCHOR =
	'aae_section_aae_cursor_hover_effect_anchor';

	/*
	|--------------------------------------------------------------------------
	| Text
	|--------------------------------------------------------------------------
	*/

	const TEXT =
	'aae_cursor_hover_text';

	/*
	|--------------------------------------------------------------------------
	| Colors
	|--------------------------------------------------------------------------
	*/

	const COLOR =
	'aae_cursor_hover_color';

	const BACKGROUND =
	'aae_cursor_hover_background';

	/*
	|--------------------------------------------------------------------------
	| Width
	|--------------------------------------------------------------------------
	*/

	const WIDTH =
	'aae_cursor_hover_width';	

	/*
	|--------------------------------------------------------------------------
	| Height
	|--------------------------------------------------------------------------
	*/

	const HEIGHT =
	'aae_cursor_hover_height';
	

	/*
	|--------------------------------------------------------------------------
	| Border
	|--------------------------------------------------------------------------
	*/

	const BORDER =
	'aae_cursor_hover_border';

	const BORDER_RADIUS =
	'aae_cursor_hover_border_radius';

	const ENABLE_EDITOR =
	'aae_cursor_hover_enable_editor';

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


		// ENABLE ON EDITOR 

		$schema[self::ENABLE_EDITOR] =
			Boolean_Prop_Type::make()
			->default(false);

		/*
		|--------------------------------------------------------------------------
		| Text
		|--------------------------------------------------------------------------
		*/

		$schema[self::TEXT] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		/*
		|--------------------------------------------------------------------------
		| Text Color
		|--------------------------------------------------------------------------
		*/

		$schema[self::COLOR] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '#ffffff',
			]);

		/*
		|--------------------------------------------------------------------------
		| Background
		|--------------------------------------------------------------------------
		*/

		$schema[self::BACKGROUND] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '#000000',
			]);

		/*
		|--------------------------------------------------------------------------
		| Width
		|--------------------------------------------------------------------------
		*/

		$schema[self::WIDTH] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '100px',
			]);		

		/*
		|--------------------------------------------------------------------------
		| Height
		|--------------------------------------------------------------------------
		*/

		$schema[self::HEIGHT] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '100px',
			]);		

		/*
		|--------------------------------------------------------------------------
		| Border
		|--------------------------------------------------------------------------
		*/

		$schema[self::BORDER] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '1px solid #ffffff',
			]);

		/*
		|--------------------------------------------------------------------------
		| Border Radius
		|--------------------------------------------------------------------------
		*/

		$schema[self::BORDER_RADIUS] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '100%',
			]);

		return $schema;
	}
}
