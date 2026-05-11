<?php
namespace WCF_ADDONS\Atomic;

use Elementor\Modules\AtomicWidgets\PropDependencies\Manager as Dependency_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared dependency-builder helpers for atomic prop schemas. Both
 * TextAnimation\Schema and RegularAnimation\Schema build hide-on-condition
 * dependencies the same way; this class is the single source of truth.
 */
final class Schema_Helpers {

	public static function dep_eq( string $source, $value ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'eq',
				'path'     => [ $source ],
				'value'    => $value,
				'effect'   => 'hide',
			] )
			->get();
	}

	public static function dep_ne( string $source, $value ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'ne',
				'path'     => [ $source ],
				'value'    => $value,
				'effect'   => 'hide',
			] )
			->get();
	}

	public static function dep_in( string $source, array $values ): array {
		return Dependency_Manager::make()
			->where( [
				'operator' => 'in',
				'path'     => [ $source ],
				'value'    => $values,
				'effect'   => 'hide',
			] )
			->get();
	}
}
