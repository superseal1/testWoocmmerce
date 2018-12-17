<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Array of settings
 */
return array(
	'enabled'          => array(
		'title'           => __( 'Relatime Rates Khoi' ),
		'type'            => 'checkbox',
		'label'           => __( 'Enable' ),
		'default'         => 'no'
	),
	'shipping_site'		=> array(
		'title'           => __( 'Shipping Site' ),
		'type'            => 'text',
		'default'         => __( 'https://d3671dc8.ngrok.io/' ),
		'desc_tip'        => true
	),
    'api_key'           => array(
		'title'           => __( 'Web Services Key' ),
		'type'            => 'text',
		'default'         => '',
		'custom_attributes' => array(
			'autocomplete' => 'off'
		)
	),
	'debug'      		=> array(
		'title'           => __( 'Debug Mode' ),
		'label'           => __( 'Enable debug mode' ),
		'type'            => 'checkbox',
		'default'         => 'no',
		'desc_tip'    	  => true,
		'description'     => __( 'Enable debug mode to show debugging information on the cart/checkout.' )
	)
);