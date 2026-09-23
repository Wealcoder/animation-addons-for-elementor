<?php

namespace Wealcoder\AnimationAddons\Atomic\HorizontalScrollAnim;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Wealcoder\AnimationAddons\Atomic\PropTypes\Responsive_JSON_Prop_Type;

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
	/**
	 * Container types only — the effect lays a container's CHILDREN out as a
	 * horizontal track, so an element with no children has nothing to scroll.
	 *
	 * `e-div-block` is the one container deliberately left out, and it is the
	 * reason this list is not simply Sticky's: the track needs the children
	 * laid out in a row, which a block container does not do.
	 *
	 * The types added below are containers whose DEFAULT display is block
	 * (`e-tab`, `e-tabs-content-area`, `e-tab-content`, and the two AAE panel
	 * children) as well as ones that default to flex (`e-tabs`,
	 * `e-tabs-menu`). They are all listed because display is a style prop the
	 * user owns: a tab panel set to `display: flex` is as valid a horizontal
	 * track as an `e-flexbox` is. The section appearing there is the point —
	 * on a still-block container the effect does nothing until the user
	 * changes display, exactly as it would on an `e-flexbox` set to block.
	 */
	public static function targeted_elements(): array {
		return [
			'e-flexbox',
			'e-grid',

			// Elementor core Tabs family.
			'e-tabs',
			'e-tabs-menu',
			'e-tab',
			'e-tabs-content-area',
			'e-tab-content',

			// AAE composite panel children.
			'e-aae-a-accordion-item',
			'e-aae-a-toggle-pane',
		];
	}
}
