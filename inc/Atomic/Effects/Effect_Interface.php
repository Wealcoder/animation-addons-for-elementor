<?php
namespace WCF_ADDONS\Atomic\Effects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract every atomic animation effect implements.
 *
 * The registry treats every effect uniformly. Effects own:
 *   - their atomic schema (top-level prop registrations)
 *   - their controls section(s)
 *   - their render-time data-attrs
 *   - their JS bundle identity (so Assets.php can decide enqueueing)
 *
 * Effects do NOT own:
 *   - the elementor/atomic-widgets/* hooks themselves
 *     (Schema, Controls, Render classes call into the registry)
 *   - script registration with WordPress
 *     (Assets.php does, via bundle() handle + script registration)
 *   - widget targeting decisions beyond returning targets()
 */
interface Effect_Interface {

	/** Stable, unique identifier (snake_case). Registry key. */
	public function name(): string;

	/**
	 * Atomic widget types this effect applies to (e.g. 'e-heading',
	 * 'e-flexbox'). Empty array means "all supported targets" — the
	 * registry consumer decides scope.
	 */
	public function targets(): array;

	/**
	 * Extend the props schema with this effect's settings. Mutates the
	 * passed schema by reference.
	 */
	public function register_props( array &$schema ): void;

	/**
	 * Return one or more Atomic Section objects holding this effect's
	 * controls. Caller flattens the array into the panel.
	 *
	 * @return array
	 */
	public function build_sections(): array;

	/**
	 * Convert the resolved widget settings into render-time data-attrs.
	 * Returns a flat [key => value] map. Empty = nothing to render.
	 */
	public function build_attrs( array $settings ): array;

	/**
	 * Script handle for this effect's JS bundle, or null if controls-only
	 * (no runtime). Multiple effects may share a bundle.
	 */
	public function bundle(): ?string;

	/**
	 * Should the effect's bundle be enqueued for a widget with these
	 * settings? V3 pattern: enqueue is widget-conditional, not page-wide.
	 */
	public function should_enqueue( array $settings ): bool;
}
