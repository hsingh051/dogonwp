<?php
/**
 * WooCommerce Gateway Braintree
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Gateway Braintree to newer
 * versions in the future. If you wish to customize WooCommerce Gateway Braintree for your
 * needs please refer to http://docs.woothemes.com/document/braintree/ for more information.
 *
 * @package     WC-Gateway-Braintree/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Braintree Addons class
 *
 * Extends the base braintree gateway class to provide support for subscriptions and pre-orders
 *
 * @since 2.0
 * @extends \WC_Gateway_Braintree
 */
class WC_Gateway_Braintree_Addons extends WC_Gateway_Braintree {


	/**
	 * Load parent gateway and add-on specific hooks
	 *
	 * @since 2.0
	 * @return \WC_Gateway_Braintree_Addons
	 */
	public function __construct() {

		// load parent gateway
		parent::__construct();

		// add subscription support if active
		if ( wc_braintree()->is_subscriptions_active() ) {

			$this->supports = array_merge( $this->supports,
				array(
					'subscriptions',
					'subscription_suspension',
					'subscription_cancellation',
					'subscription_reactivation',
					'subscription_amount_changes',
					'subscription_date_changes',
					// 1.5.x
					'subscription_payment_method_change',
					// 2.0.x
					'multiple_subscriptions',
					//'subscription_payment_method_change_customer', braintree doesn't accept $0 transactions
					'subscription_payment_method_change_admin',
				)
			);

			if ( SV_WC_Plugin_Compatibility::is_wc_subscriptions_version_gte_2_0() ) {

				// 2.0.x

				// process renewal payments
				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_renewal_payment' ), 10, 2 );

				// update the customer/token ID on the subscription when updating a previously failing payment method
				add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );

				// display the current payment method used for a subscription in the "My Subscriptions" table
				add_filter( 'woocommerce_my_subscriptions_payment_method', array( $this, 'maybe_render_subscription_payment_method' ), 10, 3 );

				// don't copy over order-specific meta to the WC_Subscription object during renewal processing
				add_filter( 'wcs_renewal_order_meta', array( $this, 'subscriptions_do_not_copy_order_meta' ) );

				// remove order-specific meta from the Subscription object after the change payment method action
				add_filter( 'woocommerce_subscriptions_process_payment_for_change_method_via_pay_shortcode', array( $this, 'remove_order_meta_from_subscriptions_change_payment' ), 10, 2 );

				// don't copy over order-specific meta to the new WC_Subscription object during upgrade to 2.0.x
				add_filter( 'wcs_upgrade_subscription_meta_to_copy', array( $this, 'do_not_copy_order_meta_during_subscriptions_upgrade' ) );

				// admin change payment method feature
				add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'subscriptions_admin_add_payment_meta' ), 10, 2 );
				add_action( 'woocommerce_subscription_validate_payment_meta_' . $this->id, array( $this, 'subscriptions_admin_validate_payment_meta' ), 10 );

			} else {

				// 1.5.x

				// process scheduled subscription payments
				add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_renewal_payment_1_5' ), 10, 3 );

				// prevent unnecessary order meta from polluting parent renewal orders
				add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_subscription_renewal_order_meta' ), 10, 4 );

				// update the customer payment profile ID on the original order when making payment for a failed automatic renewal order
				add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array( $this, 'update_failing_payment_method_1_5' ), 10, 2 );

				// display the current payment method used for a subscription in the "My Subscriptions" table
				add_filter( 'woocommerce_my_subscriptions_recurring_payment_method', array( $this, 'maybe_render_subscription_payment_method_1_5' ), 10, 3 );
			}
		}

		// add pre-orders support if active
		if ( wc_braintree()->is_pre_orders_active() ) {

			$this->supports = array_merge( $this->supports,
				array(
					'pre-orders',
				)
			);

			// process batch pre-order payments
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment' ) );
		}
	}


	/**
	 * Process payment for an order:
	 * 1) If the order contains a subscription, process the initial subscription payment (could be $0 if a free trial exists)
	 * 2) If the order contains a pre-order, process the pre-order total (could be $0 if the pre-order is charged upon release)
	 * 3) Otherwise use the parent::process_payment() method for regular product purchases
	 *
	 * @since 2.0
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = $this->get_order( $order_id );

		try {

			/* processing subscription */
			if ( wc_braintree()->is_subscriptions_active() && $this->order_contains_subscription( $order ) ) {

				return $this->process_subscription_payment( $order );

				/* processing pre-order */
			} elseif ( wc_braintree()->is_pre_orders_active() && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) ) {

				return $this->process_pre_order_payment( $order );

				/* processing regular product */
			} else {

				return parent::process_payment( $order_id );
			}

		} catch ( WC_Gateway_Braintree_Exception $e ) {

			// mark order as failed, which adds an order note for the admin and displays a generic "payment error" to the customer
			$this->mark_order_as_failed( $order, $e->getMessage() );

			// add detailed debugging information
			$this->add_debug_message( $e->getErrors() );

		} catch ( Braintree_Exception_Authorization $e ) {

			$this->mark_order_as_failed( $order, __( 'Authorization failed, ensure that your API key is correct and has permissions to create transactions.', WC_Braintree::TEXT_DOMAIN ) );

		} catch ( Exception $e ) {

			$this->mark_order_as_failed( $order, sprintf( __( 'Error Type %s', WC_Braintree::TEXT_DOMAIN ), get_class( $e ) ) );
		}
	}


	/**
	 * Returns true if an order contains a Subscription
	 *
	 * @since 2.3.1
	 * @param \WC_Order $order
	 * @return bool
	 */
	private function order_contains_subscription( WC_Order $order ) {

		return SV_WC_Plugin_Compatibility::is_wc_subscriptions_version_gte_2_0() ? wcs_order_contains_subscription( $order ) : WC_Subscriptions_Order::order_contains_subscription( $order->id );
	}


	/**
	 * Process initial payment for a subscription
	 *
	 * @since 2.0
	 * @param \WC_Order $order the order object
	 * @return array
	 */
	private function process_subscription_payment( $order ) {

		// get subscription amount, only for 1.5.x
		if ( ! SV_WC_Plugin_Compatibility::is_wc_subscriptions_version_gte_2_0() ) {
			$order->braintree_order['amount'] = SV_WC_Helper::number_format( WC_Subscriptions_Order::get_total_initial_payment( $order ) );
		}

		// create new braintree customer if needed
		if ( empty( $order->braintree_order['customerId'] ) ) {
			$order = $this->create_customer( $order );
		}

		// save card in vault if customer is using new card
		if ( empty( $order->braintree_order['paymentMethodToken'] ) ) {

			$order = $this->create_credit_card( $order );

		} else {

			// save payment token to order when processing $0 total subscriptions (e.g. those with a free trial)
			if ( 0 == $order->braintree_order['amount'] ) {

				update_post_meta( $order->id, '_wc_braintree_cc_token', $order->braintree_order['paymentMethodToken'] );
			}
		}

		// process transaction (the order amount will be $0 if a free trial exists)
		// note that customer ID & credit card token are saved to order when create_customer() or create_credit_card() are called
		if ( 0 == $order->braintree_order['amount'] || $this->do_transaction( $order ) ) {

			// mark order as having received payment
			$order->payment_complete();

			// for Subscriptions 2.0.x, save payment token and customer ID to subscription object
			if ( SV_WC_Plugin_Compatibility::is_wc_subscriptions_version_gte_2_0() ) {

				// a single order can contain multiple subscriptions
				foreach ( wcs_get_subscriptions_for_order( $order->id ) as $subscription ) {

					// payment token
					if ( ! empty( $order->braintree_order['paymentMethodToken'] ) ) {
						update_post_meta( $subscription->id, '_wc_braintree_cc_token', $order->braintree_order['paymentMethodToken'] );
					}

					// customer ID
					if ( ! empty( $order->braintree_order['customerId'] ) ) {
						update_post_meta( $subscription->id, '_wc_braintree_customer_id', $order->braintree_order['customerId'] );
					}
				}
			}

			WC()->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}


	/**
	 * Process initial payment for a pre-order
	 *
	 * @since 2.0
	 * @param \WC_Order $order the order object
	 * @throws WC_Gateway_Braintree_Exception
	 * @return array
	 */
	private function process_pre_order_payment( $order ) {

		// do pre-authorization
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order->id ) ) {

			// for an existing customer using a saved credit card, there's no way in braintree to simply
			// perform a $1 auth/void, so assume the saved card is valid already. If there is an issue
			// with the saved card, the pre-order payment will fail upon release anyway

			// exceptions are thrown if either the create_customer() or create_credit_card() method fails

			// add the braintree customer ID to the order, or create a new braintree customer and add/verify the new card added if needed
			if ( ! empty( $order->braintree_order['customerId'] ) ) {
				update_post_meta( $order->id, '_wc_braintree_customer_id', $order->braintree_order['customerId'] );
			} else {
				$order = $this->create_customer( $order );
			}

			// add the braintree credit card token to the order, or create a new credit card for the customer and verify it if needed
			if ( ! empty( $order->braintree_order['paymentMethodToken'] ) ) {
				update_post_meta( $order->id, '_wc_braintree_cc_token', $order->braintree_order['paymentMethodToken'] );
			} else {
				$order = $this->create_credit_card( $order );
			}

			// mark order as pre-ordered / reduce order stock
			WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

			// empty cart
			WC()->cart->empty_cart();

			// redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);

		} else {

			// charged upfront (or paying for a newly-released pre-order with the gateway), process just like regular product
			return parent::process_payment( $order->id );
		}
	}


	/**
	 * Process a pre-order payment when the pre-order is released
	 *
	 * @since 2.0
	 * @param \WC_Order $order original order containing the pre-order
	 * @throws WC_Gateway_Braintree_Exception
	 * @throws Exception
	 */
	public function process_pre_order_release_payment( $order ) {

		try {

			// set order defaults
			$order = $this->get_order( $order->id );

			// set braintree customer ID -- note this is set from the order meta instead of user meta as pre-orders supports guest pre-orders
			$order->braintree_order['customerId'] = get_post_meta( $order->id, '_wc_braintree_customer_id', true );

			// set credit card token
			$order->braintree_order['paymentMethodToken'] = get_post_meta( $order->id, '_wc_braintree_cc_token', true );

			// required
			if ( ! $order->braintree_order['customerId'] || ! $order->braintree_order['paymentMethodToken'] ) {
				throw new Exception( __( 'Pre-Order Release: Customer ID or Credit Card Token is missing.', WC_Braintree::TEXT_DOMAIN ) );
			}

			$response = Braintree_Transaction::sale( $order->braintree_order );

			// check for success
			if ( $response->success ) {

				// add order note
				$order->add_order_note( sprintf( __( 'Braintree Pre-Order Release Payment Approved (Transaction ID: %s) ', WC_Braintree::TEXT_DOMAIN ), $response->transaction->id ) );

				// save transaction info as order meta
				add_post_meta( $order->id, '_wc_braintree_trans_id',       $response->transaction->id );
				add_post_meta( $order->id, '_wc_braintree_trans_env',      $this->get_environment() );
				add_post_meta( $order->id, '_wc_braintree_card_type',      $response->transaction->creditCardDetails->cardType );
				add_post_meta( $order->id, '_wc_braintree_card_last_four', $response->transaction->creditCardDetails->last4 );
				add_post_meta( $order->id, '_wc_braintree_card_exp_date',  $response->transaction->creditCardDetails->expirationDate );

				// complete payment
				$order->payment_complete();

			} else {

				// failure
				throw new WC_Gateway_Braintree_Exception( 'transaction', $response );
			}

		} catch ( WC_Gateway_Braintree_Exception $e ) {

			// mark order as failed, which adds an order note for the admin and displays a generic "payment error" to the customer
			$this->mark_order_as_failed( $order, $e->getMessage() );

			// add detailed debugging information
			$this->add_debug_message( $e->getErrors() );

		} catch ( Braintree_Exception_Authorization $e ) {

			$this->mark_order_as_failed( $order, __( 'Authorization failed, ensure that your API key is correct and has permissions to create transactions.', WC_Braintree::TEXT_DOMAIN ) );

		} catch ( Exception $e ) {

			$this->mark_order_as_failed( $order, sprintf( __( '%s - Error Type %s', WC_Braintree::TEXT_DOMAIN ), $e->getMessage(), get_class( $e ) ) );
		}
	}


	/** Subscription 2.0.x Support ********************************************/


	/**
	 * Process a subscription renewal payment for Subscriptions 2.0.x
	 *
	 * @since 2.3.1
	 * @param string|float $amount_to_charge
	 * @param \WC_Order $order
	 * @return array|void
	 */
	public function process_subscription_renewal_payment( $amount_to_charge, $order ) {

		$this->subscriptions_renewal_payment_total = $amount_to_charge;

		$token = get_post_meta( $order->id, '_wc_braintree_cc_token', true );

		// token is required
		if ( empty( $token ) ) {

			$this->mark_order_as_failed( $order, __( 'Subscription Renewal: Payment Token is missing.', WC_Braintree::TEXT_DOMAIN ) );

			return;
		}

		// add subscriptions-specific data
		add_filter( 'wc_braintree_get_order', array( $this, 'subscriptions_get_order' ) );

		return $this->process_payment( $order->id );
	}


	/**
	 * Add subscription renewal amount, payment token, and customer ID to the
	 * Braintree order prior to renewal
	 *
	 * @since 2.3.1
	 * @param \WC_Order $order
	 * @return mixed
	 */
	public function subscriptions_get_order( $order ) {

		$order->braintree_order['amount'] = $this->subscriptions_renewal_payment_total;

		$order->braintree_order['paymentMethodToken'] = get_post_meta( $order->id, '_wc_braintree_cc_token', true );

		// set customer ID from order if available, otherwise this wil fallback to user meta
		if ( $customer_id = get_post_meta( $order->id, '_wc_braintree_customer_id', true ) ) {
			$order->braintree_order['customerId'] = $customer_id;
		}

		return $order;
	}


	/**
	 * Don't copy order-specific meta to renewal orders from the WC_Subscription
	 * object. Generally the subscription object should not have any order-specific
	 * meta (aside from `payment_token` and `customer_id`) as they are not
	 * copied during the upgrade (see do_not_copy_order_meta_during_upgrade()), so
	 * this method is more of a fallback in case meta accidentally is copied.
	 *
	 * @since 2.3.1
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function subscriptions_do_not_copy_order_meta( $order_meta ) {

		$meta_keys = $this->get_order_specific_meta_keys();

		foreach ( $order_meta as $index => $meta ) {

			if ( in_array( $meta['meta_key'], $meta_keys ) ) {
				unset( $order_meta[ $index ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Don't copy order-specific meta to the new WC_Subscription object during
	 * upgrade to 2.0.x. This only allows the `_wc_braintree_cc_token` and
	 * `_wc_braintree_customer_id` meta to be copied.
	 *
	 * @since 2.3.1
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function do_not_copy_order_meta_during_subscriptions_upgrade( $order_meta ) {

		foreach ( $this->get_order_specific_meta_keys() as $meta_key ) {

			if ( isset( $order_meta[ $meta_key ] ) ) {
				unset( $order_meta[ $meta_key ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Remove order meta (like trans ID) that's added to a Subscription object
	 * during the change payment method flow, which uses WC_Payment_Gateway::process_payment(),
	 * thus some order-specific meta is added that is undesirable to have copied
	 * over to renewal orders.
	 *
	 * @since 2.3.1
	 * @param array $result process_payment() result, unused
	 * @param \WC_Subscription $subscription subscription object
	 * @return array
	 */
	public function remove_order_meta_from_subscriptions_change_payment( $result, $subscription ) {

		// remove order-specific meta
		foreach ( $this->get_order_specific_meta_keys() as $meta_key ) {
			delete_post_meta( $subscription->id, $meta_key );
		}

		// if the payment method has been changed to another gateway, additionally remove the old payment token and customer ID meta
		if ( $subscription->payment_method !== $this->id && $subscription->old_payment_method === $this->id ) {
			delete_post_meta( $subscription->id, '_wc_braintree_customer_id' );
			delete_post_meta( $subscription->id, '_wc_braintree_cc_token' );
		}

		return $result;
	}


	/**
	 * Update the payment token and optional customer ID for a subscription after a customer
	 * uses this gateway to successfully complete the payment for an automatic
	 * renewal payment which had previously failed.
	 *
	 * @since 2.3.1
	 * @param \WC_Subscription $subscription subscription being updated
	 * @param \WC_Order $renewal_order order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ) {

		update_post_meta( $subscription->id, '_wc_braintree_customer_id', get_post_meta( $renewal_order->id, '_wc_braintree_customer_id', true ) );
		update_post_meta( $subscription->id, '_wc_braintree_cc_token',    get_post_meta( $renewal_order->id, '_wc_braintree_cc_token', true ) );
	}


	/**
	 * Get the order-specific meta keys that should not be copied to the WC_Subscription
	 * object during upgrade to 2.0.x or during change payment method actions
	 *
	 * @since 2.3.1
	 * @return array
	 */
	protected function get_order_specific_meta_keys() {

		return array(
			'_wc_braintree_trans_id',
			'_wc_braintree_trans_env',
			'_wc_braintree_card_type',
			'_wc_braintree_card_last_four',
			'_wc_braintree_card_exp_date',
		);
	}


	/**
	 * Render the payment method used for a subscription in the "My Subscriptions" table
	 *
	 * @since 2.3.1
	 * @param string $payment_method_to_display the default payment method text to display
	 * @param \WC_Subscription $subscription
	 * @return string the subscription payment method
	 */
	public function maybe_render_subscription_payment_method( $payment_method_to_display, $subscription ) {

		// bail for other payment methods
		if ( $this->id !== $subscription->payment_method ) {
			return $payment_method_to_display;
		}

		$card = $this->get_saved_card( get_post_meta( $subscription->id, '_wc_braintree_cc_token', true ) );

		if ( $card ) {

			$payment_method_to_display = sprintf( __( 'Via %s ending in %s', WC_Braintree::TEXT_DOMAIN ), $card->cardType, $card->last4 );
		}

		return $payment_method_to_display;
	}


	/**
	 * Include the payment meta data required to process automatic recurring
	 * payments so that store managers can manually set up automatic recurring
	 * payments for a customer via the Edit Subscriptions screen in 2.0.x
	 *
	 * @since 2.3.1
	 * @param array $meta associative array of meta data required for automatic payments
	 * @param \WC_Subscription $subscription subscription object
	 * @return array
	 */
	public function subscriptions_admin_add_payment_meta( $meta, $subscription ) {

		$meta[ $this->id ] = array(
			'post_meta' => array(
				'_wc_braintree_customer_id' => array(
					'value' => get_post_meta( $subscription->id, '_wc_braintree_customer_id', true ),
					'label' => __( 'Customer ID', WC_Braintree::TEXT_DOMAIN ),
				),
				'_wc_braintree_cc_token'   => array(
					'value' => get_post_meta( $subscription->id, '_wc_braintree_cc_token', true ),
					'label' => __( 'Payment Token', WC_Braintree::TEXT_DOMAIN ),
				),
			)
		);

		return $meta;
	}


	/**
	 * Validate the payment meta data required to process automatic recurring
	 * payments so that store managers can manually set up automatic recurring
	 * payments for a customer via the Edit Subscriptions screen in 2.0.x
	 *
	 * @since 2.3.1
	 * @param array $meta associative array of meta data required for automatic payments
	 * @throws Exception if payment token or customer ID is missing or blank
	 */
	public function subscriptions_admin_validate_payment_meta( $meta ) {

		// customer ID
		if ( empty( $meta['post_meta']['_wc_braintree_customer_id']['value'] ) ) {
			throw new Exception( __( 'Customer ID is required.', WC_Braintree::TEXT_DOMAIN ) );
		}

		// payment token
		if ( empty( $meta['post_meta']['_wc_braintree_cc_token']['value'] ) ) {
			throw new Exception( __( 'Payment Token is required.', WC_Braintree::TEXT_DOMAIN ) );
		}
	}


	/** Subscription 1.5.x Support ********************************************/


	/**
	 * Process subscription renewal
	 *
	 * @since 2.0
	 * @param float $amount_to_charge subscription amount to charge, could include multiple renewals if they've previously failed and the admin has enabled it
	 * @param \WC_Order $order original order containing the subscription
	 * @param int $product_id the ID of the subscription product
	 * @throws WC_Gateway_Braintree_Exception
	 * @throws Exception
	 */
	public function process_subscription_renewal_payment_1_5( $amount_to_charge, $order, $product_id ) {

		try {

			// set order defaults
			$order = $this->get_order( $order->id );

			// set the amount to charge
			$order->braintree_order['amount'] = $amount_to_charge;

			// set credit card token
			$order->braintree_order['paymentMethodToken'] = get_post_meta( $order->id, '_wc_braintree_cc_token', true );

			// required
			if ( ! $order->braintree_order['customerId'] || ! $order->braintree_order['paymentMethodToken'] ) {
				throw new Exception( __( 'Subscription Renewal: Customer ID or Credit Card Token is missing.', WC_Braintree::TEXT_DOMAIN ) );
			}

			// don't save shipping addresses to vault for renewals
			unset( $order->braintree_order['options']['storeShippingAddressInVault'] );

			$response = Braintree_Transaction::sale( $order->braintree_order );

			// check for success
			if ( $response->success ) {

				// add order note
				$order->add_order_note( sprintf( __( 'Braintree Subscription Renewal Payment Approved (Transaction ID: %s) ', WC_Braintree::TEXT_DOMAIN ), $response->transaction->id ) );

				// update subscription
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order, $product_id );

			} else {

				// failure
				throw new WC_Gateway_Braintree_Exception( 'transaction', $response );
			}

		} catch ( WC_Gateway_Braintree_Exception $e ) {

			// mark order as failed, which adds an order note for the admin and displays a generic "payment error" to the customer
			$this->mark_order_as_failed( $order, $e->getMessage() );

			// add detailed debugging information
			$this->add_debug_message( $e->getErrors() );

			// update subscription
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );

		} catch ( Braintree_Exception_Authorization $e ) {

			$this->mark_order_as_failed( $order, __( 'Authorization failed, ensure that your API key is correct and has permissions to create transactions.', WC_Braintree::TEXT_DOMAIN ) );

			// update subscription
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );

		} catch ( Exception $e ) {

			$this->mark_order_as_failed( $order, sprintf( __( '%s - Error Type %s', WC_Braintree::TEXT_DOMAIN ), $e->getMessage(), get_class( $e ) ) );

			// update subscription
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		}
	}


	/**
	 * Don't copy over braintree-specific order meta when creating a parent renewal order
	 *
	 * @since 2.0
	 * @param array $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string
	 */
	public function remove_subscription_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

		if ( 'parent' == $new_order_role )
			$order_meta_query .= " AND `meta_key` NOT IN ("
			  . "'_wc_braintree_trans_id', "
			  . "'_wc_braintree_trans_env', "
			  . "'_wc_braintree_card_type', "
			  . "'_wc_braintree_card_last_four', "
			  . "'_wc_braintree_card_exp_date', "
			  . "'_wc_braintree_cc_token', "
			  . "'_wc_braintree_customer_id' )";

		return $order_meta_query;
	}


	/**
	 * Update the profile IDs for a subscription after a customer used Braintree to successfully complete the payment
	 * for an automatic renewal payment which had previously failed.
	 *
	 * @since 2.0.4
	 * @param WC_Order $original_order The original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 */
	public function update_failing_payment_method_1_5( WC_Order $original_order, WC_Order $renewal_order ) {

		update_post_meta( $original_order->id, '_wc_braintree_customer_id', get_post_meta( $renewal_order->id, '_wc_braintree_customer_id', true ) );
		update_post_meta( $original_order->id, '_wc_braintree_cc_token',    get_post_meta( $renewal_order->id, '_wc_braintree_cc_token', true ) );
	}


	/**
	 * Render the payment method used for a subscription in the "My Subscriptions" table
	 *
	 * @since 2.0.4
	 * @param string $payment_method_to_display the default payment method text to display
	 * @param array $subscription_details the subscription details
	 * @param WC_Order $order the order containing the subscription
	 * @return string the subscription payment method
	 */
	public function maybe_render_subscription_payment_method_1_5( $payment_method_to_display, $subscription_details, WC_Order $order ) {

		// bail for other payment methods
		if ( $this->id !== $order->recurring_payment_method ) {
			return $payment_method_to_display;
		}

		$card = $this->get_saved_card( get_post_meta( $order->id, '_wc_braintree_cc_token', true ) );

		if ( is_object( $card ) ) {
			$payment_method_to_display = sprintf( __( 'Via %s ending in %s', WC_Braintree::TEXT_DOMAIN ), $card->cardType, $card->last4 );
		}

		return $payment_method_to_display;
	}


}
