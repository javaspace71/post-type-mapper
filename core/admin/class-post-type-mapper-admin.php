<?php
/**
 * The admin settings side to Post Type Mapper
 *
 * @since 1.0.0
 *
 * @package Post_Type_Mapper
 * @subpackage Post_Type_Mapper/core/admin
 */

defined( 'ABSPATH' ) || die();

class Post_Type_Mapper_Admin {

	/**
	 * Post_Type_Mapper_Admin constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

	}

	/**
	 * Create the Admin Page to hold our Settings
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function create_admin_page() {

		$submenu_page = add_submenu_page(
			'options-general.php',
			_x( 'Post Type Mapper', 'Admin Page Title', 'post-type-mapper' ),
			_x( 'Post Type Mapper', 'Admin Menu Title', 'post-type-mapper' ),
			'manage_options',
			'post-type-mapper',
			array( $this, 'admin_page_content' )
		);

	}

	/**
	 * Create the Content/Form for our Admin Page
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_page_content() { ?>

		<div class="wrap post-type-mapper">
			
			<h1><?php echo get_admin_page_title(); ?></h1>
			
			<input type="button" class="button button-primary execute" value="<?php echo _x( 'Map Post Types', 'Map Post Types Button', 'post-type-mapper' ); ?>" data-post_types="<?php echo implode( ',', get_post_types() ); ?>" />
			
			<div class="results hidden">
				
				<pre>"<?php echo _x( 'Post Type', 'Post Type CSV Label', 'post-type-mapper' ) ;?>","<?php echo _x( 'Meta Key', 'Meta Key CSV Label', 'post-type-mapper' ) ;?>","<?php echo _x( 'Taxonomies', 'Taxonomies CSV Label', 'post-type-mapper' ) ;?>"<?php echo "\r\n"; ?></pre>
				
				<input type="button" class="button button-primary" value="<?php echo _x( 'Download as CSV', 'Download Button', 'post-type-mapper' ); ?>" />
				
			</div>
			
		</div>

		<?php
		
	}
	
	/**
	 * Enqueue our Styles/Scripts on only our Admin Page
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_enqueue_scripts() {
		
		global $current_screen;
		
		if ( $current_screen->base == 'settings_page_post-type-mapper' ) {
			
			wp_enqueue_script( 'post-type-mapper-admin' );
			
		}
		
	}
	
}