<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wf_simplypost_woocommerce_shipping_method extends WC_Shipping_Method {
	
	private $found_rates;
	private $services;
	
	public function __construct() {
		$this->id                               = WF_Simplypost_ID;
		$this->method_title                     = __( 'Simplypost Shipping' );
		$this->method_description               = __( 'Obtains  real time shipping rates.' );
		$this->rateservice_version              = 22;
		$this->addressvalidationservice_version = 2;
		$this->init();
	}
	
	private function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled         = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
		$this->shipping_site   = $this->get_option( 'shipping_site' );
		
		$this->api_key         = $this->get_option( 'api_key' );

		$this->debug           = ( $bool = $this->get_option( 'debug' ) ) && $bool == 'yes' ? true : false;

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function debug( $message, $type = 'notice' ) {
		if ( $this->debug ) {
			wc_add_notice( $message, $type );
		}
	}

	public function admin_options() {
		// Show settings
		parent::admin_options();
	}

	public function init_form_fields() {
		$this->form_fields  = include( 'data-wf-settings.php' );
	}

	public function generate_box_packing_html() {
		ob_start();
		include( 'html-wf-box-packing.php' );
		return ob_get_clean();
	}

	public function get_simplypost_packages( $package ) {
		
		$simplypost_packages = $this->per_item_shipping( $package );
		return apply_filters( 'wf_simplypost_packages', $simplypost_packages );
	}

	private function per_item_shipping( $package ) {
		$weight_packet  = 0;
		$price_packet = 0;
		// echo('--------------ket qua packet content----------------');
		// var_dump($package['contents']);
		// Get weight of order
		foreach ( $package['contents'] as $item_id => $values ) {
    		$values['data'] = $this->wf_load_product( $values['data'] );
			if ( ! $values['data']->needs_shipping() ) {
				$this->debug( sprintf( __( 'Product # is virtual. Skipping.' ), $item_id ), 'error' );
				continue;
			}

			if ( ! $values['data']->get_weight() || ! $values['data']->get_price() ) {
				$this->debug( sprintf( __( 'Product # is missing weight or price. Aborting.' ), $item_id ), 'error' );
				return $info_package = array(
					'weight' => $weight_packet, 
					'price' => $price_packet
				);
			}
			$quantity_item = $values['quantity'];
			$weight_packet += round( wc_get_weight($values['data']->get_weight(), 'kg') * $quantity_item);
			$price_packet += round( $values['data']->get_price() * $quantity_item); 
		}

		return $info_package = array(
			'weight' => $weight_packet, 
			'price' => $price_packet
		);
	}
	public function get_country_code( $package ) {
		// Address Validation API only available for production
		if (!empty($package['destination']['country']) ) {

			return $package['destination']['country'] ;
			
		}
		return false;
	}

	public function calculate_shipping( $package = array() ) {
		// Clear rates
		$this->found_rates = array();
		// var_dump($package);
		// Debugging
		$this->debug( __( 'FEDEX debug mode is on - to hide these messages, turn debug mode off in the settings.' ) );

		// See if address is residential
		$countryCode 	=  $this->get_country_code( $package );
		// echo('--------------ket qua country code----------------');
		// echo($countryCode);
		// Get requests
		$info_packages  = (object) $this->get_simplypost_packages( $package );
		// echo('-------------ket qua info packet-----------------');
		// var_dump($info_packages);
		if ( !empty($info_packages) ) {
			$this->run_package_request( $info_packages->weight, $info_packages->price, $countryCode );
		}

		$this->add_found_rates();
	}

	public function run_package_request( $weight,  $price, $countryCode ) {
		$this->process_result( $this->get_result( $weight,  $price, $countryCode ) );
	}

	private function get_result( $weight,  $price, $countryCode ) {

		$url = $this->shipping_site.'api/gateway/v1/services?weight='.strval($weight).'&price='.strval($price).'&countryCode='.$countryCode.'&platform=woocommerce';
		// echo('-------------ket qua get url-----------------');
		// echo($url);
		
		$args = array(
			'headers' => array(
				'simplypost-api-token' => $this->api_key
			)
		);
		
		$response = wp_remote_get( $url, $args );

		wc_enqueue_js( "
			jQuery('a.debug_reveal').on('click', function(){
				jQuery(this).closest('div').find('.debug_info').slideDown();
				jQuery(this).remove();
				return false;
			});
			jQuery('pre.debug_info').hide();
		" );
		$body    =  $response['body'];
		// echo('-------------ket qua get response-----------------');
		// var_dump($body);
		
		return $body;
	}

	private function process_result( $result = '' ) {
		if ( $result ) {

			$rate_reply_details = json_decode($result);

			if(isset($rate_reply_details->error) ){
				// echo('-------------ket qua get response error go here-----------------');
				return false;
			 };
			//  echo('-------------ket qua get response no error go here-----------------');
			// Workaround for when an object is returned instead of array
			if ( is_object( $rate_reply_details ) )
				$rate_reply_details = array( $rate_reply_details );

			if ( ! is_array( $rate_reply_details ) )
				return false;

			foreach ( $rate_reply_details as $shippingMethod ) {
				$rate_name = strval( $shippingMethod->name );
				$rate_code = strval( $shippingMethod->code );
				$rate_cost = floatval( $shippingMethod->price );
				$this->prepare_rate( $rate_code, $rate_name, $rate_cost );
			}
		} else {
			return false;
		}
	}
	

	private function prepare_rate( $rate_code, $rate_name, $rate_cost ) {

		$this->found_rates[ $rate_code ] = array(
			'label'    => $rate_name,
			'cost'     => $rate_cost
		);
		// echo('-------------ket qua found rate-----------------');
		// var_dump($this->found_rates);
	}

	public function add_found_rates() {
		if ( $this->found_rates ) {

			uasort( $this->found_rates, array( $this, 'sort_rates' ) );

			foreach ( $this->found_rates as $key => $rate ) {
				$this->add_rate( $rate );
			}
		}
	}

	public function sort_rates( $a, $b ) {
		if ( $a['sort'] == $b['sort'] ) return 0;
		return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
	}

	private function wf_load_product( $product ){
		if( !$product ){
			return false;
		}
		return ( WC()->version < '0.1.1' ) ? $product : new wf_product( $product );
	}
}
