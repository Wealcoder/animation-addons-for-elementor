<?php

namespace WCF_ADDONS\Atomic\Mask;

use Elementor\Modules\AtomicWidgets\PropTypes\Base\Object_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The value behind the `mask-image` style prop: a shape slug, or a custom SVG.
 *
 * One object prop rather than a Union of "enum OR image", because a Union picks
 * its transformer from the value's own $$type — so a custom image would be
 * handed to Elementor's Image_Src_Transformer, which returns a bare
 * { id, url, alt } and never the `url(…)` CSS needs. Keeping both cases in one
 * prop keeps ONE transformer responsible for producing the final CSS value.
 *
 * Only the slug is stored for a built-in shape; the URL is resolved at render
 * time (see Shapes::url()), so the database holds nothing that a site move can
 * invalidate.
 */
class Mask_Image_Prop_Type extends Object_Prop_Type {

	public static function get_key(): string {
		return 'aae-mask-image';
	}

	protected function define_shape(): array {
		return [
			// NO ->default() here, and none on any style prop.
			// A default on a STYLE prop is not "the value to start from" — the
			// styles pipeline renders it, so `->default('circle')` put
			// `mask-image: url(circle.svg)` into the BASE STYLE of every atomic
			// element on the site and masked all of them to a circle. Core's
			// style schema sets no defaults anywhere for exactly this reason;
			// the panel supplies the initial pick instead.
			'shape' => String_Prop_Type::make()
				->enum( Shapes::slugs() )
				->description( 'Built-in mask shape slug, or "custom" to use `src`' ),

			// Only read when shape === 'custom'. Left in place otherwise so
			// switching to a built-in shape and back does not lose the upload.
			'src' => Image_Src_Prop_Type::make()
				->description( 'Custom mask image — an SVG with transparency works best' ),
		];
	}
}
