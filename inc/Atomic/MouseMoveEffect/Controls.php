<?php

namespace WCF_ADDONS\Atomic\MouseMoveEffect;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use WCF_ADDONS\Atomic\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Controls {

	const TD =
		'animation-addons-for-elementor';

	public function register(): void {

		add_filter(
			'elementor/atomic-widgets/controls',
			[ $this, 'inject_controls' ],
			10,
			2
		);
	}

	public function inject_controls(
		array $controls,
		$element
	) {

		if (
			! is_object( $element ) ||
			! method_exists(
				$element,
				'get_element_type'
			)
		) {
			return $controls;
		}

		$type =
			$element->get_element_type();

		if (
			in_array(
				$type,
				Bootstrap::target_element_types(),
				true
			)
		) {
			$controls[] =
				$this->build_section();
		}

		return $controls;
	}

	private function build_section(): Section {

		return Section::make()

			->set_label(
				Bootstrap::get_label( __( 'Mouse Move Effect', self::TD ) )
			)

			->set_items([

				/*
				|--------------------------------------------------------------------------
				| ONLY ONE PLACEHOLDER FIELD
				|--------------------------------------------------------------------------
				*/

				Text_Control::bind_to(
					Schema::SECTION_ANCHOR
				),

			]);
	}
}