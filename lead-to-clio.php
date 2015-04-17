<?php
/*
 * Plugin Name: Lead to Clio
 * Version: 1.0
 * Plugin URI: http://casewave.com/Info/lead-to-clio/
 * Description: Create new Clio profiles from your website leads 
 * Author: Trip Grass
 * Author URI: http://www.joinerylabs.com/about-us
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: lead_to_clio
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Trip Grass
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-lead-to-clio.php' );
require_once( 'includes/class-lead-to-clio-settings.php' );

// Load plugin libraries
if(is_admin()){
	require_once( 'includes/lib/class-lead-to-clio-admin.php' );
}
else{
	require_once( 'includes/lib/class-lead-to-clio-front.php' );
}

/**
 * Returns the main instance of lead_to_clio to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object lead_to_clio
 */
function lead_to_clio () {
	$instance = lead_to_clio::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		//$instance->settings = lead_to_clio_Settings::instance( $instance );
	}

	return $instance;
}

lead_to_clio();

?>