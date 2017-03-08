<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	Post_Type_Mapper
 * @subpackage Post_Type_Mapper/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		Post_Type_Mapper
 */
function POSTTYPEMAPPER() {
	return Post_Type_Mapper::instance();
}