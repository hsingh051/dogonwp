<?php
/*
 Main Canada Post Webserivce Class
 woocommerce_cpwebservice.php

Copyright (c) 2014 Jamez Picard

*/
class woocommerce_cpwebservice extends WC_Shipping_Method
{

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return woocommerce_cpwebservice
	 */
	function __construct() {
		$this->id 			= 'woocommerce_cpwebservice';
		$this->method_title 	= __('Canada Post', 'woocommerce-canadapost-webservice');
		$this->init();
	}

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		$default_options = (object) array('enabled'=>'no', 'title'=>'Canada Post', 'api_user'=>'', 'api_key'=>'','account'=>'','contractid'=>'','source_postalcode'=>'','mode'=>'live', 'display_errors'=>false, 'delivery'=>'', 'margin'=>'', 'margin_value'=>'', 'packageweight'=>floatval('0.02'), 'boxes_enable'=> false, 'lettermail_enable'=> false, 'shipping_tracking'=> true, 'email_tracking'=> true, 'log_enable'=>false,'lettermail_limits'=>false,'lettermail_maxlength'=>'','lettermail_maxwidth'=>'','lettermail_maxheight'=>'','lettermail_override_weight'=>false,'lettermail_packageweight'=>'', 'tracking_icons'=> true);
		$this->options		  = get_option('woocommerce_cpwebservice', $default_options);
		$this->options		  =	(object) array_merge((array) $default_options, (array) $this->options); // ensure all keys exist, as defined in default_options.
		$this->enabled		  = $this->options->enabled;
		$this->title 		  = $this->options->title;
		$this->type			  = 'order';
		$this->boxes		  = get_option('woocommerce_cpwebservice_boxes');
		$this->services		  = get_option('woocommerce_cpwebservice_services', array());
		$this->lettermail	  = get_option('woocommerce_cpwebservice_lettermail', array());
		$this->log 			  = (object) array('cart'=>array(),'params'=>array(),'request'=>array('http'=>'','service'=>''),'rates'=>array());

		// Defined Services
		$this->init_available_services();

		// Actions
		add_action('woocommerce_update_options_shipping_' . $this->id, array(&$this, 'process_admin_options'));
	}


		
		
	/*
	 * Defined Services
	*/
	function init_available_services() {
		$this->available_services = array(
				'DOM.RP'=>__('Regular Parcel', 'woocommerce-canadapost-webservice'),
				'DOM.EP'=>__('Expedited Parcel', 'woocommerce-canadapost-webservice'),
				'DOM.XP'=>__('Xpresspost', 'woocommerce-canadapost-webservice'),
				'DOM.XP.CERT'=>__('Xpresspost Certified', 'woocommerce-canadapost-webservice'),
				'DOM.PC'=>__('Priority', 'woocommerce-canadapost-webservice'),
				'DOM.LIB'=>__('Library Books', 'woocommerce-canadapost-webservice'),
				'USA.EP'=>__('Expedited Parcel USA', 'woocommerce-canadapost-webservice'),
				'USA.PW.ENV'=>__('Priority Worldwide Envelope USA', 'woocommerce-canadapost-webservice'),
				'USA.PW.PAK'=>__('Priority Worldwide Pak USA', 'woocommerce-canadapost-webservice'),
				'USA.PW.PARCEL'=>__('Priority Worldwide Parcel USA', 'woocommerce-canadapost-webservice'),
				'USA.SP.AIR'=>__('Small Packet USA Air', 'woocommerce-canadapost-webservice'),
				'USA.TP'=>__('Tracked Packet USA', 'woocommerce-canadapost-webservice'),
				'USA.XP'=>__('Xpresspost USA', 'woocommerce-canadapost-webservice'),
				'INT.XP'=>__('Xpresspost International', 'woocommerce-canadapost-webservice'),
				'INT.IP.AIR'=>__('International Parcel Air', 'woocommerce-canadapost-webservice'),
				'INT.IP.SURF'=>__('International Parcel Surface', 'woocommerce-canadapost-webservice'),
				'INT.PW.ENV'=>__('Priority Worldwide Envelope International', 'woocommerce-canadapost-webservice'),
				'INT.PW.PAK'=>__('Priority Worldwide Pak International', 'woocommerce-canadapost-webservice'),
				'INT.PW.PARCEL'=>__('Priority Worldwide parcel International', 'woocommerce-canadapost-webservice'),
				'INT.SP.AIR'=>__('Small Packet International Air', 'woocommerce-canadapost-webservice'),
				'INT.SP.SURF'=>__('Small Packet International Surface', 'woocommerce-canadapost-webservice'),
				'INT.TP'=>__('Tracked Packet International', 'woocommerce-canadapost-webservice')
		);
	}
		

	function get_destination_from_service($service_code){
		if (!empty($service_code) && strlen($service_code) >= 3) {
			switch(substr($service_code,0,3)) {
				case 'DOM': return __('Canada', 'woocommerce-canadapost-webservice');
				case 'USA': return __('USA', 'woocommerce-canadapost-webservice');
				case 'INT': return __('International', 'woocommerce-canadapost-webservice');
			}
		}
		return '';
	}
		
	/*
	 * Main Canada Post Lookup Rates function
	*/
	function calculate_shipping( $package = array() ) {
			
		global $woocommerce;
		//$_tax = &new woocommerce_tax();

		// Need to calculate total package weight.

		// Get total volumetric weight.
		$total_quantity = 0;
		$total_weight = 0;
		$max = array('length'=>0, 'width'=>0, 'height'=>0);
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );
		$weight_unit = get_option( 'woocommerce_weight_unit' );
		$length = $width = $height = 0;
		$cubic = 0;

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() && $values['data']->has_weight() ) {
				$total_quantity += $values['quantity'];
				$total_weight +=  $this->convertWeight($values['data']->get_weight(), $weight_unit) * $values['quantity'];
				$length = $width = $height = 0;
				if ( $values['data']->has_dimensions() ) {
					$dimensions = explode(' x ',str_replace($dimension_unit,'',$values['data']->get_dimensions()));
					if (count($dimensions) >= 3) {
						// Get cubic size.
						$length = $this->convertSize($dimensions[0], $dimension_unit);
						$width = $this->convertSize( $dimensions[1], $dimension_unit);
						$height = $this->convertSize( $dimensions[2], $dimension_unit);
					}
						
					// Max dimensions
					if ($length > $max['length']) {  $max['length'] = $length; }
					if ($width > $max['width']) {  $max['width'] = $width; }
					if ($height > $max['height']) {  $max['height'] = $height; }
						
					// Cubic size
					$cubic +=  $length * $width * $height * $values['quantity'];
						
				}

				// Cart Logging
				if ($this->options->log_enable){
					$this->log->cart[] = array('id'=>$values['data']->id, 'item'=>$values['data']->get_title(),'quantity'=>$values['quantity'], 'weight'=>$this->convertWeight($values['data']->get_weight(), $weight_unit) * $values['quantity'],
							'length'=>$length, 'width'=>$width, 'height'=>$height, 'cubic'=>($length * $width * $height * $values['quantity']));
				}
			}
		}

		// Find which box the items will fit in (by cubic + packaging factor).
		$box_fits = null; // fit.

		if ($this->options->boxes_enable && is_array($this->boxes)){
			foreach ($this->boxes as $box){
				$box_cubed = $box['cubed'];
				if ($cubic < $box_cubed){
					// It Fits!
					if (empty($box_fits)) {
						$box_fits = $box;
						// Use if smaller than previously iterated box that fits.
					} elseif (is_array($box_fits) && $box_cubed < $box_fits['cubed']) {
						$box_fits = $box;
					}
				}
			}
		}
		if (empty($box_fits)) { // If box was not found or boxes are not enabled
			// Cube Root volume instead of Boxes. (item is assumed already packaged to ship)
			$dimension = (float)pow($cubic, 1.0/3.0);
			// use max dimensions to ensure an item like 1x1x20 is estimated with enough length.
			$box_fits = array('cubed'=>$cubic, 'length'=>($dimension < $max['length'] ? $max['length'] : $dimension), 'width'=>($dimension < $max['width'] ? $max['width'] : $dimension), 'height'=>($dimension < $max['height'] ? $max['height'] : $dimension));
		}
			
		

		// Destination information (Need this to calculate rate)
		$country = $package['destination']['country']; // 2 char country code
		$state = $package['destination']['state']; // 2 char prov/state code
		$postal = $package['destination']['postcode']; // postalcode/zip code as entered by user.

		// Get a rate to ship the package.
		if ($country != '' && is_array($box_fits)) {

			$volumetric_weight = $box_fits['cubed'] > 0 ? $box_fits['cubed'] / 6000 : 0; //Canada Post: (L cm x W cm x H cm)/6000
			$total_weight = ($total_weight > 0) ? $total_weight : 0;
			// Use the largest value of total weight or volumetric/dimensional weight
			$shipping_weight = ($total_weight > $volumetric_weight) ? $total_weight : $volumetric_weight;
			// Envelope weight with bill/notes/advertising inserts: ex. 20g
			$shipping_weight += (!empty($this->options->packageweight) ? floatval($this->options->packageweight) : 0);
				
				
			$shipping_weight = round($shipping_weight,2); // 2 decimal places.
			$length = $box_fits['length'];
			$width = $box_fits['width'];
			$height = $box_fits['height'];
				
			// Debug
			if ($this->options->log_enable){
				$this->log->params = array('country'=>$country, 'state'=>$state, 'postal'=>$postal, 'shipping_weight'=>$shipping_weight, 'length'=>$length, 'width'=>$width, 'height'=>$height);
			}

			$rates = $this->get_rates($country, $state, $postal, $shipping_weight, $length, $width, $height);
			foreach($rates as $rate){
				if (!empty($this->options->margin) && $this->options->margin != 0) {
					 $rate->price = $rate->price * (1 + $this->options->margin/100); //Add margin
				}
				if (!empty($this->options->margin_value) && $this->options->margin_value != 0) {
					$rate->price = $rate->price + $this->options->margin_value; //Add margin_value
					if ($rate->price < 0){ $rate->price = 0; }
				}
				$rateitem = array(
						'id' 		=> $rate->service_code,
						'label' 	=> $rate->service . ((!empty($this->options->delivery) && $rate->expected_delivery != '') ? ' ('.__('Delivered by', 'woocommerce-canadapost-webservice') . ' ' . $rate->expected_delivery . ')' : ''),
						'cost' 		=> $rate->price
				);
				// Register the rate
				$this->add_rate( $rateitem );
					
			}

			// Lettermail Limits.
			if ($this->options->lettermail_limits=='1' && !empty($this->options->lettermail_maxlength) && !empty($this->options->lettermail_maxwidth) && !empty($this->options->lettermail_maxheight)) {
				// Check to see if within lettermail limits.
				$lettermail_cubic =  $this->options->lettermail_maxlength * $this->options->lettermail_maxwidth * $this->options->lettermail_maxheight;
				if ($lettermail_cubic > 0) {
					if ($max['length'] <= $this->options->lettermail_maxlength && $max['width'] <= $this->options->lettermail_maxwidth && $max['height'] <= $this->options->lettermail_maxheight
					&& $cubic <= $lettermail_cubic) {
						// valid, within limit.
					} else {
						// over limit. Disable lettermail rates from being applied.
						$this->options->lettermail_enable = 0;
					}
				}
			}
				
			if ($this->options->lettermail_enable=='1'){
				/*
				 Canada Post Letter-post / Flat Rates
				*/
				// If override packing weight, remove package weight and add custom package weight.
				if (!empty($this->options->lettermail_override_weight) && $this->options->lettermail_override_weight){
					$shipping_weight -= (!empty($this->options->packageweight) ? floatval($this->options->packageweight) : 0);
					$shipping_weight += (!empty($this->options->lettermail_packageweight) ? floatval($this->options->lettermail_packageweight) : 0);
					$shipping_weight = round($shipping_weight,2); // 2 decimal places.
				}
				
				foreach($this->lettermail as $lettermail) {
					if ($shipping_weight > $lettermail['weight_from'] && $shipping_weight < $lettermail['weight_to']
					&& ($country == $lettermail['country'] || ($lettermail['country']=='INT' && $country!='CA' && $country !='US'))
					&& (empty($lettermail['max_qty']) || $total_quantity <=  $lettermail['max_qty'])
					&& (empty($lettermail['min_total']) ||  $woocommerce->cart->subtotal >= $lettermail['min_total'])
					&& (empty($lettermail['max_total']) ||  $woocommerce->cart->subtotal <= $lettermail['max_total'])
					){
							
						$rateitem = array(
								'id' 		=> 'Lettermail '.$lettermail['label'],
								'label' 	=> $lettermail['label'],
								'cost' 		=> $lettermail['cost']
						);
						$this->add_rate( $rateitem );
					}
				}

			}
				
			// Sort rates (by lowest cost)
			if(!empty($this->rates)){
				usort($this->rates, array($this, 'sort_rates'));
			}
				
		}
		// Logging
		if ( $this->options->log_enable ){
			$this->log->rates = $this->rates;
			$this->log->datestamp = current_time('timestamp');
			// Save to transient for 20 minutes.
			set_transient( 'cpwebservice_log', $this->log, 20 * MINUTE_IN_SECONDS );
		}

	}
		
	// Sort Rates function
		
	function sort_rates($a, $b){
		if ($a->cost == $b->cost) {
			return 0;
		}
		return ($a->cost < $b->cost) ? -1 : 1;
	}

	// Canada Post API rates lookup function
	function get_rates($dest_country, $dest_state, $dest_postal_code, $weight_kg, $length, $width, $height) {

		$rates = array();

		$username = $this->options->api_user;
		$password = $this->options->api_key;

		// REST URL
		$service_url = ($this->options->mode=='live') ? 'https://soa-gw.canadapost.ca/rs/ship/price' : 'https://ct.soa-gw.canadapost.ca/rs/ship/price'; // dev.  prod:

		// display errors
		$display_errors = ($this->options->display_errors ? true : false);

		// Has Services flag (Services are enabled for this country)
		$has_services = false;

		$xmlRequest =  new SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate">
  <customer-number></customer-number>
  <parcel-characteristics>
    <weight></weight>
    <dimensions><length></length><width></width><height></height></dimensions>
  </parcel-characteristics>
  <services></services>
  <expected-mailing-date></expected-mailing-date>
  <origin-postal-code></origin-postal-code>
  <destination>
  </destination>
</mailing-scenario>
XML
		);

		// Create GetRates request xml
		$xmlRequest->{'origin-postal-code'} = $this->options->source_postalcode;
		$postalCode = str_replace(' ','',strtoupper($dest_postal_code)); //N0N0N0 (no spaces, uppercase)

		// Customer Number for venture one rates.
		if (!empty($this->options->account)){
			$xmlRequest->{'customer-number'} = $this->options->account;
			// Add Contract Id if entered.
			if (!empty($this->options->contractid)){
				$xmlRequest->addChild('contract-id', $this->options->contractid);
			}
		} else {
			// use public rates
			unset($xmlRequest->{'customer-number'});
			$xmlRequest->addChild('quote-type', 'counter');
		}


		// Add lead times.
		if (!empty($this->options->delivery)){
			$processTime = 86400 * $this->options->delivery; // in seconds
			$xmlRequest->{'expected-mailing-date'}  =  date('Y-m-d', time() + $processTime);
		} else {
			unset($xmlRequest->{'expected-mailing-date'}); // remove element
		}

		// Parcel Dimensions
		if ($length > 0 || $width > 0 || height > 0){

			// Round to 1 decimal place.
			$length = round($length,1); $width = round($width,1); $height = round($height,1);
				
			$xmlRequest->{'parcel-characteristics'}->dimensions->length = $length;
			$xmlRequest->{'parcel-characteristics'}->dimensions->width = $width;
			$xmlRequest->{'parcel-characteristics'}->dimensions->height = $height;
				
			//$dimensions = "<dimensions><length>{$length}</length><width>{$width}</width><height>{$height}</height></dimensions>";
		} else {
			unset($xmlRequest->{'parcel-characteristics'}->dimensions); // remove element
		}

		if ($dest_country == 'CA')
			// Canada
		{
			$xmlRequest->destination->addChild('domestic','');
			$xmlRequest->destination->domestic->addChild('postal-code', $postalCode);
			//$destination = "<domestic><postal-code>{$postalCode}</postal-code></domestic>";
				
			// Add Services
			foreach($this->services as $service_code){
				if (!empty($service_code) && strlen($service_code) >= 3 && substr($service_code,0,3) == 'DOM') {
					$xmlRequest->services->addChild('service-code', $service_code);
					//$services .= "<service-code>$service_code</service-code>";
					$has_services = true;
				}
			}
				
		} else if ($dest_country == 'US')
			// USA
		{
			$xmlRequest->destination->addChild('united-states','');
			$xmlRequest->destination->{'united-states'}->addChild('zip-code', $postalCode);
			//$destination = "<united-states><zip-code>{$postalCode}</zip-code></united-states>";
			// Add Services //$services = "<services><service-code>USA.EP</service-code><service-code>USA.SP.AIR</service-code><service-code>USA.XP</service-code></services>";
			foreach($this->services as $service_code){
				if (!empty($service_code) && strlen($service_code) >= 3 && substr($service_code,0,3) == 'USA') {
					$xmlRequest->services->addChild('service-code', $service_code);
					$has_services = true;
				}
			}
				
		} else
			// International
		{
			$xmlRequest->destination->addChild('international','');
			$xmlRequest->destination->international->addChild('country-code', $dest_country);
			//$destination = "<international><country-code>{$dest_country}</country-code></international>";
			// Add Services // $services = "<services><service-code>INT.XP</service-code><service-code>INT.IP.AIR</service-code><service-code>INT.SP.AIR</service-code></services>";
			foreach($this->services as $service_code){
				if (!empty($service_code) && strlen($service_code) >= 3 && substr($service_code,0,3) == 'INT') {
					$xmlRequest->services->addChild('service-code', $service_code);
					$has_services = true;
				}
			}
		}

		// Total Weight
		$xmlRequest->{'parcel-characteristics'}->weight = round( $weight_kg, 2); // 2 decimal places : 99.99 format

		// Service Language: (English or French) sent as Accept-language header with a value of 'fr-CA' or 'en-CA'
		// If using WPML:
		if (defined('ICL_LANGUAGE_CODE')){
			$service_language = (ICL_LANGUAGE_CODE=='fr') ? 'fr-CA':'en-CA'; // 'en-CA' is default
		} else if (WPLANG == 'fr_FR'){
			$service_language = 'fr-CA';
		} else {
			$service_language = 'en-CA';
		}
		 
		$is_international = ($dest_country != 'US' && $dest_country != 'CA');
		 
		if ($has_services && ( !empty($username) && !empty($password) ) && (!empty($postalCode) || $is_international)){ // Postal code cannot be empty for CA or US.
			try {
				$curl = curl_init($service_url); // Create REST Request
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($curl, CURLOPT_CAINFO, CPWEBSERVICE_PLUGIN_PATH . '/cert/cacert.pem'); // Signer Certificate in PEM format
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlRequest->asXML());
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.cpc.ship.rate+xml', 'Accept: application/vnd.cpc.ship.rate+xml','Accept-language: '.$service_language));
				$curl_response = curl_exec($curl); // Execute REST Request
				if(curl_errno($curl) && $display_errors){
					echo 'Curl error: ' . curl_error($curl) . "\n";
				}
				if ($display_errors || $this->options->log_enable) {
					$this->log->request['http'] = 'HTTP Response Status: ' . curl_getinfo($curl,CURLINFO_HTTP_CODE) . "\n";
					if ($display_errors){ echo $this->log->request['http']; }
				}
				curl_close($curl);

				// Using SimpleXML to parse xml response
				libxml_use_internal_errors(true);
				$xml = simplexml_load_string('<root>' . preg_replace('/<\?xml.*\?>/','',$curl_response) . '</root>');
				if (!$xml && ($display_errors || $this->options->log_enable)) {
					$errmsg = 'Failed loading XML' . "\n";
					$errmsg .= $curl_response . "\n";
					foreach(libxml_get_errors() as $error) {
						$errmsg.= "\t" . $error->message;
					}
					$this->log->request['errmsg'] = $errmsg;
					if ($display_errors) { echo $errmsg; }
				} else {
					if ($xml->{'price-quotes'} ) {
						$priceQuotes = $xml->{'price-quotes'}->children('http://www.canadapost.ca/ws/ship/rate');
						if ( $priceQuotes->{'price-quote'} ) {
							foreach ( $priceQuotes as $priceQuote ) {
								$rate = new stdClass();
								$rate->service =  (string)$priceQuote->{'service-name'};
								$rate->service_code =(string) $priceQuote->{'service-code'};
								$rate->price = round(floatval((string)$priceQuote->{'price-details'}->{'due'}),2);
								$rate->cubed = (string)$priceQuote->{'weight-details'}->{'cubed-weight'};
								$rate->guaranteed =(string)$priceQuote->{'service-standard'}->{'guaranteed-delivery'};
								$rate->expected_delivery = (string) $priceQuote->{'service-standard'}->{'expected-delivery-date'};
								// Add rate
								$rates[] = $rate;

								if ($display_errors || $this->options->log_enable) {
									$this->log->request['service'] .= "\nService: " . $priceQuote->{'service-name'} . "\n";
									$this->log->request['service'] .= 'Price: ' . $priceQuote->{'price-details'}->{'due'} . "\n";
									$this->log->request['service'] .= 'Guaranteed Delivery: ' . $priceQuote->{'service-standard'}->{'guaranteed-delivery'} . "\n";
									$this->log->request['service'] .= 'Expected Delivery: ' . $priceQuote->{'service-standard'}->{'expected-delivery-date'} . "\n";
								}
							}
							if ($display_errors && isset($this->log->request['service'])){ echo $this->log->request['service']; }
						}
					}
					if ($xml->{'messages'} && ($display_errors || $this->options->log_enable)) {
						$apierror = '';
						$messages = $xml->{'messages'}->children('http://www.canadapost.ca/ws/messages');
						foreach ( $messages as $message ) {
							$apierror .= 'Error Code: ' . $message->code . "\n";
							$apierror .= 'Error Msg: ' . $message->description . "\n\n";
						}
						$this->log->request['apierror'] = $apierror;
						if ($display_errors){ echo $apierror; }
					}

				}
			} catch (Exception $ex) {
				// cURL request went wrong.
				if ($display_errors){
					echo 'Error: ' . $ex;
				}
				if ($this->options->log_enable){
					$this->log->request['error'] = 'Error: ' . $ex;
				}
			}
		} // endif $has_services

		return $rates;

	}
		

	function admin_options() {
		global $woocommerce;
		?>
				<?php // security nonce
					  wp_nonce_field(plugin_basename(__FILE__), 'cpwebservice_options_noncename'); 
				?>
				<h3><?php _e('Canada Post', 'woocommerce-canadapost-webservice'); ?></h3>
				<div><img src="<?php echo plugins_url( 'img/canada-post.jpg' , dirname(__FILE__) ); ?>" /></div>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Enable/Disable', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
							<fieldset><legend class="screen-reader-text"><span><?php _e('Enable/Disable', 'woocommerce-canadapost-webservice') ?></span></legend>
									<label for="woocommerce_cpwebservice_enabled">
									<input name="woocommerce_cpwebservice_enabled" id="woocommerce_cpwebservice_enabled" type="checkbox" value="1" <?php checked($this->options->enabled=='yes'); ?> /> <?php _e('Enable Canada Post Webservice', 'woocommerce-canadapost-webservice') ?></label><br>
								</fieldset>
						</td>
					    </tr>
					    <tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Method Title', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
							<input type="text" name="woocommerce_cpwebservice_title" id="woocommerce_cpwebservice_title" style="min-width:50px;" value="<?php echo esc_attr($this->options->title); ?>" /> <span class="description"><?php _e('This controls the title which the user sees during checkout.', 'woocommerce-canadapost-webservice') ?></span>
						</td>
					    </tr>
					    <tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Webservice Account Settings', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
							<input type="text" name="woocommerce_cpwebservice_account" id="woocommerce_cpwebservice_account" style="min-width:50px;" value="<?php echo esc_attr($this->options->account); ?>" /> <span class="description"><?php _e('Customer Number (Venture One)', 'woocommerce-canadapost-webservice') ?></span> <span id="woocommerce_cpwebservice_contractid_button" style="<?php echo (empty($this->options->contractid) ? "":"display:none"); ?>">
							<a href="javscript:;" class="cpwebservice-showcontractid"><span class="description"><?php _e('Add Contract ID (Optional, Only if a Contract Customer)', 'woocommerce-canadapost-webservice')?></span></a>
							</span>
							<br />
							<div id="woocommerce_cpwebservice_contractid_display" style="<?php echo (!empty($this->options->contractid) ? "":"display:none"); ?>">
							<input type="text" name="woocommerce_cpwebservice_contractid" id="woocommerce_cpwebservice_contractid" style="min-width:50px;" value="<?php echo esc_attr($this->options->contractid); ?>" /> <span class="description"><?php _e('Contract ID (Optional, Only if a Contract Customer)', 'woocommerce-canadapost-webservice') ?></span>
							<br /></div>
							<input type="text" name="woocommerce_cpwebservice_api_user" id="woocommerce_cpwebservice_api_user" style="min-width:50px;" value="<?php echo esc_attr($this->options->api_user); ?>" /> <span class="description"><?php _e('API Username', 'woocommerce-canadapost-webservice') ?></span>
							<br />
							<input type="password" name="woocommerce_cpwebservice_api_key" id="woocommerce_cpwebservice_api_key" style="min-width:50px;" value="<?php echo esc_attr($this->options->api_key); ?>" /> <span class="description"><?php _e('API Password/Key', 'woocommerce-canadapost-webservice') ?></span>
							<br />
							<div><a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=cpwebservice_validate_api_credentials' ), 'cpwebservice_validate_api_credentials' ); ?>" id="woocommerce_cpwebservice_validate_btn" class="button cpwebservice-validate"><?php _e('Validate Credentials', 'woocommerce-canadapost-webservice') ?></a> <img src="<?php admin_url(); ?>/wp-admin/images/wpspin_light.gif" alt="" class="cpwebservice_ajaxupdate" style="display: none;" /><br /></div>							
							<div id="woocommerce_cpwebservice_validate" class="widefat" style="display:none;background-color: #fffbcc;padding:5px 0;border-color: #e6db55;"><p></p></div>
						</td>
					    </tr>
					    <tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Origin Postal Code', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
							<input type="text" name="woocommerce_cpwebservice_source_postalcode" id="woocommerce_cpwebservice_source_postalcode" style="min-width:50px;" class="cpwebservice-postal" value="<?php echo esc_attr($this->options->source_postalcode); ?>" /> <span class="description"><?php _e('The Postal Code that items will be shipped from.', 'woocommerce-canadapost-webservice') ?></span>
							<div class="cpwebservice-postal-error" style="display:none;background-color: #fffbcc;padding:5px;border-color: #e6db55;"><p><?php _e('Warning: Postal Code is invalid. Required to be a valid Canadian postal code.', 'woocommerce-canadapost-webservice')?></p></div>
						</td>
					    </tr>
					    <tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Order Shipping Tracking', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
							<label for="woocommerce_cpwebservice_shipping_tracking"><input name="woocommerce_cpwebservice_shipping_tracking" id="woocommerce_cpwebservice_shipping_tracking" type="checkbox" value="1" <?php checked($this->options->shipping_tracking==true); ?>  /> <?php _e('Enable Canada Post Tracking number feature on Orders', 'woocommerce-canadapost-webservice') ?></label>
							<p><label for="woocommerce_cpwebservice_email_tracking"><input name="woocommerce_cpwebservice_email_tracking" id="woocommerce_cpwebservice_email_tracking" type="checkbox" value="1" <?php checked($this->options->email_tracking==true); ?>  /> <?php _e('Enable Email notification when Parcel Tracking updates', 'woocommerce-canadapost-webservice') ?></label> <span class="description"><?php _e('Automatic email notifications to customers when "Mailed on" or "Delivered" date is updated', 'woocommerce-canadapost-webservice')?></span></p>
							<p><label for="woocommerce_cpwebservice_tracking_icons"><input name="woocommerce_cpwebservice_tracking_icons" id="woocommerce_cpwebservice_tracking_icons" type="checkbox" value="1" <?php checked($this->options->tracking_icons==true); ?>  /> <?php _e('Display icons with Tracking information', 'woocommerce-canadapost-webservice') ?></label>
						</td>
					    </tr>
					    <tr valign="top">
					    <th scope="row" class="titledesc"><?php _e('Development', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
							<fieldset><legend class="screen-reader-text"><span><?php _e('Development Mode', 'woocommerce-canadapost-webservice') ?></span></legend>
									<select name="woocommerce_cpwebservice_mode">
										<option value="dev"<?php if ($this->options->mode=='dev') echo 'selected="selected"'; ?>>Development</option>
										<option value="live" <?php if ($this->options->mode=='live') echo 'selected="selected"'; ?>>Production/Live</option>
									</select>
									<br />
									<label for="woocommerce_cpwebservice_display_errors">
									<input name="woocommerce_cpwebservice_display_errors" id="woocommerce_cpwebservice_display_errors" type="checkbox" value="1" <?php checked($this->options->display_errors=='1'); ?> /> <?php _e('Display Errors', 'woocommerce-canadapost-webservice') ?> <small><?php _e('Warning: Do not enable in production since debug/errors would be shown to customers', 'woocommerce-canadapost-webservice') ?></small></label><br>
									</fieldset>
						</td>
					    </tr>
					    <tr>
						<th scope="row" class="titledesc"><?php _e('Add Margin', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
								&nbsp; <input type="text" name="woocommerce_cpwebservice_margin" id="woocommerce_cpwebservice_margin" style="max-width:50px;" value="<?php echo esc_attr($this->options->margin); ?>" />% <span class="description"><?php _e('Add Margin Percentage (ex. 5% or -2%) to Shipping Cost', 'woocommerce-canadapost-webservice') ?></span><br />
								$<input type="text" name="woocommerce_cpwebservice_margin_value" id="woocommerce_cpwebservice_margin_value" style="max-width:50px;" value="<?php echo esc_attr($this->options->margin_value); ?>" /> &nbsp; <span class="description"><?php _e('Add Margin Amount (ex. $4 or -$1) to Shipping Cost', 'woocommerce-canadapost-webservice') ?></span>
						</td>
					    </tr>
					    <tr>
						<th scope="row" class="titledesc"><?php _e('Box/Envelope Weight', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
								<input type="text" name="woocommerce_cpwebservice_packageweight" id="woocommerce_cpwebservice_packageweight" style="max-width:50px;" value="<?php echo esc_attr($this->options->packageweight); ?>" />kg <span class="description"><?php _e('Envelope/Box weight with bill/notes/advertising inserts (ex. 0.02kg)', 'woocommerce-canadapost-webservice') ?></span>
						</td>
					    </tr>
					    <tr>
						<th scope="row" class="titledesc"><?php _e('Delivery Dates', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
								<input type="text" name="woocommerce_cpwebservice_delivery" id="woocommerce_cpwebservice_delivery" style="max-width:50px;" value="<?php echo esc_attr($this->options->delivery); ?>" /> <span class="description"><?php _e('Days to Ship after order placed', 'woocommerce-canadapost-webservice') ?></span>
								
								&nbsp;	<label for="woocommerce_cpwebservice_delivery_hide">
									<input name="woocommerce_cpwebservice_delivery_hide" id="woocommerce_cpwebservice_delivery_hide" onclick="jQuery('#woocommerce_cpwebservice_delivery').val('');" type="checkbox" value="1" <?php checked(!empty($this->options->delivery)); ?> /> <?php _e('Show Estimated Delivery Dates', 'woocommerce-canadapost-webservice') ?></label>
						</td>
					    </tr>
					    
					    <tr>
						<th scope="row" class="titledesc"><?php _e('Enable Parcel Services', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
								<fieldset><legend class="screen-reader-text"><span><?php _e('Canada Post Shipping Services', 'woocommerce-canadapost-webservice') ?></span></legend>
								<?php if (empty($this->services)){ $this->services = array_keys($this->available_services);  } // set all checked as default.
									  $s=0; // service count
									  $cur_code = ''; // heading label ?>
								<?php foreach($this->available_services as $service_code=>$service_label): ?>
								<?php $s++;
									  if (!empty($service_code) && strlen($service_code) >= 3){ $prefix = substr($service_code,0,3); } else { $prefix = ''; }
								      if ($cur_code!=$prefix){ $cur_code=$prefix; echo '<h4>'.esc_html($this->get_destination_from_service($service_code)).'</h4>'; } ?>
									<label for="woocommerce_cpwebservice_service-<?php echo $s ?>">
									<input name="woocommerce_cpwebservice_services[]" id="woocommerce_cpwebservice_service-<?php echo $s ?>" type="checkbox" value="<?php echo esc_attr($service_code) ?>" <?php checked(in_array($service_code,$this->services)); ?> /> <?php echo esc_html($service_label); ?></label><br>
								<?php endforeach; ?>
								</fieldset>
						</td>
					    </tr>
					    
					    <tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Shipping Package/Box sizes', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
						<?php if (!$this->boxes || !is_array($this->boxes) || count($this->boxes) == 0){
								$this->boxes = array(array('length'=>'9','width'=>'6', 'height'=>'15','name'=>'Standard Box'));
								$this->options->boxes_enable='1';
							}
							$box_defaults = array('length'=>0,'width'=>0, 'height'=>0,'name'=>''); ?>
						<label for="woocommerce_cpwebservice_boxes_enable">
									<input name="woocommerce_cpwebservice_boxes_enable" id="woocommerce_cpwebservice_boxes_enable" type="checkbox" value="1" <?php checked($this->options->boxes_enable=='1'); ?> /> <?php _e('Enable Shipping Box Sizes', 'woocommerce-canadapost-webservice') ?></label><br />
							<span class="description"><?php _e('Please define a number of envelope/package/box sizes that you use to ship. Dimensions are in cm.', 'woocommerce-canadapost-webservice') ?></span>
							<div id="cpwebservice_boxes">							
								<?php for($i=0;$i<count($this->boxes); $i++): ?>
								<?php $box = (is_array($this->boxes[$i]) ? array_merge($box_defaults, $this->boxes[$i]) : array()); ?>
								<p class="form-field">
								<label for="woocommerce_cpwebservice_box_length[]"><?php _e('Box Dimensions (cm)', 'woocommerce-canadapost-webservice'); ?></label><span class="wrap">
										<input name="woocommerce_cpwebservice_box_length[]" id="woocommerce_cpwebservice_box_length<?php echo $i;?>" style="max-width:50px" placeholder="Length" class="input-text" size="6" type="text" value="<?php echo esc_attr($box['length']); ?>" />
										<input name="woocommerce_cpwebservice_box_width[]" id="woocommerce_cpwebservice_box_width<?php echo $i;?>" style="max-width:50px" placeholder="Width" class="input-text" size="6" type="text" value="<?php echo esc_attr($box['width']); ?>">
										<input name="woocommerce_cpwebservice_box_height[]" id="woocommerce_cpwebservice_box_width<?php echo $i;?>" style="max-width:50px" placeholder="Height" class="input-text last" size="6" type="text" value="<?php echo esc_attr($box['height']); ?>" />
										<span class="description"><?php _e('LxWxH cm decimal form','woocommerce-canadapost-webservice'); ?></span>
										<input name="woocommerce_cpwebservice_box_name[]" id="woocommerce_cpwebservice_box_name<?php echo $i;?>" style="max-width:120px;margin-left:20px;" placeholder="Box Name" class="input-text last" size="6" type="text" value="<?php echo esc_attr($box['name']); ?>"></span>
										<span class="description"><?php _e('Box Name (internal)', 'woocommerce-canadapost-webservice'); ?></span>
										<span style="margin-left:5px;"><a href="javascript:;" title="Remove" onclick="jQuery(this).parent().parent('p').remove();" class="button"><?php _e('Remove','woocommerce-canadapost-webservice'); ?></a></span>
								</p>
								<?php endfor; ?>
							</div>
							<a href="javascript:;" id="btn_cpwebservice_boxes" class="button-secondary"><?php _e('Add More +','woocommerce-canadapost-webservice'); ?></a>
						</td>
					    </tr>
					    
					     <tr valign="top">
						<th scope="row" class="titledesc"><?php _e('Lettermail / Flat Rates', 'woocommerce-canadapost-webservice') ?></th>
						<td class="forminp">
						<?php if (empty($this->lettermail) || !is_array($this->lettermail) || count($this->lettermail) == 0){
								// Set default CP Lettermail Rates.
								/*Canada Post Letter-post USA rates:(for now)
								0-100g = $2.20
								100g-200g = $3.80
								200g-500g = $7.60*/
								$this->lettermail = array(array('country'=>'CA','label'=>'Canada Post Lettermail', 'cost'=>'2.20','weight_from'=>'0','weight_to'=>'0.1', 'max_qty'=>0, 'min_total'=>'', 'max_total'=>''),
													    array('country'=>'CA','label'=>'Canada Post Lettermail', 'cost'=>'3.80','weight_from'=>'0.1','weight_to'=>'0.2', 'max_qty'=>0, 'min_total'=>'', 'max_total'=>''),
														array('country'=>'US','label'=>'Canada Post Lettermail', 'cost'=>'2.20','weight_from'=>'0','weight_to'=>'0.1', 'max_qty'=>0, 'min_total'=>'', 'max_total'=>''),
														array('country'=>'US','label'=>'Canada Post Lettermail', 'cost'=>'3.80','weight_from'=>'0.1','weight_to'=>'0.2', 'max_qty'=>0, 'min_total'=>'', 'max_total'=>''),
														array('country'=>'US','label'=>'Canada Post Lettermail', 'cost'=>'7.60','weight_from'=>'0.2','weight_to'=>'0.5', 'max_qty'=>0, 'min_total'=>'', 'max_total'=>''));
								$this->options->lettermail_enable='';
							} 
							$lettermail_defaults = array('country'=>'','label'=>'', 'cost'=>0,'weight_from'=>'','weight_to'=>'','max_qty'=>0, 'min_total'=>'', 'max_total'=>''); ?>
						<label for="woocommerce_cpwebservice_lettermail_enable">
									<input name="woocommerce_cpwebservice_lettermail_enable" id="woocommerce_cpwebservice_lettermail_enable" type="checkbox" value="1" <?php checked($this->options->lettermail_enable=='1'); ?> /> <?php _e('Enable Lettermail / Flat Rates', 'woocommerce-canadapost-webservice') ?></label>
							<p class="description"><?php _e('Define Lettermail rates based on Weight Range (kg)', 'woocommerce-canadapost-webservice') ?>.</p>
							<p class="description"> <?php _e('Example: 0.1kg to 0.2kg: $3.80 Lettermail', 'woocommerce-canadapost-webservice') ?></p>
							<div id="cpwebservice_lettermail">							
								<?php for($i=0;$i<count($this->lettermail); $i++): ?>
								<?php $lettermail = (is_array($this->lettermail[$i]) ? array_merge($lettermail_defaults, $this->lettermail[$i]) : array()); ?>
								<p class="form-field">
								
								<span class="wrap">
								    <select name="woocommerce_cpwebservice_lettermail_country[]">
										<option value="CA"<?php if ($lettermail['country']=='CA') echo 'selected="selected"'; ?>>Canada</option>
										<option value="US" <?php if ($lettermail['country']=='US') echo 'selected="selected"'; ?>>USA</option>
										<option value="INT" <?php if ($lettermail['country']=='INT') echo 'selected="selected"'; ?>><?php _e('International', 'woocommerce-canadapost-webservice') ?></option>
									</select>
									<label for="woocommerce_cpwebservice_lettermail_label<?php echo $i;?>"> <?php _e('Label', 'woocommerce-canadapost-webservice'); ?>:</label>
										<input name="woocommerce_cpwebservice_lettermail_label[]" id="woocommerce_cpwebservice_lettermail_label<?php echo $i;?>" style="max-width:150px" placeholder="<?php _e('Lettermail','woocommerce-canadapost-webservice'); ?>" class="input-text" size="16" type="text" value="<?php echo esc_attr($lettermail['label']); ?>" />
										<span class="description"> <?php _e('Cost','woocommerce-canadapost-webservice'); ?>: $</span><input name="woocommerce_cpwebservice_lettermail_cost[]" id="woocommerce_cpwebservice_lettermail_cost<?php echo $i;?>" style="max-width:50px" placeholder="<?php _e('Cost','woocommerce-canadapost-webservice'); ?>" class="input-text" size="16" type="text" value="<?php echo esc_attr($lettermail['cost']); ?>">
										<span class="description"> <?php _e('Weight Range(kg)','woocommerce-canadapost-webservice'); ?>: </span><input name="woocommerce_cpwebservice_lettermail_weight_from[]" id="woocommerce_cpwebservice_lettermail_weight_from<?php echo $i;?>" style="max-width:40px" placeholder="" class="input-text" size="6" type="text" value="<?php echo esc_attr($lettermail['weight_from']); ?>" />kg
										 <?php _e('to','woocommerce-canadapost-webservice'); ?> &lt;
										<input name="woocommerce_cpwebservice_lettermail_weight_to[]" id="woocommerce_cpwebservice_lettermail_weight_to<?php echo $i;?>" style="max-width:40px" placeholder="" class="input-text last" size="6" type="text" value="<?php echo esc_attr($lettermail['weight_to']); ?>" />kg</span>
										<span class="description"> <?php _e('Max items (0 for no limit)','woocommerce-canadapost-webservice'); ?>: </span> <input name="woocommerce_cpwebservice_lettermail_max_qty[]" id="woocommerce_cpwebservice_lettermail_max_qty<?php echo $i;?>" style="max-width:40px" placeholder="" class="input-text" size="6" type="text" value="<?php echo esc_attr($lettermail['max_qty']); ?>" />
										<span class="description"> <?php _e('Cart subtotal','woocommerce-canadapost-webservice'); ?>: $</span><input name="woocommerce_cpwebservice_lettermail_min_total[]" id="woocommerce_cpwebservice_lettermail_min_total<?php echo $i;?>" style="max-width:50px" placeholder="" class="input-text" size="6" type="text" value="<?php echo esc_attr($lettermail['min_total']); ?>" />
										 <?php _e('to','woocommerce-canadapost-webservice'); ?> &lt;=
										$<input name="woocommerce_cpwebservice_lettermail_max_total[]" id="woocommerce_cpwebservice_lettermail_max_total<?php echo $i;?>" style="max-width:50px" placeholder="" class="input-text last" size="6" type="text" value="<?php echo esc_attr($lettermail['max_total']); ?>" />
										<span style="margin-left:5px;"><a href="javascript:;" title="Remove" onclick="jQuery(this).parent().parent('p').remove();" class="button"><?php _e('Remove','woocommerce-canadapost-webservice'); ?></a></span>
								</p>
								<?php endfor; ?>
							</div>
							<a href="javascript:;" id="btn_cpwebservice_lettermail" class="button-secondary"><?php _e('Add More +','woocommerce-canadapost-webservice'); ?></a>
							<br />
							<br />
							<label for="woocommerce_cpwebservice_lettermail_limits">
									<input name="woocommerce_cpwebservice_lettermail_limits" id="woocommerce_cpwebservice_lettermail_limits" type="checkbox" value="1" <?php checked($this->options->lettermail_limits=='1'); ?> /> <?php _e('Maximum dimensions for Lettermail/Flat Rates (Also maximum volumetric weight)', 'woocommerce-canadapost-webservice') ?></label>
							<p class="form-field">
								<input name="woocommerce_cpwebservice_lettermail_maxlength" id="woocommerce_cpwebservice_lettermail_maxlength" style="max-width:50px" placeholder="Length" class="input-text" size="6" type="text" value="<?php echo esc_attr($this->options->lettermail_maxlength); ?>" />
										<input name="woocommerce_cpwebservice_lettermail_maxwidth" id="woocommerce_cpwebservice_lettermail_maxwidth" style="max-width:50px" placeholder="Width" class="input-text" size="6" type="text" value="<?php echo esc_attr($this->options->lettermail_maxwidth); ?>">
										<input name="woocommerce_cpwebservice_lettermail_maxheight" id="woocommerce_cpwebservice_lettermail_maxheight" style="max-width:50px" placeholder="Height" class="input-text last" size="6" type="text" value="<?php echo esc_attr($this->options->lettermail_maxheight); ?>" />
										<span class="description"><?php _e('LxWxH cm decimal form','woocommerce-canadapost-webservice'); ?></span>
							</p>
							<br />
							<label for="woocommerce_cpwebservice_lettermail_override_weight">
									<input name="woocommerce_cpwebservice_lettermail_override_weight" id="woocommerce_cpwebservice_lettermail_override_weight" type="checkbox" value="1" <?php checked($this->options->lettermail_override_weight=='1'); ?> /> <?php _e('Override Box/Envelope Weights for Lettermail/Flat Rates', 'woocommerce-canadapost-webservice') ?></label>
							<p class="form-field">
								<input name="woocommerce_cpwebservice_lettermail_packageweight" id="woocommerce_cpwebservice_lettermail_packageweight" style="max-width:50px" class="input-text" size="6" type="text" value="<?php echo esc_attr($this->options->lettermail_packageweight); ?>" />kg <span class="description"><?php _e('Envelope/Box weight. This is used instead of above Box/Envelope Weight, but only for calculating Lettermail/Flat Rates.', 'woocommerce-canadapost-webservice') ?></span></p>
						</td>
					    </tr>
					    
				</table>
				<script type="text/javascript">
					jQuery(document).ready(function($) {

						$('.cpwebservice-showcontractid').click(function(){
							jQuery('#woocommerce_cpwebservice_contractid_button').hide();
							jQuery('#woocommerce_cpwebservice_contractid_display').show();
						});

						$('.cpwebservice-validate').click(function(){
							var url = $(this).attr('href');
							var postvalues= { api_user:$('input#woocommerce_cpwebservice_api_user').val(),
									api_key:$('input#woocommerce_cpwebservice_api_key').val(),
									customerid:$('input#woocommerce_cpwebservice_account').val(),
									contractid:$('input#woocommerce_cpwebservice_contractid').val() };
							// ajax request.
							$('img.cpwebservice_ajaxupdate').show();
							$.post(url,postvalues,function(data){
								console.log('Data:'+data);
								$('#woocommerce_cpwebservice_validate p').html(data);
								$('#woocommerce_cpwebservice_validate').show();
								$('img.cpwebservice_ajaxupdate').hide();
							});
							return false;
						});

						$('.cpwebservice-log-display').click(function(){
							var url = $(this).attr('href');
							$('img.cpwebservice-log-display-loading').show();
							$('#cpwebservice_log_display').hide();
							$.get(url,function(data){
								//console.log('Data:'+data);
								$('#cpwebservice_log_display').html(data);
								$('#cpwebservice_log_display').slideDown();
								$('img.cpwebservice-log-display-loading').hide();
							});
							return false;
						});

						// Validate Postal Code
						var validPostalCode = function() {
							var regex = /^[A-Za-z]{1}\d{1}[A-Za-z]{1} *\d{1}[A-Za-z]{1}\d{1}$/;
							$('.cpwebservice-postal-error').toggle(regex.test($('.cpwebservice-postal').val()) == false);
						}
						if ($('.cpwebservice-postal').val() != '') { validPostalCode(); }
						$('.cpwebservice-postal').on('blur', validPostalCode);
						
						
						jQuery('#btn_cpwebservice_boxes').click(function() {
						
							var i = jQuery('#cpwebservice_boxes p').size(); // one p tag.
							
							var fields = '';
							fields += '<p class="form-field">';
							fields += '<label for="woocommerce_cpwebservice_box_length[]"><?php _e('Box Dimensions (cm)', 'woocommerce-canadapost-webservice'); ?></label><span class="wrap"> ';
							fields += '<input name="woocommerce_cpwebservice_box_length[]" id="woocommerce_cpwebservice_box_length'+i+'" style="max-width:50px" placeholder="Length" class="input-text" size="6" type="text" value="" />';
							fields += '<input name="woocommerce_cpwebservice_box_width[]" id="woocommerce_cpwebservice_box_width'+i+'" style="max-width:50px" placeholder="Width" class="input-text" size="6" type="text" value="">';
							fields += '<input name="woocommerce_cpwebservice_box_height[]" id="woocommerce_cpwebservice_box_width'+i+'" style="max-width:50px" placeholder="Height" class="input-text" size="6" type="text" value="" />';
							fields += '<span class="description"><?php _e('LxWxH cm decimal form','woocommerce-canadapost-webservice'); ?></span> ';
							fields += '<input name="woocommerce_cpwebservice_box_name[]" id="woocommerce_cpwebservice_box_name'+i+'" style="max-width:120px;margin-left:20px;" placeholder="Box Name" class="input-text last" size="6" type="text" value=""></span>';
							fields += '<span class="description"><?php _e('Box Name (internal)', 'woocommerce-canadapost-webservice'); ?></span>';
							fields += '<span style="margin-left:5px;"><a href="javascript:;" title="Remove" onclick="'+"jQuery(this).parent().parent('p').remove();"+'" class="button"><?php _e('Remove','woocommerce-canadapost-webservice'); ?></a></span>';
							fields += '</p>';
							
							jQuery(fields).appendTo('#cpwebservice_boxes');
						});

						jQuery('#btn_cpwebservice_lettermail').click(function() {
							
							var i = jQuery('#cpwebservice_lettermail p').size(); // one p tag.
							
							var fields = '';
							fields += '<p class="form-field">';
							fields += '<select name="woocommerce_cpwebservice_lettermail_country[]"><option value="CA" selected="selected">Canada</option><option value="US">USA</option><option value="INT"><?php _e('International', 'woocommerce-canadapost-webservice') ?></option></select>';
							fields += '<label> <?php _e('Label', 'woocommerce-canadapost-webservice'); ?>:</label>';
							fields += '<input name="woocommerce_cpwebservice_lettermail_label[]" id="woocommerce_cpwebservice_lettermail_label'+i+'" style="max-width:150px" placeholder="<?php _e('Lettermail','woocommerce-canadapost-webservice'); ?>" class="input-text" size="16" type="text" value="">';
							fields += '<span class="description"> <?php _e('Cost','woocommerce-canadapost-webservice'); ?>: $</span><input name="woocommerce_cpwebservice_lettermail_cost[]" id="woocommerce_cpwebservice_lettermail_cost'+i+'" style="max-width:50px" placeholder="<?php _e('Cost','woocommerce-canadapost-webservice'); ?>" class="input-text" size="16" type="text" value="">';
							fields += '<span class="description"> <?php _e('Weight Range(kg)','woocommerce-canadapost-webservice'); ?>: </span><input name="woocommerce_cpwebservice_lettermail_weight_from[]" id="woocommerce_cpwebservice_lettermail_weight_from'+i+'" style="max-width:40px" placeholder="" class="input-text" size="6" type="text" value="0">kg';
							fields += ' <?php _e('to','woocommerce-canadapost-webservice'); ?> &lt;=';
							fields += '<input name="woocommerce_cpwebservice_lettermail_weight_to[]" id="woocommerce_cpwebservice_lettermail_weight_to'+i+'" style="max-width:40px" placeholder="" class="input-text last" size="6" type="text" value="0">kg</span>';
							fields += '<span class="description"> <?php _e('Max items (0 for no limit)','woocommerce-canadapost-webservice'); ?>: </span> <input name="woocommerce_cpwebservice_lettermail_max_qty[]" id="woocommerce_cpwebservice_lettermail_max_qty'+i+'" style="max-width:40px" placeholder="" class="input-text" size="6" type="text" value="" />';
							fields += '<span class="description"> <?php _e('Cart subtotal','woocommerce-canadapost-webservice'); ?>: $</span><input name="woocommerce_cpwebservice_lettermail_min_total[]" id="woocommerce_cpwebservice_lettermail_min_total'+i+'" style="max-width:50px" placeholder="" class="input-text" size="6" type="text" value="" />';
							fields += '<?php _e('to','woocommerce-canadapost-webservice'); ?> &lt; $<input name="woocommerce_cpwebservice_lettermail_max_total[]" id="woocommerce_cpwebservice_lettermail_max_total'+i+'" style="max-width:50px" placeholder="" class="input-text last" size="6" type="text" value="" />';
							fields += '<span style="margin-left:5px;"><a href="javascript:;" title="Remove" onclick="'+"jQuery(this).parent().parent('p').remove();"+'" class="button"><?php _e('Remove','woocommerce-canadapost-webservice'); ?></a></span>';
							fields += '</p>';
							
							jQuery(fields).appendTo('#cpwebservice_lettermail');
						});
					});

					</script>
					
					<table class="form-table">
					<tr valign="top">
					    <th scope="row" class="titledesc">
					    	<?php _e('Logging', 'woocommerce-canadapost-webservice')?>
					    </th>
						<td class="forminp">
						<label for="woocommerce_cpwebservice_log_enable">
									<input name="woocommerce_cpwebservice_log_enable" id="woocommerce_cpwebservice_log_enable" type="checkbox" value="1" <?php checked($this->options->log_enable=='1'); ?> /> <?php _e('Enable Rates Lookup Logging', 'woocommerce-canadapost-webservice') ?>
									<br /><small><?php _e('Captures most recent shipping rate lookup.  Recommended to be disabled when website development is complete. This option does not display any messages on frontend.', 'woocommerce-canadapost-webservice') ?></small></label>
						<?php if ($this->options->log_enable): ?>
						<div><a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=cpwebservice_rates_log_display' ), 'cpwebservice_rates_log_display' ); ?>" title="Display Log" class="button cpwebservice-log-display"><?php _e('Display most recent request','woocommerce-canadapost-webservice'); ?></a> <img src="<?php admin_url(); ?>/wp-admin/images/wpspin_light.gif" alt="" class="cpwebservice-log-display-loading" style="display: none;" /></div>
						<div id="cpwebservice_log_display" style="display:none;padding:5px;margin:5px;border:1px solid #ccc;width:70%">
						<p></p>
						</div>
						<?php endif; ?> 
						</td>
						</tr>
					</table>
					
			<?php 
			}

			function process_admin_options() {

				
				// check for security
				if (!isset($_POST['cpwebservice_options_noncename']) || !wp_verify_nonce($_POST['cpwebservice_options_noncename'], plugin_basename(__FILE__))) 
					return;


				if(isset($_POST['woocommerce_cpwebservice_enabled'])) $this->options->enabled = 'yes'; else $this->options->enabled ='no';
				if(isset($_POST['woocommerce_cpwebservice_title'])) $this->options->title = woocommerce_clean($_POST['woocommerce_cpwebservice_title']);
				if(isset($_POST['woocommerce_cpwebservice_account'])) $this->options->account = woocommerce_clean($_POST['woocommerce_cpwebservice_account']);
				if(isset($_POST['woocommerce_cpwebservice_contractid'])) $this->options->contractid = woocommerce_clean($_POST['woocommerce_cpwebservice_contractid']);
				if(isset($_POST['woocommerce_cpwebservice_api_user'])) $this->options->api_user = woocommerce_clean($_POST['woocommerce_cpwebservice_api_user']);
				if(isset($_POST['woocommerce_cpwebservice_api_key'])) $this->options->api_key = woocommerce_clean($_POST['woocommerce_cpwebservice_api_key']);
				if(isset($_POST['woocommerce_cpwebservice_source_postalcode'])) $this->options->source_postalcode = woocommerce_clean($_POST['woocommerce_cpwebservice_source_postalcode']);
				$this->options->source_postalcode = str_replace(' ','',strtoupper($this->options->source_postalcode)); // N0N0N0 format only
				if(isset($_POST['woocommerce_cpwebservice_mode'])) $this->options->mode = woocommerce_clean($_POST['woocommerce_cpwebservice_mode']);
				if(isset($_POST['woocommerce_cpwebservice_delivery'])) $this->options->delivery = intval(woocommerce_clean($_POST['woocommerce_cpwebservice_delivery'])); 
				if ($this->options->delivery==0) { $this->options->delivery = ''; }
				if(isset($_POST['woocommerce_cpwebservice_margin_value']))  $this->options->margin_value = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_margin_value']));
				if(isset($_POST['woocommerce_cpwebservice_margin'])) $this->options->margin = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_margin']));
				if (!empty($this->options->margin) && $this->options->margin == 0) { $this->options->margin = ''; } // percentage only != 0
				if(isset($_POST['woocommerce_cpwebservice_packageweight'])) $this->options->packageweight = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_packageweight']));
				if(isset($_POST['woocommerce_cpwebservice_display_errors'])) $this->options->display_errors = true; else $this->options->display_errors = false;
				if(isset($_POST['woocommerce_cpwebservice_log_enable'])) $this->options->log_enable = true; else $this->options->log_enable = false;
				if(isset($_POST['woocommerce_cpwebservice_boxes_enable'])) $this->options->boxes_enable = true; else $this->options->boxes_enable = false;
				if(isset($_POST['woocommerce_cpwebservice_lettermail_enable'])) $this->options->lettermail_enable = true; else $this->options->lettermail_enable = false;
				if(isset($_POST['woocommerce_cpwebservice_shipping_tracking'])) $this->options->shipping_tracking = true; else $this->options->shipping_tracking = false;
				if(isset($_POST['woocommerce_cpwebservice_email_tracking'])) $this->options->email_tracking = true; else $this->options->email_tracking = false;
				if(isset($_POST['woocommerce_cpwebservice_tracking_icons'])) $this->options->tracking_icons = true; else $this->options->tracking_icons = false;

				// services
				if(isset($_POST['woocommerce_cpwebservice_services']) && is_array($_POST['woocommerce_cpwebservice_services'])) {
					// save valid options. ( returns an array containing all the values of array1 that are present in array2 - in this case, an array of valid service codes)
					$this->services = array_intersect($_POST['woocommerce_cpwebservice_services'], array_keys($this->available_services));
					update_option('woocommerce_cpwebservice_services', $this->services);
 				}
				
				// boxes
				if( isset($_POST) && isset($_POST['woocommerce_cpwebservice_box_length']) && is_array($_POST['woocommerce_cpwebservice_box_length']) ) {
					$boxes = array();
				
					for ($i=0; $i < count($_POST['woocommerce_cpwebservice_box_length']); $i++){
						$box = array();
						$box['length'] = isset($_POST['woocommerce_cpwebservice_box_length'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_box_length'][$i]),1) : '';
						$box['width'] = isset($_POST['woocommerce_cpwebservice_box_width'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_box_width'][$i]),1) : '';
						$box['height'] = isset($_POST['woocommerce_cpwebservice_box_height'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_box_height'][$i]),1) : '';
						$box['name'] = isset($_POST['woocommerce_cpwebservice_box_name'][$i]) ? woocommerce_clean($_POST['woocommerce_cpwebservice_box_name'][$i]) : '';
						// Cubed/volumetric
						$box['cubed'] = $box['length'] * $box['width'] * $box['height'];
						
						$boxes[] = $box;
					}
				
					$this->boxes = $boxes;
					update_option('woocommerce_cpwebservice_boxes', $this->boxes);
				}
				
				// lettermail
				if( isset($_POST) && isset($_POST['woocommerce_cpwebservice_lettermail_country']) && is_array($_POST['woocommerce_cpwebservice_lettermail_country']) ) {
					$lettermail = array();
				
					for ($i=0; $i < count($_POST['woocommerce_cpwebservice_lettermail_country']); $i++){
						$row = array();
						$row['country'] = isset($_POST['woocommerce_cpwebservice_lettermail_country'][$i]) ? woocommerce_clean($_POST['woocommerce_cpwebservice_lettermail_country'][$i]) : '';
						$row['label'] = isset($_POST['woocommerce_cpwebservice_lettermail_label'][$i]) ? woocommerce_clean($_POST['woocommerce_cpwebservice_lettermail_label'][$i]) : '';
						$row['cost'] = isset($_POST['woocommerce_cpwebservice_lettermail_cost'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_lettermail_cost'][$i]),2) : '';
						$row['weight_from'] = isset($_POST['woocommerce_cpwebservice_lettermail_weight_from'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_lettermail_weight_from'][$i]),2) : '';
						$row['weight_to'] = isset($_POST['woocommerce_cpwebservice_lettermail_weight_to'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_lettermail_weight_to'][$i]),2) : '';
						if ($row['weight_from'] > $row['weight_to']) { $row['weight_from'] = $row['weight_to']; } // Weight From must be a lesser value.
						$row['max_qty'] = isset($_POST['woocommerce_cpwebservice_lettermail_max_qty'][$i]) ? intval($_POST['woocommerce_cpwebservice_lettermail_max_qty'][$i]) : 0;
						$row['min_total'] = !empty($_POST['woocommerce_cpwebservice_lettermail_min_total'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_lettermail_min_total'][$i]),2) : '';
						$row['max_total'] = !empty($_POST['woocommerce_cpwebservice_lettermail_max_total'][$i]) ? round(floatval($_POST['woocommerce_cpwebservice_lettermail_max_total'][$i]),2) : '';
						
						$lettermail[] = $row;
					}
				
					$this->lettermail = $lettermail;
					update_option('woocommerce_cpwebservice_lettermail', $this->lettermail);
				}
				if(isset($_POST['woocommerce_cpwebservice_lettermail_limits'])) $this->options->lettermail_limits = true; else $this->options->lettermail_limits = false;
				if(isset($_POST['woocommerce_cpwebservice_lettermail_maxlength'])) $this->options->lettermail_maxlength = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_lettermail_maxlength']));
				if(isset($_POST['woocommerce_cpwebservice_lettermail_maxwidth'])) $this->options->lettermail_maxwidth = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_lettermail_maxwidth']));
				if(isset($_POST['woocommerce_cpwebservice_lettermail_maxheight'])) $this->options->lettermail_maxheight = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_lettermail_maxheight']));
				if (empty($this->options->lettermail_maxlength)) $this->options->lettermail_maxlength = '';
				if (empty($this->options->lettermail_maxwidth)) $this->options->lettermail_maxwidth = '';
				if (empty($this->options->lettermail_maxheight)) $this->options->lettermail_maxheight = '';
				if(isset($_POST['woocommerce_cpwebservice_lettermail_packageweight'])) $this->options->lettermail_packageweight = floatval(woocommerce_clean($_POST['woocommerce_cpwebservice_lettermail_packageweight']));
				if(isset($_POST['woocommerce_cpwebservice_lettermail_override_weight'])) $this->options->lettermail_override_weight = true; else $this->options->lettermail_override_weight = false;
				
				// update options.
				update_option('woocommerce_cpwebservice', $this->options);
			}
			
			
			/**
			 * Ajax function to Display Rates Lookup Log.
			 */
			public function rates_log_display() {
			
				// Let the backend only access the page
				if( !is_admin() ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
					
				// Check the user privileges
				if( !current_user_can( 'manage_woocommerce_orders' ) && !current_user_can( 'edit_shop_orders' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
					
				// Nonce.
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'cpwebservice_rates_log_display' ) )
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				
				
				if (false !== ( $log = get_transient('cpwebservice_log') ) && !empty($log)){
				$log = (object) array_merge(array('cart'=>array(),'params'=>array(),'request'=>array(),'rates'=>array(), 'datestamp'=>''), (array) $log);
					?>
										<h4><?php _e('Cart Shipping Rates Request', 'woocommerce-canadapost-webservice')?> - <?php echo date("F j, Y, g:i a",$log->datestamp); ?></h4>
										<table class="table widefat">
										<tr><th><?php _e('Item', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Qty', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Weight', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Dimensions', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Cubic', 'woocommerce-canadapost-webservice')?></th></tr>
										<?php foreach($log->cart as $cart):?>
										<tr>
										<td><?php echo edit_post_link(esc_html($cart['item']),'','',$cart['id'])?></td><td><?php echo esc_html($cart['quantity'])?></td><td><?php echo esc_html($cart['weight'])?>kg</td><td><?php echo esc_html($cart['length'])?>cm x <?php echo esc_html($cart['width'])?>cm x <?php echo esc_html($cart['height'])?>cm</td><td><?php echo esc_html($cart['cubic'])?>cm<sup>3</sup></td>
										</tr>
										<?php endforeach; //$this->log->cart[] = array('quantity'=>$values['quantity'], 'weight'=>$this->convertWeight($values['data']->get_weight(), $weight_unit) * $values['quantity'], 'length'=>$length, 'width'=>$width, 'height'=>$height, 'cubic'=>$cubic); ?>
										</table>
										
										<h4><?php _e('Request / API Response', 'woocommerce-canadapost-webservice')?></h4>
										<p class="description"><?php _e('After box packing/Volumetric weight calculation and Box/Envelope Weight', 'woocommerce-canadapost-webservice')?></p>
										<table class="table widefat">
										<tr><th><?php _e('Country', 'woocommerce-canadapost-webservice')?></th><th><?php _e('State', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Destination', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Shipping Weight', 'woocommerce-canadapost-webservice')?></th><th><?php _e('Volumetric Dimensions', 'woocommerce-canadapost-webservice')?></th></tr>
										<tr>
										<td><?php echo esc_html($log->params['country'])?></td><td><?php echo esc_html($log->params['state'])?></td><td><?php echo esc_html($log->params['postal'])?></td><td><?php echo esc_html(number_format($log->params['shipping_weight'],2))?>kg</td>
										<td><?php echo esc_html(number_format($log->params['length'],2))?>cm x <?php echo esc_html(number_format($log->params['width'],2))?>cm x <?php echo esc_html(number_format($log->params['height'],2))?>cm</td>
										<?php //array('country'=>$country, 'state'=>$state, 'postal'=>$postal, 'shipping_weight'=>$shipping_weight, 'length'=>$length, 'width'=>$width, 'height'=>$height); ?>
										</tr>
										</table>
										<br />
										<table class="table widefat">
										<tr><td>
										<?php foreach($log->request as $request):?><?php echo str_replace("\n\n","</td><td>",str_replace("\n","<br />",esc_html($request))) ?><?php endforeach; ?>
										</td>
										</tr></table>
										
										<h4><?php _e('Rates displayed in Cart', 'woocommerce-canadapost-webservice')?></h4>
										<?php if(!empty($log->rates)): ?>
										<table class="table widefat">
										<?php foreach($log->rates as $rates):?>
										<tr>
										<th><?php echo $rates->label ?></th>
										<td><?php echo number_format($rates->cost, 2) ?>
										</td>
										</tr>
										<?php endforeach; ?>
										</table>
										<?php else: ?>
										<p><?php _e('No rates displayed', 'woocommerce-canadapost-webservice') ?></p>
										<?php endif; ?>
										<?php } else { ?>
						<?php _e('No log information.. yet.  Go to your shopping cart page and click on "Calculate Shipping".', 'woocommerce-purolator-webservice') ?>
						<?php  } // endif
				
			exit;
			}
			
			
			/**
			 * Load and generate the template output with ajax
			 */
			public function validate_api_credentials() {
				// Let the backend only access the page
				if( !is_admin() ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
					
				// Check the user privileges
				if( !current_user_can( 'manage_woocommerce_orders' ) && !current_user_can( 'edit_shop_orders' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
					
				// Check the nonce
				if( empty( $_GET['action'] ) || !check_admin_referer( $_GET['action'] ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
					
				// Check if all parameters are set
				if( empty( $_POST['api_user'] ) || empty( $_POST['api_key'] ) || empty( $_POST['customerid'] ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
			
				// Nonce.
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'cpwebservice_validate_api_credentials' ) )
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
					
				// Get api_user, api_key, customerid
				$api_user = sanitize_text_field( $_POST['api_user'] );
				$api_key = sanitize_text_field( $_POST['api_key'] );
				$customerid = sanitize_text_field( $_POST['customerid'] );
				$contractid = sanitize_text_field( $_POST['contractid'] );
			
				$apiValid = false;
				$message = "";
				
				// Check API.
				$username = $this->options->api_user = $api_user;
				$password = $this->options->api_key = $api_key;
				$this->options->account = $customerid;
				$this->options->contractid = $contractid;
				
				
				// REST URL  (Get Service Info)
				$service_url = ($this->options->mode=='live') ? 'https://soa-gw.canadapost.ca/rs/ship/service/DOM.EP?country=CA' : 'https://ct.soa-gw.canadapost.ca/rs/ship/service/DOM.EP?country=CA';
				
				// If using WPML:
				if (defined('ICL_LANGUAGE_CODE')){
					$service_language = (ICL_LANGUAGE_CODE=='fr') ? 'fr-CA':'en-CA'; // 'en-CA' is default
				} else if (WPLANG == 'fr_FR'){
					$service_language = 'fr-CA';
				} else {
					$service_language = 'en-CA';
				}
				
				echo ($this->options->mode=='live') ? 'Production/Live Server: ' : 'Development Server: ';
				
				if (!empty($username) && !empty($password)){ 
					try {
						$curl = curl_init($service_url); // Create REST Request
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
						curl_setopt($curl, CURLOPT_CAINFO, CPWEBSERVICE_PLUGIN_PATH . '/cert/cacert.pem'); // Signer Certificate in PEM format
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
						curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
						curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/vnd.cpc.ship.rate-v2+xml','Accept-language: '.$service_language));
						$curl_response = curl_exec($curl); // Execute REST Request
						if(curl_errno($curl)){
							$message .= 'Error: ' . curl_error($curl) . "\n";
						}
						$message .= 'HTTP Response Status: ' . curl_getinfo($curl,CURLINFO_HTTP_CODE) . "\n";
						curl_close($curl);
						
						// Using SimpleXML to parse xml response
						libxml_use_internal_errors(true);
						$xml = simplexml_load_string('<root>' . preg_replace('/<\?xml.*\?>/','',$curl_response) . '</root>');
						if (!$xml) {
							$errmsg = 'Failed loading XML' . "\n";
							$errmsg .= $curl_response . "\n";
							foreach(libxml_get_errors() as $error) {
								$errmsg.= "\t" . $error->message;
							}
							$message .= $errmsg;
						} else {
							if ($xml->{'service'} ) {
								// Success! API correctly responded.
								$apiValid = true;
							}
							if ($xml->{'messages'}) {
								$apierror = '';
								$messages = $xml->{'messages'}->children('http://www.canadapost.ca/ws/messages');
								foreach ( $messages as $message ) {
									$apierror .= 'Error Code: ' . $message->code . "\n";
									$apierror .= 'Error Msg: ' . $message->description . "\n\n";
								}
								$message .= $apierror;
							}
						}
						
					} catch (Exception $ex) {
						// cURL request went wrong.
						$message .= 'Error: ' . $ex . "\n";
					}
				}
				
				echo str_replace("\n","<br />",$message);
				
				if ($apiValid) {
					echo '<strong>Success!</strong> API Credentials validated with Canada Post.';
				} else {
					echo '<strong>Failed</strong> API Credentials did not validate.';
				}
				
				// Try get_rates to see if customer info works.
				if ($apiValid) {
					echo '<br /><strong>Testing Rates Lookup:</strong><br />';
					$this->options->display_errors = true;
					$rates = $this->get_rates('CA','ON','K1A 0B1',0.5,5,5,2); // Ship 5x5x2cm package to CP headquarters, Ottawa.
					if (is_array($rates) && !empty($rates)) {
						echo '<br /><strong>Rates Lookup Success!</strong> CustomerID/Venture One information appears to be valid.';
					} else {
						echo '<br /><strong>Rates Lookup Failed</strong> Unable to look up rates. CustomerID/Venture One account number may be invalid or inactive.';
					}
				}
				
				exit;
			}
			
			function convertSize($size,$unit_from) {
				$finalSize = $size;
				switch ($unit_from) {
					// we need the units in cm
					case 'cm':
						// change nothing
						$finalSize = $size;
						break;
					case 'in':
						// convert from in to cm
						$finalSize = $size * 2.54;
						break;
					case 'yd':
						// convert from yd to cm
						$finalSize = $size * 3 * 2.54;
						break;
					case 'm':
						// convert from m to cm
						$finalSize = $size * 100;
						break;
					case 'mm':
						// convert from mm to cm
						$finalSize = $size * 0.1;
						break;
				}
				return $finalSize;
			}
			
			function convertWeight($weight,$unit_from) {
				$finalWeight = $weight;
				switch ($unit_from) {
					// we need the units in kg
					case 'kg':
						// change nothing
						$finalWeight = $weight;
						break;
					case 'g':
						// convert from g to kg
						$finalWeight = $weight * 0.001;
						break;
					case 'lbs':
						// convert from lbs to kg
						$finalWeight = $weight * 0.4535;
						break;
					case 'oz':
						// convert from oz to kg
						$finalWeight = $weight * 0.0283;
						break;
				}
				return $finalWeight;
			}

}