<?php

namespace WCF_ADDONS\Atomic\Mask;

use Elementor\Modules\AtomicWidgets\PropsResolver\Multi_Props;
use Elementor\Modules\AtomicWidgets\PropsResolver\Props_Resolver_Context;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformer_Base;
use Elementor\Modules\AtomicWidgets\PropsResolver\Transformers_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the stored mask value into CSS.
 *
 * Only `mask-image` needs one: the other three keys are a plain enum string, a
 * Size, or a Position, all of which already have a transformer (or fall through
 * to core's Plain_Transformer) and emit correct CSS untouched.
 */
final class Transformers {

	public function register(): void {
		add_action(
			'elementor/atomic-widgets/styles/transformers/register',
			[ $this, 'register_transformers' ]
		);
	}

	public function register_transformers( Transformers_Registry $transformers ): void {
		$transformers->register( Mask_Image_Prop_Type::get_key(), new Mask_Image_Transformer() );
	}
}

/**
 * `{ shape, src }` → the `mask-image` declarations.
 *
 * Emits the property TWICE — once unprefixed and once as `-webkit-mask-image` —
 * through Multi_Props, which the render resolver merges into separate
 * declarations. Both are needed: Safari only implements the prefixed form for
 * several of the mask longhands, and v3 emitted only the prefixed one, which is
 * why a v3 mask does nothing in some Firefox builds. One prop, both spellings,
 * nothing for the panel or the user to know about.
 *
 * A missing or unresolvable image returns '' rather than a URL that 404s: CSS
 * masks an element to the OPAQUE parts of the mask image, so a failed load
 * hides the element entirely. Emitting nothing leaves it visible and unmasked,
 * which is the safe direction for a purely decorative property.
 */
class Mask_Image_Transformer extends Transformer_Base {

	public function transform( $value, Props_Resolver_Context $context ) {
		$url = $this->resolve_url( $value );

		if ( '' === $url ) {
			return '';
		}

		$css = 'url("' . esc_url( $url ) . '")';

		return Multi_Props::generate( [
			'mask-image'         => $css,
			'-webkit-mask-image' => $css,
		] );
	}

	private function resolve_url( $value ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$shape = $value['shape'] ?? '';

		if ( Shapes::CUSTOM !== $shape ) {
			return Shapes::url( is_string( $shape ) ? $shape : '' );
		}

		// Nested props arrive already transformed, so `src` is the
		// { id, url, alt } array Image_Src_Transformer returns. An attachment
		// picked by id has a null url — resolve it here.
		$src = $value['src'] ?? null;

		if ( ! is_array( $src ) ) {
			return '';
		}

		if ( ! empty( $src['url'] ) && is_string( $src['url'] ) ) {
			return $src['url'];
		}

		if ( ! empty( $src['id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $src['id'], 'full' );

			return is_string( $url ) ? $url : '';
		}

		return '';
	}
}
