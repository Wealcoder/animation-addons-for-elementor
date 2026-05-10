<?php
namespace WCF_ADDONS\Atomic;

use Elementor\Modules\AtomicWidgets\PropTypes\Object_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {

	const PROP_KEY = 'aae_animation';

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/props-schema', [ $this, 'add_animation_prop' ], 10, 2 );
	}

	public function add_animation_prop( array $schema, string $element_type ): array {
		if ( ! in_array( $element_type, Bootstrap::target_element_types(), true ) ) {
			return $schema;
		}

		if ( ! class_exists( Object_Prop_Type::class ) || ! class_exists( String_Prop_Type::class ) ) {
			return $schema;
		}

		$schema[ self::PROP_KEY ] = Object_Prop_Type::make()
			->set_shape( [
				'effect'     => String_Prop_Type::make()
					->enum( $this->effects() )
					->default( 'none' ),
				'duration'   => String_Prop_Type::make()->default( '600' ),
				'delay'      => String_Prop_Type::make()->default( '0' ),
				'easing'     => String_Prop_Type::make()
					->enum( [ 'none', 'power1.out', 'power2.out', 'power3.out', 'back.out', 'expo.out' ] )
					->default( 'power2.out' ),
				'trigger'    => String_Prop_Type::make()
					->enum( [ 'in-view', 'page-load', 'scroll-progress' ] )
					->default( 'in-view' ),
				'repeat'     => String_Prop_Type::make()->default( '0' ),
			] );

		return $schema;
	}

	public static function effects(): array {
		return [
			'none', 'fadeIn', 'fadeInUp', 'fadeInDown', 'fadeInLeft', 'fadeInRight',
			'slideUp', 'slideDown', 'zoomIn', 'zoomOut', 'rotateIn', 'flipInX', 'flipInY',
		];
	}
}
