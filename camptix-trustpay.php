<?php
/*
Plugin Name: CampTix TrustPay
Plugin URI: http://platobnebrany.sk/
Description: TrustPay Payment Gateway for CampTix Event Ticketing.
Author: Webikon (Ján Bočínec)
Version: 1.0.4
Author URI: http://www.webikon.sk
License: GPLv2
*/

/*  Copyright 2015 Webikon (email: info@webikon.sk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load the TrustPay Payment Method
function camptix_trustpay_load_payment_method() {
    require_once plugin_dir_path( __FILE__ ) . 'wi-trustpay.php';
    require_once plugin_dir_path( __FILE__ ) . 'wi-trustpay-request.php';
    require_once plugin_dir_path( __FILE__ ) . 'wi-trustpay-response.php';

	if ( ! class_exists( 'CampTix_Payment_Method_TrustPay' ) )
		require_once plugin_dir_path( __FILE__ ) . 'payment-trustpay.php';
	
	/**
	 * The last stage is to register your payment method with CampTix.
	 * Since the CampTix_Payment_Method class extends from CampTix_Addon,
	 * we use the camptix_register_addon function to register it.
	 */
	camptix_register_addon( 'CampTix_Payment_Method_TrustPay' );
}
add_action( 'camptix_load_addons', 'camptix_trustpay_load_payment_method' );