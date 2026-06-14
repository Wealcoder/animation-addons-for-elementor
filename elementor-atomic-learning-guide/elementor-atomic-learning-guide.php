<?php
/**
 * Plugin Name: Elementor Atomic Widgets Learning Guide
 * Description: A learning guide and example plugin that teaches how to build Elementor v4 Atomic Widgets. Includes a complete Alert Box example widget.
 * Plugin URI:  https://github.com/your-repo/elementor-atomic-learning-guide
 * Version:     1.0.0
 * Author:      Learning Guide
 * License:     GPL-2.0+
 *
 * 📘 এই প্লাগিনটি শেখার জন্য তৈরি করা হয়েছে।
 *
 * 📂 কী কী আছে:
 *   - atomic-guide-bangla.md   → সম্পূর্ণ বাংলা গাইড
 *   - example/alert-box/        → সম্পূর্ণ Atomic Widget উদাহরণ
 *   - elementor-atomic-learning-guide.php (এই ফাইল) → উইজেট রেজিস্ট্রেশন
 *
 * ⚠️ শর্ত: Elementor v4 (atomic-widgets experiment সক্রিয় থাকতে হবে)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EALG_VERSION', '1.0.0' );
define( 'EALG_PATH', plugin_dir_path( __FILE__ ) );
define( 'EALG_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the example Alert Box Atomic Widget.
 *
 * এটিই মূল রেজিস্ট্রেশন হুক। নতুন উইজেট রেজিস্টার করতে এই হুক ব্যবহার করুন।
 */
add_action( 'elementor/widgets/register', function( $widgets_manager ) {

	// Alert Box widget ফাইল লোড করুন
	require_once EALG_PATH . 'example/alert-box/alert-box.php';

	// উইজেট রেজিস্টার করুন
	$widgets_manager->register( new \MyPlugin\Widgets\Alert_Box\Alert_Box() );

} );

/**
 * Helper note shown on the plugins screen.
 */
add_action( 'admin_notices', function() {
	if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Module' ) ) {
		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo '<strong>Elementor Atomic Learning Guide:</strong> ';
		echo 'Elementor v4 এর "Atomic Widgets" experiment সক্রিয় নয়। ';
		echo 'WP Admin → Elementor → Settings → Experiments এ গিয়ে <code>Atomic Widgets</code> সক্রিয় করুন।';
		echo '</p></div>';
	}
} );