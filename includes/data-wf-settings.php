<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Array of settings
 */
return array(
	'enabled'          => array(
		'title'           => __( 'Relatime Rates' ),
		'type'            => 'checkbox',
		'label'           => __( 'Enable' ),
		'default'         => 'no'
	),
	'shipping_site'		=> array(
		'title'           => __( 'Shipping Site' ),
		'type'            => 'text',
		// 'default'         => __( 'https://d3671dc8.ngrok.io/' ),
		'desc_tip'        => true
	),
    'api_key'           => array(
		'title'           => __( 'Web Services Key' ),
		'type'            => 'text',
		'default'         => '',
		'custom_attributes' => array(
			'autocomplete' => 'off'
		)
	)
);