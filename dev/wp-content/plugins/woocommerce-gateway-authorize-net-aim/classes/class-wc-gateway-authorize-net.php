<?php
/**
 * WC_Authorize_Net
 *
 * @extends WC_Payment_Gateway
 */

class WC_Authorize_Net extends WC_Payment_Gateway {

	public function __construct() { 

        $this->id			= 'authorize';
		$this->method_title = __('Authorize.net', 'woocomemrce');        
        $this->icon 		= AUTHORIZE_URL . 'images/cards.png';
        $this->has_fields 	= true;
        $this->supports		= array( 'subscriptions', 'products', 'subscription_cancellation' );
			
		// Load the form fields
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->enabled 		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->description	= $this->settings['description'];
		$this->apilogin		= $this->settings['apilogin'];
		$this->transkey		= $this->settings['transkey'];
		$this->testmode		= $this->settings['testmode'];
		$this->salemethod	= $this->settings['salemethod'];
		$this->gatewayurl	= $this->settings['gatewayurl'];
		$this->debugon		= $this->settings['debugon'];
		$this->debugrecipient = $this->settings['debugrecipient'];
		$this->cvv			= $this->settings['cvv'];
		$this->cardtypes	= $this->settings['cardtypes'];

		// Hooks
		add_action('woocommerce_receipt_authorize', array( $this, 'receipt_page'));
		add_action('admin_notices', array( $this,'authorize_net_ssl_check'));

		if ( preg_match('/1\.[0-9]*\.[0-9]*/', WOOCOMMERCE_VERSION )){
			add_action('woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options'));
		} else {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));
		}
    } 


	/**
 	* Check if SSL is enabled and notify the user
 	**/
	function authorize_net_ssl_check() {
	     
	     if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
	     
	     	echo '<div class="error"><p>'.sprintf(__('Authorize.net is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woothemes'), admin_url('admin.php?page=woocommerce')).'</p></div>';
	     
	     endif;
	}
	
	
	/**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woothemes' ), 
							'label' => __( 'Enable Authorize.net', 'woothemes' ), 
							'type' => 'checkbox', 
							'description' => '', 
							'default' => 'no'
						), 
			'title' => array(
							'title' => __( 'Title', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default' => __( 'Credit card (Authorize.net)', 'woothemes' )
						), 
			'description' => array(
							'title' => __( 'Description', 'woothemes' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
							'default' => 'Pay with your credit card via Authorize.net.'
						),  
			'apilogin' => array(
							'title' => __( 'API Login', 'woothemes' ), 
							'type' => 'password', 
							'description' => __( 'This is the API Login supplied by Authorize.net.', 'woothemes' ), 
							'default' => ''
						), 
			'transkey' => array(
							'title' => __( 'Transaction Key', 'woothemes' ), 
							'type' => 'password', 
							'description' => __( 'This is the Transaction Key supplied by Authorize.net.', 'woothemes' ), 
							'default' => ''
						),
			'salemethod' => array(
							'title' => __( 'Sale Method', 'woothemes' ), 
							'type' => 'select', 
							'description' => __( 'Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'woothemes' ), 
							'options' => array('AUTH_ONLY'=>'Authorize Only', 'AUTH_CAPTURE'=>'Authorize &amp; Capture'),
							'default' => ''
						),
			'gatewayurl' => array(
							'title' => __( 'Gateway URL', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'URL for Authorize.net gateway processor.', 'woothemes' ), 
							'default' => 'https://secure.authorize.net/gateway/transact.dll'
						),
			'cardtypes'	=> array(
							'title' => __( 'Accepted Cards', 'woothemes' ), 
							'type' => 'multiselect', 
							'description' => __( 'Select which card types to accept.', 'woothemes' ), 
							'default' => '',
							'options' => array(
								'MasterCard'	=> 'MasterCard', 
								'Visa'			=> 'Visa',
								'Discover'		=> 'Discover',
								'American Express' => 'American Express'
								),
						),		
			'cvv' => array(
							'title' => __( 'CVV', 'woothemes' ), 
							'label' => __( 'Require customer to enter credit card CVV code', 'woothemes' ), 
							'type' => 'checkbox', 
							'description' => __( '', 'woothemes' ), 
							'default' => 'no'
						),
			'testmode' => array(
							'title' => __( 'Authorize.net Test Mode', 'woothemes' ), 
							'label' => __( 'Enable Test Mode', 'woothemes' ), 
							'type' => 'checkbox', 
							'description' => __( 'Place the payment gateway in development mode.', 'woothemes' ), 
							'default' => 'no'
						), 
			'debugon' => array(
							'title' => __( 'Debugging', 'woothemes' ), 
							'label' => __( 'Enable debug emails', 'woothemes' ), 
							'type' => 'checkbox', 
							'description' => __( 'Receive emails containing the data sent to and from Authorize.net. Only works in <strong>Test Mode</strong>.', 'woothemes' ), 
							'default' => 'no'
						),
			'debugrecipient' => array(
							'title' => __( 'Debugging Email', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Who should receive the debugging emails.', 'woothemes' ), 
							'default' =>  get_option('admin_email')
						),
			);
    }
	
	
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
		?>
		<h3><?php _e('Authorize.net','woothemes'); ?></h3>	    	
    	<p><?php _e( 'Authorize.net works by adding credit card fields on the checkout and then sending the details to Authorize.net for verification.', 'woothemes' ); ?></p>
    	<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->    	
    	<?php
    }
    	    
    
    /**
	 * Payment fields for Authorize.net.
	 **/
    function payment_fields() {
		?>
		<fieldset>

			<p class="form-row form-row-first">
				<label for="ccnum"><?php echo __("Credit Card number", 'woocommerce') ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="ccnum" name="ccnum" />
			</p>
	
			<p class="form-row form-row-last">
				<label for="cardtype"><?php echo __("Card type", 'woocommerce') ?> <span class="required">*</span></label>
				<select name="cardtype" id="cardtype" class="woocommerce-select">
					<?php 
				        foreach($this->cardtypes as $type) :
					        ?>
					        <option value="<?php echo $type ?>"><?php _e($type, 'woocommerce'); ?></option>
				            <?php
			            endforeach;
					?>
				</select>
			</p>
		
			<div class="clear"></div>

			<p class="form-row form-row-first">
				<label for="cc-expire-month"><?php echo __("Expiration date", 'woocommerce') ?> <span class="required">*</span></label>
				<select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
					<option value=""><?php _e('Month', 'woocommerce') ?></option>
					<?php
						$months = array();
						for ($i = 1; $i <= 12; $i++) {
						    $timestamp = mktime(0, 0, 0, $i, 1);
						    $months[date('n', $timestamp)] = date('F', $timestamp);
						}
						foreach ($months as $num => $name) {
				            printf('<option value="%u">%s</option>', $num, $name);
				        }
					?>
				</select>
				<select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
					<option value=""><?php _e('Year', 'woocommerce') ?></option>
					<?php
						$years = array();
						for ($i = date('y'); $i <= date('y') + 15; $i++) {
						    printf('<option value="20%u">20%u</option>', $i, $i);
						}
					?>
				</select>
			</p>
			<?php if ($this->cvv == 'yes') { ?>
		
			<p class="form-row form-row-last">
				<label for="cvv"><?php _e("Card security code", 'woocommerce') ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="cvv" name="cvv" maxlength="4" style="width:45px" />
			</p>
			<?php } ?>
			
			<div class="clear"></div>
		</fieldset>
		<?php  
    }


	/**
	 * Process the payment and return the result
	 **/

	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );
				
		$testmode = ($this->testmode == 'yes') ? 'TRUE' : 'FALSE';
		
		try {
		
		// ************************************************ 
		// Create request
		
			$authnet_request = array (
				"x_tran_key" 		=> $this->transkey, 
				"x_login" 			=> $this->apilogin,
				"x_amount" 			=> $order->order_total,
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
				"x_ship_to_first_name" => $order->shipping_first_name,
				"x_ship_to_last_name" => $order->shipping_last_name,
				"x_ship_to_company"	=> $order->shipping_company,
				"x_ship_to_address" => $order->shipping_address_1,
				"x_ship_to_city"	=> $order->shipping_city,
 				"x_ship_to_country" => $order->shipping_country,
				"x_ship_to_state"	=> $order->shipping_state,
				"x_ship_to_zip"		=> $order->shipping_postcode,				
				"x_cust_id" 		=> $order->user_id,
				"x_customer_ip" 	=> $_SERVER['REMOTE_ADDR'],
				"x_tax"				=> "Order Tax<|>Order Tax<|>".$order->order_tax,
				"x_invoice_num" 	=> ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) ), 
				"x_test_request" 	=> $testmode,
				"x_delim_char" 		=> '|',
				"x_encap_char" 		=> '',
			);
			
			// Don't send card details in the debug email
			$authnet_debug_request = $authnet_request; 
			$authnet_debug_request['x_card_num'] = "XXXX";
			$authnet_debug_request['x_card_code'] = "XXXX";
			$authnet_debug_request['x_exp_date'] = "XXXX";

			$this->send_debugging_email( "URL: " . $this->gatewayurl . "\n\nSENDING REQUEST:" . print_r($authnet_debug_request,true));	
		
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
				$order->payment_complete();
	
				$woocommerce->cart->empty_cart();

				// Empty awaiting payment session
				if ( preg_match('/1\.[0-9]*\.[0-9]*/', WOOCOMMERCE_VERSION )){
					unset($_SESSION['order_awaiting_payment']);
				} else {
					unset( $woocommerce->session->order_awaiting_payment );
				}
					
				// Return thank you redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
				);

			} else {
				
				$this->send_debugging_email( "AUTHORIZE.NET ERROR:\nresponse_code:" . $response['response_code'] . "\nresponse_reasib_text:" .$response['response_reason_text'] );
			
				$cancelNote = __('Authorize.net payment failed', 'woocommerce') . ' (Response Code: ' . $response['response_code'] . '). ' . __('Payment wast rejected due to an error', 'woocommerce') . ': "' . $response['response_reason_text'] . '". ';
	
				$order->add_order_note( $cancelNote );
				
				$woocommerce->add_error(__('Payment error', 'woocommerce') . ': ' . $response['response_reason_text'] . '');

			}
		
		} catch(Exception $e) {
			$woocommerce->add_error(__('Connection error:', 'woothemes') . ': "' . $e->getMessage() . '"');
			return;
		}

	}
	
	/**
	Validate payment form fields
	**/
	
	public function validate_fields() {
		global $woocommerce;

		$cardType = $this->get_post('card_type');
		$cardNumber = $this->get_post('ccnum');
		$cardCSC = $this->get_post('cvv');
		$cardExpirationMonth = $this->get_post('expmonth');
		$cardExpirationYear = $this->get_post('expyear');
	
		if ($this->cvv=='yes'){
			//check security code
			if(!ctype_digit($cardCSC)) {
				$woocommerce->add_error(__('Card security code is invalid (only digits are allowed)', 'woocommerce'));
				return false;
			}
	
			if((strlen($cardCSC) != 3 && in_array($cardType, array('Visa', 'MasterCard', 'Discover'))) || (strlen($cardCSC) != 4 && $cardType == 'American Express')) {
				$woocommerce->add_error(__('Card security code is invalid (wrong length)', 'woocommerce'));
				return false;
			}
		}

		//check expiration data
		$currentYear = date('Y');
		
		if(!ctype_digit($cardExpirationMonth) || !ctype_digit($cardExpirationYear) ||
			 $cardExpirationMonth > 12 ||
			 $cardExpirationMonth < 1 ||
			 $cardExpirationYear < $currentYear ||
			 $cardExpirationYear > $currentYear + 20
		) {
			$woocommerce->add_error(__('Card expiration date is invalid', 'woocommerce'));
			return false;
		}

		//check card number
		$cardNumber = str_replace(array(' ', '-'), '', $cardNumber);

		if(empty($cardNumber) || !ctype_digit($cardNumber)) {
			$woocommerce->add_error(__('Card number is invalid', 'woocommerce'));
			return false;
		}

		return true;
	}

	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order.', 'woocommerce').'</p>';
		
	}
	
	/**
	 * Get post data if set
	 **/
	private function get_post($name) {
		if(isset($_POST[$name])) {
			return $_POST[$name];
		}
		return NULL;
	}

	/**
	 * Send debugging email
	 **/
	function send_debugging_email( $debug ) {
		
		if ($this->debugon!='yes') return; // Debug must be enabled
		if ($this->testmode!='yes') return; // Test mode required
		if (!$this->debugrecipient) return; // Recipient needed
		
		// Send the email
		wp_mail( $this->debugrecipient, __('Authorize.net Debug', 'woothemes'), $debug );
		
	} 

}

