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

class Post_Type_Mapper_Rest {

	/**
	 * Post_Type_Mapper_Rest constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		add_action( 'rest_api_init', array( $this, 'create_routes' ) );

		add_filter( 'post_type_mapper_meta_exclude', array( $this, 'exclude_post_meta' ), 10, 2 );
		
		add_filter( 'post_type_mapper_taxonomy_exclude', array( $this, 'exclude_taxonomies' ), 10, 2 );

	}
	
	public function create_routes() {
		
		register_rest_route( 'rbm-ptm/v1', '/submit', array(
			'methods' => 'POST',
			'callback' => array( $this, 'map_post_type' ),
		) );
		
	}

	/**
	 * Construct a CSV with all Content Type Data
	 */
	public function map_post_type() {
		
		$csv = '';
		
		$post_type = $_POST['post_type'];
			
		$meta_keys = $this->get_post_type_meta_keys( $post_type );
		$taxonomies = $this->get_post_type_taxonomies( $post_type );

		// We need to combine the Data into a single Array for iterating our CSV
		$data = array();

		foreach ( $meta_keys as $index => $meta_key ) {

			$data[ $index ]['meta_key'] = $meta_key;

		}

		foreach ( $taxonomies as $index => $taxonomy ) {

			if ( ! isset( $data[ $index ] ) ) {
				$data[ $index ] = array( 'taxonomy' => $taxonomy );
			}
			else {
				$data[ $index ]['taxonomy'] = $taxonomy;
			}

		}

		foreach ( $data as $index => $row ) {

			if ( $index !== 0 ) {

				$csv .= '" ",';
				$csv .= ( isset( $row['meta_key'] ) ) ? $row['meta_key'] : '';
				$csv .= ',';
				$csv .= ( isset( $row['taxonomy'] ) ) ? $row['taxonomy'] : '';

			}
			else {

				$csv .= $post_type . ',';
				$csv .= ( isset( $row['meta_key'] ) ) ? $row['meta_key'] : '';
				$csv .= ',';
				$csv .= ( isset( $row['taxonomy'] ) ) ? $row['taxonomy'] : '';

			}

			$csv .= "\r\n";

		}
		
		return $csv;

	}

	/**
	 * Grabs all (saved) Meta Keys for a Post Type
	 * 
	 * @param		string $post_type Post Type
	 *                                
	 * @access		private
	 * @since		1.0.0
	 * @return		array  Array of Meta Keys for a Post Type
	 */
	private function get_post_type_meta_keys( $post_type ) {

		if ( empty( $post_type ) ) return array();

		// Ensure to get Product Variations as well as Products if we're checking WooCommerce Products
		$post_type = ( $post_type == 'product' and class_exists( 'WooCommerce' ) ) ? array( 'product', 'product_variation' ) : array( $post_type );

		global $wpdb;

		$table_prefix = $wpdb->prefix;

		// Grab any saved Meta from the Database for this Post Type
		$meta_keys = $wpdb->get_results( "SELECT DISTINCT {$table_prefix}postmeta.meta_key FROM {$table_prefix}postmeta, {$table_prefix}posts WHERE {$table_prefix}postmeta.post_id = {$table_prefix}posts.ID AND {$table_prefix}posts.post_type IN ('" . implode('\',\'', $post_type) . "') AND {$table_prefix}postmeta.meta_key NOT LIKE '_edit%' LIMIT 500" );


		$existing_meta_keys = array();
		if ( ! empty( $meta_keys ) ) {

			// Allow keys to be excluded via a Filter. Optionally also allow them to be excluded by Post Type
			$exclude_keys = apply_filters( 'post_type_mapper_meta_exclude', array(), $post_type );

			foreach ( $meta_keys as $meta_key ) {
				if ( strpos( $meta_key->meta_key, '_tmp' ) === false &&
					strpos( $meta_key->meta_key, '_v_' ) === false &&
					! in_array( $meta_key->meta_key, $exclude_keys ) ) {

					$existing_meta_keys[] = $meta_key->meta_key;

				}

			}

		}

		return $existing_meta_keys;

	}

	/**
	 * Grab all Taxonomies for a Post Type
	 * 
	 * @param		string $post_type Post Type
	 *                                
	 * @access		private
	 * @since		1.0.0
	 * @return		array  Array of Taxonomy Names for a Post Type
	 */
	private function get_post_type_taxonomies( $post_type ) {

		if ( empty( $post_type ) ) return array();

		// Allow Taxonomies to be excluded via a Filter. Optionally also allow them to be excluded by Post Type
		$post_taxonomies = array_diff_key(
			$this->get_taxonomies_by_object_type( array( $post_type ), 'object' ),
			array_flip( apply_filters( 'post_type_mapper_taxonomy_exclude', array(), $post_type ) ) // Exclude Array Values from Results
		);

		$existing_taxonomies = array();

		if ( ! empty( $post_taxonomies ) ) {

			foreach ( $post_taxonomies as $taxonomy ) {

				// Exclude Taxonomies prefixed with "pa_"
				// Must be to exclude unnecessary results from a Plugin?
				if ( strpos( $taxonomy->name, 'pa_' ) !== 0 ) {

					$existing_taxonomies[] = $taxonomy->name;

				}

			}

		}

		return $existing_taxonomies;

	}

	/**
	 * get_taxnomies() doesn't filter properly by object_type
	 * This also allows Taxonomies without data to be returned, which is a nice bonus
	 * 
	 * @param		string|array $object_type Post Type
	 * @param		string       $output      Defaults to "names"
	 *                                               
	 * @access		private
	 * @since		1.0.0
	 * @return		test         test
	 */
	private function get_taxonomies_by_object_type( $object_type, $output = 'names' ) {

		global $wp_taxonomies;

		is_array( $object_type ) or $object_type = array( $object_type );
		
		$field = ( $output == 'names' ) ? 'name' : false;
		
		$filtered = array();
		foreach ( $wp_taxonomies as $key => $obj ) {
			
			if ( array_intersect( $object_type, $obj->object_type ) ) {
				$filtered[ $key ] = $obj;
			}
			
		}
		
		if ( $field ) {
			$filtered = wp_list_pluck( $filtered, $field );
		}
		
		return $filtered;

	}

	public function exclude_post_meta( $exclude_keys, $post_type ) {

		if ( in_array( 'product', $post_type ) &&
			class_exists( 'WooCommerce' ) ) {
			$exclude_keys[] = '_first_variation_attributes';
			$exclude_keys[] = '_is_first_variation_created';
		}

		return $exclude_keys;

	}
	
	public function exclude_taxonomies( $exclude_taxonomies, $post_type ) {

		if ( $post_type == 'post' ) {
			$exclude_taxonomies[] = 'post_format';
		}

		return $exclude_taxonomies;

	}

}

// AJAX Hook for Creating/Updating Templates
add_action( 'wp_ajax_post_type_mapper', array( 'Post_Type_Mapper_Rest', 'map_post_types' ) );