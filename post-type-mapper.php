<?php
/**
 * Plugin Name: Post Type Mapper
 * Plugin URI: https://github.com/realbigplugins/post-type-mapper
 * Description: Maps Out Post Types within a WP Install as a CSV containing Data about a Post Type and its Taxonomies and Meta Fields
 * Version: 0.1.0
 * Text Domain: post-type-mapper
 * Author: Eric Defore
 * Author URI: http://realbigmarketing.com/
 * Contributors: d4mation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Post_Type_Mapper' ) ) {

	/**
	 * Main Post_Type_Mapper class
	 *
	 * @since	  1.0.0
	 */
	class Post_Type_Mapper {
		
		/**
		 * @var			Post_Type_Mapper $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			Post_Type_Mapper $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;
		
		/**
		 * @var			Post_Type_Mapper $admin Holds the Admin Page
		 * @since		1.0.0
		 */
		public $admin;
		
		/**
		 * @var			Post_Type_Mapper $rest Holds the Rest API Endpoint
		 * @since		1.0.0
		 */
		public $rest;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true Post_Type_Mapper
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %s or higher to be installed!', 'Outdated Dependency Error', 'post-type-mapper' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>WordPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'Post_Type_Mapper_VER' ) ) {
				// Plugin version
				define( 'Post_Type_Mapper_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'Post_Type_Mapper_DIR' ) ) {
				// Plugin path
				define( 'Post_Type_Mapper_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'Post_Type_Mapper_URL' ) ) {
				// Plugin URL
				define( 'Post_Type_Mapper_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'Post_Type_Mapper_FILE' ) ) {
				// Plugin File
				define( 'Post_Type_Mapper_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = Post_Type_Mapper_DIR . '/languages/';
			$lang_dir = apply_filters( 'post_type_mapper_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'post-type-mapper' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'post-type-mapper', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/post-type-mapper/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/post-type-mapper/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'post-type-mapper', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/post-type-mapper/languages/ folder
				load_textdomain( 'post-type-mapper', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'post-type-mapper', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			if ( is_admin() &&
			   current_user_can( 'manage_options' ) ) {
				
				require_once Post_Type_Mapper_DIR . 'core/admin/class-post-type-mapper-admin.php';
				$this->admin = new Post_Type_Mapper_Admin();
				
			}
			
			require_once Post_Type_Mapper_DIR . 'core/rest/class-post-type-mapper-rest.php';
			$this->rest = new Post_Type_Mapper_Rest();
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'post-type-mapper',
				Post_Type_Mapper_URL . 'assets/css/style.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Post_Type_Mapper_VER
			);
			
			wp_register_script(
				'post-type-mapper',
				Post_Type_Mapper_URL . 'assets/js/script.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Post_Type_Mapper_VER,
				true
			);
			
			wp_localize_script( 
				'post-type-mapper',
				'postTypeMapper',
				apply_filters( 'post_type_mapper_localize_script', array() )
			);
			
			wp_register_style(
				'post-type-mapper-admin',
				Post_Type_Mapper_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Post_Type_Mapper_VER
			);
			
			wp_register_script(
				'post-type-mapper-admin',
				Post_Type_Mapper_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Post_Type_Mapper_VER,
				true
			);
			
			wp_localize_script( 
				'post-type-mapper-admin',
				'postTypeMapper',
				apply_filters( 'post_type_mapper_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true Post_Type_Mapper
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \Post_Type_Mapper The one true Post_Type_Mapper
 */
add_action( 'plugins_loaded', 'post_type_mapper_load' );
function post_type_mapper_load() {

	require_once __DIR__ . '/core/post-type-mapper-functions.php';
	POSTTYPEMAPPER();

}
