<?php
namespace WCF_ADDONS\Atomic\RegularAnimation;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use WCF_ADDONS\Atomic\Schema_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regular (preset-based) animation: fadeIn / slideUp / zoomIn etc. Applied
 * to every atomic widget in Bootstrap::target_element_types(). Non-responsive
 * — one flat prop per setting.
 */
final class Schema {

	const ANIM_EFFECT        = 'aae_anim_effect';
	const ANIM_TRIGGER       = 'aae_anim_trigger';
	const ANIM_DURATION      = 'aae_anim_duration';
	const ANIM_DELAY         = 'aae_anim_delay';
	const ANIM_EASING        = 'aae_anim_easing';
	const ANIM_REPEAT        = 'aae_anim_repeat';
	const ANIM_ENABLE_EDITOR = 'aae_anim_enable_editor';
	const ANIM_PLAY_TOKEN    = 'aae_anim_play_token';

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_animation_props' ] );
	}

	public function add_animation_props( array $schema ): array {
		if ( ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::ANIM_EFFECT ] = String_Prop_Type::make()
			->enum( self::effects() )
			->default( 'none' );

		$anim_active = Schema_Helpers::dep_ne( self::ANIM_EFFECT, 'none' );

		$schema[ self::ANIM_TRIGGER ] = String_Prop_Type::make()
			->enum( [ 'in-view', 'page-load', 'scroll-progress' ] )
			->default( 'in-view' )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_DURATION ] = Number_Prop_Type::make()->float()
			->default( 600 )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_DELAY ] = Number_Prop_Type::make()->float()
			->default( 0 )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_EASING ] = String_Prop_Type::make()
			->enum( [ 'none', 'power1.out', 'power2.out', 'power3.out', 'back.out', 'expo.out' ] )
			->default( 'power2.out' )
			->set_dependencies( $anim_active );

		// Repeat count is integer — no .float() needed.
		$schema[ self::ANIM_REPEAT ] = Number_Prop_Type::make()
			->default( 0 )
			->set_dependencies( $anim_active );

		$schema[ self::ANIM_ENABLE_EDITOR ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies( $anim_active );

		// Play Animation — only when an effect is selected AND Enable On Editor is ON.
		// Switch_Control expects a Boolean bind; the JS shim in editor-bridge.js
		// replaces its UI row with a "Play Now" button.
		$schema[ self::ANIM_PLAY_TOKEN ] = Boolean_Prop_Type::make()
			->default( false )
			->set_dependencies(
				Dependency_Manager::make( Dependency_Manager::RELATION_AND )
					->where( [
						'operator' => 'ne',
						'path'     => [ self::ANIM_EFFECT ],
						'value'    => 'none',
						'effect'   => 'hide',
					] )
					->where( [
						'operator' => 'eq',
						'path'     => [ self::ANIM_ENABLE_EDITOR ],
						'value'    => true,
						'effect'   => 'hide',
					] )
					->get()
			);

		return $schema;
	}

	public static function effects(): array {
		return [
			'none', 'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight',
			'slideUp', 'slideDown', 'zoomIn', 'zoomOut', 'rotateIn', 'flipInX', 'flipInY',
		];
	}
}
