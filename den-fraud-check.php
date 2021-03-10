<?php
/*
Plugin Name: Den/Elixirr - Fraud Check plugin skill assessment
Description: Fires after being redirected from payment gateway back to site - submits order details to a fraud check system and updates the order status based on API response
Version: 1.0
Author: Leslie Albrecht
*/
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

include_once(plugin_dir_path( __FILE__ ) . "includes/admin.php");
if ( ! class_exists( 'DenFraudChecker' ) ) :
	class DenFraudChecker {
		private $order;

		private $api_url;
		private $api_user;
		private $api_password;


		public function __construct() {
			$this->api_url 		  = empty(get_option('api_url')) ? false : get_option('api_url');
			$this->api_user 		= empty(get_option('api_user')) ? false : get_option('api_user');
			$this->api_password = empty(get_option('api_password')) ? false : get_option('api_password');
			//note that this will not trigger for any of the WooCommerce core payment methods...

			add_action( 'woocommerce_new_order', array($this, 'set_status_on_hold'), 20);
			add_action('woocommerce_payment_complete', array($this, 'get_gateway_response'), 20);

			add_action('wp_enqueue_scripts', array($this, 'load_assets'), 20);
			add_action( 'admin_enqueue_scripts', array($this, 'load_admin_assets'), 20);

			if (is_admin()) {
				register_activation_hook(__FILE__, array($this, 'plugin_activation'));
				register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));
			}
		}
		/*
		 *
		 */
		function set_status_on_hold($order_id) {
			$this->order = wc_get_order($order_id);
			$this->order->update_status("on-hold");
		}
		/**
		 * @param $order_id
		 * @return
		 * Prepare query for submission to fraud-checking API and update order status based on response
		 */
		function get_gateway_response( $order_id ) {
			$this->order 		  = wc_get_order($order_id);
			$payload 		 	    = $this->build_api_payload();
			$response	   	    = $this->submit_api_payload($payload);

			//expectation here is that we're dealing with a JSON response - update this statement as neccesary if dealing with SOAP or other
			$decoded_response = json_decode($response['body']);
			if ($decoded_response->success === true) {
				$this->order->update_status("failed");
			} else {
				$this->order->update_status("failed");
			}
		}

		/**
		 * @param $payload
		 * @return mixed|null
		 */
		function submit_api_payload($payload) {
			$response = wp_remote_post( $this->api_url,
				array(
					'method'      => 'POST',
					'timeout'     => 10,
					'body'        => $payload,
					'headers' 		=> array(
						'Authorization' => 'Basic '.base64_encode( $this->api_user . ':' . $this->api_password)
					)
				));

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				echo $error_message;
				die();
			}

			return $response;
		}

		/**
		 * @return string
		 * Data needs to be present in payload:
		 * Customer IP
		 * Order ID
		 * Order Session ID/cart hash
		 * Payment method
		 * Total price
		 */
		function build_api_payload() {
			$form_data 	 = array(
				'customer_ip' 		 => $this->order->customer_ip_address,
				'order_id' 			   => $this->order->id,
				'order_session_id' => $this->order->cart_hash,
				'payment_method'   => $this->order->payment_method,
				'total_price' 		 => $this->order->total
			);

			return json_encode(array($form_data));
		}
		/**
		 * 'Ere be startup methods! - move into abstract class if time allows or just delete if not being used when done
		 */
		/**
		 * boilerplate function - add functions to create any database tables, encryption keys, etc or any other once-off-at-plugin-activation-only events
		 */
		function plugin_activation() {
			return true;
		}
		/**
		 * boilerplate function - add functions to do cleanup - delete plugin-specific files, drop tables, etc
		 */
		function plugin_deactivation() {
			return true;
		}

		/**
		 *
		 */
		function load_assets() {
//			wp_enqueue_script('den_form_handler', plugin_dir_url(__FILE__) . 'js/main.js');
//			wp_enqueue_style('style', plugin_dir_url(__FILE__) . 'css/main.css');
		}

		/**
		 * admin dashboard startup methods - move into abstract class if time allows or just delete if not being used when done
		 */
		function load_admin_assets() {
			//wp_enqueue_script('den_admin_handler', plugin_dir_url(__FILE__) . 'js/admin.js');
			//wp_enqueue_style('admin_style', plugin_dir_url(__FILE__) . 'css/admin.css');
		}
	}

endif;

add_action( 'plugins_loaded', function() {
	new DenFraudChecker();
	new DenFraudCheckerAdmin();
});