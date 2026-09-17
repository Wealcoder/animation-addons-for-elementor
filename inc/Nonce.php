<?php
/**
 * The plugin's nonce actions, under the `aaeaddon_` prefix -- and the rule
 * that keeps a renamed nonce from locking anyone out.
 *
 * A nonce is minted for one ACTION string and verified against the same
 * string; there is no alias mechanism in WordPress for it. So a rename has
 * two ways to break a working site, and both are silent (a bare `-1` / 403
 * from admin-ajax, nothing in the log):
 *
 *   1. A nonce minted under the OLD spelling is still in flight -- a
 *      page-cached front page carries `WCF_ADDONS_JS._wpnonce` for hours, an
 *      editor tab opened before the update holds the old admin nonce, and a
 *      nonce is valid for a day. verify() therefore accepts the legacy
 *      spelling as well as the new one, permanently.
 *
 *   2. The SHIPPED Pro plugin verifies four of these actions by literal --
 *      `check_ajax_referer( 'wcf_admin_nonce', 'nonce' )` guards its licence
 *      activation, the Performance wizard, the usage scan and the cache
 *      panel; `wcf-addons-frontend` guards post rating and reactions. Free
 *      cannot make those literals accept a new spelling, so while a Pro older
 *      than 4.3 is active, create() mints THOSE four under the legacy spelling
 *      (free's own handlers accept either). A Pro from 4.3 on verifies both
 *      spellings itself and the new one is minted everywhere.
 *
 * Both rules live here and nowhere else: every `wp_create_nonce()` /
 * `wp_verify_nonce()` / `check_ajax_referer()` in the plugin goes through
 * this class, so a nonce action is spelled in exactly one file.
 *
 * @package Wealcoder\AnimationAddons
 */

namespace Wealcoder\AnimationAddons;

defined( 'ABSPATH' ) || die();

final class Nonce {

	/** The dashboard, the setup wizard and every admin-ajax writer (`WCF_ADDONS_ADMIN.nonce`). */
	const ADMIN = 'aaeaddon_admin_nonce';

	/** Front-end visitors: live search, post shares/views/reactions, mailchimp (`WCF_ADDONS_JS._wpnonce`). */
	const FRONTEND = 'aaeaddon_frontend';

	/** The Elementor editor's own AJAX (mailchimp list fields, `WCF_Addons_Editor._wpnonce`). */
	const EDITOR = 'aaeaddon_editor';

	/** The atomic editor bridge's Loop Grid / Nav endpoints. */
	const LOOP_GRID = 'aaeaddon_loop_grid';

	/** Loop Grid pagination and filtering on the front end. */
	const LOOP_GRID_FRONT = 'aaeaddon_loop_grid_front';

	/** The editor's template library modal. */
	const TEMPLATE_LIBRARY = 'aaeaddon_template_library';

	/** The Theme Builder's create / edit modal. */
	const THEME_BUILDER = 'aaeaddon_theme_builder';

	/** The Code Snippet screen's AJAX (list, search, bulk actions, status toggle). */
	const CODE_SNIPPET = 'aaeaddon_code_snippet';

	/** The Code Snippet edit form's classic POST. */
	const CODE_SNIPPET_FORM = 'aaeaddon_code_snippet_form';

	/** The Form Builder's public submit token (`Rest::token()` -> submit / upload). */
	const FORM_SUBMIT = 'aaeaddon_form_submit';

	/** The Form Builder's CSV export (`admin-post.php?action=aaeaddon_form_csv`). */
	const FORM_CSV = 'aaeaddon_form_csv';

	/** The category / term meta fields on the term edit screen. */
	const CATEGORY_META = 'aaeaddon_category_meta';

	/** The category media picker (`AAECategoryMedia.nonce`). */
	const CATEGORY_MEDIA = 'aaeaddon_category_media';

	/** The v3 Loop Builder editor controls, its templates and its front-end pagination. */
	const LOOP_BUILDER = 'aaeaddon_loop_builder';

	/**
	 * The pre-4.2 spelling of each action. verify() accepts these for good;
	 * create() mints the ones in PRO_VERIFIES while an older Pro is active.
	 */
	const LEGACY = array(
		self::ADMIN            => 'wcf_admin_nonce',
		self::FRONTEND         => 'wcf-addons-frontend',
		self::EDITOR           => 'wcf-addons-editor',
		self::LOOP_GRID        => 'aae_loop_grid',
		self::LOOP_GRID_FRONT  => 'aae_loop_grid_front',
		self::TEMPLATE_LIBRARY => 'wcf-template-library',
		self::THEME_BUILDER    => 'wcf_tmp_nonce',
		self::CODE_SNIPPET     => 'wcf_custom_code_security',
		self::CODE_SNIPPET_FORM => 'wcf_code_snippet',
		self::FORM_SUBMIT      => 'aae_form_submit',
		self::FORM_CSV         => 'aae_form_csv',
		self::CATEGORY_META    => 'aae_category_meta_action',
		self::CATEGORY_MEDIA   => 'aae_category_media',
		self::LOOP_BUILDER     => 'aae_loop_builder_nonce',
	);

	/**
	 * Actions the shipped Pro (< 4.3) verifies by their OLD literal. Measured
	 * on the released build: wcf_admin_nonce x9 check_ajax_referer,
	 * wcf-addons-frontend x3 wp_verify_nonce, wcf-addons-editor x2,
	 * aae_loop_grid x1 (LoopFilters\Preview).
	 */
	const PRO_VERIFIES = array( self::ADMIN, self::FRONTEND, self::EDITOR, self::LOOP_GRID );

	/** First Pro version that verifies the new spellings itself. */
	const PRO_KNOWS_NEW_NAMES = '4.3.0';

	/**
	 * Mint a nonce for one of the actions above.
	 *
	 * @param string $action One of the class constants.
	 * @return string
	 */
	public static function create( $action ) {
		return wp_create_nonce( self::minted_as( $action ) );
	}

	/**
	 * The spelling create() mints for `$action` right now -- the new one,
	 * unless an older Pro that verifies this action by its old literal is
	 * active. Public so a test can assert the rule directly.
	 *
	 * @param string $action One of the class constants.
	 * @return string
	 */
	public static function minted_as( $action ) {
		if ( isset( self::LEGACY[ $action ] ) && in_array( $action, self::PRO_VERIFIES, true ) && self::old_pro_active() ) {
			return self::LEGACY[ $action ];
		}
		return $action;
	}

	/**
	 * `wp_verify_nonce()` that also accepts the legacy spelling.
	 *
	 * @param string $nonce  The value posted back.
	 * @param string $action One of the class constants.
	 * @return int|false 1 or 2 like wp_verify_nonce(), false when neither spelling verifies.
	 */
	public static function verify( $nonce, $action ) {
		$nonce  = is_string( $nonce ) ? $nonce : '';
		$result = wp_verify_nonce( $nonce, $action );
		if ( false === $result && isset( self::LEGACY[ $action ] ) ) {
			$result = wp_verify_nonce( $nonce, self::LEGACY[ $action ] );
		}
		return $result;
	}

	/**
	 * `check_admin_referer()` that also accepts the legacy spelling. Mirrors
	 * core: fires `check_admin_referer`, and on failure shows the "link has
	 * expired" screen and dies.
	 *
	 * @param string $action    One of the class constants.
	 * @param string $query_arg Request key holding the nonce.
	 * @return int|false
	 */
	public static function check_admin( $action, $query_arg = '_wpnonce' ) {
		$nonce  = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this IS the verification.
		$result = self::verify( $nonce, $action );
		do_action( 'check_admin_referer', $action, $result );
		if ( ! $result ) {
			wp_nonce_ays( $action );
			die();
		}
		return $result;
	}

	/**
	 * `check_ajax_referer()` that also accepts the legacy spelling. Same
	 * signature, same return, same `-1` / 403 on failure -- it IS core's
	 * function, called once per spelling, so the `check_ajax_referer` action
	 * still fires and a failure still dies the way every handler expects.
	 *
	 * @param string      $action    One of the class constants.
	 * @param string|false $query_arg Request key holding the nonce.
	 * @param bool        $die       Die on failure, as core does.
	 * @return int|false
	 */
	public static function check_ajax( $action, $query_arg = 'nonce', $die = true ) {
		$result = check_ajax_referer( $action, $query_arg, false );
		if ( false === $result ) {
			$again  = isset( self::LEGACY[ $action ] ) ? self::LEGACY[ $action ] : $action;
			$result = check_ajax_referer( $again, $query_arg, $die );
		}
		return $result;
	}

	/**
	 * Is a Pro that still verifies the OLD spellings by literal active?
	 *
	 * @return bool
	 */
	private static function old_pro_active() {
		return aaeaddon_pro_defined( 'VERSION' )
			&& version_compare( aaeaddon_pro_constant( 'VERSION' ), self::PRO_KNOWS_NEW_NAMES, '<' );
	}
}
