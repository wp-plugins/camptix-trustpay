<?php
class CampTix_Payment_Method_TrustPay extends CampTix_Payment_Method {

	/**
	 * The following variables are required for every payment method.
	 */
	public $id = 'trustpay';
	public $name = 'TrustPay';
	public $description = 'PayPal Express Checkout';
	//array( 'BGN', 'CZK', 'EUR', 'GBP', 'HRK', 'HUF', 'NOK', 'RON', 'TRY', 'USD' );
	public $supported_currencies = array( 'BGN', 'CZK', 'EUR', 'GBP', 'HRK', 'HUF', 'NOK', 'RON', 'TRY', 'USD' );
	// public $supported_features = array(
	// 	'refund-single' => true,
	// 	'refund-all' => true,
	// );

	/**
	 * We can have an array to store our options.
	 * Use $this->get_payment_options() to retrieve them.
	 */
	protected $options = array();

	/**
	 * Runs during camptix_init, loads our options and sets some actions.
	 * @see CampTix_Addon
	 */
	function camptix_init() {
		$this->supported_currencies = WI_Trustpay::get_valid_currencies();

		$this->options = array_merge( array(
			'merchant_id' => '',
			'secure_key' => '',
			'is_test' => true,
		), $this->get_payment_options() );

		add_action( 'template_redirect', array( $this, 'trustpay_ipn' ) );
	}

	/**
	 * This runs during settings field registration in CampTix for the
	 * payment methods configuration screen. If your payment method has
	 * options, this method is the place to add them to. You can use the
	 * helper function to add typical settings fields. Don't forget to
	 * validate them all in validate_options.
	 */
	function payment_settings_fields() {
		$this->add_settings_field_helper( 'merchant_id', __( 'Merchant AID', 'camptix_trustpay' ), array( $this, 'field_text' ) );
		$this->add_settings_field_helper( 'secure_key', __( 'Secret key', 'camptix_trustpay' ), array( $this, 'field_text' ) );
		$this->add_settings_field_helper( 'is_test', __( 'Test mode', 'camptix_trustpay' ), array( $this, 'field_yesno' ) );
	}

	/**
	 * Validate the above option. Runs automatically upon options save and is
	 * given an $input array. Expects an $output array of filtered payment method options.
	 */
	function validate_options( $input ) {
		$output = $this->options;

		if ( isset( $input['merchant_id'] ) )
			$output['merchant_id'] = $input['merchant_id'];

		if ( isset( $input['secure_key'] ) )
			$output['secure_key'] = $input['secure_key'];

		if ( isset( $input['is_test'] ) )
			$output['is_test'] = (bool) $input['is_test'];

		return $output;
	}

	function trustpay_ipn() {
		if ( ! isset( $_REQUEST['tix_payment_method'] ) || 'trustpay' != $_REQUEST['tix_payment_method'] )
			return;

		$payment_token = ( isset( $_REQUEST['REF'] ) ) ? trim( $_REQUEST['REF'] ) : '';
		$trustpay_token = ( isset( $_REQUEST['RES'] ) ) ? trim( $_REQUEST['RES'] ) : '';

		if ( ! $payment_token || $trustpay_token == '' )
			return;
			//die( 'empty token' );

		$order = $this->get_order( $payment_token );

		if ( ! $order )
			die( 'could not find order' );

		$response = new WI_Trustpay_Response($_GET, $this->options['secure_key']);

        $msg = "TrustPay response data:\n";
        $msg .= "AID=".(isset($response->AID) ? esc_html($response->AID) : '');
        $msg .= " &TYP=".(isset($response->TYP) ? esc_html($response->TYP) : '');
        $msg .= " &AMT=".(isset($response->AMT) ? esc_html($response->AMT) : '');
        $msg .= " &CUR=".(isset($response->CUR) ? esc_html($response->CUR) : '');
        $msg .= " &REF=".(isset($response->REF) ? esc_html($response->REF) : '');
        $msg .= " &RES=".(isset($response->RES) ? esc_html($response->RES) : '');
        $msg .= " &TID=".(isset($response->TID) ? esc_html($response->TID) : '');
        $msg .= " &OID=".(isset($response->OID) ? esc_html($response->OID) : '');
        $msg .= " &TSS=".(isset($response->TSS) ? esc_html($response->TSS) : '');
        $msg .= " &SIG=".(isset($response->SIG) ? esc_html($response->SIG) : '');
        $msg .= " &PID=".(isset($response->PID) ? esc_html($response->PID) : '');
        $msg .= "\n\nSTATUS=".$response->getStatus()."\nRESULT=".$response->getResult();

		$this->log( $msg, $order['attendee_id'] );

		$payment_data = array(
			'transaction_id' => $payment_token,
			'transaction_details' => array(),
		);

		if($response->getResult()==WI_Trustpay::RES_OK)
		{
			if($response->AMT==sprintf("%01.2F", floatval($order['total'])))
			{
				$this->log( __('Payment Completed.', 'camptix_trustcard'), $order['attendee_id'] );
				return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_COMPLETED, $payment_data );
			}
			else
			{
				$this->log( __('Order price does not match payment notification price.', 'camptix_trustpay'), $order['attendee_id'] );
				return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_REFUND_FAILED, $payment_data );
            }
		}
		else if($response->getResult()==WI_Trustpay::RES_NOTSIGNED && $response->RES!==1)
		{
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_PENDING, $payment_data );
		}
        else if($response->getResult()==WI_Trustpay::RES_NOTSIGNED && $response->RES==1)
        {
        	$this->log( __('Customer selected offline payment.', 'camptix_trustpay'), $order['attendee_id'] );
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_PENDING, $payment_data );
        }
		else if($response->getResult()==WI_Trustpay::RES_TO)
		{
			$this->log( __('Payment timeout (pending, announced or processing). Wait for signed notification or do a manual check.', 'camptix_trustpay'), $order['attendee_id'] );
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_PENDING, $payment_data );
		}
		else // RES_FAILED
		{
			$this->log( __('Payment failed or was cancelled.', 'camptix_trustpay'), $order['attendee_id'] );
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_FAILED, $payment_data );
		}			

		die();
	}

	function payment_checkout( $payment_token ) {
		/** @var CampTix_Plugin $camptix */
		global $camptix;


		if ( ! $payment_token || empty( $payment_token ) )
			return false;

		$currency = $this->camptix_options['currency'];

		if ( ! in_array( $currency, $this->supported_currencies ) )
			die( __( 'The selected currency is not supported by this payment method.', 'camptix_trustpay' ) );

		$is_test = (!empty($this->options['is_test']) && $this->options['is_test']) ? true : false;

		$lang = 'sk';
		$locale = substr(get_locale(), 0, 2);
		if(in_array($locale, WI_Trustpay::get_valid_languages()))
			$lang = $locale;

		$order = $this->get_order( $payment_token );

		$desc = remove_accents(implode(', ', wp_list_pluck( $order['items'], 'name' )));

		// build the $data array
		$data = array(
			'REF' => $payment_token,
			'AID' => $this->options['merchant_id'],
			'AMT' => sprintf("%01.2F", floatval($order['total'])),
			'CUR' => $currency,
			'LNG' => $lang,
			'DSC' => $desc
		);

        // $email = $_POST['tix_attendee_info'][1]['email'];
        // if(!empty($email))
        //     $data['EMA'] = $email;

        foreach ( array('URL', 'RURL', 'CURL', 'EURL', 'NURL') as $url ) {
	 		$data[$url] = add_query_arg( array(
				'tix_payment_method' => 'trustpay',
			), add_query_arg( 'tix_payment_method', 'trustpay', $this->get_tickets_url() ) );       	
        }

		$request = new WI_Trustpay_Request($data, $this->options['secure_key'], $is_test);

		if ( $request->getStatus()==WI_Trustpay::SIGNED ) {
			wp_redirect( $request->getRedirectUrl() );
		} else {
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_FAILED );
		}
	}	
}
