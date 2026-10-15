<?php
/**
 * Main Dynamic Tags Module Manager
 *
 * Orchestrates dynamic tag registration, security, and integration
 * with Elementor's dynamic tags system.
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Dynamic Tags Module.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\Core
 */
class Dynamic_Tags_Module {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * True while Elementor is building its Atomic (v4) dynamic-tags config.
	 *
	 * @var bool
	 */
	private static $atomic_config_pass = false;

	/**
	 * Security filter component
	 *
	 * @var \Wealcoder\AnimationAddons\DynamicTags\Tags\Components\Author_Meta_Security_Filter
	 */
	private $author_meta_filter;

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function load_dependencies() {
		// Load traits first (required by base classes).
		require_once AAEADDON_PATH . 'inc/dynamic-tags/trait-postid.php';
		require_once AAEADDON_PATH . 'inc/dynamic-tags/base/atomic-compatibility-trait.php';

		// Load base classes.
		require_once AAEADDON_PATH . 'inc/dynamic-tags/base/tag-trait-enhanced.php';
		require_once AAEADDON_PATH . 'inc/dynamic-tags/base/tag-base.php';
		require_once AAEADDON_PATH . 'inc/dynamic-tags/base/data-tag-base.php';

		// Load components.
		require_once AAEADDON_PATH . 'inc/dynamic-tags/components/author-meta-security-filter.php';
		require_once AAEADDON_PATH . 'inc/dynamic-tags/components/query-manager.php';
		require_once AAEADDON_PATH . 'inc/dynamic-tags/components/ajax-handler.php';

		// Load custom controls.
		require_once AAEADDON_PATH . 'inc/dynamic-tags/controls/select2-control.php';

		// Initialize security filter.
		$this->author_meta_filter = new \Wealcoder\AnimationAddons\DynamicTags\Tags\Components\Author_Meta_Security_Filter();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function init_hooks() {
		// Register groups early (priority 5) to ensure they exist before tags try to use them.
		// Tags register at priority 25 (after inc/extensions/dynamic-tags.php registers groups at priority 20).
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tag_groups' ), 5 );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_core_dynamic_tags' ), 25 );

		// Register custom controls.
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );

		// Security filter.
		add_filter( 'elementor/document/save/data', array( $this->author_meta_filter, 'filter' ), 10, 2 );

		// Mark out the window in which Elementor builds its Atomic (v4) tag
		// config, so `Atomic_Compatibility_Trait` knows which panel is asking.
		//
		// The v3 config (`$client_env['dynamicTags']`) is assembled BEFORE this
		// filter runs — see Elementor's
		// core/editor/loader/common/editor-common-scripts-settings.php, where the
		// key is built inside the array literal and the filter is applied further
		// down. Elementor's atomic module then calls `get_tags_config()` again
		// from inside the chain. Widening the window across the whole chain keeps
		// this independent of anyone's filter priority.
		add_filter( 'elementor/editor/localize_settings', array( $this, 'open_atomic_config_pass' ), 1 );
		add_filter( 'elementor/editor/localize_settings', array( $this, 'close_atomic_config_pass' ), PHP_INT_MAX );
	}

	/**
	 * Open the Atomic config window.
	 *
	 * @param array $settings Editor settings, passed through untouched.
	 * @return array
	 */
	public function open_atomic_config_pass( $settings ) {
		self::$atomic_config_pass = true;

		return $settings;
	}

	/**
	 * Close the Atomic config window.
	 *
	 * @param array $settings Editor settings, passed through untouched.
	 * @return array
	 */
	public function close_atomic_config_pass( $settings ) {
		self::$atomic_config_pass = false;

		return $settings;
	}

	/**
	 * Is Elementor currently building its Atomic (v4) dynamic-tags config?
	 *
	 * The `localize_settings` window alone is not enough from Elementor 4.3:
	 * `Dynamic_Prop_Types_Mapping` now asks the Atomic registry for tag names
	 * while props schemas are built, which happens inside `initial_document`,
	 * long before `localize_settings` runs. The registry memoises that first
	 * answer, so it was built outside the window, got the v3 `text` controls,
	 * and the Internal URL pickers turned into plain text boxes with no search
	 * results. That caller always goes through `Dynamic_Tags_Editor_Config`,
	 * whenever it runs, so the stack decides as well as the window.
	 *
	 * @return bool
	 */
	public static function is_atomic_config_pass() {
		if ( self::$atomic_config_pass ) {
			return true;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- caller detection, not debugging.
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 ) as $frame ) {
			if ( isset( $frame['class'] ) && 'Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Editor_Config' === $frame['class'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register custom controls.
	 *
	 * @param \Elementor\Controls_Manager $controls_manager Controls manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_controls( $controls_manager ) {
		$controls_manager->register( new \Wealcoder\AnimationAddons\DynamicTags\Controls\Select2_Control() );
	}

	/**
	 * Register dynamic tag groups.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_dynamic_tag_groups( $dynamic_tags_manager ) {
		// Verify we have a valid manager.
		if ( ! $dynamic_tags_manager ) {
			return;
		}

		// Register organized groups for better UX.
		// Groups 'aae' and 'aae-posts' are registered by inc/extensions/dynamic-tags.php.

		$groups = array(
			'aae-author'   => array(
				'title' => esc_html__( 'AAE Author', 'animation-addons-for-elementor' ),
			),
			'aae-site'     => array(
				'title' => esc_html__( 'AAE Site', 'animation-addons-for-elementor' ),
			),
			'aae-taxonomy' => array(
				'title' => esc_html__( 'AAE Taxonomy', 'animation-addons-for-elementor' ),
			),
			'aae-archive'  => array(
				'title' => esc_html__( 'AAE Archive', 'animation-addons-for-elementor' ),
			),
			'aae-comments' => array(
				'title' => esc_html__( 'AAE Comments', 'animation-addons-for-elementor' ),
			),
			'aae-contact'  => array(
				'title' => esc_html__( 'AAE Contact and Social', 'animation-addons-for-elementor' ),
			),
		);

		foreach ( $groups as $group_id => $group_args ) {
			$dynamic_tags_manager->register_group( $group_id, $group_args );
		}
	}

	/**
	 * Register core WordPress dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_core_dynamic_tags( $dynamic_tags_manager ) {
		// Verify we have a valid manager.
		if ( ! $dynamic_tags_manager ) {
			return;
		}

		// Load and register Post tags.
		$this->register_post_tags( $dynamic_tags_manager );

		// Load and register Author tags.
		$this->register_author_tags( $dynamic_tags_manager );

		// Load and register Site tags.
		$this->register_site_tags( $dynamic_tags_manager );

		// Load and register Archive tags.
		$this->register_archive_tags( $dynamic_tags_manager );

		// Load and register Taxonomy tags.
		$this->register_taxonomy_tags( $dynamic_tags_manager );

		// Load and register Comments tags.
		$this->register_comments_tags( $dynamic_tags_manager );

		// Load and register Contact & Social tags.
		$this->register_contact_tags( $dynamic_tags_manager );
	}

	/**
	 * Register Post dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_post_tags( $dynamic_tags_manager ) {
		$post_tags = array(
			'Post_Title_Tag'          => 'post/post-title-tag.php',
			'Post_Content_Tag'        => 'post/post-content-tag.php',
			'Post_Excerpt_Tag'        => 'post/post-excerpt-tag.php',
			'Post_URL_Tag'            => 'post/post-url-tag.php',
			'Post_Date_Tag'           => 'post/post-date-tag.php',
			'Post_Modified_Date_Tag'  => 'post/post-modified-date-tag.php',
			'Post_Author_Name_Tag'    => 'post/post-author-name-tag.php',
			'Post_Featured_Image_Tag' => 'post/post-featured-image-tag.php',
			'Post_ID_Tag'             => 'post/post-id-tag.php',
			'Post_Slug_Tag'           => 'post/post-slug-tag.php',
			'Post_Type_Tag'           => 'post/post-type-tag.php',
			'Post_Terms_Tag'          => 'post/post-terms-tag.php',
		);

		foreach ( $post_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Post\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$tag_instance = new $class_name();
						$dynamic_tags_manager->register( $tag_instance );
					} catch ( \Exception $e ) {
						// Log error if registration fails.
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'AAE Dynamic Tag: Class not found - ' . $class_name ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}
	}

	/**
	 * Register Author dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_author_tags( $dynamic_tags_manager ) {
		$author_tags = array(
			'Author_Name_Tag'         => 'author/author-name-tag.php',
			'Author_Display_Name_Tag' => 'author/author-display-name-tag.php',
			'Author_First_Name_Tag'   => 'author/author-first-name-tag.php',
			'Author_Last_Name_Tag'    => 'author/author-last-name-tag.php',
			'Author_Email_Tag'        => 'author/author-email-tag.php',
			'Author_URL_Tag'          => 'author/author-url-tag.php',
			'Author_Website_Tag'      => 'author/author-website-tag.php',
			'Author_Bio_Tag'          => 'author/author-bio-tag.php',
			'Author_Info_Tag'         => 'author/author-info-tag.php',
			'Author_Avatar_Tag'       => 'author/author-avatar-tag.php',
		);

		foreach ( $author_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Author\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$dynamic_tags_manager->register( new $class_name() );
					} catch ( \Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			}
		}
	}

	/**
	 * Register Site dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_site_tags( $dynamic_tags_manager ) {
		$site_tags = array(
			'Site_Title_Tag'       => 'site/site-title-tag.php',
			'Site_Tagline_Tag'     => 'site/site-tagline-tag.php',
			'Site_Description_Tag' => 'site/site-description-tag.php',
			'Site_URL_Tag'         => 'site/site-url-tag.php',
			'Site_Admin_Email_Tag' => 'site/site-admin-email-tag.php',
			'Site_Language_Tag'    => 'site/site-language-tag.php',
			'Site_Charset_Tag'     => 'site/site-charset-tag.php',
			'Site_Logo_Tag'        => 'site/site-logo-tag.php',
			'Internal_URL_Tag'     => 'site/internal-url-tag.php',
			'Lightbox_Tag'         => 'site/lightbox-tag.php',
			'Shortcode_Tag'        => 'site/shortcode-tag.php',
		);

		foreach ( $site_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Site\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$dynamic_tags_manager->register( new $class_name() );
					} catch ( \Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			}
		}
	}

	/**
	 * Register Archive dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_archive_tags( $dynamic_tags_manager ) {
		$archive_tags = array(
			'Archive_Title_Tag'       => 'archive/archive-title-tag.php',
			'Archive_Description_Tag' => 'archive/archive-description-tag.php',
		);

		foreach ( $archive_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Archive\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$dynamic_tags_manager->register( new $class_name() );
					} catch ( \Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			}
		}
	}

	/**
	 * Register Taxonomy dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_taxonomy_tags( $dynamic_tags_manager ) {
		$taxonomy_tags = array(
			'Current_Term_Name_Tag'        => 'taxonomy/current-term-name-tag.php',
			'Current_Term_URL_Tag'         => 'taxonomy/current-term-url-tag.php',
			'Current_Term_Description_Tag' => 'taxonomy/current-term-description-tag.php',
			'Current_Term_Count_Tag'       => 'taxonomy/current-term-count-tag.php',
			'Current_Term_Image_Tag'       => 'taxonomy/current-term-image-tag.php',
		);

		foreach ( $taxonomy_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Taxonomy\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$dynamic_tags_manager->register( new $class_name() );
					} catch ( \Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			}
		}
	}

	/**
	 * Register Comments dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_comments_tags( $dynamic_tags_manager ) {
		$comments_tags = array(
			'Comments_Number_Tag'     => 'comments/comments-number-tag.php',
			'Post_Comments_Count_Tag' => 'comments/post-comments-count-tag.php',
			'Comments_Link_Tag'       => 'comments/comments-link-tag.php',
		);

		foreach ( $comments_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Comments\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$dynamic_tags_manager->register( new $class_name() );
					} catch ( \Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			}
		}
	}

	/**
	 * Register Contact & Social dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	private function register_contact_tags( $dynamic_tags_manager ) {
		$contact_tags = array(
			'Contact_Email_Tag'    => 'contact/contact-email-tag.php',
			'Contact_Phone_Tag'    => 'contact/contact-phone-tag.php',
			'Contact_WhatsApp_Tag' => 'contact/contact-whatsapp-tag.php',
			'Contact_Skype_Tag'    => 'contact/contact-skype-tag.php',
			'Contact_Telegram_Tag' => 'contact/contact-telegram-tag.php',
			'Social_Facebook_Tag'  => 'contact/social-facebook-tag.php',
			'Social_Twitter_Tag'   => 'contact/social-twitter-tag.php',
			'Social_Instagram_Tag' => 'contact/social-instagram-tag.php',
			'Social_LinkedIn_Tag'  => 'contact/social-linkedin-tag.php',
			'Social_YouTube_Tag'   => 'contact/social-youtube-tag.php',
		);

		foreach ( $contact_tags as $class => $file ) {
			$file_path = AAEADDON_PATH . 'inc/dynamic-tags/' . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				$class_name = '\\Wealcoder\\AnimationAddons\\DynamicTags\\Tags\\Contact\\' . $class;
				if ( class_exists( $class_name ) ) {
					try {
						$dynamic_tags_manager->register( new $class_name() );
					} catch ( \Exception $e ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'AAE Dynamic Tag Registration Error (' . $class . '): ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			}
		}
	}

	/**
	 * Get all registered tag names.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function get_registered_tags() {
		return array(
			// Post tags (12).
			'aae-post-title',
			'aae-post-content',
			'aae-post-excerpt',
			'aae-post-url',
			'aae-post-date',
			'aae-post-modified-date',
			'aae-post-author-name',
			'aae-post-featured-image',
			'aae-post-id',
			'aae-post-slug',
			'aae-post-type',
			'aae-post-terms',
			// Author tags (10).
			'aae-author-name',
			'aae-author-display-name',
			'aae-author-first-name',
			'aae-author-last-name',
			'aae-author-email',
			'aae-author-url',
			'aae-author-website',
			'aae-author-bio',
			'aae-author-info',
			'aae-author-avatar',
			// Site tags (11).
			'aae-site-title',
			'aae-site-tagline',
			'aae-site-description',
			'aae-site-url',
			'aae-site-admin-email',
			'aae-site-language',
			'aae-site-charset',
			'aae-site-logo',
			'aae-internal-url',
			'aae-lightbox',
			'aae-shortcode',
			// Archive tags (2).
			'aae-archive-title',
			'aae-archive-description',
			// Taxonomy tags (5).
			'aae-current-term-name',
			'aae-current-term-url',
			'aae-current-term-description',
			'aae-current-term-count',
			'aae-current-term-image',
			// Comments tags (3).
			'aae-comments-number',
			'aae-post-comments-count',
			'aae-comments-link',
			// Contact & Social tags (10).
			'aae-contact-email',
			'aae-contact-phone',
			'aae-contact-whatsapp',
			'aae-contact-skype',
			'aae-contact-telegram',
			'aae-social-facebook',
			'aae-social-twitter',
			'aae-social-instagram',
			'aae-social-linkedin',
			'aae-social-youtube',
		);
	}
}

// Initialize the module.
Dynamic_Tags_Module::instance();
