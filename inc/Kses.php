<?php
/**
 * Output escaping for markup this plugin did not write itself.
 *
 * Most of what the plugin echoes is markup another renderer produced --
 * Elementor's `get_builder_content()`, a theme-builder document, a Loop
 * Item's Twig output, a post's `the_content`. That markup carries things
 * `wp_kses_post()` refuses (SVG icons, iframes, forms, media) and, when
 * Elementor renders "with CSS", `<style>` blocks that no kses call can
 * carry at all: kses turns a stray `>` in text into `&gt;`, which breaks
 * every child selector. Escaping late still has to mean escaping, so this
 * is the one place that knows how.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Kses {

	/**
	 * Memoised allow-list, built once per request.
	 *
	 * @var array<string, array<string, bool>>|null
	 */
	private static $allowed = null;

	/**
	 * Escape markup produced by Elementor (or a post's `the_content`) for
	 * output.
	 *
	 * `<style>` blocks are lifted out first and put back after kses, their
	 * CSS passed through wp_strip_all_tags() -- the same treatment the
	 * plugin's own inline CSS gets, and the only one that keeps a `>` in a
	 * selector intact. A data `<script>` (application/json and the like) is
	 * carried the same way; an executable one is removed WITH its contents,
	 * because kses alone would strip the tag and leave the source on the page
	 * as visible text, which is neither safe nor what anyone wants to read.
	 *
	 * Measured against every Elementor render on the dev site -- 22 templates
	 * and pages, then the four Loop Filter demos live -- and tuned until the
	 * only difference left was whitespace inside a CSS comment.
	 *
	 * @param string $html Rendered markup.
	 * @return string
	 */
	public static function builder_html( $html ) {
		$html = (string) $html;
		if ( '' === $html ) {
			return '';
		}

		$blocks = array();
		$token  = self::token();
		$html   = self::lift_blocks( $html, $blocks, $token );

		// Scoped to this call: `style` attributes go through
		// safecss_filter_attr(), whose property list is core's and lacks the
		// layout properties a page builder writes inline -- measured on this
		// site's own Nested Slider track (`touch-action`, `perspective`,
		// `transform-style`). Hooked around the one wp_kses() and removed
		// after, so no other kses call on the site is widened.
		add_filter( 'safe_style_css', array( __CLASS__, 'safe_style_css' ) );
		$html = wp_kses( $html, self::allowed_html() );
		remove_filter( 'safe_style_css', array( __CLASS__, 'safe_style_css' ) );

		if ( $blocks ) {
			$html = preg_replace_callback(
				'/' . preg_quote( $token, '/' ) . '(\d+)@@/',
				static function ( $m ) use ( $blocks ) {
					return isset( $blocks[ (int) $m[1] ] ) ? self::block_html( $blocks[ (int) $m[1] ] ) : '';
				},
				$html
			);
		}

		return (string) $html;
	}

	/**
	 * Print markup produced by Elementor (or a post's `the_content`) -- the
	 * printing twin of builder_html(), for the `echo` sites.
	 *
	 * Same lifting, same allow-list, same result on the page. The difference
	 * is WHO prints each piece: the markup segments go out through
	 * `echo wp_kses()`, a lifted `<style>` through WordPress's own inline
	 * stylesheet printer (print_css()), and a data `<script>` through
	 * wp_print_inline_script_tag(), which is what core uses for its own JSON
	 * payloads and which encodes a `</script` sequence itself. Nothing this
	 * plugin wrote decides how a byte reaches the page -- which is also what
	 * lets the Plugin Directory's own analyser read every print here as core
	 * escaping rather than as a helper it has to take on trust.
	 *
	 * Segments are split at element boundaries only (a whole `<style>` or
	 * `<script>` element is lifted), and kses keeps no state across tags, so
	 * kses of the parts equals kses of the whole.
	 *
	 * @param string $html Rendered markup.
	 * @return void
	 */
	public static function print_builder_html( $html ) {
		$html = (string) $html;
		if ( '' === $html ) {
			return;
		}

		$blocks = array();
		$token  = self::token();
		$html   = self::lift_blocks( $html, $blocks, $token );
		$parts  = preg_split( '/' . preg_quote( $token, '/' ) . '(\d+)@@/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			$parts = array( $html );
		}

		add_filter( 'safe_style_css', array( __CLASS__, 'safe_style_css' ) );
		foreach ( $parts as $i => $part ) {
			if ( 0 === $i % 2 ) {
				if ( '' !== $part ) {
					echo wp_kses( $part, self::allowed_html() );
				}
				continue;
			}
			if ( ! isset( $blocks[ (int) $part ] ) ) {
				continue;
			}
			$block = $blocks[ (int) $part ];
			if ( 'style' === $block['kind'] ) {
				self::print_css( $block['text'], $block['attrs'] );
			} else {
				wp_print_inline_script_tag( $block['text'], $block['attrs'] );
			}
		}
		remove_filter( 'safe_style_css', array( __CLASS__, 'safe_style_css' ) );
	}

	/**
	 * Print a block of CSS inside a `<style>` element, through WordPress's
	 * own inline-stylesheet printer.
	 *
	 * There is no core escaper CSS text survives -- esc_html() rewrites `a >
	 * b` and every quoted url(); kses turns a lone `>` into `&gt;` -- and the
	 * one thing CSS text can do to a page is end the block early. So the CSS
	 * is tag-stripped and then handed to WP_Styles: a src-less handle carries
	 * it as inline data, and `WP_Styles::print_inline_style()` prints the
	 * element the way it prints every `wp_add_inline_style()` on the site.
	 * The handle is never enqueued, so nothing else prints it, and its data
	 * is dropped after the print so a second block under the same id does not
	 * repeat the first. The element's id is `<handle>-inline-css`, core's
	 * spelling.
	 *
	 * Works wherever a page is being written -- head, body, footer, and an
	 * admin-ajax response, where no enqueue pass is ever going to run.
	 *
	 * @param string               $css   CSS text.
	 * @param string|array<string> $attrs Handle suffix, or a `<style>` attribute
	 *                                    map (`id`, `media`). Empty: a hash of
	 *                                    the CSS.
	 * @return void
	 */
	public static function print_css( $css, $attrs = '' ) {
		$css = wp_strip_all_tags( (string) $css );
		if ( '' === trim( $css ) ) {
			return;
		}

		$attrs = is_array( $attrs ) ? $attrs : array( 'id' => (string) $attrs );
		$media = isset( $attrs['media'] ) ? trim( (string) $attrs['media'] ) : '';
		if ( '' !== $media && 'all' !== strtolower( $media ) ) {
			// A `media` attribute has no place on an inline-style handle; the
			// equivalent at-rule keeps the CSS conditional the same way.
			$css = '@media ' . wp_strip_all_tags( $media ) . " {\n" . $css . "\n}";
		}

		$id     = isset( $attrs['id'] ) ? sanitize_key( (string) $attrs['id'] ) : '';
		$handle = 'aae-css-' . ( '' !== $id ? $id : substr( md5( $css ), 0, 12 ) );
		$styles = wp_styles();

		if ( ! isset( $styles->registered[ $handle ] ) ) {
			// No src, so the version never reaches a URL; it is here because a
			// registration without one reads as an unversioned asset.
			wp_register_style( $handle, false, array(), defined( 'AAEADDON_VERSION' ) ? AAEADDON_VERSION : '1.0' );
		}
		wp_add_inline_style( $handle, $css );
		$styles->print_inline_style( $handle );
		$styles->registered[ $handle ]->extra['after'] = array();
	}

	/**
	 * One placeholder token per call: unguessable, so no document can carry
	 * one of its own.
	 *
	 * @return string
	 */
	private static function token() {
		return '@@AAEKSES' . wp_rand( 100000, 999999 ) . 'S';
	}

	/**
	 * Lift every `<style>` and every data `<script>` out of the markup,
	 * leaving a placeholder token where each stood.
	 *
	 * A <script> whose type is a data MIME -- application/json,
	 * application/ld+json, text/template -- never executes; it is how a
	 * widget hands its runtime a payload (the Woo Variation swatch map:
	 * three per store page, measured gone under a blanket removal). Those
	 * are kept beside kses the way <style> is. Every executable script goes,
	 * with its contents -- kses alone would leave the source on the page
	 * as visible text.
	 *
	 * @param string  $html   Markup.
	 * @param array[] $blocks Filled: each `{kind: style|script, attrs, text}`.
	 * @param string  $token  Placeholder prefix.
	 * @return string Markup with placeholders.
	 */
	private static function lift_blocks( $html, array &$blocks, $token ) {
		$out = preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script\s*>#is',
			static function ( $m ) use ( &$blocks, $token ) {
				if ( ! self::is_data_script( $m[1] ) ) {
					return '';
				}
				$i            = count( $blocks );
				$blocks[ $i ] = array(
					'kind'  => 'script',
					'attrs' => self::script_attributes( $m[1] ),
					'text'  => $m[2],
				);
				return $token . $i . '@@';
			},
			$html
		);
		if ( null === $out ) {
			// PCRE limit on a huge document: fall back to plain kses, which
			// still removes the tag.
			$blocks = array();
			$out    = $html;
		}

		$html = $out;
		$out  = preg_replace_callback(
			'#<style\b([^>]*)>(.*?)</style\s*>#is',
			static function ( $m ) use ( &$blocks, $token ) {
				$i            = count( $blocks );
				$blocks[ $i ] = array(
					'kind'  => 'style',
					'attrs' => self::style_attributes( $m[1] ),
					'text'  => $m[2],
				);
				return $token . $i . '@@';
			},
			$html
		);
		if ( null === $out ) {
			$blocks = array();
			$out    = $html;
		}

		return (string) $out;
	}

	/**
	 * A lifted block as a string, for builder_html().
	 *
	 * @param array $block One entry from lift_blocks().
	 * @return string
	 */
	private static function block_html( array $block ) {
		$attrs = '';
		foreach ( $block['attrs'] as $name => $value ) {
			$attrs .= true === $value ? ' ' . $name : ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		if ( 'style' === $block['kind'] ) {
			return '<style' . $attrs . '>' . wp_strip_all_tags( $block['text'] ) . '</style>';
		}
		// The one sequence that could end a data block early, neutralised.
		return '<script' . $attrs . '>' . str_replace( '</', '<\/', $block['text'] ) . '</script>';
	}

	/**
	 * CSS properties allowed inside a `style` attribute, on top of core's.
	 *
	 * @param string[] $props Core's list.
	 * @return string[]
	 */
	public static function safe_style_css( $props ) {
		return array_merge(
			(array) $props,
			array(
				'touch-action',
				'perspective',
				'perspective-origin',
				'transform-style',
				'backface-visibility',
				'will-change',
				'pointer-events',
				'user-select',
				'inset',
				'inset-inline',
				'inset-inline-start',
				'inset-inline-end',
				'inset-block',
				'inset-block-start',
				'inset-block-end',
				'-webkit-line-clamp',
				'-webkit-box-orient',
				'aspect-ratio',
				'object-fit',
				'object-position',
				'mix-blend-mode',
				'isolation',
				'scroll-snap-type',
				'scroll-snap-align',
				'overscroll-behavior',
				'visibility',
				'cursor',
			)
		);
	}

	/**
	 * Is this <script> a data carrier rather than code? Only a `type` naming
	 * a JSON or template MIME qualifies; no type, or a JavaScript one, means
	 * it runs.
	 *
	 * @param string $raw The attribute string after `<script`.
	 * @return bool
	 */
	private static function is_data_script( $raw ) {
		if ( ! preg_match( '/\btype\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $raw, $m ) ) {
			return false;
		}
		$type = strtolower( trim( isset( $m[3] ) && '' !== $m[3] ? $m[3] : $m[2] ) );
		return (bool) preg_match( '#^(application/(ld\+)?json|text/(template|x-template|x-handlebars-template|html))$#', $type );
	}

	/**
	 * The attributes a data <script> may carry: its type, an id, and the
	 * data-* hooks a runtime finds it by. Name => value, a valueless hook as
	 * true; the tag never meets kses, so the printer escapes them.
	 *
	 * @param string $raw The attribute string after `<script`.
	 * @return array<string, string|true>
	 */
	private static function script_attributes( $raw ) {
		$out  = array();
		$seen = array();
		if ( preg_match_all( '/\b(type|id|data-[a-z0-9_-]+)\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $raw, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $a ) {
				$name         = strtolower( $a[1] );
				$out[ $name ] = isset( $a[4] ) && '' !== $a[4] ? $a[4] : $a[3];
				$seen[]       = $name;
			}
		}
		// A valueless hook, `data-aae-woo-variations` on its own.
		if ( preg_match_all( '/\s(data-[a-z0-9_-]+)(?![\s]*=)/i', ' ' . $raw, $m ) ) {
			foreach ( array_unique( array_map( 'strtolower', $m[1] ) ) as $name ) {
				if ( ! in_array( $name, $seen, true ) ) {
					$out[ $name ] = true;
				}
			}
		}
		return $out;
	}

	/**
	 * Keep only the attributes a `<style>` tag legitimately carries, as
	 * name => value -- the tag itself never goes through kses.
	 *
	 * @param string $raw The attribute string after `<style`.
	 * @return array<string, string>
	 */
	private static function style_attributes( $raw ) {
		$out = array();
		if ( preg_match_all( '/\b(id|media|type)\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $raw, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $a ) {
				$out[ strtolower( $a[1] ) ] = isset( $a[4] ) && '' !== $a[4] ? $a[4] : $a[3];
			}
		}
		return $out;
	}

	/**
	 * `wp_kses_allowed_html( 'post' )` plus what a page builder's output
	 * needs: inline SVG, iframes, media, forms, and the ARIA states the
	 * global list leaves out.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html() {
		if ( null !== self::$allowed ) {
			return self::$allowed;
		}

		$allowed = wp_kses_allowed_html( 'post' );

		$global = array(
			'aria-atomic'          => true,
			'aria-busy'            => true,
			'aria-checked'         => true,
			'aria-disabled'        => true,
			'aria-haspopup'        => true,
			'aria-invalid'         => true,
			'aria-modal'           => true,
			'aria-multiselectable' => true,
			'aria-orientation'     => true,
			'aria-owns'            => true,
			'aria-pressed'         => true,
			'aria-readonly'        => true,
			'aria-required'        => true,
			'aria-roledescription' => true,
			'aria-selected'        => true,
			'aria-sort'            => true,
			'aria-valuemax'        => true,
			'aria-valuemin'        => true,
			'aria-valuenow'        => true,
			'aria-valuetext'       => true,
			'draggable'            => true,
			'itemprop'             => true,
			'itemscope'            => true,
			'itemtype'             => true,
			'itemid'               => true,
			'slot'                 => true,
			'translate'            => true,
		);

		$svg_presentation = array(
			'class'             => true,
			'id'                => true,
			'style'             => true,
			'fill'              => true,
			'fill-opacity'      => true,
			'fill-rule'         => true,
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-miterlimit' => true,
			'stroke-dasharray'  => true,
			'stroke-dashoffset' => true,
			'stroke-opacity'    => true,
			'opacity'           => true,
			'transform'         => true,
			'clip-path'         => true,
			'clip-rule'         => true,
			'mask'              => true,
			'filter'            => true,
			'color'             => true,
			'vector-effect'     => true,
			'paint-order'       => true,
			'shape-rendering'   => true,
			'visibility'        => true,
			'display'           => true,
		);

		$svg_geometry = array(
			'x'      => true,
			'y'      => true,
			'x1'     => true,
			'y1'     => true,
			'x2'     => true,
			'y2'     => true,
			'cx'     => true,
			'cy'     => true,
			'r'      => true,
			'rx'     => true,
			'ry'     => true,
			'dx'     => true,
			'dy'     => true,
			'd'      => true,
			'points' => true,
			'width'  => true,
			'height' => true,
		);

		$svg = array(
			'svg'              => array_merge(
				$svg_presentation,
				$svg_geometry,
				array(
					'xmlns'               => true,
					'xmlns:xlink'         => true,
					'version'             => true,
					'viewbox'             => true,
					'preserveaspectratio' => true,
					'focusable'           => true,
					'overflow'            => true,
				)
			),
			'g'                => array_merge( $svg_presentation, array( 'transform' => true ) ),
			'path'             => array_merge( $svg_presentation, $svg_geometry, array( 'pathlength' => true ) ),
			'circle'           => array_merge( $svg_presentation, $svg_geometry ),
			'ellipse'          => array_merge( $svg_presentation, $svg_geometry ),
			'rect'             => array_merge( $svg_presentation, $svg_geometry ),
			'line'             => array_merge( $svg_presentation, $svg_geometry ),
			'polyline'         => array_merge( $svg_presentation, $svg_geometry ),
			'polygon'          => array_merge( $svg_presentation, $svg_geometry ),
			'text'             => array_merge(
				$svg_presentation,
				$svg_geometry,
				array(
					'text-anchor'       => true,
					'font-size'         => true,
					'font-family'       => true,
					'font-weight'       => true,
					'dominant-baseline' => true,
				)
			),
			'tspan'            => array_merge( $svg_presentation, $svg_geometry, array( 'text-anchor' => true ) ),
			'title'            => array( 'id' => true ),
			'desc'             => array( 'id' => true ),
			'defs'             => array( 'id' => true ),
			'symbol'           => array_merge(
				$svg_presentation,
				array(
					'viewbox'             => true,
					'preserveaspectratio' => true,
				)
			),
			'use'              => array_merge(
				$svg_presentation,
				$svg_geometry,
				array(
					'href'       => true,
					'xlink:href' => true,
				)
			),
			'clippath'         => array_merge( $svg_presentation, array( 'clippathunits' => true ) ),
			'mask'             => array_merge(
				$svg_presentation,
				$svg_geometry,
				array(
					'maskunits'        => true,
					'maskcontentunits' => true,
				)
			),
			'lineargradient'   => array(
				'id'                => true,
				'x1'                => true,
				'y1'                => true,
				'x2'                => true,
				'y2'                => true,
				'gradientunits'     => true,
				'gradienttransform' => true,
				'spreadmethod'      => true,
				'href'              => true,
				'xlink:href'        => true,
			),
			'radialgradient'   => array(
				'id'                => true,
				'cx'                => true,
				'cy'                => true,
				'r'                 => true,
				'fx'                => true,
				'fy'                => true,
				'fr'                => true,
				'gradientunits'     => true,
				'gradienttransform' => true,
				'spreadmethod'      => true,
				'href'              => true,
				'xlink:href'        => true,
			),
			'stop'             => array(
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
				'style'        => true,
				'class'        => true,
			),
			'pattern'          => array_merge(
				$svg_presentation,
				$svg_geometry,
				array(
					'patternunits'        => true,
					'patterncontentunits' => true,
					'patterntransform'    => true,
					'viewbox'             => true,
					'href'                => true,
					'xlink:href'          => true,
				)
			),
			'image'            => array_merge(
				$svg_presentation,
				$svg_geometry,
				array(
					'href'                => true,
					'xlink:href'          => true,
					'preserveaspectratio' => true,
				)
			),
			'filter'           => array(
				'id'                          => true,
				'x'                           => true,
				'y'                           => true,
				'width'                       => true,
				'height'                      => true,
				'filterunits'                 => true,
				'primitiveunits'              => true,
				'color-interpolation-filters' => true,
			),
			'fegaussianblur'   => array(
				'in'           => true,
				'stddeviation' => true,
				'result'       => true,
				'edgemode'     => true,
			),
			'feoffset'         => array(
				'in'     => true,
				'dx'     => true,
				'dy'     => true,
				'result' => true,
			),
			'feblend'          => array(
				'in'     => true,
				'in2'    => true,
				'mode'   => true,
				'result' => true,
			),
			'fecolormatrix'    => array(
				'in'     => true,
				'type'   => true,
				'values' => true,
				'result' => true,
			),
			'femerge'          => array( 'result' => true ),
			'femergenode'      => array( 'in' => true ),
			'feflood'          => array(
				'flood-color'   => true,
				'flood-opacity' => true,
				'result'        => true,
			),
			'fecomposite'      => array(
				'in'       => true,
				'in2'      => true,
				'operator' => true,
				'k1'       => true,
				'k2'       => true,
				'k3'       => true,
				'k4'       => true,
				'result'   => true,
			),
			'animate'          => array(
				'attributename' => true,
				'values'        => true,
				'dur'           => true,
				'repeatcount'   => true,
				'begin'         => true,
				'from'          => true,
				'to'            => true,
				'fill'          => true,
				'calcmode'      => true,
				'keytimes'      => true,
				'keysplines'    => true,
			),
			'animatetransform' => array(
				'attributename' => true,
				'type'          => true,
				'values'        => true,
				'dur'           => true,
				'repeatcount'   => true,
				'begin'         => true,
				'from'          => true,
				'to'            => true,
				'additive'      => true,
			),
		);

		$media = array(
			'iframe'  => array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'frameborder'     => true,
				'allow'           => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'name'            => true,
				'referrerpolicy'  => true,
				'sandbox'         => true,
				'scrolling'       => true,
			),
			'video'   => array(
				'src'         => true,
				'poster'      => true,
				'controls'    => true,
				'autoplay'    => true,
				'loop'        => true,
				'muted'       => true,
				'playsinline' => true,
				'preload'     => true,
				'width'       => true,
				'height'      => true,
				'crossorigin' => true,
			),
			'audio'   => array(
				'src'      => true,
				'controls' => true,
				'autoplay' => true,
				'loop'     => true,
				'muted'    => true,
				'preload'  => true,
			),
			'source'  => array(
				'src'    => true,
				'srcset' => true,
				'sizes'  => true,
				'type'   => true,
				'media'  => true,
			),
			'track'   => array(
				'src'     => true,
				'kind'    => true,
				'srclang' => true,
				'label'   => true,
				'default' => true,
			),
			'picture' => array(),
			'canvas'  => array(
				'width'  => true,
				'height' => true,
			),
		);

		$form = array(
			'form'     => array(
				'action'         => true,
				'method'         => true,
				'enctype'        => true,
				'target'         => true,
				'name'           => true,
				'novalidate'     => true,
				'autocomplete'   => true,
				'accept-charset' => true,
			),
			'input'    => array(
				'type'         => true,
				'name'         => true,
				'value'        => true,
				'placeholder'  => true,
				'required'     => true,
				'disabled'     => true,
				'readonly'     => true,
				'checked'      => true,
				'min'          => true,
				'max'          => true,
				'step'         => true,
				'minlength'    => true,
				'maxlength'    => true,
				'pattern'      => true,
				'autocomplete' => true,
				'autofocus'    => true,
				'inputmode'    => true,
				'multiple'     => true,
				'accept'       => true,
				'size'         => true,
				'list'         => true,
				'form'         => true,
				'src'          => true,
				'alt'          => true,
				'width'        => true,
				'height'       => true,
			),
			'textarea' => array(
				'name'         => true,
				'placeholder'  => true,
				'required'     => true,
				'disabled'     => true,
				'readonly'     => true,
				'rows'         => true,
				'cols'         => true,
				'minlength'    => true,
				'maxlength'    => true,
				'autocomplete' => true,
				'wrap'         => true,
				'form'         => true,
			),
			'select'   => array(
				'name'         => true,
				'required'     => true,
				'disabled'     => true,
				'multiple'     => true,
				'size'         => true,
				'autocomplete' => true,
				'form'         => true,
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
				'disabled' => true,
				'label'    => true,
			),
			'optgroup' => array(
				'label'    => true,
				'disabled' => true,
			),
			'button'   => array(
				'type'           => true,
				'name'           => true,
				'value'          => true,
				'disabled'       => true,
				'form'           => true,
				'formaction'     => true,
				'formmethod'     => true,
				'formnovalidate' => true,
				'formtarget'     => true,
				'autofocus'      => true,
			),
			'label'    => array(
				'for'  => true,
				'form' => true,
			),
			'fieldset' => array(
				'name'     => true,
				'disabled' => true,
				'form'     => true,
			),
			'legend'   => array(),
			'datalist' => array(),
			'output'   => array(
				'for'  => true,
				'name' => true,
				'form' => true,
			),
			'progress' => array(
				'value' => true,
				'max'   => true,
			),
			'meter'    => array(
				'value'   => true,
				'min'     => true,
				'max'     => true,
				'low'     => true,
				'high'    => true,
				'optimum' => true,
			),
		);

		// `<template>` carries markup a runtime clones later; `<noscript>`
		// is the no-JS fallback several widgets ship.
		$structural = array(
			'template'   => array(),
			'noscript'   => array(),
			'main'       => array(),
			'nav'        => array(),
			'header'     => array(),
			'footer'     => array(),
			'section'    => array(),
			'article'    => array(),
			'aside'      => array(),
			'time'       => array( 'datetime' => true ),
			'mark'       => array(),
			// WooCommerce wraps every price amount in <bdi>.
			'bdi'        => array(),
			'bdo'        => array(),
			'data'       => array( 'value' => true ),
			'ruby'       => array(),
			'rt'         => array(),
			'rp'         => array(),
			'figure'     => array(),
			'figcaption' => array(),
			'wbr'        => array(),
			'dialog'     => array( 'open' => true ),
			'details'    => array( 'open' => true ),
			'summary'    => array(),
		);

		foreach ( array( $svg, $media, $form, $structural ) as $group ) {
			foreach ( $group as $tag => $attrs ) {
				$allowed[ $tag ] = array_merge( isset( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $attrs );
			}
		}

		// Every element gets the global set, and a few attributes Elementor
		// puts on plain elements that the post list lacks.
		$extra_common = array(
			'loading'         => true,
			'decoding'        => true,
			'fetchpriority'   => true,
			'srcset'          => true,
			'sizes'           => true,
			'download'        => true,
			'referrerpolicy'  => true,
			'contenteditable' => true,
			'spellcheck'      => true,
		);
		// kses merges its own global attributes (class, id, style, data-*,
		// role, the common aria-*...) into the post list at load time, so a
		// tag added here would otherwise carry none of them. `span` in the
		// post list is that global set plus `align`.
		$core_global = isset( $allowed['span'] ) ? $allowed['span'] : array();
		foreach ( $allowed as $tag => $attrs ) {
			$allowed[ $tag ] = array_merge( $core_global, $attrs, $global );
			if ( in_array( $tag, array( 'img', 'a', 'source', 'iframe', 'video', 'audio', 'link' ), true ) ) {
				$allowed[ $tag ] = array_merge( $allowed[ $tag ], $extra_common );
			}
		}

		/**
		 * Filter the allow-list used to escape builder output.
		 *
		 * @since 4.2.0
		 *
		 * @param array $allowed wp_kses() allow-list.
		 */
		self::$allowed = apply_filters( 'aaeaddon/kses/allowed_html', $allowed );

		return self::$allowed;
	}
}
