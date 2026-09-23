<?php

namespace Wealcoder\AnimationAddons\Atomic\CustomCss;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Wealcoder\AnimationAddons\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if (! defined('ABSPATH')) {
	exit;
}

final class Schema
{

	const SECTION_ANCHOR = 'aae_section_aae_custom_css_anchor';

	const CSS = 'aae_custom_css_css';

	const ENABLE = 'aae_custom_css_enable';

	const ENABLE_EDITOR = 'aae_custom_css_enable_editor';

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

		$schema[self::ENABLE] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => false,
			]);

		$schema[self::ENABLE_EDITOR] =
			Boolean_Prop_Type::make()
			->default(false);

		$schema[self::CSS] =
			Responsive_JSON_Prop_Type::make()
			->default([
				'desktop' => '',
			]);

		return $schema;
	}

	/**
	 * NOT what decides where the panel section appears — Controls and Render
	 * both test Bootstrap::target_element_types(). This list exists only so
	 * Schema_Trim can union the two and starve neither reader, which is why a
	 * type here that is missing from the shared list keeps its props in the
	 * editor but shows no section.
	 *
	 * The four Tabs entries used to read `e-tabs-controls`, `e-tabs-content`
	 * and `e-tabs-content-wrapper`. No Elementor build has ever registered
	 * those names — the real family is `e-tabs-menu`, `e-tab`,
	 * `e-tabs-content-area` and `e-tab-content` (see
	 * elementor/modules/atomic-widgets/elements/atomic-tabs/) — so the three
	 * stale strings matched nothing and are corrected here.
	 */
	public static function target_element_types(): array
	{
		// all atomic widget support
		return [
			'e-heading',
			'e-paragraph',
			'e-button',
			'e-image',
			'e-svg',
			'e-flexbox',
			'e-div-block',
			'e-grid',
			'e-divider',
			'e-tabs',
			'e-tabs-menu',
			'e-tab',
			'e-tabs-content-area',
			'e-tab-content',
			'e-aae-a-nav',
			'e-aae-a-offcanvas',
			'e-aae-a-accordion-item',
			'e-aae-a-toggle-pane',
		];
	}
}
