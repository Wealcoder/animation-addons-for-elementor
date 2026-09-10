<?php
/**
 * Panel upsell cards for the Pro-owned atomic widgets.
 *
 * An atomic element type that nothing registers does not merely vanish from the
 * panel — Elementor DELETES it from `_elementor_data` on the next save. Free
 * used to ship transitional copies of the widgets that moved to Pro for exactly
 * that reason, retiring them through a version-gated stand-down. Those copies
 * and that machinery are both gone now, so on a free-only site every widget
 * below genuinely fails to register and earns its card.
 *
 * The list is the CANDIDATES; the REGISTRY decides. `missing_widgets()` asks
 * `Atomic::widget_code_present()` per slug, so a widget free still ships is
 * never advertised, and a widget added to Pro later starts advertising itself
 * the moment free stops shipping it — with no edit here.
 *
 * This is also why Counter and Nav are deliberately NOT listed: they came back
 * to free permanently and are not Pro-owned any more, so a card for either
 * would be an upsell for something the site already has.
 *
 * ELEMENTOR DOES ALL THE WORK; this class only supplies the data.
 * `elementor/editor/localize_settings` => `atomicWidgetPromotions`, and then:
 *
 *   - editor.js `initElementsCollection()` adds each entry's widgets to the
 *     panel collection with `editable: false` and our `promotionType`;
 *   - `editable: false` makes views/element.js `onRender()` return BEFORE it
 *     wires click-to-add and html5Draggable, which is what makes the card
 *     un-draggable, and makes the card template print a corner lock icon;
 *   - e-react-promotions.js `attachAtomicWidgetPromotionListeners()` registers
 *     a `<type>-promotion:open` listener that mounts the upgrade card carrying
 *     OUR title / content / ctaText / widgetCtaUrl.
 *
 * So there is no AAE JavaScript here at all, and none should be added: every
 * behaviour above is Elementor's own, which is the point — the cards behave
 * identically to a native Elementor Pro promotion, including the dialog's
 * placement, theme and RTL handling.
 *
 * VERIFIED against the installed Elementor 4.2.4, not just the source clone —
 * `atomicWidgetPromotions` is present in both `assets/js/editor.js` (the panel
 * half) and `assets/js/e-react-promotions.js` (the dialog half). If a future
 * Elementor drops the key the cards just stop appearing; nothing errors and no
 * real widget is affected, which is the right way for an upsell to fail.
 *
 * KNOWN LIMITATION — on a site that has Elementor Pro installed but NOT
 * connected, `applyProConnectPromotionOverrides()` (app-manager.js) replaces
 * the ctaUrl/ctaText of EVERY promotion card, ours included, with Elementor's
 * own "Connect & Activate" link. That is unconditional inside their app and
 * cannot be opted out of while we ride their card. The alternative is drawing
 * our own dialog, which costs a React surface in free and diverges from the
 * "just like Elementor's" behaviour this was asked to match.
 *
 * @package AnimationAddonsForElementor
 */

namespace WCF_ADDONS\AtomicWidgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pro_Promotion {

	/**
	 * Namespaces the `<type>-promotion:open` DOM event and the wrapper class
	 * Elementor mounts the card into (`e-<type>-promotion-wrapper`), so ours
	 * cannot collide with Elementor's own `atomic-form` promotion.
	 */
	const PROMOTION_TYPE = 'aae-pro';

	/**
	 * Every promoted widget declares this in its own `get_categories()`, so the
	 * locked cards land among the real ones in "AAE General" rather than in a
	 * separate upsell group.
	 */
	const PANEL_CATEGORY = 'aae-atomic-general';

	/** @see Atomic::UPGRADE_URL — one destination for every editor upsell. */
	const UPGRADE_URL = Atomic::UPGRADE_URL;

	/**
	 * The Pro widgets worth a card, as `slug => [ label, icon ]`.
	 *
	 * DELIBERATELY A SECOND COPY of what `AtomicV4\Widgets\Bootstrap::cards()`
	 * holds in the Pro plugin. It has to be: this list's whole job is to
	 * describe widgets on a site where that file is not on disk, so it cannot be
	 * derived from it, and the labels must live in free's own text domain to be
	 * translated at all on such a site.
	 *
	 * The two lists drifting apart fails in only one direction, and it is the
	 * harmless one: a Pro widget missing here gets no upsell card. It can never
	 * produce a duplicate, or a card for a widget that works, because
	 * `missing_widgets()` asks the live registry rather than trusting this list.
	 *
	 * Internal child elements (`aae-a-stack-card`, the four Offcanvas parts, the
	 * Nav items, `aae-a-lottie-player`) are omitted on purpose — they carry
	 * `hide_from_panel` / `is_internal` in Pro's own cards() and have no panel
	 * card to advertise when Pro IS installed either.
	 *
	 * @return array<string,array>
	 */
	private static function widgets(): array {
		return [
			'aae-a-draw-svg'    => [
				'label' => esc_html__( 'DrawSVG', 'animation-addons-for-elementor' ),
				'icon'  => 'eicon-animation',
			],
			'aae-a-stack-cards' => [
				'label' => esc_html__( 'Stack Cards', 'animation-addons-for-elementor' ),
				'icon'  => 'eicon-post-list',
			],
			'aae-a-btn-pro'     => [
				'label' => esc_html__( 'Button Pro', 'animation-addons-for-elementor' ),
				'icon'  => 'wcf-icon-Button',
			],
			'aae-a-offcanvas'   => [
				'label' => esc_html__( 'Offcanvas', 'animation-addons-for-elementor' ),
				'icon'  => 'eicon-sidebar',
			],
			'aae-a-lottie'      => [
				'label' => esc_html__( 'Lottie', 'animation-addons-for-elementor' ),
				'icon'  => 'eicon-animation',
			],
			'aae-a-toc'         => [
				'label' => esc_html__( 'Table of Content', 'animation-addons-for-elementor' ),
				'icon'  => 'eicon-table-of-contents',
			],
		];
	}

	public function register(): void {
		add_filter( 'elementor/editor/localize_settings', [ $this, 'add_promotion_data' ] );
	}

	/**
	 * @param array $settings Elementor's editor config.
	 * @return array
	 */
	public function add_promotion_data( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		// Mirrors Elementor's own Atomic_Form_Widget_Promotion: only someone who
		// can act on the offer is shown it, and every other role sees the panel
		// exactly as it is today.
		if ( ! current_user_can( 'manage_options' ) ) {
			return $settings;
		}

		$widgets = $this->missing_widgets();

		if ( ! $widgets ) {
			return $settings;
		}

		if ( ! isset( $settings['atomicWidgetPromotions'] ) || ! is_array( $settings['atomicWidgetPromotions'] ) ) {
			$settings['atomicWidgetPromotions'] = [];
		}

		$settings['atomicWidgetPromotions'][] = [
			'type'     => self::PROMOTION_TYPE,
			'cardType' => 'atomic',
			'widgets'  => $widgets,
			'content'  => [
				'title'         => esc_html__( 'Animation Addons Pro', 'animation-addons-for-elementor' ),
				'content'       => esc_html__( 'Unlock Counter, Stack Cards, Offcanvas, Nav, Lottie and more scroll-driven atomic widgets.', 'animation-addons-for-elementor' ),
				'ctaText'       => esc_html__( 'Upgrade now', 'animation-addons-for-elementor' ),
				'widgetCtaUrl'  => self::UPGRADE_URL,
				'sectionCtaUrl' => self::UPGRADE_URL,
			],
		];

		return $settings;
	}

	/**
	 * The promotable widgets whose element type will NOT register on this site.
	 *
	 * `widget_code_present()`, not "is the Pro plugin active" and not
	 * `is_widget_active()`. Those are three different questions and only this one
	 * is right in every state:
	 *
	 *   - Pro absent, free still shipping its transitional copy → the code IS
	 *     there, the widget works, and advertising it would be a lie.
	 *   - Pro absent and no free copy left → the type never registers. Card.
	 *   - Registry entry with the class file gone (`aae-a-btn-pro` today) → the
	 *     type never registers either, so registry membership alone is not the
	 *     test. Card.
	 *   - Pro present and licensed → every slug resolves, empty list, no card.
	 *   - Switched OFF in the dashboard → the user's own choice, the code is
	 *     present, and it must NOT come back as a paid upgrade. No card.
	 *
	 * @return array
	 */
	private function missing_widgets(): array {
		$atomic = Atomic::instance();
		$out    = [];

		foreach ( self::widgets() as $slug => $widget ) {
			if ( $atomic->widget_code_present( $slug ) ) {
				continue;
			}

			$out[] = [
				'name'       => 'e-' . $slug,
				'title'      => $widget['label'],
				'icon'       => $widget['icon'],
				// Elementor JSON.parse()s this rather than reading an array —
				// see editor.js initElementsCollection().
				'categories' => wp_json_encode( [ self::PANEL_CATEGORY ] ),
			];
		}

		return $out;
	}
}
