<?php
/**
 * Licence gate for the Pro form fields and the multi-step feature.
 *
 * WHAT IS AND IS NOT GATED, and why the difference matters more than the list:
 *
 *   - The element TYPE always registers, licensed or not. Elementor instantiates
 *     every element on save and DROPS any whose type is unregistered
 *     (get_elements_raw_data(), elementor/core/base/document.php:1111), so
 *     unregistering a Pro field would permanently delete it — and, for a
 *     container, everything inside it — from `_elementor_data` on the next save
 *     of any page using it. The gate therefore never touches registration.
 *   - What IS gated: the panel card stops being draggable, the frontend renders
 *     nothing, and the field leaves the submission schema.
 *
 * FOUR SEAMS, one list. Miss any of them and the failure is silent:
 *
 *   1. PANEL — Pro_Gated::is_editable() returns false while locked, which is all
 *      Elementor needs: views/element.js `onRender()` returns before it wires
 *      html5Draggable and click-to-add, the card template prints a lock icon,
 *      and mousedown opens the upgrade card. Verified that `editable` is read
 *      ONLY under regions/panel/pages/elements/ — an element already on the
 *      canvas (dropped by a preset, say) stays fully selectable and editable,
 *      which is exactly what we want: it must show a Pro notice, not become
 *      unreachable.
 *   2. FRONTEND — render_content returns ''. Leaf widgets only; see the note on
 *      Step below, which is a container and must NOT be blanked.
 *   3. SCHEMA + VALIDATOR — the important one, and the one nothing would remind
 *      you about. Schema_Walker builds the schema from the saved tree at save
 *      time, so a gated field stays in the schema even though the visitor never
 *      sees it. A REQUIRED gated field would then reject every submission with
 *      "this field is required" for a field that is not on the page. Conditional
 *      Display hit the identical problem first and left the seam behind
 *      (`aae_form/validator/skip_field`), so this reuses it.
 *   4. UPSELL COPY — our own title/body/CTA on Elementor's promotion card via
 *      `v4Promotions`, so a locked card sells this plugin rather than showing
 *      Elementor's generic Pro pitch.
 *
 * MULTI-STEP IS FLATTENED, NOT BLANKED — deliberately, and it is the one place
 * where "render nothing" would be the wrong reading of "make it Pro":
 *
 *   - `e-aae-a-form-step` is a CONTAINER. Blanking it takes every field inside
 *     it with it, so a lapsed customer's multi-step contact form renders as an
 *     empty box. That is not an upsell, it is a broken page that silently stops
 *     collecting leads.
 *   - Blanking only Next/Prev is worse still: the step base style is
 *     `display: none` until the runtime adds `aae-form-step-active`, so the
 *     visitor would be stranded on step one with no way forward.
 *
 *   So while locked the runtime reveals EVERY step at once (see
 *   assets/js/lib/multi-step.js, flattenSteps) and the nav widgets render
 *   nothing. The paid feature — stepping — is gone; the form still works and
 *   still converts. Step itself is in GATED_STRUCTURE for the panel and the
 *   editor notice, but is never passed to the render gate.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\Forms;

use WCF_ADDONS\AtomicWidgets\Atomic;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pro_Gate {

	/**
	 * Leaf field widgets: element type => the schema field type it produces.
	 *
	 * The value is what Schema_Walker::FIELD_TYPES maps this element to, and it
	 * is what the validator sees — the two halves of the gate read different
	 * identifiers for the same widget, so both belong in one table rather than
	 * in two lists that can drift.
	 */
	const GATED_FIELDS = [
		'e-aae-a-form-rating'      => 'rating',
		'e-aae-a-form-range'       => 'range',
		'e-aae-a-form-country'     => 'country',
		'e-aae-a-form-password'    => 'password',
		'e-aae-a-form-calculation' => 'calculation',
	];

	/**
	 * Multi-step: gated in the panel and the editor, but NOT rendered blank.
	 *
	 * Next and Prev are leaf widgets and DO render blank — with the steps
	 * flattened they have nothing left to navigate. Step is a container and is
	 * excluded from the render gate entirely; read the class docblock before
	 * moving it into GATED_FIELDS.
	 */
	const GATED_STRUCTURE = [
		'e-aae-a-form-step',
		'e-aae-a-form-next',
		'e-aae-a-form-prev',
	];

	/** Containers whose children must survive the gate. */
	const NEVER_BLANK = [ 'e-aae-a-form-step' ];

	/** @see Atomic::UPGRADE_URL — one destination for every editor upsell. */
	const UPGRADE_URL = Atomic::UPGRADE_URL;

	/**
	 * Is this site missing a valid Pro licence?
	 *
	 * Licence, not "is Pro installed": an expired licence leaves Pro's files on
	 * disk while the customer has not paid, and these are paid fields.
	 */
	public static function is_locked(): bool {
		return ! Atomic::pro_licensed();
	}

	/** Every element type this gate covers, whatever its treatment. */
	public static function gated_types(): array {
		return array_merge( array_keys( self::GATED_FIELDS ), self::GATED_STRUCTURE );
	}

	public static function is_gated( string $type ): bool {
		return in_array( $type, self::gated_types(), true );
	}

	public function register(): void {
		// Priority 5: ahead of the extensions that splice data-attrs into a
		// widget's HTML at the default 10, so a gated widget's markup is gone
		// before anything spends work decorating it.
		add_filter( 'elementor/widget/render_content', [ $this, 'blank_gated_widget' ], 5, 2 );

		add_filter( 'aae_form/schema_walker/schema', [ $this, 'drop_gated_fields' ], 10, 2 );
		add_filter( 'aae_form/validator/skip_field', [ $this, 'skip_gated_field' ], 10, 2 );

		if ( is_admin() ) {
			add_filter( 'elementor/editor/localize_settings', [ $this, 'add_promotion_copy' ] );

			// Priority 5 so the notice lands ABOVE every section the widget and
			// the extensions add — a warning below the fold is not a warning.
			add_filter( 'elementor/atomic-widgets/controls', [ $this, 'inject_pro_notice' ], 5, 2 );
		}
	}

	/**
	 * Put a Pro notice at the top of a locked widget's editing panel.
	 *
	 * ONLY for an instance already on the canvas — the panel lock stops new ones
	 * being dragged in, so anything reaching this is a preset's work or a page
	 * built while the site was licensed. Those stay editable deliberately, which
	 * means nothing else on screen would tell the builder that this field is not
	 * going to render for visitors.
	 *
	 * @param array  $controls Controls for this element.
	 * @param object $element  The element instance.
	 * @return array
	 */
	public function inject_pro_notice( $controls, $element ) {
		if ( ! self::is_locked() || ! is_array( $controls ) ) {
			return $controls;
		}

		if ( ! is_object( $element ) || ! method_exists( $element, 'get_name' ) || ! self::is_gated( $element->get_name() ) ) {
			return $controls;
		}

		// BOTH setters are mandatory, not optional polish: Element_Control_Base
		// declares get_label(): string and get_meta(): array while defaulting both to
		// null, so jsonSerialize() throws a TypeError the moment the editor config is
		// encoded — which takes the whole editor down with a fatal, not a warning.
		array_unshift(
			$controls,
			( new Pro_Notice_Control() )
				->set_label( esc_html__( 'Pro feature', 'animation-addons-for-elementor' ) )
				->set_meta( [ 'aae_pro_type' => $element->get_name() ] )
		);

		return $controls;
	}

	/**
	 * Render nothing for a locked leaf field.
	 *
	 * @param string $content Rendered widget HTML.
	 * @param object $widget  The widget instance.
	 * @return string
	 */
	public function blank_gated_widget( $content, $widget ) {
		if ( ! self::is_locked() || ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
			return $content;
		}

		$type = $widget->get_name();

		if ( in_array( $type, self::NEVER_BLANK, true ) ) {
			return $content;
		}

		return self::is_gated( $type ) ? '' : $content;
	}

	/**
	 * Take the locked fields out of the schema the submission is validated against.
	 *
	 * Without this a REQUIRED locked field rejects every submission for a field
	 * the visitor cannot see — a 422 with no visible cause, on a form that looks
	 * fine. Dropping it here also keeps it out of the stored submission and out
	 * of the notification email, which is right: nothing collected it.
	 *
	 * Only the ACTIVE schema is filtered. Snapshots already stored against past
	 * submissions are untouched, so historical data keeps rendering with the
	 * fields it was actually collected with.
	 *
	 * @param array $schema Canonical schema.
	 * @return array
	 */
	public function drop_gated_fields( $schema, $form = null ) {
		if ( ! self::is_locked() || ! is_array( $schema ) || empty( $schema['fields'] ) || ! is_array( $schema['fields'] ) ) {
			return $schema;
		}

		$locked = array_values( self::GATED_FIELDS );

		$schema['fields'] = array_values(
			array_filter(
				$schema['fields'],
				static function ( $field ) use ( $locked ) {
					return ! ( is_array( $field ) && in_array( $field['type'] ?? '', $locked, true ) );
				}
			)
		);

		return $schema;
	}

	/**
	 * Belt for a submission validated against a schema saved BEFORE the licence
	 * lapsed, whose snapshot still carries the gated field. drop_gated_fields()
	 * cannot reach that one — it only shapes schemas being built now.
	 *
	 * @param bool  $skip  Current decision.
	 * @param array $field Schema field entry.
	 * @return bool
	 */
	public function skip_gated_field( $skip, $field ) {
		if ( $skip || ! self::is_locked() || ! is_array( $field ) ) {
			return $skip;
		}

		return in_array( $field['type'] ?? '', array_values( self::GATED_FIELDS ), true );
	}

	/**
	 * Our own copy on the card Elementor opens when a locked panel item is clicked.
	 *
	 * `v4Promotions` is keyed by widget type and matched after stripping `-`/`_`
	 * and lower-casing (app-manager.js resolveWidgetPromotionData), so the plain
	 * element type is the right key. Without an entry the card falls back to
	 * Elementor's generic "upgrade to Elementor Pro" pitch, which sells the wrong
	 * product from inside our own widget.
	 *
	 * KNOWN LIMITATION, shared with AtomicWidgets\Pro_Promotion: on a site with
	 * Elementor Pro installed but NOT connected, applyProConnectPromotionOverrides()
	 * replaces the ctaUrl/ctaText of every promotion card — ours included — with
	 * Elementor's "Connect & Activate" link. Unconditional inside their app.
	 *
	 * @param array $settings Editor config.
	 * @return array
	 */
	public function add_promotion_copy( $settings ) {
		if ( ! is_array( $settings ) || ! self::is_locked() ) {
			return $settings;
		}

		if ( ! isset( $settings['v4Promotions'] ) || ! is_array( $settings['v4Promotions'] ) ) {
			$settings['v4Promotions'] = [];
		}

		foreach ( self::promotions() as $type => $copy ) {
			// Never clobber an entry Elementor's own remote promotions API
			// supplied for a type it knows about.
			if ( isset( $settings['v4Promotions'][ $type ] ) ) {
				continue;
			}

			$settings['v4Promotions'][ $type ] = [
				'title'   => $copy['title'],
				'content' => $copy['content'],
				'ctaText' => esc_html__( 'Upgrade to Pro', 'animation-addons-for-elementor' ),
				'ctaUrl'  => self::UPGRADE_URL,
			];
		}

		return $settings;
	}

	/**
	 * Per-widget upsell copy.
	 *
	 * Each line names what the visitor gets, not what the builder is missing —
	 * a card that reads "Rating is a Pro feature" states a restriction, while
	 * one that reads "collect star ratings" states a reason to buy. Same reason
	 * the field stays visible in the panel rather than being hidden: someone has
	 * to want it before they will pay for it.
	 *
	 * @return array<string,array>
	 */
	private static function promotions(): array {
		return [
			'e-aae-a-form-rating'      => [
				'title'   => esc_html__( 'Star Rating field', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Let visitors rate you out of five, right inside the form, and collect the score with every submission.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-range'       => [
				'title'   => esc_html__( 'Range Slider field', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Ask for budgets, quantities and sizes with a slider people actually enjoy using — styled to match your design.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-country'     => [
				'title'   => esc_html__( 'Country field', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'A ready-made list of every country, submitting clean ISO codes and filling itself in from the browser.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-password'    => [
				'title'   => esc_html__( 'Password field', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Build sign-up forms with confirm-match and a reveal toggle. Passwords are redacted or hashed, never stored in the clear.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-calculation' => [
				'title'   => esc_html__( 'Calculation field', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Turn a form into a quote calculator: live totals from any fields you choose, recomputed on the server so they cannot be tampered with.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-step'        => [
				'title'   => esc_html__( 'Multi-Step Forms', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Split a long form into steps with per-step validation and animated transitions — the single biggest lift to completion rates.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-next'        => [
				'title'   => esc_html__( 'Multi-Step Forms', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Next and Previous buttons that validate the current step before moving on, so visitors never lose what they typed.', 'animation-addons-for-elementor' ),
			],
			'e-aae-a-form-prev'        => [
				'title'   => esc_html__( 'Multi-Step Forms', 'animation-addons-for-elementor' ),
				'content' => esc_html__( 'Next and Previous buttons that validate the current step before moving on, so visitors never lose what they typed.', 'animation-addons-for-elementor' ),
			],
		];
	}
}
