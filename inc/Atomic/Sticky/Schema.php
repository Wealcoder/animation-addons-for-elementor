<?php

namespace WCF_ADDONS\Atomic\Sticky;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\PropTypes\Responsive_JSON_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {

	const STICKY_SECTION_ANCHOR      = 'aae_sticky_section_anchor';

	const STICKY_ENABLE              = 'aae_sticky_enable';

	const STICKY_PIN_TRIGGER         = 'aae_sticky_pin_trigger';
	const STICKY_CUSTOM_PIN_AREA     = 'aae_sticky_custom_pin_area';

	const STICKY_PIN_END_TRIGGER     = 'aae_sticky_pin_end_trigger';
	const STICKY_CUSTOM_PIN_END_AREA = 'aae_sticky_custom_pin_end_area';

	const STICKY_PIN                 = 'aae_sticky_pin';
	const STICKY_CUSTOM_PIN          = 'aae_sticky_custom_pin';

	const STICKY_PIN_START           = 'aae_sticky_pin_start';
	const STICKY_CUSTOM_PIN_START    = 'aae_sticky_custom_pin_start';

	const STICKY_PIN_END             = 'aae_sticky_pin_end';
	const STICKY_CUSTOM_PIN_END      = 'aae_sticky_custom_pin_end';

	const STICKY_PIN_SPACING         = 'aae_sticky_pin_spacing';

	const STICKY_PIN_MARKERS         = 'aae_sticky_pin_markers';

	const STICKY_BORDER              = 'aae_sticky_border';

	const STICKY_ENABLE_EDITOR       = 'aae_sticky_enable_editor';

	const STICKY_CUSTOM_CSS          = 'aae_sticky_custom_css';

	public function register(): void {

		add_filter(
			'elementor/atomic-widgets/props-schema',
			[ $this, 'add_sticky_props' ]
		);
	}

	public function add_sticky_props( array $schema ): array {

		if ( ! class_exists( Boolean_Prop_Type::class ) ) {
			return $schema;
		}

		/*
		|--------------------------------------------------------------------------
		| Anchor
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_SECTION_ANCHOR ] =
			Section_Anchor_Prop_Type::make()->default( '' );

		/*
		|--------------------------------------------------------------------------
		| Enable
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_ENABLE ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => false,
				]);

		/*
		|--------------------------------------------------------------------------
		| Sticky Enabled Dependency
		|--------------------------------------------------------------------------
		*/

		$sticky_enabled_dependency = Dependency_Manager::make()
			->where([
				'operator' => 'eq',
				'path'     => [ self::STICKY_ENABLE ],
				'value'    => false,
				'effect'   => 'hide',
			])
			->get();

		/*
		|--------------------------------------------------------------------------
		| Pin Trigger
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN_TRIGGER ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => 'default',
				])
				->set_dependencies( $sticky_enabled_dependency );

		$schema[ self::STICKY_CUSTOM_PIN_AREA ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);

		/*
		|--------------------------------------------------------------------------
		| Pin End Trigger
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN_END_TRIGGER ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => 'default',
				])
				->set_dependencies( $sticky_enabled_dependency );

		$schema[ self::STICKY_CUSTOM_PIN_END_AREA ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);

		/*
		|--------------------------------------------------------------------------
		| Pin
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => true,
				])
				->set_dependencies( $sticky_enabled_dependency );

		$schema[ self::STICKY_CUSTOM_PIN ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);

		/*
		|--------------------------------------------------------------------------
		| Pin Start
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN_START ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => 'top top',
				])
				->set_dependencies( $sticky_enabled_dependency );

		$schema[ self::STICKY_CUSTOM_PIN_START ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);

		/*
		|--------------------------------------------------------------------------
		| Pin End
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN_END ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => 'bottom bottom',
				])
				->set_dependencies( $sticky_enabled_dependency );

		$schema[ self::STICKY_CUSTOM_PIN_END ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => '',
				]);

		/*
		|--------------------------------------------------------------------------
		| Pin Spacing
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN_SPACING ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => true,
				])
				->set_dependencies( $sticky_enabled_dependency );

		/*
		|--------------------------------------------------------------------------
		| Pin Markers
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_PIN_MARKERS ] =
			Boolean_Prop_Type::make()
				->default( false )
				->set_dependencies( $sticky_enabled_dependency );

		/*
		|--------------------------------------------------------------------------
		| Border
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_BORDER ] =
			Responsive_JSON_Prop_Type::make()
				->default([
					'desktop' => [
						'style'  => '',
						'width'  => [
							'top'    => '',
							'right'  => '',
							'bottom' => '',
							'left'   => '',
						],
						'color'  => '',
						'radius' => '',
					],
				])
				->set_dependencies( $sticky_enabled_dependency );

		$schema[ self::STICKY_CUSTOM_CSS ] =
			String_Prop_Type::make()
				->default( '' )
				->set_dependencies( $sticky_enabled_dependency );

		/*
		|--------------------------------------------------------------------------
		| Enable Editor
		|--------------------------------------------------------------------------
		*/

		$schema[ self::STICKY_ENABLE_EDITOR ] =
			Boolean_Prop_Type::make()
				->default( false )
				->set_dependencies( $sticky_enabled_dependency );

		return $schema;
	}

	public static function targeted_elements(): array {
		return [ 'e-flexbox', 'e-div-block', 'e-grid' ];
	}
}