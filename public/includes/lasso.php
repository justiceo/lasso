<?php
/**
 * AH Editor
 *
 * @package   Lasso
 * @author    Nick Haskins <nick@aesopinteractive.com>
 * @license   GPL-2.0+
 * @link      http://aesopinteractive.com
 * @copyright 2015 Aesopinteractive LLC
 */
namespace lasso_public_facing;
/**
 *
 *
 * @package Lasso
 * @author  Nick Haskins <nick@aesopinteractive.com>
 */
class lasso {

	/**
	 *
	 *
	 * @since    0.0.1
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'lasso';

	/**
	 * Instance of this class.
	 *
	 * @since    0.0.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 *
	 *
	 * @since     0.0.1
	 */
	private function __construct() {

		require_once LASSO_DIR.'/public/includes/underscore-templates.php';

		$this->require_overrides();

		require_once LASSO_DIR.'/public/includes/editor-modules.php';
		require_once LASSO_DIR.'/public/includes/helpers.php';
		require_once LASSO_DIR.'/public/includes/editor-modules--gallery.php';
		require_once LASSO_DIR.'/public/includes/components.php';
		require_once LASSO_DIR.'/public/includes/option-engine.php';
		require_once LASSO_DIR.'/public/includes/wrap-shortcodes.php';

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		
		add_action( 'wp_ajax_get_aesop_component',     array( $this, 'get_aesop_component' ) );

		//enqueue assets
		new assets();

	}

	public function require_overrides() {
		require_once LASSO_DIR.'/public/includes/editor-modules-v2.php';
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    0.0.1
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.0.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.0.1
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.0.1
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.0.1
	 *
	 * @param int     $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.0.1
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.0.1
	 */
	private static function single_activate() {

		$curr_version = get_option( 'lasso_version' );

		// update upgraded from
		if ( $curr_version ) {
			update_option( 'lasso_updated_from', $curr_version );
		}

		// update lasso version option
		update_option( 'lasso_version', LASSO_VERSION );

		// set transietn for activation welcome
		set_transient( '_lasso_welcome_redirect', true, 30 );


	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.0.1
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		$out = load_textdomain( $domain, trailingslashit( LASSO_DIR ). 'languages/' . $domain . '-' . $locale . '.mo' );
	}
	
	
	public function get_aesop_component()
	{
		
		
		$code= $_POST["code"];
		$atts = array(
		 );
		foreach ($_POST as $key => $value) {
			if ($key !="code" && $key !="action") {
			    //$shortcode = $shortcode.$key.'="'.$value.'" ';
				$atts[$key] = $value;
			}
		}
		/*if ($code == "aesop_video") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-video.php');
		    echo aesop_video_shortcode($atts)."</div>";
		}*/
		
		if ($code == "aesop_image") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-image.php');
		    echo aesop_image_shortcode($atts);
		}
		if ($code == "aesop_quote") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-quote.php');
		    echo aesop_quote_shortcode($atts);
		}
		
		if ($code == "aesop_parallax") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-parallax.php');
		    echo aesop_parallax_shortcode($atts);
		}
		
		if ($code == "aesop_character") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-character.php');
		    echo aesop_character_shortcode($atts);
		}
		
		if ($code == "aesop_collection") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-collections.php');
		    echo aesop_collection_shortcode($atts);
		}
		
		if ($code == "aesop_chapter") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-heading.php');
		    echo aesop_chapter_shortcode($atts);
		}
		
		if ($code == "aesop_gallery") {
		    require_once( ABSPATH . '/wp-content/plugins/aesop-story-engine/public/includes/components/component-gallery.php');
		    echo do_shortcode( '[aesop_gallery id="'.$atts["id"].'"]');
		}
		
		exit; 
	}
}
