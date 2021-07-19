<?php
/**
 * Plugin Name: Mundo Pagos Gateway
 * Plugin URI: https://omukiguy.com
 * Author: Techiepress
 * Author URI: https://omukiguy.com
 * Description: Mundo pagos gateway
 * Version: 0.1.0
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: payleo-payments-woo
 * 
 * Class WC_Gateway_Payleo file.
 *
 * @package WooCommerce\PayLeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'payleo_payment_init', 11 );
// add_option ('woocommerce_pay_page_id', $page_id); 
add_filter( 'woocommerce_currencies', 'techiepress_add_ugx_currencies' );
add_filter( 'woocommerce_currency_symbol', 'techiepress_add_ugx_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_payleo_payment_gateway');

function payleo_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-payleo.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/payleo-order-statuses.php';
	}
}

function add_to_woo_payleo_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_MundoPagos';
    return $gateways;
}

function techiepress_add_ugx_currencies( $currencies ) {
	$currencies['USD'] = __( 'Dollar', 'payleo-payments-woo' );
	return $currencies;
}

function techiepress_add_ugx_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'USD': 
			$currency_symbol = 'USD'; 
		break;
	}
	return $currency_symbol;
}