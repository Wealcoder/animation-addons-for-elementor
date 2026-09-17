<?php
namespace Wealcoder\AnimationAddons\AtomicWidgets\Widgets\PostExcerpt;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AAE Post Excerpt — native Atomic 4 widget.
 *
 * The current post's excerpt as plain text, with a length limit the builder
 * chooses: by WORDS, by CHARACTERS, or by LINES (a pure-CSS line clamp). The
 * same three modes the Post Title widget offers, so a card's title and its
 * excerpt are limited the same way in the same panel shape.
 *
 * Where the text comes from depends on the mode, and the difference is the
 * whole reason word/char limits do not simply run over get_the_excerpt():
 *
 *   - `none` / `line`  -> get_the_excerpt(). WordPress's own excerpt, every
 *                         theme/plugin filter honoured, capped at the site's
 *                         `excerpt_length` (55 words by default). A line clamp
 *                         only needs "enough text to overflow", and the
 *                         standard excerpt is what a theme's own cards show.
 *   - `word` / `char`  -> the manual excerpt when the author wrote one,
 *                         otherwise the post CONTENT stripped to text. The
 *                         number the builder types is then the number they
 *                         get: a word limit of 80 on a post with no manual
 *                         excerpt would otherwise be silently capped at 55 by
 *                         the `excerpt_length` filter with nothing to say so.
 *
 * Inside a Loop Grid the global post is already the card's post; in an AAE
 * theme-builder template it resolves to the most recent post the way the Post
 * Content widget does; in the editor with no post it previews the shared
 * sample post so the card reads as real.
 *
 * @package AnimationAddonsForElementor
 * @since   4.2.0
 */

class Aaeaddon_A_Post_Excerpt extends Atomic_Widget_Base {

	use Has_Template;

	const BASE_STYLE_KEY = 'base';

	/** The panel's ceiling on a limit; the schema clamps to it as well. */
	const MAX_LIMIT = 5000;

	public static function get_element_type(): string {
		return 'e-aae-a-post-excerpt';
	}

	public function get_title() {
		return esc_html__( 'Post Excerpt', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'eicon-post-excerpt';
	}

	public function get_keywords() {
		return [ 'excerpt', 'summary', 'post', 'atomic', 'dynamic' ];
	}

	public function get_categories(): array {
		return [ 'aae-atomic-post' ];
	}

	protected static function define_props_schema(): array {
		// Bake an editor preview into the prop DEFAULT so a freshly-dropped
		// widget shows text at once: the editor renders a new element
		// client-side from the schema defaults before get_atomic_settings()
		// has run for it. Same reasoning as the Post Content widget.
		$preview = '';
		if ( class_exists( '\Elementor\Plugin' ) && Plugin::$instance->editor->is_edit_mode() ) {
			$preview = self::editor_sample_text();
		}

		$has_limit = Dependency_Manager::make()
			->where( [
				'operator' => 'ne',
				'path'     => [ 'limit_by' ],
				'value'    => 'none',
				'effect'   => 'hide',
			] )
			->get();

		$is_trim = Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ 'limit_by' ],
				'value'    => [ 'word', 'char' ],
				'effect'   => 'hide',
			] )
			->get();

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),
			'tag'        => String_Prop_Type::make()->enum( [ 'p', 'div', 'span' ] )->default( 'p' ),
			// Default: a 3-line CSS clamp — uniform card heights out of the box,
			// identical in the editor and on the front end, no text rewritten.
			'limit_by'   => String_Prop_Type::make()->enum( [ 'none', 'word', 'char', 'line' ] )->default( 'line' ),
			'limit'      => Number_Prop_Type::make()->default( 3 )->set_dependencies( $has_limit ),
			// What ends a trimmed excerpt. Only word/char trims rewrite the text;
			// a line clamp leaves the words alone, so it has nothing to append.
			'more'       => String_Prop_Type::make()->default( '…' )->set_dependencies( $is_trim ),
			// Populated at render time from the post; never edited directly.
			'post_excerpt' => String_Prop_Type::make()->default( $preview )->meta( Overridable_Prop_Type::ignore() ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Excerpt Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'tag' )
						->set_label( __( 'HTML Tag', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'p', 'label' => 'p' ],
							[ 'value' => 'div', 'label' => 'div' ],
							[ 'value' => 'span', 'label' => 'span' ],
						] ),

					Select_Control::bind_to( 'limit_by' )
						->set_label( __( 'Limit By', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'none', 'label' => __( 'None', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'word', 'label' => __( 'Word Count', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'char', 'label' => __( 'Character Count', 'animation-addons-for-elementor' ) ],
							[ 'value' => 'line', 'label' => __( 'Line Clamp (CSS)', 'animation-addons-for-elementor' ) ],
						] ),

					Number_Control::bind_to( 'limit' )
						->set_label( __( 'Limit', 'animation-addons-for-elementor' ) )
						->set_min( 1 )
						->set_max( self::MAX_LIMIT ),

					Text_Control::bind_to( 'more' )
						->set_label( __( 'Ending', 'animation-addons-for-elementor' ) )
						->set_placeholder( '…' ),
				] ),
		];
	}

	protected function define_base_styles(): array {
		return [
			self::BASE_STYLE_KEY => Style_Definition::make()
				->add_variant(
					Style_Variant::make()->add_props( [
						'display' => String_Prop_Type::generate( 'block' ),
						'width'   => Size_Prop_Type::generate( [ 'size' => 100, 'unit' => '%' ] ),
						// A <p> carries the theme's own margins; a card excerpt
						// should sit where the builder puts it.
						'margin'  => Dimensions_Prop_Type::generate( [
							'block-start' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
							'block-end'   => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
						] ),
					] )
				),
		];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-post-excerpt' => __DIR__ . '/aae-a-post-excerpt.html.twig',
		];
	}

	public function get_style_depends(): array {
		return []; // Base styles + the Style tab; the clamp is inline from the twig.
	}

	/**
	 * Resolve the excerpt for the twig. Runs on every server render (front end
	 * AND the editor's server-side refresh), so the canvas and the page agree.
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$limit_by = isset( $settings['limit_by'] ) ? (string) $settings['limit_by'] : 'none';
		$limit    = isset( $settings['limit'] ) ? (int) $settings['limit'] : 0;
		$more     = isset( $settings['more'] ) ? (string) $settings['more'] : '…';

		$settings['post_excerpt'] = self::excerpt_for( self::resolve_post(), $limit_by, $limit, $more );

		if ( '' === $settings['post_excerpt'] && Plugin::$instance->editor->is_edit_mode() ) {
			$settings['post_excerpt'] = self::trim( self::editor_sample_text(), $limit_by, $limit, $more );
		}

		return $settings;
	}

	/**
	 * The post this widget describes.
	 *
	 * Inside a loop that is the global post. In an AAE theme-builder template
	 * the global post is the template itself, whose excerpt is empty, so the
	 * most recent published post stands in — the same substitution the Post
	 * Content widget and the legacy Post Excerpt make.
	 *
	 * @return \WP_Post|null
	 */
	private static function resolve_post() {
		$post = get_post();

		if ( $post && 'wcf-addons-template' === $post->post_type ) {
			$recent = wp_get_recent_posts( [ 'numberposts' => 1, 'post_status' => 'publish' ] );
			if ( isset( $recent[0]['ID'] ) ) {
				$post = get_post( $recent[0]['ID'] );
			}
		}

		return $post instanceof \WP_Post ? $post : null;
	}

	/**
	 * The excerpt text for a post under the chosen limit. Plain text — the twig
	 * escapes it — so a `<strong>` inside a manual excerpt is not a tag the
	 * character count has to step around.
	 *
	 * @param \WP_Post|null $post     The post, or null for nothing.
	 * @param string        $limit_by none | word | char | line.
	 * @param int           $limit    The number for word / char; ignored otherwise.
	 * @param string        $more     Appended after a word / char trim.
	 * @return string
	 */
	public static function excerpt_for( $post, string $limit_by, int $limit, string $more ): string {
		if ( ! $post ) {
			return '';
		}

		// WordPress says so itself for a protected post, in every mode.
		if ( post_password_required( $post ) ) {
			return self::plain( get_the_excerpt( $post ) );
		}

		$trimming = in_array( $limit_by, [ 'word', 'char' ], true ) && $limit > 0;

		$text = $trimming ? self::full_text( $post ) : self::plain( get_the_excerpt( $post ) );

		return self::trim( $text, $limit_by, $limit, $more );
	}

	/**
	 * The text a word / char limit trims FROM: the manual excerpt when the
	 * author wrote one, otherwise the whole content as plain text. Public so
	 * the editor's loop-grid preview can hand the canvas the same source the
	 * front end trims (`ajax_loop_post_data`).
	 *
	 * @param \WP_Post $post The post.
	 * @return string
	 */
	public static function full_text( \WP_Post $post ): string {
		if ( post_password_required( $post ) ) {
			return self::plain( get_the_excerpt( $post ) );
		}

		return self::plain( has_excerpt( $post ) ? $post->post_excerpt : $post->post_content );
	}

	/**
	 * Apply a word or character limit. A line clamp is CSS and leaves the text
	 * alone, so it (and `none`) return the text unchanged.
	 *
	 * A character trim ends on a WORD boundary when one is near — "...cutting
	 * a word in ha…" reads as a bug, not a limit — but only when the boundary
	 * is inside the last fifth of the allowance, so a limit of 12 on a long
	 * word still shows something rather than nothing.
	 */
	public static function trim( string $text, string $limit_by, int $limit, string $more ): string {
		$limit = min( max( 0, $limit ), self::MAX_LIMIT );

		if ( $limit <= 0 ) {
			return $text;
		}

		if ( 'word' === $limit_by ) {
			return wp_trim_words( $text, $limit, $more );
		}

		if ( 'char' === $limit_by ) {
			if ( mb_strlen( $text ) <= $limit ) {
				return $text;
			}
			$cut = mb_substr( $text, 0, $limit );
			// Cut mid-word? Step back to the last space if it is close enough.
			if ( ! preg_match( '/\s/u', mb_substr( $text, $limit, 1 ) ) ) {
				$last_space = mb_strrpos( $cut, ' ' );
				if ( false !== $last_space && $last_space >= (int) floor( $limit * 0.8 ) ) {
					$cut = mb_substr( $cut, 0, $last_space );
				}
			}
			return rtrim( $cut, " \t\n\r\0\x0B,;:" ) . $more;
		}

		return $text;
	}

	/**
	 * Content → one line of plain text: shortcodes and blocks removed the way
	 * wp_trim_excerpt() removes them, tags stripped, entities decoded so a
	 * character count counts characters and not `&amp;`, whitespace collapsed.
	 *
	 * EVERY entity is decoded, not just the five wp_specialchars_decode()
	 * knows: WordPress's own excerpt ends in ` [&hellip;]`, and the twig
	 * escapes this text on output, so an undecoded entity would reach the
	 * visitor as the literal characters `[&hellip;]`. Decoding is safe for
	 * the same reason — nothing here is printed raw.
	 */
	private static function plain( string $html ): string {
		$text = strip_shortcodes( $html );
		if ( function_exists( 'excerpt_remove_blocks' ) ) {
			$text = excerpt_remove_blocks( $text );
		}
		if ( function_exists( 'excerpt_remove_footnotes' ) ) {
			$text = excerpt_remove_footnotes( $text );
		}
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( (string) $text );
	}

	/**
	 * Editor-only sample: the shared sample post's excerpt (the SAME post the
	 * Post Title / Post Image widgets preview, so a card reads as one post), or
	 * a labelled sentence when the site has nothing to sample.
	 */
	private static function editor_sample_text(): string {
		if ( class_exists( '\Wealcoder\AnimationAddons\AtomicWidgets\Atomic' ) ) {
			$sample = \Wealcoder\AnimationAddons\AtomicWidgets\Atomic::get_sample_post();
			if ( $sample ) {
				$text = self::excerpt_for( $sample, 'none', 0, '' );
				if ( '' !== $text ) {
					return $text;
				}
			}
		}

		return __( 'The post excerpt will be displayed here — a short summary of the post, limited by words, characters or lines.', 'animation-addons-for-elementor' );
	}
}
