<?php
/*
	Plugin Name: SimplyPost WooCommerce Extension
	Plugin URI: 
	Description: Obtain live shipping rates via SimplyPost Shipping API.
	Version: 1.0.0
	Author: SimplyPost
	Author URI: https://www.simplypost.asia/
	Text Domain: wc-shipping-simplypost
	WC requires at least: 1.0.0
	WC tested up to: 1.0
*/
if (!defined('WF_Simplypost_ID')){
	define("WF_Simplypost_ID", "wf_simplypost_woocommerce_shipping");
}

if (!defined('WF_SIMPLYPOST_ADV_DEBUG_MODE')){
	define("WF_SIMPLYPOST_ADV_DEBUG_MODE", "on"); // Turn 'on' to allow advanced debug mode.
}

/**
 * Plugin activation check
 */
function wf_simplypost_plugin_pre_activation_check(){
	//check if basic version is there
	if ( is_plugin_active('simplypost-woocommerce-shipping/simplypost-woocommerce-shipping.php') ){
        deactivate_plugins( basename( __FILE__ ) );
		wp_die(__("Is everything fine? You already have the Premium version installed in your website. For any issues, kindly raise a ticket via <a target='_blank' href='//support.pluginhive.com/'>support.pluginhive.com</a>","wf-shipping-simplypost"), "", array('back_link' => 1 ));
	}
}
register_activation_hook( __FILE__, 'wf_simplypost_plugin_pre_activation_check' );

/**
 * Check if WooCommerce is active
 */
if (in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {	

	
	if (!function_exists('wf_get_settings_url')){
		function wf_get_settings_url(){
			return version_compare(WC()->version, '0.1', '>=') ? "wc-settings" : "woocommerce_settings";
		}
	}
	
	if (!function_exists('wf_plugin_override')){
		add_action( 'plugins_loaded', 'wf_plugin_override' );
		function wf_plugin_override() {
			if (!function_exists('WC')){
				function WC(){
					return $GLOBALS['woocommerce'];
				}
			}
		}
	}
	if (!function_exists('wf_get_shipping_countries')){
		function wf_get_shipping_countries(){
			$woocommerce = WC();
			$shipping_countries = method_exists($woocommerce->countries, 'get_shipping_countries')
					? $woocommerce->countries->get_shipping_countries()
					: $woocommerce->countries->countries;
			return $shipping_countries;
		}
	}
	if(!class_exists('wf_simplypost_wooCommerce_shipping_setup')){
		class wf_simplypost_wooCommerce_shipping_setup {
			
			public function __construct() {
				
				// $this->wf_init();
				add_action( 'init', array( $this, 'init' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
				add_action( 'woocommerce_shipping_init', array( $this, 'wf_simplypost_wooCommerce_shipping_init' ) );
				add_filter( 'woocommerce_shipping_methods', array( $this, 'wf_simplypost_wooCommerce_shipping_methods' ) );		
				add_filter( 'admin_enqueue_scripts', array( $this, 'wf_simplypost_scripts' ) );		
							
			}

			public function init(){
				if ( ! class_exists( 'wf_order' ) ) {
					include_once 'includes/class-wf-legacy.php';
				}		
			}
			// public function wf_init() {
			// 	// Localisation
			// 	load_plugin_textdomain( 'wf-shipping-simplypost', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );
			// }
			
			public function wf_simplypost_scripts() {
				wp_enqueue_script( 'jquery-ui-sortable' );
			}
			
			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="' . admin_url( 'admin.php?page=' . wf_get_settings_url() . '&tab=shipping&section=wf_simplypost_woocommerce_shipping_method' ) . '">' . __( 'Settings', 'wf-shipping-simplypost' ) . '</a>',
				);
				return array_merge( $plugin_links, $links );
			}			
			
			public function wf_simplypost_wooCommerce_shipping_init() {
				include_once( 'includes/class-wf-simplypost-woocommerce-shipping.php' );
			}

			
			public function wf_simplypost_wooCommerce_shipping_methods( $methods ) {
				$methods[] = 'wf_simplypost_woocommerce_shipping_method';
				return $methods;
			}	
			
			
		}
		new wf_simplypost_wooCommerce_shipping_setup();
	}
}


// Plugin updater
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/simplypost/woocommerce-plugin',
	__FILE__,
	'simplypost-woocommerce-shipping-method'
);

//Optional: If you're using a private repository, specify the access token like this:
$myUpdateChecker->setAuthentication('44604f93ae5998a2141d40c3fd1e4d84cb14c273');

//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('Master');