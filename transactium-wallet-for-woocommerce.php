<?php
/*
Plugin Name: Transactium Wallet for WooCommerce
Description: Transactium Wallet for WooCommerce
Version: 1.2
Author: Transactium Ltd
Author URI: http://www.transactium.com
*/

// Include our Gateway Class and Register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'transactium_wallet_for_woocommerce_init', 0);

function transactium_wallet_for_woocommerce_init()
{
    if (!function_exists('is_woocommerce_active')) {
		function is_woocommerce_active() {
			return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || is_plugin_active_for_network('woocommerce/woocommerce.php');
		}
	}
	
	// If the parent WC_Payment_Gateway class doesn't exist
    // it means WooCommerce is not installed on the site
    // so do nothing
    if (!class_exists('WC_Payment_Gateway') || ! is_woocommerce_active() || class_exists('transactium_wallet_for_woocommerce'))
        return;
    
    // payment gateway class
    class transactium_wallet_for_woocommerce extends WC_Payment_Gateway
    {
        
        // Setup our Gateway's id, description and other values
        function __construct()
        {
            
            // The global ID for this Payment method
            $this->id = "transactium_wallet_for_woocommerce";
            
            // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
            $this->method_title = __("Transactium Wallet", 'transactium-wallet-for-woocommerce');
            
            // The description for this Payment Gateway, shown on the actual Payment options page on the backend
            $this->method_description = __("Transactium Wallet Plug-in for WooCommerce", 'transactium-wallet-for-woocommerce');
            
            // The title to be used for the vertical tabs that can be ordered top to bottom
            $this->title = __("Transactium Wallet", 'transactium-wallet-for-woocommerce');
            
            // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
            $this->icon = null;
            
            // Bool. Can be set to true if you want payment fields to show on the checkout 
            // if doing a direct integration, which we are doing in this case
            $this->has_fields = false;
            
            // Supports the following functionalities
            $this->supports = array(
				'refunds',
			);
            
            // This basically defines your settings which are then loaded with init_settings()
            $this->init_form_fields();
            
            // After init_settings() is called, you can get the settings and load them into variables, e.g:
            // $this->title = $this->get_option( 'title' );
            $this->init_settings();
            
            // Turn these settings into variables we can use
            foreach ($this->settings as $setting_key => $value) {
                $this->$setting_key = is_string($value) ? trim($value) : $value;
            }
            
            // Lets check for SSL and WooCommerce version
            add_action('admin_notices', array(
                $this,
                'do_admin_checks'
            ));
            
            // Save settings
            if (is_admin()) {
                // Versions over 2.0
                // Save our administration options. Since we are not going to be doing anything special
                // we have not defined 'process_admin_options' in this class so the method in the parent
                // class will be used instead
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                    $this,
                    'process_admin_options'
                ));
            }
            
            //handler is called after 3dsecure (HOST) completes from transactium - End3DSHostPostbackURL
            add_action('woocommerce_api_wc_gateway_transactium_wallet_for_woocommerce', array(
                $this,
                'return_handler'
            ));
            add_action('woocommerce_api_wc_gateway_transactium_wallet_for_woocommerce_complete', array(
                $this,
                'complete_payment'
            ));       
			add_filter( 'woocommerce_checkout_fields' , array( $this, 'override_checkout_fields' ));
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'checkout_field_display_admin_order_meta' ), 10, 1 );
        } // End __construct()
        
        // Admin Panel Options.
        // - Options for bits like 'title' and availability on a country-by-country basis.
        public function admin_options()
        {
            parent::admin_options();
            $this->checks();
			echo '<div class="notice"><p>' . sprintf(__('Return URL : ' . WC()->api_request_url('WC_Gateway_Transactium_Wallet_for_woocommerce_Complete') , 'transactium-wallet-for-woocommerce')) . '</p></div>';
        }
        
        // Check if SSL is enabled and notify the user.
        public function checks()
        {
            if ('no' == $this->enabled) {
                return;
            }
            
            // PHP Version
            if (version_compare(phpversion(), '5.3', '<')) {
                echo '<div class="error"><p>' . sprintf(__('Transactium Wallet Error: Transactium Wallet requires PHP 5.3 and above. You are using version %s.', 'transactium-wallet-for-woocommerce'), phpversion()) . '</p></div>';
            }
            // Check required fields
			elseif(!$this->url) {
				echo '<div class="error"><p>' . __('Transactium Wallet Error: Please enter your API URL', 'transactium-wallet-for-woocommerce') . '</p></div>';
            }
            elseif (!$this->private_key) {
                echo '<div class="error"><p>' . __('Transactium Wallet Error: Please enter your private key', 'transactium-wallet-for-woocommerce') . '</p></div>';
            }
            // Show message when using standard mode and no SSL on the checkout page
            elseif ($this->ssl_verification !== "yes") {
                echo '<div class="error"><p>' . sprintf(__('Transactium Wallet is enabled, but the <b>SSL Verification</b> option is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Transactium Wallet will only work in sandbox mode.', 'transactium-wallet-for-woocommerce')) . '</p></div>';
            }
			 
        }
        
        /**
         * Check if this gateway is enabled.
         *
         * @return bool
         */
        public function is_available()
        {
            if ('yes' !== $this->enabled) {
                return false;
            }
			
			if (!$this->url) {
                return false;
            }
            
            if (!$this->private_key) {
                return false;
            }
			
			if ($this->WC_3() === null) {
				return false;
			}
            
            return true;
        }
        
        // Check if we are forcing SSL on checkout pages
        // Custom function not required by the Gateway
        function do_admin_checks()
        {
            if ($this->enabled == "yes") {
                if ($this->WC_3() === null) {
					echo "<div class=\"error\"><p>" . sprintf(__("This version of <strong>%s</strong> requires WooCommerce v2.4 or later. Please upgrade WooCommerce.", 'transactium-wallet-for-woocommerce'), $this->method_title) . "</p></div>";
				}
				if (!wc_checkout_is_https() && $this->ssl_verification === "yes") {
                    echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured</a>. After enabling refresh to hide this message."), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
                }
            }
        }
		
		function WC_3() {
			if(version_compare( get_option( 'woocommerce_db_version' ), '3', '>=' )) {
				return true;
			} else if (version_compare( get_option( 'woocommerce_db_version' ), '2.4', '>=' ) && version_compare( get_option( 'woocommerce_db_version' ), '3', '<' )) {
				return false;
			} else {
				return null;
			}
		}
		
		function WC_compat($order, $old, $new = null, $old_is_property = true, $new_is_property = false) {
			$method_property_name = !$new ? 'get_'.$old : $new;
			if (!$new_is_property) {
				return method_exists($order, $method_property_name) ? $order->$method_property_name() : ($old_is_property ? $order->$old : $order->$old());
			} else {
				return property_exists($order, $method_property_name) ? $order->$method_property_name : ($old_is_property ? $order->$old : $order->$old());
			}
		}
        
        function get_base_url()
        {
			return rtrim($this->url, '/') . '/';
        }
        
        function get_wp_request_array()
        {
            return array(
                'sslverify' => $this->ssl_verification === "yes",
                'user-agent' => null,
                'compress' => false,
                'decompress' => false,
                'headers' => array(
                    'Accept' => 'application/json',
                    'Accept-Encoding' => null,
					//'Referer'=> null,
                )
            );
        }
        
        // Build the administration fields for this specific Gateway
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable / Disable', 'transactium-wallet-for-woocommerce'),
                    'label' => __('Enable this payment gateway', 'transactium-wallet-for-woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'transactium-wallet-for-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('Payment title the customer will see during the checkout process.', 'transactium-wallet-for-woocommerce'),
                    'default' => __('Wallet', 'transactium-wallet-for-woocommerce')
                ),
                'description' => array(
                    'title' => __('Description', 'transactium-wallet-for-woocommerce'),
                    'type' => 'textarea',
                    'desc_tip' => __('Payment description the customer will see during the checkout process.', 'transactium-wallet-for-woocommerce'),
                    'default' => __('Pay securely using your wallet.', 'transactium-wallet-for-woocommerce'),
                    'css' => 'max-width:350px;'
                ),
				'url' => array(
                    'title' => __('API URL', 'transactium-wallet-for-woocommerce'),
                    'desc_tip' => __('Please insert the API URL provided by Transactium.', 'transactium-wallet-for-woocommerce'),
                    'type' => 'text',
                    'default' => '', // WC >= 2.0
					'placeholder' => 'e.g. https://epaypro.co.za'
                ),
                'private_key' => array(
                    'title' => __('Private Key', 'transactium-wallet-for-woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('This is the Private Key provided by Transactium when you signed up for an account.', 'transactium-wallet-for-woocommerce')
                ),
                'ssl_verification' => array(
                    'title' => __('SSL Verification', 'transactium-wallet-for-woocommerce'),
                    'label' => __('Enable SSL Verification', 'transactium-wallet-for-woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('This enables SSL verification. Turn off by deselecting. This should be left on for security reasons.', 'transactium-wallet-for-woocommerce'),
                    'default' => 'yes'
                ),
				'auto_customer_registration_wallet' => array(
                    'title' => __('Automatic Wallet Customer Registration', 'transactium-wallet-for-woocommerce'),
                    'label' => __('Enable automatic Wallet Customer Registration', 'transactium-wallet-for-woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('On checkout, this option prepares a customer Wallet account using same user billing details pre-entered on WordPress, providing a smoother experience for the customer. A verification email will be sent to the user to confirm the created account.', 'transactium-wallet-for-woocommerce'),
                    'default' => 'no'
                )
            );
        }
		
		public function override_checkout_fields( $fields ) {
			 
			 wp_enqueue_style('transactium-wallet-for-woocommerce', plugins_url('assets/css/transactium-wallet-for-woocommerce.css',__FILE__));
			 
			 $fields['billing']['billing_dob_month'] = array(
				'label'     => __('Birth Month', 'transactium-wallet-for-woocommerce'),
				'placeholder'   => _x('MMM', 'placeholder', 'transactium-wallet-for-woocommerce'),
				'required'  => true,
				'class'     => array('form-row-first', 'billing_dateofbirth_month'),
				'clear'     => false,
				'type'		=> 'select',
				'options'	=> array(
					'' => __('- Select -', 'transactium-wallet-for-woocommerce'),
					'01' => __('January', 'transactium-wallet-for-woocommerce'),
					'02' => __('February', 'transactium-wallet-for-woocommerce'),
					'03' => __('March', 'transactium-wallet-for-woocommerce'),
					'04' => __('April', 'transactium-wallet-for-woocommerce'),
					'05' => __('May', 'transactium-wallet-for-woocommerce'),
					'06' => __('June', 'transactium-wallet-for-woocommerce'),
					'07' => __('July', 'transactium-wallet-for-woocommerce'),
					'08' => __('August', 'transactium-wallet-for-woocommerce'),
					'09' => __('September', 'transactium-wallet-for-woocommerce'),
					'10' => __('October', 'transactium-wallet-for-woocommerce'),
					'11' => __('November', 'transactium-wallet-for-woocommerce'),
					'12' => __('December', 'transactium-wallet-for-woocommerce'),
				),
			 );
			 
			 $fields['billing']['billing_dob_day'] = array(
				'label'     => __('Day', 'transactium-wallet-for-woocommerce'),
				'placeholder'   => _x('DD', 'placeholder', 'transactium-wallet-for-woocommerce'),
				'required'  => true,
				'class'     => array('form-row-first', 'billing_dateofbirth_day'),
				'clear'     => false,
				'type'		=> 'number',
				'custom_attributes' => array(
					'min'		=> '1',
					'max' 		=> '31',
				),
			 );
			 
			 $fields['billing']['billing_dob_year'] = array(
				'label'     => __('Year', 'transactium-wallet-for-woocommerce'),
				'placeholder'   => _x('YYYY', 'placeholder', 'transactium-wallet-for-woocommerce'),
				'required'  => true,
				'class'     => array('form-row-first', 'billing_dateofbirth_year'),
				'clear'     => false,
				'type'		=> 'number',
				'custom_attributes' => array(
					'min'		=> date("Y")-100,
					'max' 		=> date("Y"),
				),
			 );

			 return $fields;
		}

		public function checkout_field_display_admin_order_meta($order){
			echo '<p><strong>'.__('Date of Birth From Checkout Form').':</strong> ' . get_post_meta( $this->WC_compat($order, 'id'), 'Birth Date', true ) . '</p>';
		}
		
		public function get_user_dateofbirth($customer_order, $tostring = false) {
			
			$year = $_POST['billing_dob_year'];
			$current_year = date("Y");
			$year = (strlen(''.$year) == 4 && ($year > $current_year-100) && $year < $current_year) ? $year : 0;
			if ($year == 0) return null;
			
			$month = $_POST['billing_dob_month'];
			$month = (strlen(''.$month) == 2 && $month > 0 && $month < 13) ? $month : 0;
			if ($month == 0) return null;
			
			$day = $_POST['billing_dob_day'];
			$day = (strlen(''.$day) <= 2 && $day > 0 && $day < 32) ? $day : 0;
			if ($day == 0) return null;
			
			if (!checkdate($month, $day, $year)) return null;
			
			$date = mktime(0, 0, 0, $month, $day, $year);
			
			if (!isset($customer_order->billing_dob) || empty($customer_order->billing_dob)) {
				$customer_order->billing_dob = $date;
				update_post_meta( $this->WC_compat($customer_order, 'id'), 'Birth Date', sanitize_text_field( date('Y-m-d', $date) ) );
				$user_id = $customer_order->get_user_id();  // getting user id
				if ( $user_id != 0 ) {             // check if user is not a guest
					update_user_meta( $user_id, 'billing_dob_year', sanitize_text_field( $year ) );
					update_user_meta( $user_id, 'billing_dob_month', sanitize_text_field( $month ) );
					update_user_meta( $user_id, 'billing_dob_day', sanitize_text_field( $day ) );
				}
			}
			
			return $tostring ? date(DATE_ATOM, $date) : $date;
		}
        
        public function process_payment($order_id)
        {
            global $woocommerce;
            
            // Get this Order's information so that we know
            // who to charge and how much
            $customer_order = new WC_Order($order_id);
			
			$date = $this->get_user_dateofbirth($customer_order, true);
			
			if ($date == null)
			{
				wc_add_notice(__("Invalid Birth Date", 'transactium-wallet-for-woocommerce'), 'error');
				return;
			}
			
			if ($this->auto_customer_registration_wallet === "yes") {
			
				$customer_registration = array(
					"merchantPrivateKey" => $this->private_key,
					"customerBillingDetails" => array(
						"fullName" => urlencode($this->WC_compat($customer_order, 'billing_first_name') . " " . $this->WC_compat($customer_order, 'billing_last_name')),
						"dateOfBirth" => urlencode($date),
						"email" => urlencode($this->WC_compat($customer_order, 'billing_email')),
						"phone" => urlencode($this->WC_compat($customer_order, 'billing_phone')),
						"address" => array(
							"houseNameNumber" => urlencode($this->WC_compat($customer_order, 'billing_address_2')),
							"street" => urlencode($this->WC_compat($customer_order, 'billing_address_1')),
							"city" => urlencode($this->WC_compat($customer_order, 'billing_city')),
							"state" => urlencode($this->WC_compat($customer_order, 'billing_state')),
							"countryCode" => urlencode($this->WC_compat($customer_order, 'billing_country')),
							"postCode" => urlencode($this->WC_compat($customer_order, 'billing_postcode'))
						),
					),
				);
				
				$register_response = wp_remote_get(add_query_arg($customer_registration, $this->get_base_url() . "api/merchant/RegisterCustomer"), array_merge(array(
					'timeout' => 10
				), $this->get_wp_request_array()));
				
				$body = "";
				
				if (is_wp_error($register_response)) {
					wc_add_notice(__('Customer registration failed. Reason: '.json_encode($register_response), 'transactium-wallet-for-woocommerce'), 'error');
				} else if (isset($register_response['body'])) {
					$body = (object)json_decode($register_response['body'], true);
					
					$error_log = "";
					
					if (isset($body->ModelState)) {
						foreach($body->ModelState as $fieldName => $fieldErrors) foreach($fieldErrors as $error) $error_log .= $fieldName.' -> '.$error.'<br />';
						wc_add_notice(__($error_log, 'transactium-wallet-for-woocommerce'), 'error');
						return;
					} else if (isset($body->Message)) {
						if ($body->Message != "Client is already registered.") {
							wc_add_notice(__($body->Message, 'transactium-wallet-for-woocommerce'), 'error');
							return;
						}
					}
					
				} else if (wp_remote_retrieve_response_code($register_response) !== 200) {
					//Email in Use / Client Already Registered or Invalid Submission data
					// wc_add_notice(__('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'transactium-wallet-for-woocommerce'));
					// return;
				}
			}
			
			$payload = array(
                "merchantPrivateKey" => urlencode($this->private_key),
                "currencyCode" => urlencode($this->WC_compat($customer_order, 'get_order_currency', 'get_currency', false)),
                "amount" => urlencode($this->WC_compat($customer_order, 'order_total', 'get_total')),
                "returnURL" => add_query_arg(array(
                    'reference' => urlencode($order_id)
                ), WC()->api_request_url('WC_Gateway_Transactium_Wallet_For_Woocommerce')),
                "validUntil" => null,
				"orderref"=> urlencode($order_id),
				"random"=> mt_rand()
            );
			
            $response = wp_remote_get(add_query_arg($payload, $this->get_base_url() . "api/merchant/CreatePayment"), array_merge(array(
                'timeout' => 10
            ), $this->get_wp_request_array()));
			
			$transaction_id  = '';
            $transaction_url = '';
            
            if (is_wp_error($response)) {
                wc_add_notice(__('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'transactium-wallet-for-woocommerce'), 'error');
                return;
            } else if (empty($response['body'])) {
                wc_add_notice(__('Gateway Response was empty.', 'transactium-wallet-for-woocommerce'), 'error');
                return;
            } else if (isset($response['body'])) {
				$body = (object)json_decode($response['body'], true);
				
				$error_log = "";
				
				if (isset($body->ModelState)) {
					foreach($body->ModelState as $fieldName => $fieldErrors) foreach($fieldErrors as $error) $error_log .= $fieldName.' -> '.$error.'<br />';
					wc_add_notice(__($error_log, 'transactium-wallet-for-woocommerce'), 'error');
					return;
				} else if (isset($body->Message)) {
					if ($body->Message != "Client is already registered.") {
						wc_add_notice(__($body->Message, 'transactium-wallet-for-woocommerce'), 'error');
						return;
					}
				} else if (isset($body->payment_id) && isset($body->payment_url)) {
					$transaction_id = $body->payment_id;
					$transaction_url = $body->payment_url;
				} else {
					wc_add_notice(__('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'transactium-wallet-for-woocommerce'), 'error');
					return;
				}
				
			} else if (wp_remote_retrieve_response_code($response) !== 200) {
				//Email in Use / Client Already Registered or Invalid Submission data
				wc_add_notice(__('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'transactium-wallet-for-woocommerce'), 'error');
				return;
			}
            
            $customer_order->add_order_note(sprintf(__('Transactium Wallet Pending (ID: %s, URL: %s)', 'transactium-wallet-for-woocommerce'), $transaction_id, $transaction_url));
            
            update_post_meta($order_id, 'transaction_id', $transaction_id);
            
            // Redirect to thank you page
            return array(
                'result' => "success",
                'redirect' => $transaction_url
            );
        }
        
        public function return_handler()
        {
            @ob_clean();
            header('HTTP/1.1 200 OK');
            
            $order_id       = absint($_REQUEST['reference']);
            $customer_order = wc_get_order($order_id);
            $payment_id     = get_post_meta($order_id, 'transaction_id', true);
            
            $payload = array(
                "merchantPrivateKey" => urlencode($this->private_key),
                "payment_id" => urlencode($payment_id)
            );
            
            
            $response = wp_remote_get(add_query_arg($payload, $this->get_base_url() . "api/merchant/GetPaymentStatus"), array_merge(array(
                'timeout' => 10
            ), $this->get_wp_request_array()));
            
            if (is_wp_error($response)) {
                wc_add_notice(__('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'transactium-wallet-for-woocommerce'), 'error');
                return;
            }
            
            if (empty($response['body'])) {
                wc_add_notice(__('Gateway Response was empty.', 'transactium-wallet-for-woocommerce'), 'error');
                return;
            }
            $jd = (object) json_decode(wp_remote_retrieve_body($response), true);
            
            $payment_status = $jd->payment_status;
            
            switch ($payment_status) {
                case "Approved":
                    // Payment has been successful
                    $customer_order->add_order_note(sprintf(__('Transactium Wallet approved (ID: %s)', 'transactium-wallet-for-woocommerce'), $payment_id));
                    
                    // Mark order as Paid
                    $customer_order->payment_complete();
                    
                    // Empty the cart (Very important step)
                    WC()->cart->empty_cart();
                    
                    // Redirect to thank you page
                    wp_redirect($this->get_return_url($customer_order));
                    exit;
                    break;
				case "AddressLoaded": //IBAN Payment
				case "Pending":
					// Payment is pending eg. IBAN, Bitcoin...
                    $customer_order->add_order_note(sprintf(__('Transactium Wallet pending (ID: %s)', 'transactium-wallet-for-woocommerce'), $payment_id));
				
                    // Empty the cart (Very important step)
                    WC()->cart->empty_cart();
                    
                    // Redirect to thank you page
                    wp_redirect($this->get_return_url($customer_order));
                    exit;
					break;
                default:
                    // Transaction was not succesful
                    // Add notice to the cart
                    wc_add_notice("Transaction " . $payment_status, 'error');
                    // Add note to the order for your reference
                    $customer_order->add_order_note('Error: ' . $payment_status);
                    wp_redirect(wc_get_page_permalink('cart'));
                    exit();
            }
        }
		
		//In case of IBAN, Bitcoin, ... payments
		public function complete_payment()
        {
			try {
				
				@ob_clean();
				$jd = (object) json_decode(file_get_contents('php://input'),true);
				$payment_id = $jd->payment_id;
				$payment_status = $jd->payment_status;
				$merchant_amount = $jd->merchant_amount;
				$merchant_currency = $jd->merchant_currency;
				$order_id = absint($jd->order_reference);
				$mac = $jd->mac;
				
				$customer_order = wc_get_order($order_id);
				
				$payment_id_wc     = get_post_meta($order_id, 'transaction_id', true);
				
				if ($payment_id != $payment_id_wc) {
					header('HTTP/1.1 500 Payment ID Mismatch');
					echo 'Payment ID Mismatch.';
					die;
				}
				
				$key = $this->private_key;
				$secret = $payment_id.$payment_status.$merchant_amount.$merchant_currency.$order_id;
				$gen_mac = hash_hmac('sha256', $secret, $key);
				
				if (!$this->hash_compare($mac, $gen_mac)) {
					header('HTTP/1.1 500 Hash Mismatch');
					echo 'Hash Mismatch.';
					die;
				}
				
				$customer_order->add_order_note(sprintf(__('NOTIFICATION: Transactium Wallet %s (ID: %s)', 'transactium-wallet-for-woocommerce'), $payment_status, $payment_id));
				
				switch ($payment_status) {
					case "Approved":
						// Payment has been successful
						// Mark order as Paid
						$customer_order->payment_complete();
						
						break;
					case "Pending":
						//do nothing
						break;
					default:
						$customer_order->cancel_order();
						break;
				}
				header('HTTP/1.1 200 OK');
				echo $payment_status;
				die;
				
			} catch(Exception $e)
			{
				header('HTTP/1.1 500 Exception Happened');
				echo 'Exception Happened.';
				print_r($e);
				die;
			}
		}
		
		public function hash_compare($a, $b) { 
			if (!is_string($a) || !is_string($b)) { 
				return false; 
			} 
			
			$len = strlen($a); 
			if ($len !== strlen($b)) { 
				return false; 
			} 

			$status = 0; 
			for ($i = 0; $i < $len; $i++) { 
				$status |= ord($a[$i]) ^ ord($b[$i]); 
			} 
			return $status === 0; 
		}
		
		public function process_refund($order_id, $amount = null, $reason = '') {
			
			$payment_id = get_post_meta( $order_id, 'transaction_id', true );
			$customer_order = new WC_Order($order_id);
			
			if ($amount === null || $amount == 0 || $amount === '') {
				return new WP_Error( 'transactium_wallet_woocommerce_refund_error', __('Amount is NOT optional.','transactium-wallet-for-woocommerce'));
			}

			$payload = array(
				"merchantPrivateKey" => urlencode($this->private_key),
				"paymentID" => urlencode($payment_id),
			);
			
			$response = wp_remote_get(add_query_arg($payload, $this->get_base_url() . "api/merchant/RefundPayment"), array_merge(array(
                'timeout' => 10
            ), $this->get_wp_request_array()));
			
			if (is_wp_error($response))
				return new WP_Error( 'transactium_wallet_woocommerce_refund_error',__('We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'transactium-wallet-for-woocommerce'));
			
			if (wp_remote_retrieve_response_code($response) === 200) {
				
				$customer_order->add_order_note('Refund of '.$amount.' Approved', 'transactium-wallet-for-woocommerce');
				return true;
				
			} else {
				
				// Transaction was not succesful
				// Add notice to the cart
				wc_add_notice("Refund of ".$amount." Failed with Response Code: ".wp_remote_retrieve_response_code($response), 'error');
				// Add note to the order for your reference
				$customer_order->add_order_note('Error: Refund of '.$amount.' Failed');
				
				return new WP_Error( 'transactium_wallet_woocommerce_refund_error', __( 'Refund of '.$amount.' Failed [Code: '.wp_remote_retrieve_response_code($response).'] - please try again.', 'transactium-wallet-for-woocommerce' ) );
			}

			return false;
		}
    }
    
    // Now that we have successfully included our class,
    // Lets add it too WooCommerce
    add_filter('woocommerce_payment_gateways', 'transactium_wallet_for_woocommerce_gateway');
    function transactium_wallet_for_woocommerce_gateway($methods)
    {
        $methods[] = 'transactium_wallet_for_woocommerce';
        return $methods;
    }
}


// Add custom action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'transactium_wallet_for_woocommerce_action_links');
function transactium_wallet_for_woocommerce_action_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=transactium_wallet_for_woocommerce') . '">' . __('Settings', 'transactium-wallet-for-woocommerce') . '</a>'
    );
    
    // Merge our new link with the default ones
    return array_merge($plugin_links, $links);
}

?>