<?php

/**
 * Mundo pagos Gateway.
 *
 * Mundo pagos Gateway.
 *
 * @class       WC_Gateway_Payleo
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce/Classes/Payment
 */

class BuyerInfo
{

	public $firstName;
	public $lastName;
	public $dni;
	public $phone;
	public $email;
	public $address;
	public $country;
	public $others;

	public function __construct($fnb, $lnb, $dni, $pb, $email, $address, $cb)
	{
		$this->firstName = $fnb;
		$this->lastName = $lnb;
		$this->dni = $dni;
		$this->phone = $pb;
		$this->email = $email;
		$this->address = $address;
		$this->country = $cb;
		$this->others = null;
	}
}

class WC_Gateway_MundoPagos extends WC_Payment_Gateway
{

	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		// Setup general properties.
		$this->setup_properties();
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		// Get settings.
		$this->title              = $this->get_option('title');
		$this->description        = $this->get_option('description');
		$this->token            = $this->get_option('token');
		$this->id_agreement          = $this->get_option('id_agreement');
		$this->urlServiceConection            = $this->get_option('urlServiceConection');
		$this->urlRedirect            = $this->get_option('urlRedirect');
		$this->instructions       = $this->get_option('instructions');
		$this->enable_for_methods = $this->get_option('enable_for_methods', array());
		$this->enable_for_virtual = $this->get_option('enable_for_virtual', 'yes') === 'yes';

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		// add_action( 'woocommerce_api_confirm_payment', array( $this, 'webhook' ) );
		add_filter('woocommerce_payment_complete_order_status', array($this, 'change_payment_complete_order_status'), 10, 3);
		// Customer Emails.
		add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties()
	{
		$this->id                 = 'payleo';
		// $this->icon               = apply_filters( 'woocommerce_payleo_icon', plugins_url('../assets/icon.png', __FILE__ ) );
		$this->method_title       = __( 'Mundo pagos', 'payleo-payments-woo' );
		$this->token            = __( 'Token', 'payleo-payments-woo' );
		$this->id_agreement          = __( 'Id convenio', 'payleo-payments-woo' );
		$this->urlServiceConection            = __( 'urlServiceConection', 'payleo-payments-woo' );
		$this->urlRedirect            = __( 'urlRedirect', 'payleo-payments-woo' );
		$this->method_description = __( 'Have your customers pay with Mundo pagos Payments.', 'payleo-payments-woo' );
		$this->has_fields         = false;
	}
	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'payleo-payments-woo' ),
				'label'       => __( 'Enable Mundo pagos', 'payleo-payments-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'payleo-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Mundo pagos method description that the customer will see on your checkout.', 'payleo-payments-woo' ),
				'default'     => __( 'Mundo pagos Payments', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'token'             => array(
				'title'       => __( 'Token de seguridad', 'payleo-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'token de seguridad', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'id_agreement'           => array(
				'title'       => __( 'Id. convenio', 'payleo-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Add id convenio mundo pagos', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'urlServiceConection'           => array(
				'title'       => __( 'urlServiceConection', 'payleo-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'urlServiceConection', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'urlRedirect'           => array(
				'title'       => __( 'urlRedirect', 'payleo-payments-woo' ),
				'type'        => 'text',
				'description' => __( 'Add id convenio mundo pagos', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Description', 'payleo-payments-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Mundo pagos Payment method description that the customer will see on your website.', 'payleo-payments-woo' ),
				'default'     => __( 'Mundo pagosPayments before delivery.', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'payleo-payments-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'payleo-payments-woo' ),
				'default'     => __( 'Mundo pagos Payments before delivery.', 'payleo-payments-woo' ),
				'desc_tip'    => true,
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'payleo-payments-woo' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => __( 'If payleo is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'payleo-payments-woo' ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'payleo-payments-woo' ),
				),
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Accept for virtual orders', 'payleo-payments-woo' ),
				'label'   => __( 'Accept payleo if the order is virtual', 'payleo-payments-woo' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available()
	{
		$order          = null;
		$needs_shipping = false;
		// Test if shipping is needed first.
		if (WC()->cart && WC()->cart->needs_shipping()) {
			$needs_shipping = true;
		} elseif (is_page(wc_get_page_id('checkout')) && 0 < get_query_var('order-pay')) {
			$order_id = absint(get_query_var('order-pay'));
			$order    = wc_get_order($order_id);
			// Test if order needs shipping.
			if (0 < count($order->get_items())) {
				foreach ($order->get_items() as $item) {
					$_product = $item->get_product();
					if ($_product && $_product->needs_shipping()) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}
		$needs_shipping = apply_filters('woocommerce_cart_needs_shipping', $needs_shipping);
		// Virtual order, with virtual disabled.
		if (!$this->enable_for_virtual && !$needs_shipping) {
			return false;
		}
		// Only apply if all packages are being shipped via chosen method, or order is virtual.
		if (!empty($this->enable_for_methods) && $needs_shipping) {
			$order_shipping_items            = is_object($order) ? $order->get_shipping_methods() : false;
			$chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

			if ($order_shipping_items) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids($order_shipping_items);
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids($chosen_shipping_methods_session);
			}
			if (!count($this->get_matching_rates($canonical_rate_ids))) {
				return false;
			}
		}
		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings()
	{
		if (is_admin()) {
			// phpcs:disable WordPress.Security.NonceVerification
			if (!isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
				return false;
			}
			if (!isset($_REQUEST['tab']) || 'checkout' !== $_REQUEST['tab']) {
				return false;
			}
			if (!isset($_REQUEST['section']) || 'payleo' !== $_REQUEST['section']) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification
			return true;
		}
		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *$product_name = $item['name'] . "-". $item['product_id']
	 * @return array
	 */
	private function load_shipping_method_options()
	{
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if (!$this->is_accessing_settings()) {
			return array();
		}
		$data_store = WC_Data_Store::load('shipping-zone');
		$raw_zones  = $data_store->get_zones();
		foreach ($raw_zones as $raw_zone) {
			$zones[] = new WC_Shipping_Zone($raw_zone);
		}
		$zones[] = new WC_Shipping_Zone(0);
		$options = array();
		foreach (WC()->shipping()->load_shipping_methods() as $method) {
			$options[$method->get_method_title()] = array();
			// Translators: %1$s shipping method name.
			$options[$method->get_method_title()][$method->id] = sprintf(__('Any &quot;%1$s&quot; method', 'payleo-payments-woo'), $method->get_method_title());
			foreach ($zones as $zone) {
				$shipping_method_instances = $zone->get_shipping_methods();
				foreach ($shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance) {
					if ($shipping_method_instance->id !== $method->id) {
						continue;
					}
					$option_id = $shipping_method_instance->get_rate_id();
					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf(__('%1$s (#%2$s)', 'payleo-payments-woo'), $shipping_method_instance->get_title(), $shipping_method_instance_id);
					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf(_('%1$s &ndash; %2$s', 'payleo-payments-woo'), $zone->get_id() ? $zone->get_zone_name() : _('Other locations', 'payleo-payments-woo'), $option_instance_title);
					$options[$method->get_method_title()][$option_id] = $option_title;
				}
			}
		}
		return $options;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids($order_shipping_items)
	{
		$canonical_rate_ids = array();
		foreach ($order_shipping_items as $order_shipping_item) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}
		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids($chosen_package_rate_ids)
	{
		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();
		if (!empty($chosen_package_rate_ids) && is_array($chosen_package_rate_ids)) {
			foreach ($chosen_package_rate_ids as $package_key => $chosen_package_rate_id) {
				if (!empty($shipping_packages[$package_key]['rates'][$chosen_package_rate_id])) {
					$chosen_rate          = $shipping_packages[$package_key]['rates'][$chosen_package_rate_id];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}
		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return boolean
	 */
	private function get_matching_rates($rate_ids)
	{
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique(array_merge(array_intersect($this->enable_for_methods, $rate_ids), array_intersect($this->enable_for_methods, array_unique(array_map('wc_get_string_before_colon', $rate_ids)))));
	}
 
	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment($order_id)
	{
		global $woocommerce;
		$order = new WC_Order($order_id);
		//setup the request, you can also use CURLOPT_URL
		$ch = curl_init($this->urlServiceConection);

		// Returns the data/output as a string instead of raw data
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$product_name ="";
		// Get the names of the items 
		foreach($order->get_items() as $item) {
			$product_name .= $item['name'] . "-". $item['product_id'] . " ";	
		}
		// build firm with Id agreement, value_total in format double ex: 50.00, name product, currency, token security of agreement
		// *NOTE: If value_total contains ',', parser ',' in '.'.
		// Example
		$firm_init = $this->id_agreement . ":" .number_format($order->get_total(), 2, '.', ' ') . ":" . $product_name . ":" . $order->get_currency() . ":" . $this->token;
		$firm_order = $firm_init . ":" . $order_data['order_key'];
		// Encrypt firm in SHA256 and send in paylaod
		$firmSha256_order = hash('sha256', $firm_order);
		$order->update_meta_data('firm', $firmSha256_order);
		$order_data = $order->get_data();
		//BUILD A BUYERINFO WITH DATA OF BUYER 
		$buyerinfo = new BuyerInfo(
			$order_data['billing']['first_name'],
			$order_data['billing']['last_name'],
			$order_data['billing']['phone'],
			$order_data['billing']['phone'],
			$order_data['billing']['email'],
			$order_data['billing']['address_1'],
			$order_dta['billing']['country']
		);
		
		$firm = $firm_init;
		$firmSha256 = hash('sha256', $firm);
		//BUILD PAYLOAD AND SEND
		$peticionPayShopOnline = array(
			'buyerInfo' => $buyerinfo,
			'value' => $order->get_total(),	// value total order/invoice
			'tax' => $order->get_total_tax(), // tax total order/invoice,
			'description' => 'Compra de ' . $product_name . ' en woocommerce a mundo boletos', //name product, name of producto in processing of pay,
			'tag' => $product_name, //name product, name of producto in processing of pay
			'codeReference' => $order_id,// identificator of order/invoice generate in your plataform
			'codeAgreement' => $this->id_agreement,
			'firm' => $firmSha256, // firm buil and encrypt in SHA256
			'others' => [
				'keyOrder' => $order_data['order_key'] //order_key create in woocommerce
			]
		);
		//Set your auth headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			// 'Authorization: Bearer ' . $token,
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($peticionPayShopOnline));
		// get stringified data/output. See CURLOPT_RETURNTRANSFER
		$response = curl_exec($ch);
		// get info about the request
		$info = curl_getinfo($ch);
		// // close curl resource to free up system resources
		curl_close($ch);
		$response = json_decode($response);
		//Start redirection to https://demo.mundoboletos.com:3232
		if ($response->status == 200) {
			$firmResponse = $response->data->firm; // ENCRYPT SHA256
			$codeReference = $response->data->data->codeReference;
			$order->update_meta_data('codeReference', $codeReference);
			$encryptSha256 = $firm . ":" . $order_id;
			$firmsha256 = hash('sha256', $encryptSha256);
			//validate both firms for security of redirect 
			if ($firmsha256 == $firmResponse) {
				$url = $this->urlRedirect . $response->data->params;
				$order->update_status('pending', __('Esperando pago', 'woocommerce'));
				// 	// Remove cart
				$woocommerce->cart->empty_cart();
				// Return checkout mundo boletos
				return array(
					'result' => 'success',
					'redirect' => $url
				);
			} else {
				print_r ('Firms no valids!');
			}
		} else {
			print_r($response);
		}
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page()
	{
		if ($this->instructions) {
			echo wp_kses_post(wpautop(wptexturize($this->instructions)));
		}
	}

	/**
	 * Change payment complete order status to completed for payleo orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status($status, $order_id = 0, $order = false)
	{
		if ($order && 'payleo' === $order->get_payment_method()) {
			$status = 'completed';
		}
		return $status;
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions($order, $sent_to_admin, $plain_text = false)
	{
		if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
			echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
		}
	}


	public function request()
	{
		// **NOTE**
		//Before creating the connection with world payments, please create the corresponding order, invoice, etc. on your platform.
		//So you can obtain the data that our platform requires for full operation. 
	
		//setup the request, you can also use CURLOPT_URL
		$ch = curl_init('http://demo.mundoboletos.com:2525/commercial/api/shop-online/v1/peticion-pay');

		// Returns the data/output as a string instead of raw data
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// build firm with Id agreement, value_total in format double ex: 50.00, name product, currency, token security of agreement
		// *NOTE: If value_total contains ',', parser ',' in '.'.
		// Example
		$firm = 'Id agreemen in mundo boletos' . ":" . 'Value total order/invoice' . ":" . 'Shorts example conection' . ":" . 'USD' . ":" . 'qwertyuiopasdfghjklzxcvbnm123456';
		// Encrypt firm in SHA256 and send in paylaod
		$firmSha256 = hash('sha256', $firm);
		//BUILD A BUYERINFO WITH DATA OF BUYER 
		$buyerinfo = array(
			'first_name' => 'Buyer first name',
			'last_name' => 'Buyer last name',
			'phone' => 'Buyer phone',
			'dni' => 'Buyer identification',
			'email' => 'Buyer email',
			'address' => 'Buyer address',
			'country' => 'Buyer country'
		);
		//BUILD PAYLOAD AND SEND
		$peticionPayShopOnline = array(
			'buyerInfo' => $buyerinfo,
			'value' => 'Value total order/invoice', // value total order/invoice
			'tax' => 'Tax total order/invoice', // tax total order/invoice
			'description' => 'Buy of ' . 'Name product' . ' in mundo boletos', //name product, name of producto in processing of pay
			'tag' => 'Name product', //name product, name of producto in processing of pay
			'codeReference' => 'Order/Invoice id', // identificator of order/invoice generate in your plataform
			'codeAgreement' => 'Id agreemen in mundo boletos',
			'firm' => $firmSha256, // firm buil and encrypt in SHA256
			'others' => [] //optional
		);
		//Set your auth headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($peticionPayShopOnline));
		// get stringified data/output. See CURLOPT_RETURNTRANSFER
		$response = curl_exec($ch);
		// get info about the request
		$info = curl_getinfo($ch);
		// // close curl resource to free up system resources
		curl_close($ch);
		$response = json_decode($response);
		print_r ($peticionPayShopOnline);
		//Start redirection to https://boletos.mundoboletos.com
		if ($response->succes == true) {
			$firmResponse = $response->data->firm; // ENCRYPT SHA256
			$encryptSha256 = $firm . ":" . 'Order/Invoice id';
			$sha256Firm = hash('sha256', $encryptSha256);
			if ($sha256Firm == $firmResponse) {
				$url =  "https://boletos.mundoboletos.com/payment/ecommerce-pay?params=" . $response->data->params;
				// Return checkout  https://boletos.mundoboletos.com 
				return array(
					'result' => 'success',
					'redirect' => $url
					);
				}
		} else {
		//SHOW ERROS IN RESPONSE.
			print_r($response);
		}
		
	}

}