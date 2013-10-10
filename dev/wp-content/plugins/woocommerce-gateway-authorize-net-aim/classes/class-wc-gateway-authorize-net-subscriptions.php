<?php

/**
 * WC_Authorize_Net_Subscription
 *
 * @extends WC_Authorize_Net
 */
class WC_Authorize_Net_Subscriptions extends WC_Authorize_Net {


	function __construct() { 
	
		parent::__construct();

		$this->gatewayurl_subs_prod	= "https://api.authorize.net/xml/v1/request.api";
		$this->gatewayurl_subs_test	= "https://apitest.authorize.net/xml/v1/request.api";

		add_action( 'cancelled_subscription_' . $this->id, array(&$this, 'cancelled_subscription_authorize'), 10, 2 );		
		add_action( 'scheduled_subscription_payment_' . $this->id, array( &$this, 'scheduled_subscription_payment' ), 10, 3 );
	}


	/**
     * Process the payment
     */
	function process_payment( $order_id ) {
		global $woocommerce;
		
		if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id ) ) {	

			$order = new WC_Order( $order_id );
			$order_items = $order->get_items();
			$product = $order->get_product_from_item( $order_items[0] );

			// Gather subscription settings
			$s_initial 	= WC_Subscriptions_Order::get_total_initial_payment( $order );
			$s_price	= WC_Subscriptions_Order::get_price_per_period( $order );
			$s_fee 		= WC_Subscriptions_Order::get_sign_up_fee( $order );
			$s_period 	= WC_Subscriptions_Order::get_subscription_period( $order );
			$s_interval = WC_Subscriptions_Order::get_subscription_interval( $order );
			$s_length 	= (int) WC_Subscriptions_Order::get_subscription_length( $order );
			$s_trial 	= WC_Subscriptions_Order::get_subscription_trial_length( $order );
			
			$subscription_name = sprintf( __( 'Subscription for "%s"', 'woocommerce' ), $product->get_title() ) . ' ' . sprintf( __( '(Order %s)', 'woocommerce' ), $order->get_order_number() );								
			$exp_month = (int) $_POST['expmonth']; 
			$expirationDate = ( 10 > $exp_month) ? $_POST['expyear'] . "-0" . $_POST['expmonth'] : $_POST['expyear'] . "-" . $_POST['expmonth']; 
			$start_date = date( 'Y-m-d'); // 2012-10-11
			$trial_occurrences = $s_trial / $s_interval; 
			if ( $s_length == 0 ) {
				$total_occurrences = '9999';
			} else {
				$total_occurrences = ( $s_length / $s_interval ) + ( $s_trial / $s_interval ); 
			}

			switch ( $s_period ){
				case 'week' :
					$interval_unit = 'days';
					$interval_length = 7 * $s_interval;
					break;
				case 'month' :
					$interval_unit = 'months';
					$interval_length = $s_interval;
					break;
				case 'day' :
				case 'year' :
					$woocommerce->add_error(__('Error: Subscription period of this product not supported by this payment gateway.', 'woocommerce') );
					return;			
			}



			try {
			
				// ************************************************ 
				// Process Signup Fee
				if ( $s_fee > 0 ){
					$this->process_signup_fee_payment( $order_id );
				}
			
				// ************************************************ 
				// Create request
			
				$xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
				$xml .= "<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">";
				$xml .= "<merchantAuthentication>";
				$xml .= "<name>" . $this->apilogin . "</name>";
				$xml .= "<transactionKey>" . $this->transkey . "</transactionKey>";
				$xml .= "</merchantAuthentication>";
				$xml .= "<refId>" . $order->id . "</refId>";				
				$xml .= "<subscription>";
				$xml .= "<name>" . $subscription_name . "</name>";
				$xml .= "<paymentSchedule>";
				$xml .= "<interval>";
				$xml .= "<length>" . $interval_length . "</length>";
				$xml .= "<unit>" . $interval_unit . "</unit>";
				$xml .= "</interval>";
				$xml .= "<startDate>" . $start_date . "</startDate>";
				$xml .= "<totalOccurrences>" . $total_occurrences . "</totalOccurrences>";
				if ( $trial_occurrences > 0 ) {
				$xml .= "<trialOccurrences>" . $trial_occurrences . "</trialOccurrences>";
				}
				$xml .= "</paymentSchedule>";
				$xml .= "<amount>" . $s_price . "</amount>";
				if ( $s_trial > 0 ) {
				// Only free trials are supported by WC Subscriptions
				$xml .= "<trialAmount>0.00</trialAmount>";
				}
				$xml .= "<payment>";
				$xml .= "<creditCard>";
				$xml .= "<cardNumber>" . $_POST['ccnum'] . "</cardNumber>";
				$xml .= "<expirationDate>" . $expirationDate . "</expirationDate>";
				$xml .= "<cardCode>" . $_POST['cvv'] . "</cardCode>";
				$xml .= "</creditCard>";
				$xml .= "</payment>";
				$xml .= "<billTo>";
				$xml .= "<firstName>" . $order->billing_first_name . "</firstName>";
				$xml .= "<lastName>" . $order->billing_last_name . "</lastName>";				
				$xml .= "<address>" . $order->billing_address_1 . "</address>";
				$xml .= "<city>" . $order->billing_city . "</city>";
				$xml .= "<state>" . $order->billing_state . "</state>";
				$xml .= "<zip>" . $order->billing_postcode . "</zip>";
				$xml .= "<country>" . $order->billing_country . "</country>";
				$xml .= "</billTo>";
				$xml .= "</subscription>";
				$xml .= "</ARBCreateSubscriptionRequest>";

				// Debug XML				
				$debug_xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
				$debug_xml .= "<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">\n";
				$debug_xml .= "<merchantAuthentication>\n";
				$debug_xml .= "<name>XXXXXXXXX</name>\n";
				$debug_xml .= "<transactionKey>XXXXXXXXXX</transactionKey>\n";
				$debug_xml .= "</merchantAuthentication>\n";
				$debug_xml .= "<refId>" . $order->id . "</refId>\n";				
				$debug_xml .= "<subscription>\n";
				$debug_xml .= "	<name>" . $subscription_name . "</name>\n";
				$debug_xml .= "	<paymentSchedule>\n";
				$debug_xml .= "		<interval>\n";
				$debug_xml .= "			<length>" . $interval_length . "</length>\n";
				$debug_xml .= "			<unit>" . $interval_unit . "</unit>\n";
				$debug_xml .= "		</interval>\n";
				$debug_xml .= "		<startDate>" . $start_date . "</startDate>\n";
				$debug_xml .= "		<totalOccurrences>" . $total_occurrences . "</totalOccurrences>\n";
				$debug_xml .= "		<trialOccurrences>" . $trial_occurrences . "</trialOccurrences>\n";
				$debug_xml .= "	</paymentSchedule>\n";
				$debug_xml .= "	<amount>" . $s_price . "</amount>\n";
				$debug_xml .= "	<trialAmount>0.00</trialAmount>\n";
				$debug_xml .= "	<payment>\n";
				$debug_xml .= "		<creditCard>\n";
				$debug_xml .= "			<cardNumber>XXXXXXXXXXXXXXXX</cardNumber>\n";
				$debug_xml .= "			<expirationDate>XXXX-XX</expirationDate>\n";
				$debug_xml .= "			<cardCode>XXX</cardCode>";
				$debug_xml .= "		</creditCard>\n";
				$debug_xml .= "	</payment>\n";
				$debug_xml .= "	<billTo>\n";
				$debug_xml .= "		<firstName>" . $order->billing_first_name . "</firstName>\n";
				$debug_xml .= "		<lastName>" . $order->billing_last_name . "</lastName>\n";
				$debug_xml .= "		<address>" . $order->billing_address_1 . "</address>";
				$debug_xml .= "		<city>" . $order->billing_city . "</city>";
				$debug_xml .= "		<state>" . $order->billing_state . "</state>";
				$debug_xml .= "		<zip>" . $order->billing_postcode . "</zip>";
				$debug_xml .= "		<country>" . $order->billing_country . "</country>";
				$debug_xml .= "	</billTo>\n";
				$debug_xml .= "</subscription>\n";
				$debug_xml .= "</ARBCreateSubscriptionRequest>\n";
				
				if ( $this->testmode == 'yes' ){
					$gatewayurl = $this->gatewayurl_subs_test;
				} else {
					$gatewayurl = $this->gatewayurl_subs_prod;
				}

				$debug_string = "\n\nSENDING REQUEST:\n"
								. "(Authentication and credit card info replaced with X's)\n\n" 
								. "Gateway URL:" . $gatewayurl . "\n\n"
								. $debug_xml;								
								
				$this->send_debugging_email( $debug_string );	
			
				// ************************************************ 
				// Process request
				
				$response = wp_remote_post( 
				    $gatewayurl, 
				    array(
				        'method' => 'POST',
				        'timeout' => 70,
				        'redirection' => 5,
				        'httpversion' => '1.0',
				        'headers' => array(
				            'Content-Type' => 'text/xml'
				        ),
				        'body' => $xml,
				        'sslverify' => false
				    )
				);				

				if ( is_wp_error( $response ) ) 
					return new WP_Error( 'authorize_net_error', __('There was a problem connecting to the payment gateway.', 'woocommerce') );


				libxml_use_internal_errors(true);
				$response_object = simplexml_load_string( $response['body'] );
				libxml_use_internal_errors(false);
				
				$this->send_debugging_email( "Authorize.net Gateway Response: \n\nRESPONSE:\n" 
										. print_r($response,true));	
			
				
				if( empty($response_object) ) throw new Exception(__('Empty Authorize.net response.', 'woothemes'));
				
			
				// ************************************************ 
				// Retreive response

				if (( $response_object->messages->resultCode == "Ok" )) {
					// Successful payment
	
					$order->add_order_note( __('Authorize.net ARB payment completed', 'woocommerce') . ' (Response Code: ' . $response_object->messages->message->code . " | " . $response_object->messages->message->text . ')' );
					
					// store the subscription ID with the Order					
					update_post_meta( $order->id, '_authorize_subscription_id', (int) $response_object->subscriptionId );
										
					$order->payment_complete();		
					$woocommerce->cart->empty_cart();
	
					// Empty awaiting payment session
					unset($_SESSION['order_awaiting_payment']);
						
					// Return thank you redirect
					return array(
						'result' 	=> 'success',
						'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
					);
	
				} else {
					
					$this->send_debugging_email( "AUTHORIZE.NET ERROR:\nResponse_code:" . $response_object->messages->resultCode 
								. "\nResponse Message:" . $response_object->messages->message->text 
								. "(" . $response_object->messages->message->code . ")" );
				
					$cancelNote = __('Authorize.net payment failed', 'woocommerce') . ' (Response Code: ' . $response_object->messages->resultCode . '). ' . __('Payment wast rejected due to an error', 'woocommerce') . ': "' 
					. $response_object->messages->message->text . "(" . $response_object->messages->message->code . ")" . '". ';
		
					$order->add_order_note( $cancelNote );
					
					$woocommerce->add_error(__('Payment error', 'woocommerce') . ': ' . $response_object->messages->message->text . '');
	
				}

			} catch(Exception $e) {
				$woocommerce->add_error(__('Error:', 'woocommerce') . ': "' . $e->getMessage() . '"');
				return;
			}						
									
		} else {

			return parent::process_payment( $order_id );
			
		}
	}
			
	/**
	 * scheduled_subscription_payment()
	 *
	 * Check status of this subscription with Authorize.net
	 * 
	 * @param $amount_to_charge float The amount to charge.
	 * @param $order WC_Order The WC_Order object of the order which the subscription was purchased in.
	 * @param $product_id int The ID of the subscription product for which this payment relates.
	 * @access public
	 * @return void
	 */
	public function scheduled_subscription_payment ( $amount_to_charge, $order, $product_id ) {
		global $woocommerce;
				
		// ************************************************ 
		// get subscription token
		$subscription_id = get_post_meta( $order->id, '_authorize_subscription_id', true );
		
		if ( empty( $subscription_id ) ) {
			return; 
		}
	
		// ************************************************ 
		// Create request
		
		$xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$xml .= "<ARBGetSubscriptionStatusRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">";
		$xml .= "<merchantAuthentication>";
		$xml .= "<name>" . $this->apilogin . "</name>";
		$xml .= "<transactionKey>" . $this->transkey . "</transactionKey>";
		$xml .= "</merchantAuthentication>";
		$xml .= "<refId>" . $order->id . "</refId>";
		$xml .= "<subscriptionId>" . $subscription_id . "</subscriptionId>";
		$xml .= "</ARBGetSubscriptionStatusRequest>";
	
		if ( $this->testmode == 'yes' ){
			$gatewayurl = $this->gatewayurl_subs_test;
		} else {
			$gatewayurl = $this->gatewayurl_subs_prod;
		}

		$debug_string = "\n\nSENDING STATUS REQUEST:\n"
						. "Gateway URL:" . $gatewayurl . "\n\n"
						. $xml;								
						
		$this->send_debugging_email( $debug_string );	
	
		// ************************************************ 
		// Process request

		$response = wp_remote_post( 
		    $gatewayurl, 
		    array(
		        'method' => 'POST',
		        'timeout' => 70,
		        'redirection' => 5,
		        'httpversion' => '1.0',
		        'headers' => array(
		            'Content-Type' => 'text/xml'
		        ),
		        'body' => $xml,
		        'sslverify' => false
		    )
		);				

		if ( is_wp_error( $response ) ) {
			$order->add_order_note(  __('There was a problem connecting to the payment gateway.', 'woocommerce') );

			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
			return;		
		}

		libxml_use_internal_errors(true);
		$response_object = simplexml_load_string( $response['body'] );
		libxml_use_internal_errors(false);
		
		$this->send_debugging_email( "Subscription Update Response: \n\nRESPONSE:\n" . print_r($response,true));	
		
		if( empty($response_object) ) {
			$order->add_order_note( __('Empty Authorize.net subscription status response.', 'woothemes') );
			return;			
		} 
			
		// ************************************************ 
		// Retreive response

		if ( $response_object->messages->resultCode == "Ok" ) {

			// check status code
			switch ( $response_object->status ){
				case 'active' :
					$order->add_order_note( __('Authorize.net ARB payment completed', 'woocommerce') 
							. ' (Response Code: ' . $response_object->messages->message->code . " | " 
							. $response_object->messages->message->text . ')' );
		
					WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );			
					break;
				case 'expired' :
				case 'suspended' :
				case 'cancelled' :
				case 'terminated' :
					$order->add_order_note( __('Authorize.net ARB Update', 'woocommerce') 
							. __(' Subscription reported as:') . $response_object->status . " | "
							. ' (Response Code: ' . $response_object->messages->message->code . " | " 
							. $response_object->messages->message->text . ')' );

					WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );			
					break;				
			}
		
			$order->add_order_note( __('Authorize.net ARB payment completed', 'woocommerce') 
					. ' (Response Code: ' . $response_object->messages->message->code . " | " 
					. $response_object->messages->message->text . ')' );

			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );			
			
		} else {
			
			$this->send_debugging_email( "Authorize.net Subscription Status Error:\nResponse_code:" 
					. $response_object->messages->resultCode 
					. "\nResponse Message:" . $response_object->messages->message->text 
					. "(" . $response_object->messages->message->code . ")" );
		
			$cancelNote = __('Authorize.net subscription status failed', 'woocommerce') 
					. ' (Response Code: ' . $response_object->messages->resultCode . '). ' 
					. __('Payment wast rejected due to an error', 'woocommerce') . ': "' 
					. $response_object->messages->message->text . "(" . $response_object->messages->message->code . ")" . '". ';

			$order->add_order_note( $cancelNote );
			
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		}

		return;
					
	}
	
	
	/**
	 * process_signup_fee_payment()
	 * 
	 * @param $order WC_Order The WC_Order object of the order which the subscription was purchased in.
	 * @access public
	 * @throws Exception - if there is an error in processing. 
	 * @return void
	 */
	function process_signup_fee_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Get signup fee to charge
		$s_signup_fee = WC_Subscriptions_Order::get_sign_up_fee( $order );

		$testmode = ($this->testmode == 'yes') ? 'TRUE' : 'FALSE';
		
		// ************************************************ 
		// Create request
		
		$authnet_request = array (
			"x_tran_key" 		=> $this->transkey, 
			"x_login" 			=> $this->apilogin,
			"x_amount" 			=> $s_signup_fee,
			"x_card_num" 		=> $_POST['ccnum'],
			"x_card_code" 		=> (isset($_POST['cvv'])) ? $_POST['cvv'] : '',
			"x_exp_date" 		=> $_POST['expmonth'] . "-" . $_POST['expyear'],
			"x_type" 			=> $this->salemethod,
			"x_version" 		=> "3.1",
			"x_delim_data" 		=> "TRUE",
			"x_relay_response" 	=> "FALSE",
			"x_method" 			=> "CC",
			"x_first_name" 		=> $order->billing_first_name,
			"x_last_name" 		=> $order->billing_last_name,
			"x_address" 		=> $order->billing_address_1,
			"x_city" 			=> $order->billing_city,
			"x_state" 			=> $order->billing_state,
			"x_zip" 			=> $order->billing_postcode,
			"x_country" 		=> $order->billing_country,
			"x_phone" 			=> $order->billing_phone,
			"x_email"			=> $order->billing_email,
			"x_cust_id" 		=> $order->user_id,
			"x_customer_ip" 	=> $_SERVER['REMOTE_ADDR'],
			"x_invoice_num" 	=> $order->id,
			"x_test_request" 	=> $testmode,
			"x_delim_char" 		=> '|',
			"x_encap_char" 		=> '',
		);
		
		$this->send_debugging_email( "Subscription Signup Fee\n\n" .
									 "URL: " . $this->gatewayurl . 
									 "\n\nSENDING REQUEST:" . print_r($authnet_request,true));	
	
		// ************************************************ 
		// Send request

		$post = '';
		foreach($authnet_request AS $key => $val){
			$post .= urlencode($key) . "=" . urlencode($val) . "&";
		}
		$post = substr($post, 0, -1);
		
		$response = wp_remote_post( $this->gatewayurl, array(
				'method'		=> 'POST',
			'body' 			=> $post,
			'timeout' 		=> 70,
			'sslverify' 	=> false
		));
		
		if ( is_wp_error($response) ) throw new Exception(__('There was a problem connecting to the payment gateway.', 'woothemes'));
		
		if( empty($response['body']) ) throw new Exception(__('Empty Authorize.net response.', 'woothemes'));
		
		$content = $response['body'];

		// prep response
		foreach ( preg_split("/\r?\n/", $content) as $line ) {
			if (preg_match("/^1|2|3\|/", $line)) {
				$data = explode("|", $line);
			}
		}

		// store response
		$response['response_code'] = $data[0];
		$response['response_sub_code'] = $data[1];
		$response['response_reason_code'] = $data[2];
		$response['response_reason_text'] = $data[3];
		$response['approval_code'] = $data[4];
		$response['avs_code'] = $data[5];
		$response['transaction_id'] = $data[6];
		$response['invoice_number_echo'] = $data[7];
		$response['description_echo'] = $data[8];
		$response['amount_echo'] = $data[9];
		$response['method_echo'] = $data[10];
		$response['transaction_type_echo'] = $data[11];
		$response['customer_id_echo'] = $data[12];
		$response['first_name_echo'] = $data[13];
		$response['last_name_echo'] = $data[14];
		$response['company_echo'] = $data[15];
		$response['billing_address_echo'] = $data[16];
		$response['city_echo'] = $data[17];
		$response['state_echo'] = $data[18];
		$response['zip_echo'] = $data[19];
		$response['country_echo'] = $data[20];
		$response['phone_echo'] = $data[21];
		$response['fax_echo'] = $data[22];
		$response['email_echo'] = $data[23];
		$response['ship_first_name_echo'] = $data[24];
		$response['ship_last_name_echo'] = $data[25];
		$response['ship_company_echo'] = $data[26];
		$response['ship_billing_address_echo'] = $data[27];
		$response['ship_city_echo'] = $data[28];
		$response['ship_state_echo'] = $data[29];
		$response['ship_zip_echo'] = $data[30];
		$response['ship_country_echo'] = $data[31];
		$response['tax_echo'] = $data[32];
		$response['duty_echo'] = $data[33];
		$response['freight_echo'] = $data[34];
		$response['tax_exempt_echo'] = $data[35];
		$response['po_number_echo'] = $data[36];
	
		$response['md5_hash'] = $data[37];
		$response['cvv_response_code'] = $data[38];
		$response['cavv_response_code'] = $data[39];

		$this->send_debugging_email( "RESPONSE RAW: " . $content . "\n\nRESPONSE:" . print_r($response,true));	
	
		// ************************************************ 
		// Retreive response

		if (($response['response_code'] == 1) || ($response['response_code'] == 4)) {
			// Successful payment
			$order->add_order_note( __('Authorize.net payment completed', 'woocommerce') . ' (Response Code: ' . $response['response_code'] . ')' );

			return true;

		} else {

			$this->send_debugging_email( "Subscription Sign Up Fee Error:\nresponse_code:" . $response['response_code'] . "\nresponse_reasib_text:" .$response['response_reason_text'] );
		
			$cancelNote = __('Subscription Sign Up Fee failed', 'woocommerce') . ' (Response Code: ' . $response['response_code'] . '). ' . __('Payment wast rejected due to an error', 'woocommerce') . ': "' . $response['response_reason_text'] . '". ';

			$order->add_order_note( $cancelNote );

			throw new Exception(__('Payment error', 'woocommerce') . ': ' . $response['response_reason_text'] . ''); 			
			
		}
		
	}
	
	
	/**
	 * cancel_subscription_authorize ()
	 * 
	 * @param $order WC_Order The WC_Order object of the order which the subscription was purchased in.
	 * @access public
	 * @return void
	 */
	function cancelled_subscription_authorize( $order, $product_id ) {
		global $woocommerce;
	
		// ************************************************ 
		// get subscription token
		$subscription_id = get_post_meta( $order->id, '_authorize_subscription_id', true );
		
		if ( empty( $subscription_id ) ) {
			return; 
		}

		// ************************************************ 
		// Create request
		$xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$xml .= "<ARBCancelSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">";
		$xml .= "<merchantAuthentication>";
		$xml .= "<name>" . $this->apilogin . "</name>";
		$xml .= "<transactionKey>" . $this->transkey . "</transactionKey>";
		$xml .= "</merchantAuthentication>";
		$xml .= "<refId>" . $order->id . "</refId>";				
		$xml .= "<subscriptionId>" . $subscription_id . "</subscriptionId>";
		$xml .= "</ARBCancelSubscriptionRequest>";

		if ( $this->testmode == 'yes' ){
			$gatewayurl = $this->gatewayurl_subs_test;
		} else {
			$gatewayurl = $this->gatewayurl_subs_prod;
		}

		$debug_string = "\n\nSENDING CANCEL REQUEST:\n"
						. "Gateway URL:" . $gatewayurl . "\n\n"
						. $xml;								
						
		$this->send_debugging_email( $debug_string );	
	
		// ************************************************ 
		// Process request
		
		$response = wp_remote_post( 
		    $gatewayurl, 
		    array(
		        'method' => 'POST',
		        'timeout' => 70,
		        'redirection' => 5,
		        'httpversion' => '1.0',
		        'headers' => array(
		            'Content-Type' => 'text/xml'
		        ),
		        'body' => $xml,
		        'sslverify' => false
		    )
		);				

		if ( is_wp_error( $response ) ) {
			$order->add_order_note(  __('There was a problem connecting to the payment gateway.', 'woocommerce') );
			return;		
		}


		libxml_use_internal_errors(true);
		$response_object = simplexml_load_string( $response['body'] );
		libxml_use_internal_errors(false);
		
		$this->send_debugging_email( "Cancel Subscription Response: \n\nRESPONSE:\n" 
								. print_r($response,true));	
	
		
		if( empty($response_object) ) {
			$order->add_order_note( __('Empty Authorize.net cancel subscription response.', 'woothemes') );
			return;			
		} 
		
	
		// ************************************************ 
		// Retreive response

		if ( $response_object->messages->resultCode == "Ok" ) {
			// Successful cancelation

			$order->add_order_note( __('Authorize.net ARB Subscription Cancel Complete', 'woocommerce') . ' (Response Code: ' . $response_object->messages->message->code . " | " . $response_object->messages->message->text . ')' );
			
		} else {
			
			$this->send_debugging_email( "Authorize.net Subscription Cancel Error:\nResponse_code:" . $response_object->messages->resultCode 
						. "\nResponse Message:" . $response_object->messages->message->text 
						. "(" . $response_object->messages->message->code . ")" );
		
			$cancelNote = __('Authorize.net subscription cancel failed', 'woocommerce') . ' (Response Code: ' . $response_object->messages->resultCode . '). ' . __('Payment wast rejected due to an error', 'woocommerce') . ': "' 
			. $response_object->messages->message->text . "(" . $response_object->messages->message->code . ")" . '". ';

			$order->add_order_note( $cancelNote );
			
		}
		
		return; 
	}

}