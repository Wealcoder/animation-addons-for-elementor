=== Elementor Atomic Widgets Learning Guide ===
Contributors: learning-guide
Tags: elementor, atomic-widgets, v4, guide, tutorial
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later

বাংলা গাইড ও উদাহরণ সহ Elementor v4 Atomic Widget তৈরির শেখার প্লাগিন।

== Description ==

এই প্লাগিনটি Elementor v4 এর নতুন "Atomic Widgets" সিস্টেমে উইজেট তৈরি শেখার জন্য।
কোনো অফিশিয়াল ডকুমেন্টেশন নেই, তাই সব কিছু সোর্স কোড থেকে বিশ্লেষণ করে বানানো হয়েছে।

বৈশিষ্ট্য:

* সম্পূর্ণ বাংলা গাইড (`atomic-guide-bangla.md`)
* সম্পূর্ণ Alert Box Atomic Widget উদাহরণ
* ৫টি ধাপে schema, controls, styles, template শেখা যায়

== Installation ==

1. প্লাগিন ফোল্ডার `wp-content/plugins/` এ আপলোড করুন
2. WP Admin → Plugins থেকে সক্রিয় করুন
3. WP Admin → Elementor → Settings → Experiments এ গিয়ে "Atomic Widgets" সক্রিয় করুন
4. Elementor এডিটর খুলে "Alert Box" সার্চ করুন

== Files ==

* `atomic-guide-bangla.md` - সম্পূর্ণ বাংলা গাইড
* `example/alert-box/alert-box.php` - Atomic Widget PHP class
* `example/alert-box/alert-box.html.twig` - Twig template
* `elementor-atomic-learning-guide.php` - Main plugin file

== Frequently Asked Questions ==

= উইজেট এডিটরে দেখাচ্ছে না কেন? =

"Atomic Widgets" experiment সক্রিয় করা আছে কিনা যাচাই করুন
(WP Admin → Elementor → Settings → Experiments)।

= error "Prop is not defined in the schema" =

`bind_to('field')` এর ফিল্ড নাম `define_props_schema()` এ আছে কিনা যাচাই করুন।

== Changelog ==

= 1.0.0 =
* প্রথম রিলিজ
* Alert Box উদাহরণ উইজেট
* সম্পূর্ণ বাংলা গাইড