<?php

namespace WCF_ADDONS\Atomic\CustomCss;

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

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
			'e-tabs',
			'e-tabs-controls',
			'e-tabs-content',
			'e-tabs-content-wrapper',		
			'e-aae-a-nav',
			'e-aae-a-offcanvas',
		];
	}
}
