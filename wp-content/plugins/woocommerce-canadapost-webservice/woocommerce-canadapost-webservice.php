<?php
/*
Plugin Name: WooCommerce Canada Post Webservice Method
Plugin URI: http://truemedia.ca/plugins/cpwebservice
Description: Extends WooCommerce with Shipping Rates and Tracking from Canada Post via Web Services
Version: 1.3.7
Author: Jamez Picard support@truemedia.ca
Author URI: http://truemedia.ca/

Copyright (c) 2013-2014 Jamez Picard

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED 
TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF 
CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
IN THE SOFTWARE.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

// Plugin Path
define('CPWEBSERVICE_PLUGIN_PATH', dirname(__FILE__));
	
// Shipping Method Init Action
add_action('woocommerce_shipping_init', 'woocommerce_cpwebservice_shipping_init', 0);

//Shipping Method Init Function
function woocommerce_cpwebservice_shipping_init() {
	if (class_exists('WC_Shipping_Method') && !class_exists('woocommerce_cpwebservice')) {
		
		// Main Class File
		require_once(CPWEBSERVICE_PLUGIN_PATH . '/models/woocommerce_cpwebservice.php');
	
		// Add Class to woocommerce_shipping_methods filter
		function add_cpwebservice_method( $methods ) {
			$methods[] = 'woocommerce_cpwebservice'; return $methods;
		}
		add_filter('woocommerce_shipping_methods', 'add_cpwebservice_method' );
	}

}
// Ajax Validate Action
add_action('wp_ajax_cpwebservice_validate_api_credentials', 'woocommerce_cpwebservice_validate');
function woocommerce_cpwebservice_validate() {
	// Load up woocommerce shipping stack.
	do_action('woocommerce_shipping_init');
	$shipping = new woocommerce_cpwebservice();
	$shipping->validate_api_credentials();
}

// Ajax Rates Log Display
add_action('wp_ajax_cpwebservice_rates_log_display', 'cpwebservice_rates_log_display');
function cpwebservice_rates_log_display() {
	// Load up woocommerce shipping stack. 
	do_action('woocommerce_shipping_init');
	$shipping = new woocommerce_cpwebservice();
	$shipping->rates_log_display();
}

// Tracking Details, Init, Include Class.
if (!class_exists('woocommerce_cpwebservice_tracking')) {
	// Load Class
	require_once(CPWEBSERVICE_PLUGIN_PATH . '/models/woocommerce_cpwebservice_tracking.php');
	
}

// Wire up tracking
add_action( 'admin_init', 'cpwebservice_load_tracking'); // Admin: Order Management
add_action( 'woocommerce_order_items_table', 'cpwebservice_load_tracking'); // Customer View Order page.. outside of admin_init.
//add_action( 'woocommerce_email_before_order_table', 'cpwebservice_load_tracking'); // Customer Completion Email.// already wired up with admin_init.
function cpwebservice_load_tracking() {
	$cp = new woocommerce_cpwebservice_tracking();
}


// Wire up plugins settings.
add_action( 'admin_init', 'cpwebservice_load_pluginsettings');
function cpwebservice_load_pluginsettings() {
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_$plugin", 'cpwebservice_settings_link' );
}
// Add settings link on plugin page
function cpwebservice_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=woocommerce_settings&tab=shipping&section=woocommerce_cpwebservice">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

/** Activation hook - wireup schedule to update Tracking. */
register_activation_hook( __FILE__, 'cpwebservice_activation' );
function cpwebservice_activation() {
	wp_clear_scheduled_hook( 'cpwebservice_tracking_schedule_update' );
	wp_schedule_event( time() - 18 * 60 * 60, 'daily', 'cpwebservice_tracking_schedule_update' );
}
/** On deactivation, remove function from the scheduled action hook. */
register_deactivation_hook( __FILE__, 'cpwebservice_deactivation' );
function cpwebservice_deactivation() {
	wp_clear_scheduled_hook( 'cpwebservice_tracking_schedule_update' );
}

// Action hook, run update on tracked orders.
add_action('cpwebservice_tracking_schedule_update',  'cpwebservice_schedule_update' );
function cpwebservice_schedule_update() {
	$cp = new woocommerce_cpwebservice_tracking();
	if ($cp->options->email_tracking) {
		$cp->scheduled_update_tracked_orders();
	}
}

// Hooks filter woocommerce_cart_shipping_method_full_label to allow for better formatting.
add_filter('woocommerce_cart_shipping_method_full_label', 'cpwebservice_shipping_method_label' );
function cpwebservice_shipping_method_label($label) {
	if (get_option('woocommerce_shipping_method_format') != 'select') {
		// Update Label to have a <span> around the (Delivered by)
		$label = preg_replace('/(\('.__('Delivered by', 'woocommerce-canadapost-webservice').' [0-9\-]+\))/','<span class="shipping-delivery">$1</span>',$label);
	}
	return $label;
}

/**
 * Load Localisation
 */
add_action( 'plugins_loaded', 'cpwebservice_load_localisation');
function cpwebservice_load_localisation() {
	load_plugin_textdomain( 'woocommerce-canadapost-webservice', false, dirname(plugin_basename(__FILE__)). '/languages' );
}


} // End check if WooCommerce is active
