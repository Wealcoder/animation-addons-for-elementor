<?php
/**
 * AAE Forms — email-marketing provider interface.
 *
 * The "adapters, not one-off code" contract for third-party subscriber
 * sync (Brevo, Mailchimp, ActiveCampaign, …). The FREE plugin owns this
 * interface, the global-key store, and the provider registry; PRO ships
 * the concrete providers (real API calls) and registers them via the
 * `aae_form/integrations` filter — mirroring how Actions\Registry lets the
 * queue resolve action types.
 *
 * A provider is a lightweight descriptor + three network operations. The
 * per-form subscribe work itself runs through the async Queue as an
 * Actions\Action_Base (the pro Email_Marketing action), NOT here — this
 * interface is for editor/admin concerns (connect, validate, list picker).
 *
 * @package AnimationAddonsForElementor
 * @since   4.0.0
 */

namespace WCF_ADDONS\Forms\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Provider {

	/** Unique id — also the actions_json key and option suffix, e.g. 'brevo'. */
	public static function id(): string;

	/** Human label for the connect card / provider picker, e.g. 'Brevo'. */
	public static function label(): string;

	/**
	 * The contact attributes this provider accepts, in the order the editor
	 * mapping UI should show them. Each row maps ONE provider attribute to a
	 * form field. The first `required` attribute is the identifier (email).
	 * Static so the editor can list attributes without a stored key.
	 *
	 * @return array<int,array{key:string,label:string,required?:bool}>
	 *   e.g. [ ['key'=>'EMAIL','label'=>'Email','required'=>true], … ]
	 */
	public static function attributes(): array;

	/**
	 * Where/how the admin finds this provider's API key — shown on the
	 * dashboard Integrations connect card so "paste your API key" isn't a
	 * dead end. Static so it renders even before pro validates anything.
	 *
	 * @return array{text:string,url:string} `url` may be '' if there's
	 *   nothing sensible to link (text alone still renders).
	 */
	public static function help(): array;

	/**
	 * Validate an API key against the provider (a real network call).
	 *
	 * @return array{ok:bool, message:string, account?:string} ok=true means
	 *   the key authenticates; `account` is an optional display name/email.
	 */
	public function validate_key( string $api_key ): array;

	/**
	 * Fetch the provider's subscriber lists for the list picker.
	 *
	 * @return array{ok:bool, message:string, lists?:array<int,array{id:string,name:string}>}
	 */
	public function fetch_lists( string $api_key ): array;

	/**
	 * Subscribe/upsert one contact. Called from the pro Email_Marketing
	 * action inside the Queue (attempt + retry owned by the Queue).
	 *
	 * @param string              $api_key     Global key for this provider.
	 * @param array<string,mixed> $settings    Per-form config (list_id, mapping, …).
	 * @param array<string,mixed> $context     Submission context (fields/labels/…).
	 * @return array{success:bool, message:string, request:array, response:array}
	 */
	public function subscribe( string $api_key, array $settings, array $context ): array;
}
