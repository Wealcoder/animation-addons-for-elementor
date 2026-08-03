<?php

namespace WCF_ADDONS\Atomic\Mask;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The mask shape catalogue.
 *
 * Elementor already ships these twenty SVGs at assets/mask-shapes/ for its own
 * v3 widget mask, and they are plain files with no v3 code attached — so this
 * reuses them rather than shipping a second identical set. The URL is built at
 * RENDER time from ELEMENTOR_ASSETS_URL, never stored, so a site that moves
 * domain or changes its content directory keeps working; only the shape SLUG
 * lives in the database.
 *
 * v3 equivalent: Common_Base::get_shapes() (includes/widgets/common-base.php).
 * That one also exposes an `elementor/mask_shapes/additional_shapes` filter;
 * ours is honoured here too, so a shape added for the v3 widget mask shows up
 * in the v4 section as well.
 */
final class Shapes {

	/** slug => human label. Order is the order they appear in the panel grid. */
	const SHAPES = [
		'circle'               => 'Circle',
		'oval-vertical'        => 'Oval Vertical',
		'oval-horizontal'      => 'Oval Horizontal',
		'pill-vertical'        => 'Pill Vertical',
		'pill-horizontal'      => 'Pill Horizontal',
		'triangle'             => 'Triangle',
		'diamond'              => 'Diamond',
		'pentagon'             => 'Pentagon',
		'hexagon-vertical'     => 'Hexagon Vertical',
		'hexagon-horizontal'   => 'Hexagon Horizontal',
		'heptagon'             => 'Heptagon',
		'octagon'              => 'Octagon',
		'parallelogram-right'  => 'Parallelogram Right',
		'parallelogram-left'   => 'Parallelogram Left',
		'trapezoid-up'         => 'Trapezoid Up',
		'trapezoid-down'       => 'Trapezoid Down',
		'flower'               => 'Flower',
		'sketch'               => 'Sketch',
		'hexagon'              => 'Hexagon',
		'blob'                 => 'Blob',
	];

	/** The value that means "use my own SVG" instead of a built-in shape. */
	const CUSTOM = 'custom';

	/**
	 * slug => [ label, image ] for the panel, plus the custom entry.
	 *
	 * @return array<string,array{label:string,image:string}>
	 */
	public static function all(): array {
		$shapes = [];

		foreach ( self::SHAPES as $slug => $label ) {
			$shapes[ $slug ] = [
				'label' => $label,
				'image' => self::url( $slug ),
			];
		}

		foreach ( self::additional() as $slug => $shape ) {
			$shapes[ $slug ] = [
				'label' => $shape['title'] ?? $slug,
				'image' => $shape['image'] ?? '',
			];
		}

		return $shapes;
	}

	/** Valid values for the shape prop — every slug plus `custom`. */
	public static function slugs(): array {
		return array_merge( array_keys( self::all() ), [ self::CUSTOM ] );
	}

	/**
	 * Absolute URL of a built-in shape's SVG, or '' for an unknown slug.
	 *
	 * An unknown slug returns empty rather than a broken URL: the transformer
	 * then emits nothing at all, which leaves the element unmasked. A 404'd
	 * mask-image would instead hide the element completely — CSS masks an
	 * element to the *opaque* parts of the image, and a failed load has none.
	 */
	public static function url( string $slug ): string {
		$additional = self::additional();

		if ( isset( $additional[ $slug ]['image'] ) ) {
			return (string) $additional[ $slug ]['image'];
		}

		if ( ! isset( self::SHAPES[ $slug ] ) || ! defined( 'ELEMENTOR_ASSETS_URL' ) ) {
			return '';
		}

		return ELEMENTOR_ASSETS_URL . 'mask-shapes/' . $slug . '.svg';
	}

	/** Shapes third parties added through Elementor's own filter. */
	private static function additional(): array {
		$additional = apply_filters( 'elementor/mask_shapes/additional_shapes', [] );

		return is_array( $additional ) ? $additional : [];
	}
}
