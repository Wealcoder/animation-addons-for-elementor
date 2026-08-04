<?php

namespace WCF_ADDONS\Atomic\Mask;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mask — real atomic STYLE props, not settings props.
 *
 * v4's style schema has no mask keys at all, and v3's mask
 * (Common_Base::_section_masking) is widget-only — a container/flexbox/grid
 * simply cannot be masked in v3. So this is new capability, not a port.
 *
 * WHY the style schema and not a settings-prop extension like Background Video:
 * a mask is purely declarative CSS. Registered here it rides Elementor's own
 * styles engine, which means responsive values, `:hover` and other state
 * variants, and global classes all work with no code of ours — and the CSS
 * compiles into the element's stylesheet, so there is no runtime JS and no
 * flash of unmasked content. A settings extension could deliver none of that.
 *
 * The schema key IS the CSS property name (see Style_Schema::get_style_schema()),
 * so these four keys need no mapping table anywhere.
 */
final class Schema {

	const MASK_IMAGE = 'mask-image';
	const MASK_SIZE = 'mask-size';
	const MASK_POSITION = 'mask-position';
	const MASK_REPEAT = 'mask-repeat';

	/** v3's nine named positions, plus Custom (an x/y offset pair). */
	const POSITIONS = [
		'center center',
		'center left',
		'center right',
		'top center',
		'top left',
		'top right',
		'bottom center',
		'bottom left',
		'bottom right',
	];

	/** v3's six repeat modes. */
	const REPEATS = [
		'no-repeat',
		'repeat',
		'repeat-x',
		'repeat-y',
		'round',
		'space',
	];

	public function register(): void {
		add_filter( 'elementor/atomic-widgets/styles/schema', [ $this, 'add_props' ] );
	}

	public function add_props( array $schema ): array {
		$schema[ self::MASK_IMAGE ] = Mask_Image_Prop_Type::make()
			->description( 'Clips the element to a shape. AAE Mask extension.' );

		// Plain enums, NOT Unions — and that is a UI constraint, not a
		// preference. A Union style prop needs a control built for it (core
		// binds its own Union `object-position` with PositionControl, never with
		// SelectControl); a SelectControl over a Union throws when the section
		// body mounts, and the panel's error boundary then removes the whole
		// section — it looks like the section "disappears when you open it".
		//
		// So v3's Custom scale / Custom x-y offset are deliberately not offered
		// yet. Adding them means adding the Size / Position arm back here AND
		// giving each field a control that understands it, together.
		$schema[ self::MASK_SIZE ] = String_Prop_Type::make()
			->enum( [ 'contain', 'cover', 'auto' ] )
			->set_dependencies( $this->when_masked() )
			->description( 'Fit, Fill or Auto' );

		$schema[ self::MASK_POSITION ] = String_Prop_Type::make()
			->enum( self::POSITIONS )
			->set_dependencies( $this->when_masked() )
			->description( 'Where the mask sits on the element' );

		// v3 hides Repeat when size is `cover`, where it cannot do anything.
		$schema[ self::MASK_REPEAT ] = String_Prop_Type::make()
			->enum( self::REPEATS )
			->set_dependencies(
				Dependency_Manager::make( Dependency_Manager::RELATION_AND )
					->where( [
						'operator' => 'exists',
						'path'     => [ self::MASK_IMAGE ],
					] )
					->where( [
						'operator' => 'ne',
						'path'     => [ self::MASK_SIZE ],
						'value'    => 'cover',
					] )
					->get()
			)
			->description( 'How the mask tiles' );

		return $schema;
	}

	/** Size / position mean nothing until an image is chosen. */
	private function when_masked(): ?array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'exists',
				'path'     => [ self::MASK_IMAGE ],
			] )
			->get();
	}
}
