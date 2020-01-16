<?php
/**
 * Provides helper functions.
 *
 * @since	  {{VERSION}}
 *
 * @package	AccessForAll_Inject_Last_Modified_Date
 * @subpackage AccessForAll_Inject_Last_Modified_Date/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		{{VERSION}}
 *
 * @return		AccessForAll_Inject_Last_Modified_Date
 */
function ACCESSFORALLINJECTLASTMODIFIEDDATE() {
	return AccessForAll_Inject_Last_Modified_Date::instance();
}