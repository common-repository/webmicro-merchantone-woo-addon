<?php
/**
 * Plugin Name: Merchant One WooCommerce Addon
 * Plugin URI: Plugin URI: https://wordpress.org/plugins/webmicro-merchantone-woo-addon/
 * Description: This plugin adds a payment option in WooCommerce for customers to pay with their Credit Cards Via Merchant One.
 * Version: 1.0.0
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function merchantone_init()
{
	include(plugin_dir_path( __FILE__ )."class/MerchantOne.php");
	function add_merchantone_gateway_class( $methods ) 
	{
		$methods[] = 'WC_MerchantOne_Gateway'; 
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_merchantone_gateway_class' );
	
	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_MerchantOne_Gateway extends WC_Payment_Gateway 
		{
		public function __construct()
		{

		$this->id               = 'merchantonegateway';
		$this->icon             = plugins_url( 'images/merchantone.png' , __FILE__ )  ;
		$this->has_fields       = true;
		$this->method_title     = 'Merchant One Cards Settings';		
		$this->init_form_fields();
		$this->init_settings();

		$this->supports                 = array( 'products');

		$this->title			          = $this->get_option( 'merchantone_title' );
		$this->merchantone_description        = $this->get_option( 'merchantone_description');

		$this->merchantone_apilogin       = $this->get_option( 'merchantone_apilogin' );
		$this->merchantone_transactionkey = $this->get_option( 'merchantone_transactionkey' );
		$this->merchantone_authorize_only = $this->get_option( 'merchantone_authorize_only' );
		$this->merchantone_cardtypes      = $this->get_option( 'merchantone_cardtypes'); 
		

		if(!defined("MERCHANTONE_TRANSACTION_MODE"))
		{ define("MERCHANTONE_TRANSACTION_MODE"  , ($this->merchantone_authorize_only =='yes'? true : false)); }

		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		 if (is_admin()) 
		 {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 		 }

		}
		
		
		
		public function admin_options()
		{
		?>
		<h3><?php _e( 'Merchant One addon for WooCommerce', 'woocommerce' ); ?></h3>
		<p><?php  _e( 'Merchant One is a payment gateway service provider allowing merchants to accept credit card.', 'woocommerce' ); ?></p>
		<table class="form-table">
		  <?php $this->generate_settings_html(); ?>
		</table>
		<?php
		}
		
		
		
		public function init_form_fields()
		{
		$this->form_fields = array
		(
			'enabled' => array(
			  'title' => __( 'Enable/Disable', 'woocommerce' ),
			  'type' => 'checkbox',
			  'label' => __( 'Enable Merchant One', 'woocommerce' ),
			  'default' => 'yes'
			  ),
			'merchantone_title' => array(
			  'title' => __( 'Title', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This controls the title which the buyer sees during checkout.', 'woocommerce' ),
			  'default' => __( 'Merchant One', 'woocommerce' ),
			  'desc_tip'      => true,
			  ),

			'merchantone_description' => array(
						'title' => __( 'Description', 'woocommerce' ),
						'type' => 'textarea',
						'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
						'default' => __( 'All cards are charged by merchantone servers.', 'woocommerce' ),
						'desc_tip'      => true,
						),

			'merchantone_apilogin' => array(
			  'title' => __( 'Merchantone username', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is the Merchant One login username.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Merchant One API Login ID'
			  ),
			'merchantone_transactionkey' => array(
			  'title' => __( 'Merchantone password', 'woocommerce' ),
			  'type' => 'text',
			  'description' => __( 'This is the Merchant One login password.', 'woocommerce' ),
			  'default' => '',
			  'desc_tip'      => true,
			  'placeholder' => 'Merchant One Transaction Key',
			  ),

			'merchantone_authorize_only' => array(
				'title'       => __( 'Authorize Only', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Authorize Only Mode (Authorize & Capture If Unchecked)', 'woocommerce' ),
				'description' => __( 'If checked will only authorize the credit card only upon checkout.', 'woocommerce' ),
				'desc_tip'      => true,
				'default'     => 'no',
				),

			'merchantone_cardtypes' => array(
			 'title'    => __( 'Accepted Cards', 'woocommerce' ),
			 'type'     => 'multiselect',
			 'class'    => 'chosen_select',
			 'css'      => 'width: 350px;',
			 'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
			 'options'  => array(
				'mastercard'       	=> 'MasterCard',
				'visa'             	=> 'Visa',
				'discover'         	=> 'Discover',
				'amex' 		    	=> 'American Express',
				'jcb'		    	=> 'JCB',
				'dinersclub'       	=> 'Dinners Club',
			 ),
			 'default' => array( 'mastercard', 'visa', 'discover', 'amex' ),
			)

	  	);
  		}


  		public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
		}

  		public function get_icon() {
		$icon = '';
		if(is_array($this->merchantone_cardtypes ))
		{
        foreach ($this->merchantone_cardtypes as $card_type ) {

			if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
				
				$icon .= '<img width="45" src="' . esc_url( $url ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />';
			}
		  }
		}
		else
		{
			$icon .= '<img src="' . esc_url( plugins_url( 'images/merchantone.png' , __FILE__ ) ).'" alt="Mercant One Gateway" />';	  
		}

         return apply_filters( 'woocommerce_merchantone_icon', $icon, $this->id );
		}
      
		public function get_payment_method_image_url( $type ) {

		$image_type = strtolower( $type );

			return  WC_HTTPS::force_https_url( plugins_url( 'images/' . $image_type . '.png' , __FILE__ ) ); 
		}


				
		/*Get Card Types*/
		function get_card_type($number)
		{
		    $number=preg_replace('/[^\d]/','',$number);
		    if (preg_match('/^3[47][0-9]{13}$/',$number))
		    {
		        return 'amex';
		    }
		    elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
		    {
		        return 'dinersclub';
		    }
		    elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
		    {
		        return 'discover';
		    }
		    elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
		    {
		        return 'jcb';
		    }
		    elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
		    {
		        return 'mastercard';
		    }
		    elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
		    {
		        return 'visa';
		    }
		    else
		    {
		        return 'invalid';
		    }
		}// End of getcard type function
		
		
		//Function to check IP
		function get_client_ip() 
		{
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
				$ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = '0.0.0.0';
			return $ipaddress;
		}
		
		//End of function to check IP

		
		     /*Start of credit card form */

  		public function payment_fields() {
  			echo apply_filters( 'wc_merchantonegateway_description', wpautop(wp_kses_post( wptexturize(trim($this->merchantone_description) ) ) ) );
			$this->form();
		}

  		public function field_name( $name ) {
		return $this->supports( 'tokenization' ) ? '' : ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
	}

  		public function form() {
		wp_enqueue_script( 'wc-credit-card-form' );
		$fields = array();
		$cvc_field = '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
			<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . '/>
		</p>';
		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
			</p>',
			'card-cvc-field'  => $cvc_field
		);
		
		 $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>

		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
		
	}
  		/*End of credit card form*/


		/*Payment Processing Fields*/
		public function process_payment($order_id)
		{
		
			global $woocommerce;
         	$wc_order = new WC_Order($order_id);
         		
			$cardtype = $this->get_card_type( sanitize_text_field(str_replace(" ", "",$_POST['merchantonegateway-card-number']) ) );
			
         		if(!in_array($cardtype ,$this->merchantone_cardtypes ))
         		{
         			wc_add_notice('Merchant do not accept '.$cardtype.' card' ,  $notice_type = 'error' );
         			return false;die;
         		}
         
		$exp_date         = explode( "/", sanitize_text_field($_POST['merchantonegateway-card-expiry']));
			$exp_month        = str_replace( ' ', '', $exp_date[0]);
			$exp_year         = str_replace( ' ', '',$exp_date[1]);

			if (strlen($exp_year) == 2) {
			$exp_year += 2000;
			}

			$gw = new merchantoneapi;
			$gw->setLogin($this->merchantone_apilogin, $this->merchantone_transactionkey);
			$gw->setBilling(
						$wc_order->billing_first_name,
						$wc_order->billing_first_name,
						$wc_order->billing_company,
						$wc_order->billing_address_1,
						$wc_order->billing_address_2, 
						$wc_order->shipping_city,
						$wc_order->billing_state,
						$wc_order->billing_postcode,
						$wc_order->billing_country,
						$wc_order->billing_phone,
						$wc_order->billing_phone,
						$wc_order->billing_email,
						get_bloginfo('url')
						);
			$gw->setShipping(
						$wc_order->shipping_first_name,
						$wc_order->shipping_last_name,
						$wc_order->shipping_company,
						$wc_order->shipping_address_1,
						$wc_order->shipping_address_2, 
						$wc_order->shipping_city,
						$wc_order->shipping_state,
						$wc_order->shipping_postcode,
						$wc_order->shipping_country,
						$wc_order->shipping_email);

			$gw->setOrder(
						$wc_order->get_order_number(),
						get_bloginfo('blogname').' Order #'.$wc_order->get_order_number(),
						number_format($wc_order->get_total_tax(),2,".",""), 
						number_format($wc_order->get_total_shipping(),2,".",""), 
						$wc_order->get_order_number(),
						$this->get_client_ip()
						);

			//authorize only tru so authorization only 
			if(true == MERCHANTONE_TRANSACTION_MODE)
			{
				$r = $gw->doAuth(
				number_format($wc_order->order_total,2,".",""),
				sanitize_text_field(str_replace(" ", "",$_POST['merchantonegateway-card-number']) ),
				$exp_month.$exp_year ,
				sanitize_text_field($_POST['merchantonegateway-card-cvc'])

						);
			}
			//authorize and capture  
			else
			{
			$r = $gw->doSale(
				number_format($wc_order->order_total,2,".",""),
				sanitize_text_field(str_replace(" ", "",$_POST['merchantonegateway-card-number']) ),
				$exp_month.$exp_year ,
				sanitize_text_field($_POST['merchantonegateway-card-cvc'])

						);
			}

		if ( count($gw->responses) > 1 )
		{
			 if( 100 == $gw->responses['response_code'] )
			{
			$wc_order->add_order_note( __( $gw->responses['responsetext'].' on '.date("d-m-Y h:i:s e"). ' with Transaction ID = '.$gw->responses['transactionid'].' AVS Response: '.$gw->responses['avsresponse'].' CVS Response: '.$gw->responses['cvvresponse'].' #Order no: '.$gw->responses['orderid'].' Trx Type: '.$gw->responses['type'].' Authorization Code: '.$gw->responses['authcode'].'Response '.$gw->responses['response']  , 'woocommerce' ) );
			
			$wc_order->payment_complete($gw->responses['transactionid']);
			WC()->cart->empty_cart();
			return array (
						'result'   => 'success',
						'redirect' => $this->get_return_url( $wc_order ),
					   );
			}
			else 
			{
				$wc_order->add_order_note( __( $gw->responses['responsetext']  , 'woocommerce' ) );	 
				wc_add_notice($gw->responses['responsetext'] , $notice_type = 'error' );
			}
		}
		else 
		{
			$wc_order->add_order_note( __( $gw->responses['responsetext']  , 'woocommerce' ) );	 
				wc_add_notice($gw->responses['responsetext'] , $notice_type = 'error' );
		}
        
		}// End of process_payment
		
		
		}// End of class WC_Authorizenet_merchantone_Gateway
	} // End if WC_Payment_Gateway
}// End of function authorizenet_merchantone_init

add_action( 'plugins_loaded', 'merchantone_init' );

function merchantone_addon_activate() {

	if(!function_exists('curl_exec'))
	{
		 wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated </pre>' );
	}
}
register_activation_hook( __FILE__, 'merchantone_addon_activate' );

/*Plugin Settings Link*/
function merchantone_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_merchantone_gateway">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'merchantone_settings_link' );
