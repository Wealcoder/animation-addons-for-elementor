<?php

namespace WCF_ADDONS\Atomic\ImageHover;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Image Hover panel — only the section anchor is registered here.
 * The React replacement renders all controls (Enable, Media, dimensions,
 * Play) inside a <ResponsiveSection>.
 */
final class Controls
{

	public function register(): void
	{
		add_filter('elementor/atomic-widgets/controls', [$this, 'inject_controls'], 10, 2);
	}

	public function inject_controls(array $controls, $element)
	{
		if (! is_object($element) || ! method_exists($element, 'get_element_type')) {
			return $controls;
		}

		if (! class_exists(Section::class)) {
			return $controls;
		}

		$type = $element->get_element_type();

		if (in_array($type, Schema::image_hover_widgets(), true)) {
			$controls[] = $this->build_section();
		}

		return $controls;
	}

	private function build_section(): Section
	{
		return Section::make()
			->set_label(__('Image Reveal on Hover', 'animation-addons-for-elementor'))
			->set_items([
				// Anchor — React replacement renders all controls (Enable,
				// Media picker, Width / Height / Top / Left / Z-Index + Play).
				Text_Control::bind_to(Schema::IH_SECTION_ANCHOR)
			]);
	}
}
