<?php
/*
 Main Canada Post Tracking Class
woocommerce_cpwebservice_tracking.php

Copyright (c) 2014 Jamez Picard

*/
class woocommerce_cpwebservice_tracking
{

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return woocommerce_cpwebservice_tracking
	 */
	function __construct() {
		$this->init();
	}

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		$default_options = (object) array('enabled'=>'no', 'title'=>'Canada Post', 'api_user'=>'', 'api_key'=>'','account'=>'','contractid'=>'','source_postalcode'=>'','mode'=>'live', 'display_errors'=>false, 'delivery'=>'', 'margin'=>'', 'packageweight'=>floatval('0.02'), 'boxes_enable'=> false, 'lettermail_enable'=> false, 'shipping_tracking'=> true, 'email_tracking'=> true, 'log_enable'=>false,'lettermail_limits'=>false,'lettermail_maxlength'=>'','lettermail_maxwidth'=>'','lettermail_maxheight'=>'', 'tracking_icons'=> true);
		$this->options		= get_option('woocommerce_cpwebservice', $default_options);
		$this->options		= (object) array_merge((array) $default_options, (array) $this->options); // ensure all keys exist, as defined in default_options.
		$this->enabled		= $this->options->shipping_tracking && ( !empty($this->options->api_user) && !empty($this->options->api_key) );

		if ($this->enabled) {
			// Actions
			add_action( 'add_meta_boxes', array(&$this, 'add_tracking_details_box') );
			add_action('wp_ajax_cpwebservice_update_order_tracking', array(&$this, 'update_order_tracking'));
			add_action('woocommerce_order_details_after_order_table',  array(&$this, 'add_tracking_details_customer') );
			add_action('woocommerce_email_after_order_table',  array(&$this, 'add_tracking_details_customer') );
		}

	}

	// Customer My Order page displays tracking information.
	public function add_tracking_details_customer($order) {
		$post_id = $order->id;
		//if ($order->status!='pending'){
		// Lookup Shipping method used. If Canada Post, then look for postmeta with a Tracking Number.
		$trackingPin = get_post_meta( $post_id, '_cpwebservice_tracking', true);
		$trackingData = array();
			
		if (!empty($trackingPin) && is_array($trackingPin)){
				
			foreach($trackingPin as $pin){
				// Does cached lookup
				$trackingData[] = $this->lookup_tracking($post_id, $pin);
			}
				
			echo '<header><h2>'.__( 'Order Shipping Tracking', 'woocommerce-canadapost-webservice' ).'</h2></header>';
			echo $this->display_tracking($trackingData, $post_id, false, false); // does not display admin btns.
				
		}
		//}
	}

	/* Adds a box to the main column on the Post and Page edit screens */
	public function add_tracking_details_box() {
		add_meta_box( 'cpwebservice_tracking', __( 'Order Shipping Tracking', 'woocommerce-canadapost-webservice' ),  array(&$this,'display_tracking_view'), 'shop_order', 'normal', 'default' );
	}

	public function display_tracking_view(){
		global $post_id;
		?>
		<div id="cpwebservice_tracking_result">
		<?php 
		// Lookup Shipping method used. If Canada Post, then look for postmeta with a Tracking Number.
		$trackingPin = get_post_meta( $post_id, '_cpwebservice_tracking', true);
		
		$trackingData = array();
		
		if (!empty($trackingPin) && is_array($trackingPin)){
			
			foreach($trackingPin as $pin){
				// Does cached lookup 
				$trackingData[] = $this->lookup_tracking($post_id, $pin);
			}
	
			echo $this->display_tracking($trackingData, $post_id, false, true);
			
		}
		?>
		</div>
		<ul>
		<li><img src="<?php echo plugins_url( 'img/canada-post.jpg' , dirname(__FILE__) ); ?>" style="vertical-align:middle" /> <input type="text" class="input-text" size="22" name="cpwebservice_trackingid" id="cpwebservice_trackingid" placeholder="" value="" /> 
		<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=cpwebservice_update_order_tracking&order_id=' . $post_id ), 'cpwebservice_update_order_tracking' ); ?>&trackingno=" class="button tips cpwebservice-tracking" target="_blank" title="<?php _e( 'Add Tracking Pin', 'woocommerce-canadapost-webservice' ); ?>" data-tip="<?php _e( 'Add Tracking Pin', 'woocommerce-canadapost-webservice' ); ?>">
		<?php _e( 'Add Tracking Pin', 'woocommerce-canadapost-webservice' ); ?> 
		</a> <img src="<?php admin_url(); ?>/wp-admin/images/wpspin_light.gif" alt="" class="cpwebservice_ajaxsave" style="display: none;" /></li>
		</ul>
		
		<?php wp_nonce_field( plugin_basename( __FILE__ ), 'cpwebservice_tracking_noncename' ); ?>
		<script type="text/javascript">
					jQuery(document).ready(function($) {
						$('.cpwebservice-tracking').on('click', function(event) {
							
							var url = $(this).attr('href') + $('input#cpwebservice_trackingid').val();
							// ajax request.
							$('img.cpwebservice_ajaxsave').show();
							$.get(url,function(data){
								if (data!='Duplicate Pin.') {
									if (data.indexOf('<table')==0){
										$('#cpwebservice_tracking_result').html(data);
									} else {
										$('#cpwebservice_tracking_result table').append(data);
									}
								}
								$('img.cpwebservice_ajaxsave').hide();
							});

							return false;
						});
						$('#cpwebservice_tracking_result').on('click','.cpwebservice_refresh',function() {
							var url = $(this).attr('href');
							var pin = $(this).data('pin');
							$('img.cpwebservice_ajaxsave').show();
							$.get(url,function(data){
								$('#cpwebservice_tracking_result').find('.cpwebservice_track_'+pin).replaceWith(data);
								$('img.cpwebservice_ajaxsave').hide();
							});
							return false;
						});
						$('#cpwebservice_tracking_result').on('click','.cpwebservice_remove',function() {
							var url = $(this).attr('href');
							var pin = $(this).data('pin');
							$('img.cpwebservice_ajaxsave').show();
							$.get(url,function(data){
								$('#cpwebservice_tracking_result').find('.cpwebservice_track_'+pin).remove();
								$('img.cpwebservice_ajaxsave').hide();
							});
							return false;
						});
					});
		</script>
		<?php 
		
	}
	
	/* Does Lookup & Displays Tracking information */
	public function display_tracking($trackingData, $post_id, $only_rows=false, $display_buttons=false){
		
		// Locale for Link to CP.
		// $locale = 'en' : 'fr';
		if (defined('ICL_LANGUAGE_CODE')){
			$locale = (ICL_LANGUAGE_CODE=='fr') ? 'fr':'en'; // 'en' is default
		} else if (WPLANG == 'fr_FR'){
			$locale = 'fr';
		} else {
			$locale = 'en';
		}
		
		// Display Tracking info:
		$html = '';
		if (count($trackingData) > 0){
			
			$html.= $only_rows ? '' : '<table class="widefat fixed"><tr>'.($display_buttons ? '<th></th>' : '').'<th>'. __( 'Tracking Number', 'woocommerce-canadapost-webservice' ).'</th><th>'. __( 'Event', 'woocommerce-canadapost-webservice' ).'</th><th>'. __( 'Shipping Service', 'woocommerce-canadapost-webservice' ).'</th><th>'. __( 'Shipped', 'woocommerce-canadapost-webservice' ).'</th><th>'. __( 'Delivery', 'woocommerce-canadapost-webservice' ).'</th><th>'. __( 'Reference', 'woocommerce-canadapost-webservice' ).'</th></tr>';
			foreach ($trackingData as $trackingRow) {
				if (count($trackingRow) > 0){
					
					foreach($trackingRow as $track){
						$html.='<tr class="cpwebservice_track_'.esc_attr($track['pin']).'">';
						if ($display_buttons) {
							$html.='<td><a href="'.wp_nonce_url( admin_url( 'admin-ajax.php?action=cpwebservice_update_order_tracking&refresh_row=1&order_id=' . $post_id.'&trackingno='.esc_attr($track['pin']) ), 'cpwebservice_update_order_tracking' ).'" class="button cpwebservice_refresh" data-pin="'.esc_attr($track['pin']).'">'.__('Update','woocommerce-canadapost-webservice').'</a> ';
							$html.='<a href="'.wp_nonce_url( admin_url( 'admin-ajax.php?action=cpwebservice_update_order_tracking&remove_tracking=1&order_id=' . $post_id.'&trackingno='.esc_attr($track['pin']) ), 'cpwebservice_update_order_tracking' ).'" class="button cpwebservice_remove" data-pin="'.esc_attr($track['pin']).'">'.__('Remove','woocommerce-canadapost-webservice').'</a></td>';
						}
						$html.='<td class="shipping-trackingno"><a href="https://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber='.esc_attr($track['pin']).'&LOCALE='.$locale.'" target="_blank">';
						if ($this->options->tracking_icons) { $html.= '<img src="'.plugins_url( 'img/shipped.png' , dirname(__FILE__) ).'" width="16" height="16" border="0" style="vertical-align:middle" alt="Tracking" /> '; }
						$html.= esc_html($track['pin']) . '</a></td>';
						if (!empty($track['event-description']) && !empty($track['event-date-time'])){
							$html.='<td class="shipping-eventinfo">' . esc_html($track['event-description']) . '<br />' .esc_html($this->format_cp_time($track['event-date-time'])) . ' ' . esc_html($track['event-location']) . '</td>';
							$html.='<td class="shipping-servicename">';
							if ($this->options->tracking_icons) { $html.= '<img src="'.plugins_url( 'img/ship_canadapost.png' , dirname(__FILE__) ).'"  style="vertical-align:middle" /> ';} else { $html.= __('Canada Post','woocommerce-canadapost-webservice').' '; }
							$html.= esc_html($track['service-name']).'</td>';
							$html.='<td class="shipping-date">Shipped on <strong>'.esc_html($track['mailed-on-date']) . '</strong><br />'.esc_html($track['origin-postal-id']).' to '.esc_html($track['destination-postal-id']).'</td>';
							if ($track['actual-delivery-date']) { 
								$html.= '<td class="shipping-delivered">' . __('Delivered','woocommerce-canadapost-webservice').': ' . esc_html($track['actual-delivery-date']) . '</td>';
							} else if ($track['expected-delivery-date']) {
								$html.= '<td class="shipping-expected">' . __('Expected Delivery','woocommerce-canadapost-webservice').': ' . esc_html($track['expected-delivery-date']) . '</td>';
							} else {
								$html.='<td></td>';
							}
							$html.='<td class="shipping-refno">'.esc_html($track['customer-ref-1']) . '</td>';
						} else {
							$html.='<td colspan="5" class="shipping-info">'. __( 'No Tracking Data Found', 'woocommerce-canadapost-webservice' ).'.</td></tr>';
						}
						$html.='</tr>';
					}
				}
			}
			$html .= $only_rows ? '' : '</table>';
			
		} 
		// Return display Html
		return $html;
	}
	
	public function lookup_tracking($post_id, $trackingPin, $refresh=false){
		if (!empty($trackingPin)) {
			// Get post meta (cached) data
			$trackingData = get_post_meta( $post_id,'cpwebservice_tracking_'.$post_id.'_'.$trackingPin, true);
			
			// If data is older than 4 hrs but less than 1 week, auto-update.
			if (!empty($trackingData) && is_array($trackingData) && isset($trackingData[0]['update-date-time'])){
				$update = intval($trackingData[0]['update-date-time']);
				if ($update > 0){
					$diff = time() - $update;
					if ($diff > 14400 && $diff < 604800){ // More then 4 hrs but less than 7 days in seconds
						$refresh = true;
					}
				}
			}
			
			// Run Live Lookup
			if (empty($trackingData) || $refresh){
	
				// Live Lookup at Canada Post.
				$trackingData = array();
								
				// Options Data
				$username = $this->options->api_user;
				$password = $this->options->api_key;
				
				// REST URL
				$service_url = ($this->options->mode=='live') ? 'https://soa-gw.canadapost.ca/vis/track/pin/{pin}/summary' : 'https://ct.soa-gw.canadapost.ca/vis/track/pin/{pin}/summary'; // dev.  prod:
				
				// display errors
				$display_errors = ($this->options->display_errors ? true : false);

				// Service Language: (English or Francais) sent as Accept-language header with a value of 'fr-CA' or 'en-CA'
				// If using WPML:
				if (defined('ICL_LANGUAGE_CODE')){
					$service_language = (ICL_LANGUAGE_CODE=='fr') ? 'fr-CA':'en-CA'; // 'en-CA' is default
				} else if (WPLANG == 'fr_FR'){
					$service_language = 'fr-CA';
				} else {
					$service_language = 'en-CA';
				}
				

				try {

					// Set tracking number in REST request url
					$service_url = str_replace("{pin}",$trackingPin,$service_url);

					$curl = curl_init($service_url); // Create REST Request
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
					curl_setopt($curl, CURLOPT_CAINFO, CPWEBSERVICE_PLUGIN_PATH . '/cert/cacert.pem'); // Signer Certificate in PEM format
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
					curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept:application/vnd.cpc.track+xml', 'Accept-Language:'.$service_language));
					$curl_response = curl_exec($curl); // Execute REST Request
					if(curl_errno($curl) && $display_errors){
						echo 'Curl error: ' . curl_error($curl) . "\n";
					}
					
					if ($display_errors){
						echo 'HTTP Response Status: ' . curl_getinfo($curl,CURLINFO_HTTP_CODE) . "\n";
					}
					
					curl_close($curl);
					
					// Using SimpleXML to parse xml response
					libxml_use_internal_errors(true);
					$xml = simplexml_load_string($curl_response);
					if (!$xml && $display_errors) {
						echo 'Failed loading XML' . "\n";
						echo $curl_response . "\n";
						foreach(libxml_get_errors() as $error) {
							echo "\t" . $error->message;
						}
					} else if ($xml) {
						
						$trackingSummary = $xml->children('http://www.canadapost.ca/ws/track');
						if ( $trackingSummary->{'pin-summary'} ) {
							
							foreach ( $trackingSummary as $pinSummary ) {
								$row['pin'] = (string)$pinSummary->{'pin'};
								$row['mailed-on-date'] =  (string)$pinSummary->{'mailed-on-date'};
								$row['event-description'] =  (string)$pinSummary->{'event-description'};
								$row['origin-postal-id'] =  (string)$pinSummary->{'origin-postal-id'};
								$row['destination-postal-id'] =  (string)$pinSummary->{'destination-postal-id'};
								$row['destination-province'] = (string) $pinSummary->{'destination-province'};
								$row['service-name'] =  (string)$pinSummary->{'service-name'};
								$row['expected-delivery-date'] =  (string)$pinSummary->{'expected-delivery-date'};
								$row['actual-delivery-date'] = (string) $pinSummary->{'actual-delivery-date'};
								$row['event-date-time'] = (string) $pinSummary->{'event-date-time'};
								$row['attempted-date'] = (string) $pinSummary->{'attempted-date'};
								$row['customer-ref-1'] =  (string)$pinSummary->{'customer-ref-1'};
								$row['event-type'] =  (string)$pinSummary->{'event-type'};
								$row['event-location'] =  (string)$pinSummary->{'event-location'};
								$row['update-date-time'] = time();
								
								$trackingData[] = $row;
							}
						} else if ($display_errors) {
							$messages = $xml->children('http://www.canadapost.ca/ws/messages');
							foreach ( $messages as $message ) {
								echo 'Error Code: ' . $message->code . "\n";
								echo 'Error Msg: ' . $message->description . "\n\n";
							}
						}
					} else {
						// No tracking available for that pin.
					}
				} catch (Exception $ex) {
						// cURL request went wrong.
						if ($display_errors){
							echo 'Error: ' . $ex; 
						}
				}
				
				if (empty($trackingData)){
					// No tracking was available. just save pin so that this can be displayed to user/and/or able to be removed.
					$row['pin'] = $trackingPin;
					$trackingData[] = $row;
				}
			
				// Save data post meta
				update_post_meta($post_id, 'cpwebservice_tracking_'.$post_id.'_'.$trackingPin, $trackingData );
			}
			
			return $trackingData;
			
		}
		
		return array();
	}
	
	
	/**
	 * Load and generate the template output with ajax
	 */
	public function update_order_tracking() {
		// Let the backend only access the page
		if( !is_admin() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
			
		// Check the user privileges
		if( !current_user_can( 'manage_woocommerce_orders' ) && !current_user_can( 'edit_shop_orders' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
			
		// Check the action
		if( empty( $_GET['action'] ) || !check_admin_referer( $_GET['action'] ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
			
		// Check if all parameters are set
		if( empty( $_GET['trackingno'] ) || empty( $_GET['order_id'] ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		// Nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'cpwebservice_update_order_tracking' ) )
			return;
			
		// Get tracking no, post_id
		$trackingnumber = sanitize_text_field( $_GET['trackingno'] );
		$post_id = intval( $_GET['order_id'] );
		
		// Remove spaces and dashes from tracking number
		$trackingnumber = preg_replace('/[\r\n\t \-]+/', '', $trackingnumber);
		
		// Current tracking pins:
		$trackingPins = get_post_meta($post_id, '_cpwebservice_tracking', true);
		
		// Do action: Refresh
		if( !empty( $_GET['refresh_row'] ) && !empty($trackingPins) ) {
			$t = $this->lookup_tracking($post_id, $trackingnumber, true); // force refresh.
			echo $this->display_tracking(array($t),$post_id, true, true);
			exit;
		}
		
		// Do action: Remove
		if( !empty( $_GET['remove_tracking'] ) && !empty($trackingPins) ) {
			$updatedPins = array_diff($trackingPins, array($trackingnumber));			

			// Remove data (if any)
			delete_post_meta($post_id, 'cpwebservice_tracking_'.$post_id.'_'.$trackingnumber );
			// Remove Pin
			if (!empty($updatedPins)){
				update_post_meta($post_id, '_cpwebservice_tracking' , $updatedPins );
			} else {
				delete_post_meta($post_id, '_cpwebservice_tracking' );
			}
			echo 'Removed.';
			exit;
		}
		
		// Do action: Add
		if (empty($trackingPins) || !in_array($trackingnumber, $trackingPins)){ // ensures pin isn't added twice.
		
			$addmode = empty($trackingPins);			

			if (!is_array($trackingPins))
				$trackingPins = array();
			
			$trackingPins[] = $trackingnumber;
			
			// Save Tracking Pins.
			if ($addmode){
				add_post_meta($post_id, '_cpwebservice_tracking' , $trackingPins, true);
			} else {
				update_post_meta($post_id, '_cpwebservice_tracking' , $trackingPins );
			}
			
			// Lookup & display tracking
			$t = $this->lookup_tracking($post_id, $trackingnumber);
			echo $this->display_tracking(array($t),$post_id, (count($trackingPins)!=1), true);
			
			exit;
		}
		
		echo 'Duplicate Pin.';
		
		exit;
	}
	
	public function format_cp_time($datetime){
		// format: 20130703:175923
		if (strlen($datetime)>13){
			$d = substr($datetime,0,4).'-'.substr($datetime,4,2).'-'.substr($datetime,6,2);
			$d .=  ' ' .substr($datetime,9,2).':'.substr($datetime,11,2);
			return $d; //date("m/d/Y",strtotime($d));
		}		
		return $datetime;
	}
	

	// This function runs on a regular basis to update recent orders that have tracking attached.
	// It will send an email if configured.
	public function scheduled_update_tracked_orders() {

		global $woocommerce;
		$orders = '';
		$order_email_queue = array();
		add_filter('posts_where', array( &$this,  'tracked_orders_where_dates') );
		$orders = get_posts( array(
				'numberposts' => 50,
				'offset' => 0,
				'orderby' => 'post_date',
				'order' => 'DESC',
				'post_type' => 'shop_order',
				'meta_key' => '_cpwebservice_tracking',
				'tax_query' => array(
	                array(
	                    'taxonomy' => 'shop_order_status',
	                    'field' => 'slug',
	                    'terms' => array('pending','processing','completed')
	                )
	            )
		) );
		remove_filter('posts_where', array( &$this,  'tracked_orders_where_dates'));
		
		if (!empty($orders)) {

		    foreach( $orders as $order ) {  setup_postdata($order);
				// Check for tracking numbers.
				$trackingPins = get_post_meta($order->ID, '_cpwebservice_tracking', true);
				// Check for last update.
				$trackingUpdates = array();
				
				if (!empty($trackingPins) && is_array($trackingPins)){
						
					foreach($trackingPins as $pin){
						
						$trackingData = get_post_meta( $order->ID,'cpwebservice_tracking_'.$order->ID.'_'.$pin, true);
						
						// If data is older than 1 day but less than 30 days, do update.
						if (!empty($trackingData) && is_array($trackingData) && isset($trackingData[0]['update-date-time'])){
							$update = intval($trackingData[0]['update-date-time']);
							if ($update > 0){
								$diff = time() - $update;
								if ($diff > 86400 && $diff < 86400 * 30 ){ // More then 1 day but less than 30 days in seconds
									
									// DO TRACKING UPDATE.
									// Update Tracking
									$trackingUpdated = $this->lookup_tracking($order->ID, $pin, true);
									// Compare to current data
									if (!empty($trackingUpdated) && is_array($trackingUpdated) && isset($trackingUpdated[0]['update-date-time'])){
													
										// Compare 'mailed-on-date', if it is now a value, then an email notification should go out.
										if ((empty($trackingData[0]['mailed-on-date']) && !empty($trackingUpdated[0]['mailed-on-date'])) 
											|| (isset($trackingData[0]['mailed-on-date']) && !empty($trackingUpdated[0]['mailed-on-date']) && $trackingUpdated[0]['mailed-on-date'] != $trackingData[0]['mailed-on-date'])) {
											// Send out email notification for this order.
											if (!in_array($order->ID,$order_email_queue)) { $order_email_queue[] = 	$order->ID; }

										}
										
										// Compare 'actual-delivery-date', if it is now a value (and was not before), then an email notification should go out.
										elseif ((empty($trackingData[0]['actual-delivery-date']) && !empty($trackingUpdated[0]['actual-delivery-date']))
												|| (isset($trackingData[0]['actual-delivery-date']) && !empty($trackingUpdated[0]['actual-delivery-date']) && $trackingUpdated[0]['actual-delivery-date'] != $trackingData[0]['actual-delivery-date'])) {
											// Send out email notification for this order.
											if (!in_array($order->ID,$order_email_queue)) { $order_email_queue[] = 	$order->ID; }
										
										}

									}

								} // end if within specified update time.
							}
						}
						
					}
					
				}
						
			} // endforeach
			
			// Loop through $order_email_queue and send out notification emails.  Will be 'resending' invoice email.
			$invoice = null;
			$mailer = $woocommerce->mailer();
			$mails = $mailer->get_emails();
			if ( ! empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( $mail->id == 'customer_invoice' ) {
						$invoice = $mail;
					}
				}
			} 
			
			if ($invoice) {
			
				foreach($order_email_queue as $order_id_email) {
					try {
						$invoice->trigger( $order_id_email );
					}
					 catch (Exception $ex){
						// email unable to send.
					}
					
				}
			}

		}

	}
	
	// Only update tracking on order updated in the last 30 days.
	public function tracked_orders_where_dates( $where ){
		global $wpdb;
	
		$where .= $wpdb->prepare(" AND post_date >= '%s' ", date("Y-m-d",time() - 30 * 24 * 60 * 60));
	
		return $where;
	}
	
}