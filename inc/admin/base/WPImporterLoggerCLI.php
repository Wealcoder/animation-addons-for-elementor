<?php

namespace WCF_ADDONS\Admin\Base;

defined( 'ABSPATH' ) || die();

class WPImporterLoggerCLI extends WPImporterLogger {
	
	public $min_level = 'notice';

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( $this->level_to_numeric( $level ) < $this->level_to_numeric( $this->min_level ) ) {
			return;
		}

		printf(
			'[%s] %s' . PHP_EOL,
			esc_html(strtoupper( $level )),
			esc_html($message)
		);
	}

	public static function level_to_numeric( $level ) {
		$levels = array(
			'emergency' => 8,
			'alert'     => 7,
			'critical'  => 6,
			'error'     => 5,
			'warning'   => 4,
			'notice'    => 3,
			'info'      => 2,
			'debug'     => 1,
		);
		if ( ! isset( $levels[ $level ] ) ) {
			return 0;
		}

		return $levels[ $level ];
	}
}
