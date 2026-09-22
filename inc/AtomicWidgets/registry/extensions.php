<?php
/**
 * Atomic extension DASHBOARD CARD metadata.
 *
 * The extension half of the same story, plus each entry's `usage_prop` -- the
 * prop that proves the extension is in use on a page, or `false` for a
 * site-level feature that writes nothing into a page. That key is REQUIRED on
 * every entry: absent would mean both "not countable" and "somebody forgot",
 * and the second fails as a permanent silent zero on the card.
 *
 * Separated from the class for the same measured reason as the widget list:
 * `is_extension_active()` reads the saved option only, so no front-end request
 * ever needs this array. See registry/widgets.php.
 *
 * @package AnimationAddonsForElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [

			'regular-animation' => [
				'label'        => 'Regular Animation',
				'usage_prop'   => array( 'aae_anim_interactions', 'filled' ),
				'description'  => 'Preset-based entrance/exit animations applied to every atomic widget.',
				'icon'         => 'wcf-icon-Animation',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['animation', 'entrance', 'fade', 'slide', 'regular animation'],
				'category'     => 'animation',
				'order'        => 1,
			],

			'parallax' => [
				'label'        => 'Parallax',
				'usage_prop'   => array( 'aae_plx_enable', 'boolean' ),
				'description'  => 'ScrollSmoother-powered parallax depth effect on scroll.',
				'icon'         => 'wcf-icon-Animation-Builder',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['parallax', 'scroll', 'depth', 'scroll smoother'],
				'category'     => 'animation',
				'order'        => 2,
			],

			'text-animation' => [
				'label'        => 'Text Animation',
				'usage_prop'   => array( 'aae_text_interactions', 'filled' ),
				'description'  => 'Character/word/line reveal animations for heading-class widgets.',
				'icon'         => 'wcf-icon-Text-Animation',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['text animation', 'char animation', 'word reveal', 'text reveal'],
				'category'     => 'animation',
				'order'        => 3,
			],

			'image-animation' => [
				'label'        => 'Image Animation',
				'usage_prop'   => array( 'aae_img_interactions', 'filled' ),
				'description'  => 'Reveal/scale/stretch animations for image and SVG widgets.',
				'icon'         => 'wcf-icon-Image-Animation',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['image animation', 'image reveal', 'scale', 'stretch'],
				'category'     => 'animation',
				'order'        => 4,
			],

			'image-hover' => [
				'label'        => 'Image Hover',
				'usage_prop'   => array( 'aae_ih_enable', 'boolean' ),
				'description'  => 'Cursor-following floating image overlay on any atomic widget.',
				'icon'         => 'wcf-icon-Image-Hover-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['image hover', 'cursor follow', 'floating image', 'hover effect'],
				'category'     => 'interaction',
				'order'        => 5,
			],

			'sticky' => [
				'label'        => 'Sticky',
				'usage_prop'   => array( 'aae_sticky_enable', 'boolean' ),
				'description'  => 'Pin elements to viewport on scroll with configurable offsets.',
				'icon'         => 'wcf-icon-Pin-Elements',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['sticky', 'pin', 'fixed', 'scroll pin'],
				'category'     => 'interaction',
				'order'        => 6,
			],

			'horizontal-scroll-anim' => [
				'label'        => 'Horizontal Scroll Animation',
				'usage_prop'   => array( 'aae_horizontal_enable', 'boolean' ),
				'description'  => 'GSAP-powered horizontal scroll-triggered animation.',
				'icon'         => 'wcf-icon-Horizontal',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['horizontal scroll', 'scroll animation', 'sideways', 'horizontal'],
				'category'     => 'animation',
				'order'        => 7,
			],

			'cursor-hover-effect' => [
				'label'        => 'Cursor Hover Effect',
				'usage_prop'   => array( 'aae_cursor_hover_enable', 'boolean' ),
				'description'  => 'Cursor-following floating element effect on any atomic widget.',
				'icon'         => 'wcf-icon-Cursor-Hover-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['cursor', 'hover', 'cursor effect', 'mouse hover'],
				'category'     => 'interaction',
				'order'        => 8,
			],

			'mouse-move-effect' => [
				'label'        => 'Mouse Move Effect',
				'usage_prop'   => array( 'aae_mouse_move_effect_enable', 'boolean' ),
				'description'  => 'Element moves/rotates based on mouse position.',
				'icon'         => 'wcf-icon-Cursor-Move-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['mouse move', 'mouse parallax', 'tilt on move', 'mouse effect'],
				'category'     => 'interaction',
				'order'        => 9,
			],

			'advance-tooltip' => [
				'label'        => 'Advance Tooltip',
				'usage_prop'   => array( 'aae_advance_tooltip_enable', 'boolean' ),
				'description'  => 'Rich content tooltips on hover for any atomic widget.',
				'icon'         => 'wcf-icon-Advanced-Tooltip',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['tooltip', 'hover tooltip', 'info popup', 'advance tooltip'],
				'category'     => 'interaction',
				'order'        => 10,
			],

			'tilt' => [
				'label'        => 'Tilt',
				'usage_prop'   => array( 'aae_tilt_enable', 'boolean' ),
				'description'  => '3D tilt perspective effect on hover.',
				'icon'         => 'wcf-icon-Tilt-Effect',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['tilt', '3d tilt', 'perspective', 'hover tilt'],
				'category'     => 'interaction',
				'order'        => 11,
			],

			'scroll-to' => [
				'label'        => 'Scroll To',
				'usage_prop'   => array( 'aae_scroll_to_enable', 'boolean' ),
				'description'  => 'Smooth scroll-to-target anchor navigation.',
				'icon'         => 'wcf-icon-Horizontal',
				'is_pro'       => true,
				'badge_only'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['scroll to', 'anchor', 'smooth scroll', 'scroll navigation'],
				'category'     => 'interaction',
				'order'        => 12,
			],

			// Implemented in the Pro plugin (inc/extensions/dynamic-tags.php +
			// inc/core/dynamic-tags/). It used to be reachable ONLY through the v3
			// extension list, so a site working purely in v4 had no way to switch it
			// on and dynamic tags silently did nothing on atomic widgets. Pro loads
			// it from this toggle as well — see Pro's Plugin::register_extensions().
			'dynamic-tags' => [
				'label'        => 'Dynamic Tags',
				'usage_prop'   => false,
				'description'  => 'Bind atomic widget content to dynamic sources: post, author, site, archive, comments and ACF fields.',
				// Capitalised on purpose: this is the glyph name that actually
				// exists in the icon font. The lower-case names the other atomic
				// extensions use (wcf-icon-parallax, wcf-icon-custom-css, …) match
				// nothing, which is why they render as empty circles.
				'icon'         => 'wcf-icon-Dynamic-Tags',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['dynamic tags', 'dynamic', 'acf', 'custom field', 'post data'],
				'category'     => 'utility',
				'order'        => 13,
			],

			'mask' => [
				'label'        => 'Mask',
				'usage_prop'   => array( 'mask-image', 'present' ),
				'description'  => 'Clip a Flexbox, Div Block or Grid to a shape — 20 built-in shapes or your own SVG, with responsive and hover variants.',
				'icon'         => 'wcf-icon-Custom-CSS',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['mask', 'shape', 'clip', 'svg', 'container'],
				'category'     => 'utility',
				'order'        => 16,
			],

			'background-video' => [
				'label'        => 'Background Video',
				'usage_prop'   => array( 'aae_bgv_enable', 'boolean' ),
				'description'  => 'Play a looping video behind a Flexbox, Div Block or Grid — the option the atomic Background control is missing.',
				'icon'         => 'wcf-icon-Custom-CSS',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['background video', 'video', 'background', 'container'],
				'category'     => 'utility',
				'order'        => 15,
			],

			'custom-css' => [
				'label'        => 'Custom CSS',
				'usage_prop'   => array( 'aae_custom_css_enable', 'boolean' ),
				'description'  => 'Add custom CSS rules per-element in the atomic editor.',
				'icon'         => 'wcf-icon-Custom-CSS',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['custom css', 'css', 'style', 'custom style'],
				'category'     => 'utility',
				'order'        => 14,
			],

			'image-overlay' => [
				'label'        => 'Image Overlay',
				'usage_prop'   => array( 'aae_img_ovl_enable', 'boolean' ),
				'description'  => 'Color/gradient tint overlay on Image and SVG widgets.',
				'icon'         => 'wcf-icon-Image',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['image overlay', 'overlay', 'tint', 'color overlay', 'gradient overlay'],
				'category'     => 'utility',
				'order'        => 16,
			],

			/*
			 * Pro AtomicV4 modules (animation-addons-for-elementor-pro/inc/AtomicV4/).
			 * They used to load unconditionally from AtomicV4\Bootstrap, so they never
			 * appeared here and could not be switched off. The registry lives in the
			 * free plugin — same constraint as widgets — so their definitions sit here
			 * and Pro gates itself on is_extension_active().
			 *
			 * `requires` lists the widget slugs an extension is useless without, and
			 * `requires_note` is the ready-to-render tooltip string for the dashboard.
			 * Both are optional; extensions that apply to any atomic element omit them.
			 */
			// Slug stays `flexbox-child-hover` — it is what Pro's AtomicV4
			// Bootstrap gates on, and a saved option is keyed by slug, so
			// renaming it would orphan every site's setting. The user-facing
			// label/keywords take the clearer "Parent Child Hover" wording;
			// keywords carry both namings so search finds it either way.
			'flexbox-child-hover' => [
				'label'        => 'Parent Child Hover',
				'usage_prop'   => array( array( 'aae_v4_fch_source', 'aae_v4_fch_target' ), 'boolean' ),
				'description'  => 'Hover a container to trigger a "Parent Hover" style state on its child elements.',
				'icon'         => 'wcf-icon-Grid-Hover-Posts',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['parent child hover', 'flexbox child hover', 'parent hover', 'hover source', 'hover target', 'child hover', 'container hover'],
				'category'     => 'interaction',
				'order'        => 15,
				'requires_note' => 'Applies to Elementor\'s Flexbox container and its children.',
			],

			'form-conditions' => [
				'label'        => 'Conditional Display',
				'usage_prop'   => array( 'aae_cond_enable', 'boolean' ),
				'description'  => 'Show or hide AAE Form fields/containers based on the value of other fields.',
				'icon'         => 'wcf-icon-Toggle-Switch',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['conditional display', 'conditional logic', 'show hide fields', 'form conditions', 'dynamic fields'],
				'category'     => 'form',
				'order'        => 16,
				'requires'     => ['aae-a-form'],
				'requires_note' => 'Requires the Form widget.',
			],

			'form-validation' => [
				'label'        => 'Validation Pro',
				'usage_prop'   => array( 'aae_regex_pattern', 'filled' ),
				'description'  => 'Regex validation rules with custom messages on form inputs and textareas.',
				// There is no `wcf-icon-Form` in the icon font — it rendered as
				// an empty circle on the dashboard card.
				'icon'         => 'wcf-icon-Content-Protection',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['validation', 'regex', 'pattern', 'form validation'],
				'category'     => 'form',
				'order'        => 17,
				'requires'     => ['aae-a-form'],
				'requires_note' => 'Requires the Form widget.',
			],

			'form-user' => [
				'label'        => 'Create User',
				'usage_prop'   => false,
				'description'  => 'Turn a form submission into a real WordPress account, with role and alias mapping.',
				// See the note on Validation Pro above — `wcf-icon-Form` does
				// not exist in the icon font.
				'icon'         => 'wcf-icon-Team',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['create user', 'registration', 'signup', 'account'],
				'category'     => 'form',
				'order'        => 18,
				'requires'     => ['aae-a-form'],
				'requires_note' => 'Requires the Form widget. Configured per form in the Actions dialog.',
			],

			'popup' => [
				'label'        => 'Popup',
				'usage_prop'   => array( 'aae_v4_popup_enabled', 'boolean' ),
				'description'  => 'Site-wide popup system for atomic elements, triggered from AAE Builder templates.',
				'icon'         => 'wcf-icon-Popup',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['popup', 'modal', 'lightbox', 'dialog'],
				'category'     => 'interaction',
				'order'        => 19,
				'requires_note' => 'Popups are built as AAE Builder templates.',
			],

			/*
			 * Template Library — the "Add AAE Template" modal in the Elementor
			 * editor (Library_Source + inc/class-template-library.php, driven
			 * by assets/js/wcf-template-library.js).
			 *
			 * There is a `template-library` entry in the V3 registry (config.php)
			 * too, and since 2026-09-21 class-plugin.php::include_files() reads
			 * BOTH keys as an OR (before that the v3 card was display-only, so a
			 * v3 site with it ON still had no library in the editor). Either
			 * toggle gates the require of
			 * inc/class-template-library.php, which in turn is what
			 * defines Library_Source and therefore satisfies the two
			 * class_exists('\Wealcoder\AnimationAddons\Library_Source') checks that register the
			 * editor script and the modal's Underscore templates.
			 *
			 * `default` is false on purpose. Unlike the Pro AtomicV4 modules
			 * above — which this flag exists to rescue, because they USED to load
			 * unconditionally — this file has never been required from anywhere,
			 * so no site has ever had the feature on. Defaulting to true would
			 * make migrate_newly_offered_extensions() silently introduce a new
			 * editor modal on every existing install rather than restore
			 * something they already had.
			 */
			'template-library' => [
				'label'        => 'Template Library',
				'usage_prop'   => false,
				'description'  => 'Ready-made AAE layouts, importable from a library modal inside the Elementor editor.',
				// Capitalised to match the glyph that actually exists in the icon
				// font (\e957) — see the Dynamic Tags note above for why the
				// lower-case spellings render as empty circles.
				'icon'         => 'wcf-icon-Template-library',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['template library', 'templates', 'layout', 'import', 'blocks', 'pages', 'library'],
				'category'     => 'utility',
				'order'        => 20,
			],

			/*
			 * Site-wide admin features that predate the atomic dashboard.
			 *
			 * These four are not element extensions — they add wp-admin screens
			 * (a font manager, a post-type builder, an icon-set uploader, a
			 * snippet editor) and their output is consumed by v3 and v4 alike:
			 * a font uploaded here shows up in the atomic Typography control,
			 * a post type built here is what an atomic Loop Grid queries.
			 *
			 * They used to be reachable ONLY from the v3 extension list, so a
			 * site working purely in v4 had no way to switch them on at all —
			 * the same defect Dynamic Tags had (see its entry above). Their
			 * slugs deliberately MATCH the v3 config.php keys so the two option
			 * arrays stay legible side by side.
			 *
			 * Loading is an OR of the two toggles — see
			 * class-plugin.php::register_extensions() and include_files(). Turning
			 * one off here does NOT stop it if the v3 switch is still on; that is
			 * the same contract Dynamic Tags and Template Library already have,
			 * and it is what keeps an existing v3 site working untouched.
			 *
			 * `default` is false on purpose: these have shipped for a long time
			 * and every site already has a deliberate answer for them stored in
			 * `aaeaddon_save_extensions`. Defaulting to true would make
			 * migrate_newly_offered_extensions() switch four features on for
			 * everybody, including the people who turned them off. The real
			 * answer is copied across once by backfill_v3_admin_extensions().
			 */
			'custom-fonts' => [
				'label'        => 'Custom Fonts',
				'usage_prop'   => false,
				'description'  => 'Upload and manage your own font families, selectable from the Elementor typography controls.',
				'icon'         => 'wcf-icon-Custom-Fonts',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['custom fonts', 'fonts', 'typography', 'webfont', 'woff', 'font upload'],
				'category'     => 'utility',
				'order'        => 21,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/custom-fonts/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/custom-fonts/',
			],

			'custom-cpt' => [
				'label'        => 'Post Type Builder',
				'usage_prop'   => false,
				'description'  => 'Create custom post types and taxonomies without code, ready to query from a Loop Grid.',
				'icon'         => 'wcf-icon-Custom-Post-Type',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['post type builder', 'custom post type', 'cpt', 'taxonomy', 'content type'],
				'category'     => 'utility',
				'order'        => 22,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/post-type-builder/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/post-type-builder/',
			],

			'custom-icon' => [
				'label'        => 'Custom Icon',
				'usage_prop'   => false,
				'description'  => 'Upload icon-font sets (IcoMoon/Fontello zips) and use them anywhere Elementor offers an icon picker.',
				'icon'         => 'wcf-icon-Custom-Icons',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['custom icon', 'icons', 'icon font', 'icomoon', 'fontello', 'svg icons'],
				'category'     => 'utility',
				'order'        => 23,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/custom-icon/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/custom-icon/',
			],

			'code-snippet' => [
				'label'        => 'Code Snippet',
				'usage_prop'   => false,
				'description'  => 'Add PHP, CSS, JS or HTML snippets from wp-admin, with per-snippet placement and activation.',
				// There is no `wcf-icon-Code-Snippet` glyph in the icon font —
				// this is the same one the v3 card uses. See the Dynamic Tags
				// note above for why a guessed name renders as an empty circle.
				'icon'         => 'wcf-icon-Content-Protection',
				'is_pro'       => false,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => false,
				'keywords'     => ['code snippet', 'snippets', 'php', 'custom code', 'functions'],
				'category'     => 'utility',
				'order'        => 24,
				'demo_url'     => 'https://animation-addons.com/docs/general-extensions/code-snippet/',
				'doc_url'      => 'https://animation-addons.com/docs/general-extensions/code-snippet/',
			],

			/*
			 * The two PRO admin modules (Custom Fields, AI & Connections). Same
			 * shape as the four above — a card on BOTH dashboards, loaded on an
			 * OR of the two toggles — but the gate lives in the Pro plugin
			 * (Fields\Bootstrap::enabled(), Platform\Bootstrap::enabled()), the
			 * only place the modules exist. `default` is TRUE here, unlike the
			 * four above: these have no v3 history to copy and shipped switched
			 * on, so migrate_newly_offered_extensions() keeps them on across the
			 * update that introduces the cards — otherwise every site loses its
			 * Custom Fields menu the day it updates.
			 */
			'custom-fields' => [
				'label'        => 'Custom Fields',
				'usage_prop'   => false,
				'description'  => 'Build field groups for any post type, taxonomy, user or options page — dynamic tags, Loop Filter sources and selling entries through WooCommerce included.',
				'icon'         => 'wcf-icon-Dynamic-Tags',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['custom fields', 'field group', 'meta box', 'acf', 'repeater', 'options page', 'sell', 'woocommerce'],
				'category'     => 'utility',
				'order'        => 25,
				'demo_url'     => '',
				'doc_url'      => '',
			],

			'ai-connections' => [
				'label'        => 'AI & Connections',
				'usage_prop'   => false,
				'description'  => 'Let claude.ai, ChatGPT and Claude Desktop work on this site over MCP with OAuth sign-in, and use your own AI key inside the builders.',
				'icon'         => 'wcf-icon-Animation-Builder',
				'is_pro'       => true,
				'is_extension' => true,
				'is_upcoming'  => false,
				'default'      => true,
				'keywords'     => ['ai', 'mcp', 'claude', 'chatgpt', 'oauth', 'connections', 'assistant', 'api key'],
				'category'     => 'utility',
				'order'        => 26,
				'demo_url'     => '',
				'doc_url'      => '',
			],
		];
