<?php

/*
  Plugin Name: Taurus Datafast WooCommerce Payment Gateway
  Plugin URI: https://www.taurusonline.com
  Description: Taurus Datafast Payment Gateway allows you to accept payment through various channels such as Maestro, Mastercard, AMex cards, Diners  and Visa cards On your Woocommerce Powered Site.
  Version: 1.0
  Author: Abraham
  Author URI: https://www.taurusonline.com
  License: GPL-3.0+
  License URI: http://www.gnu.org/licenses/gpl-3.0.txt
  WC tested up to: 4.3.0
  Text Domain: woo-payment-gateway-for-taurus-datafast
  Domain Path: /languages
*/
 /*
 Based on original plugin "Piraeus Bank Greece Payment Gateway for WooCommerce" by emspace.gr [https://wordpress.org/plugins/woo-payment-gateway-piraeus-bank-greece/]
 */

if (!defined('ABSPATH'))
    exit;

add_action('plugins_loaded', 'woocommerce_taurusdatafast_init', 0);

function woocommerce_taurusdatafast_init() {

    if (!class_exists('WC_Payment_Gateway'))
        return;

    load_plugin_textdomain('woo-payment-gateway-for-taurus-datafast', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    /**
     * Gateway class
     */
    class WC_taurusdatafast_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            global $woocommerce;

            $this->id = 'taurusdatafast_gateway';
            //$this->icon = apply_filters('taurusdatafast_icon', plugins_url('img/df-logo.jpg', __FILE__));
            $this->has_fields = true;
            $this->notify_url = WC()->api_request_url('WC_taurusdatafast_Gateway');
            $this->method_description = __('Taurus Datafast Payment Gateway allows you to accept payment through various channels such as Maestro, Mastercard, AMex cards, Diners  and Visa cards On your Woocommerce Powered Site.', 'woo-payment-gateway-for-taurus-datafast');
            $this->redirect_page_id = $this->get_option('redirect_page_id');
            $this->method_title = 'Taurus Datafast  Gateway';

            // Load the form fields.
            $this->init_form_fields();



            global $wpdb;

            if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "taurusdatafast_transactions'") === $wpdb->prefix . 'taurusdatafast_transactions') {
                // The database table exist
            } else {
                // Table does not exist
                $query = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'taurusdatafast_transactions (id int(11) unsigned NOT NULL AUTO_INCREMENT, merch_ref varchar(50) not null, trans_ticket varchar(32) not null , timestamp datetime default null, PRIMARY KEY (id))';
                $wpdb->query($query);
            }


            // Load the settings.
            $this->init_settings();


            // Define user set variables
            $this->title = sanitize_text_field($this->get_option('title'));
            $this->description = sanitize_text_field($this->get_option('description'));
            $this->pb_PayMerchantId = sanitize_text_field($this->get_option('pb_PayMerchantId'));
            $this->pb_TransactionId = sanitize_text_field($this->get_option('pb_TransactionId'));
            $this->pb_EntityId = sanitize_text_field($this->get_option('pb_EntityId'));
            $this->pb_AccessToken = sanitize_text_field($this->get_option('pb_AccessToken'));
            $this->pb_render_logo = sanitize_text_field($this->get_option('pb_render_logo'));
            //Actions
            add_action('woocommerce_receipt_taurusdatafast_gateway', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_taurusdatafast_gateway', array($this, 'check_taurusdatafast_response'));

//            if ($this->pb_authorize == "yes") {
//                add_action('admin_notices', array($this, 'authorize_warning_notice'));
//            }
            if($this->pb_render_logo == "yes") {
                $this->icon = apply_filters('taurusdatafast_icon', plugins_url('img/df-logo.svg', __FILE__));
            }
        }

        /**
         * Admin Panel Options
         * */
        public function admin_options() {
            echo '<h3>' . __('Taurus Datafast Gateway', 'woo-payment-gateway-for-taurus-datafast') . '</h3>';
            echo '<p>' . __('Taurus Datafast Gateway allows you to accept payment through various channels such as Maestro, Mastercard, AMex cards, Diners  and Visa cards.', 'woo-payment-gateway-for-taurus-datafast') . '</p>';


            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
            // $host = (is_ssl() == true ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/'; 
            // echo '<div>';
            // echo '<h4>Στοιχεία για διασύνδεση με την τράπεζα</h4>';
            // echo '<ul>';

            // echo '<li>Website URL: ' . $host . '</li>';
            // echo '<li>Referrer url: '. $host . (get_option('permalink_structure') ? 'checkout/'  : 'checkout/' ) .' </li>';
            // echo '<li>Success page: '. $host . (get_option('permalink_structure') ? 'wc-api/WC_taurusdatafast_Gateway?peiraeus=success'  : '?wc-api=WC_taurusdatafast_Gateway&peiraeus=success' ) .' </li>';
            // echo '<li>Failure page: '. $host . (get_option('permalink_structure') ? 'wc-api/WC_taurusdatafast_Gateway?peiraeus=fail'  : '?wc-api=WC_taurusdatafast_Gateway&peiraeus=fail' ) .' </li>';
            // echo '<li>Cancel page: '. $host . (get_option('permalink_structure') ? 'wc-api/WC_taurusdatafast_Gateway?peiraeus=cancel'  : '?wc-api=WC_taurusdatafast_Gateway&peiraeus=cancel' ) .' </li>';
            // echo '<li>Response method : GET / POST </li>';
            // echo '<li>Server Ip: ' . $_SERVER['SERVER_ADDR'] . '</li>';
            // echo '</ul>';
            // echo '<pre>'; print_r($_SERVER); echo '</pre>';
            // // echo '<li>Website url: '.(get_option('permalink_structure') ? ''  : ) .'</li>';
            // echo '</div>';
        }
        /**
         * Initialise Gateway Settings Form Fields
         * */
        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'checkbox',
                    'label' => __('Enable Taurus Datafast Gateway', 'woo-payment-gateway-for-taurus-datafast'),
                    'description' => __('Enable or disable the gateway.', 'woo-payment-gateway-for-taurus-datafast'),
                    'desc_tip' => true,
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woo-payment-gateway-for-taurus-datafast'),
                    'desc_tip' => false,
                    'default' => __('Taurus Datafast Gateway', 'woo-payment-gateway-for-taurus-datafast')
                ),
                'description' => array(
                    'title' => __('Description', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woo-payment-gateway-for-taurus-datafast'),
                    'default' => __('Pay Via Taurus Datafast: Accepts  Mastercard, Visa cards and etc.', 'woo-payment-gateway-for-taurus-datafast')
                ),
                'pb_render_logo' => array(
                    'title' => __('Display the logo of Taurus Datafast', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'checkbox',
                    'description' => __('Enable to display the logo of Taurus Datafast next to the title which the user sees during checkout.', 'woo-payment-gateway-for-taurus-datafast'),
                    'default' => 'yes'
                ),
                'pb_PayMerchantId' => array(
                    'title' => __('Taurus Datafast Merchant ID', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'text',
                    'description' => __('Enter Your Taurus Datafast Merchant ID', 'woo-payment-gateway-for-taurus-datafast'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'pb_TransactionId' => array(
                    'title' => __('Taurus Datafast Transaction ID', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'text',
                    'description' => __('Enter Your Taurus Datafast Transation ID', 'woo-payment-gateway-for-taurus-datafast'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'pb_EntityId' => array(
                    'title' => __('Taurus Datafast Entity ID', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'text',
                    'description' => __('Enter your Taurus Datafast Entity ID', 'woo-payment-gateway-for-taurus-datafast'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'pb_AccessToken' => array(
                    'title' => __('Taurus Datafast Access Token', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'text',
                    'description' => __('Enter your Taurus Datafast Encryption Token', 'woo-payment-gateway-for-taurus-datafast'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'redirect_page_id' => array(
                    'title' => __('Return Page', 'woo-payment-gateway-for-taurus-datafast'),
                    'type' => 'select',
                    'options' => $this->pb_get_pages('Select Page'),
                    'description' => __('URL of success page', 'woo-payment-gateway-for-taurus-datafast')
                )
            );
        }

        
        // display the success page option in settings
        function pb_get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title)
                $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            $page_list[-1] = __('Thank you page', 'woo-payment-gateway-for-taurus-datafast');
            return $page_list;
        }

        function payment_fields() {
            global $woocommerce;
            
            //get: order or cart total, to compute max installments number.
            if(absint(get_query_var('order-pay'))) {
                $order_id = absint(get_query_var('order-pay'));
                $order = new WC_Order($order_id);
                $Amount = $order->get_total();
            } else if(!$woocommerce->cart->is_empty()) {
                $Amount = $woocommerce->cart->total;
            }

//            if ($description = $this->get_description()) {
//                echo wpautop(wptexturize($description));
//            }
        }

        /**
         * Generate the  Taurus Datafast Payment button link
         * */
        function generate_taurusdatafast_form($order_id) {
            global $woocommerce;
            global $wpdb;

            $currency = get_woocommerce_currency();
            $locale = get_locale();
            $availableLocales = array (
                'en' => 'en-US',
                'en_US' => 'en-US',
                'en_AU' => 'en-US',
                'en_CA' => 'en-US',
                'en_GB' => 'en-US',
                'en_NZ' => 'en-US',
                'en_ZA' => 'en-US',
                'el' => 'el-GR',
                'ru_RU' => 'ru-RU',
                'de_DE' => 'de-DE',
                'de_DE_formal' => 'de-DE',
                'de_CH' => 'de-DE',
                'de_CH_informal' => 'de-DE'
            );

            if(isset($availableLocales[$locale])) {
               $lang = $availableLocales[$locale];
            } else {
                $lang = 'en-US';
            }

            $order = new WC_Order($order_id);

                $requestType = '02';
                $ExpirePreauth = '0';
                $installments = get_post_meta($order_id, '_doseis', 1);
            
                $ticketRequest = array(
                    'MerchantId' => $this->pb_PayMerchantId,
                    'TransactionId' => $this->pb_TransactionId,
                    'EntityId' => $this->pb_EntityId,
                    'AccessToken' => $this->pb_AccessToken,
                    'MerchantReference' => $order_id,
                    'RequestType' => $requestType,
                    'ExpirePreauth' => $ExpirePreauth,
                    'Amount' => $order->get_total(),
                    'CurrencyCode' => '978',
                    'Installments' => $installments,
                    'Bnpl' => '0',
                    'Parameters' => ''
                );
            
                    echo "<br />Entity Id: " . $this->pb_EntityId . "<br />";
                    //redirect to payment
            
                    $second_name="J";
                    $total = (float)$order->get_total();
                    $vat = "000000000012";

                    $totalTarif12 = sanitize_text_field(str_pad($order->get_cart_tax()*100,12,'0',STR_PAD_LEFT));
                    echo "<br /><br /> totalTarif12: $totalTarif12 <br />";
                    
                    $totalBase0 = "000000000200";

                    $idCard = "0920709388";
                    $transaction = "40";
                    $ip = "192.168.1.1";
                    $finger = "";

//                    $clientAddress = $order->get_billing_address_1() . " "  . $order->get_billing_address_2();
//                    $shippingAddress = $order->get_shipping_address_1() . " "  . $order->get_shipping_address_2();
                    $clientAddress="Clent Address";
                    $clientCountry="IN";
                    $shippingAddress = "Shipping Address";
                    $shippingCountry = "IN";       //$order->get_shipping_country()
                    $responseData = $this->datafastPayment($order->get_items(), $total, $vat, $totalTarif12, $totalBase0, $order->get_billing_email(), $order->get_billing_first_name(), $second_name, $order->get_billing_last_name(), $idCard, $transaction, $ip, $finger, $order->get_billing_phone(), $clientAddress, $clientCountry, $shippingAddress, $shippingCountry, $this->pb_EntityId, $this->pb_AccessToken, $this->pb_PayMerchantId, $this->pb_TransactionId);
            
                    $order =  WC($order_id);
            
                    return '<script src="https://test.oppwa.com/v1/paymentWidgets.js?checkoutId='. $responseData->id . '"></script>
                   <form id="xx" action="/wp-content/plugins/woo-payment-gateway-for-taurus-datafast/testpayresult.php" class="paymentWidgets" data-brands="VISA MASTER AMEX DINER DISCOVER">
                    </form>
                    <script>
                        $(document).on("submit","xx", function(){
                          var input = $("<input />").attr("type", "hidden").attr("name", "orderId").val("1");
                          $("#xx").append(input);
                          return true;
                    });
                    </script>';                         
        }

        /**
         * Process the payment and return the result
         * */
        function process_payment($order_id) {
            /*
              get_permalink was used instead of $order->get_checkout_payment_url in redirect in order to have a fixed checkout page to provide to Taurus Datafast
             */

            $order = new WC_Order($order_id);
            $doseis = intval($_POST[esc_attr($this->id) . '-card-doseis']);
            if ($doseis > 0) {
                $this->generic_add_meta($order_id, '_doseis', $doseis);
            }

            return array(
                'result' => 'success',
                'redirect' => add_query_arg('order-pay', $order->get_id(), add_query_arg('key', $order->get_order_key(), wc_get_page_permalink('checkout')))
            );
        }

        /**
         * Output for the order received page.
         * */
        function receipt_page($orderId) {
            $order = wc_get_order( $orderId );
//            echo "<br /> RECEIPT PAGE: ORDER <br />";
//            var_dump($order);
//            echo "<br />";
            
            echo '<p>' . __('Thank you - your order is now pending payment. You should be automatically redirected to Taurus Datafast Paycenter to make payment.', 'woo-payment-gateway-for-taurus-datafast') . '</p>';
            
//            $order->update_status('processing');
//            echo "<br /> Done";
            echo $this->generate_taurusdatafast_form($order);            
            //check_transaction_status();
        }

        /**
         * Verify a successful Payment!
         * */
        function check_taurusdatafast_response() {

            echo "<br />check_taurusdatafast_response<br />";
            global $woocommerce;
            global $wpdb;

            if (isset($_REQUEST['peiraeus']) && ($_REQUEST['peiraeus'] == 'success')) {

                $ResultCode = filter_var($_REQUEST['ResultCode'], FILTER_SANITIZE_STRING);
                $order_id = filter_var($_REQUEST['MerchantReference'], FILTER_SANITIZE_STRING);
                $order = new WC_Order($order_id);

                if ($ResultCode != 0) {
                    $message = __('A technical problem occured. <br />The transaction wasn\'t successful, payment wasn\'t received.', 'woo-payment-gateway-for-taurus-datafast');
                    $message_type = 'error';
                    $pb_message = array(
                        'message' => $message,
                        'message_type' => $message_type
                    );
                    $this->generic_add_meta($order_id, '_taurusdatafast_message', $pb_message);
                    $this->generic_add_meta($order_id, '_taurusdatafast_message_debug', $pb_message);
                    wc_add_notice(__('Payment error:', 'woo-payment-gateway-for-taurus-datafast') . $message, $message_type);
                    //Update the order status
                    $order->update_status('failed', '');
                    $checkout_url = $woocommerce->cart->get_checkout_url();
                    wp_redirect($checkout_url);
                    exit;
                }

                $ResponseCode = filter_var($_REQUEST['ResponseCode'], FILTER_SANITIZE_STRING);
                $StatusFlag = filter_var($_REQUEST['StatusFlag'], FILTER_SANITIZE_STRING);
                $HashKey = filter_var($_REQUEST['HashKey'], FILTER_SANITIZE_STRING);
                $SupportReferenceID = absint($_REQUEST['SupportReferenceID']);
                $ApprovalCode = filter_var($_REQUEST['ApprovalCode'], FILTER_SANITIZE_STRING);
                $Parameters = filter_var($_REQUEST['Parameters'], FILTER_SANITIZE_STRING);
                $AuthStatus = filter_var($_REQUEST['AuthStatus'], FILTER_SANITIZE_STRING);
                $PackageNo = absint($_REQUEST['PackageNo']);




                $ttquery = 'SELECT trans_ticket
			FROM `' . $wpdb->prefix . 'taurusdatafast_transactions`
			WHERE `merch_ref` = ' . $order_id . '	;';
                $tt = $wpdb->get_results($ttquery);

                $hasHashKeyNotMatched = true;

                    foreach($tt as $transaction) {

                        if(!$hasHashKeyNotMatched)
                            break;

                        $transticket = $transaction->trans_ticket;

                        $stcon = $transticket . $this->pb_PosId . $this->pb_AcquirerId . $order_id . $ApprovalCode . $Parameters . $ResponseCode . $SupportReferenceID . $AuthStatus . $PackageNo . $StatusFlag;

                        $conhash = strtoupper(hash('sha256', $stcon));

                        // $newHashKey
                        $stconHmac = $transticket . ';' . $this->pb_PosId . ';' .  $this->pb_AcquirerId . ';' .  $order_id . ';' .  $ApprovalCode . ';' .  $Parameters . ';' .  $ResponseCode . ';' .  $SupportReferenceID . ';' .  $AuthStatus . ';' .  $PackageNo . ';' .  $StatusFlag;
                        $consHashHmac = strtoupper(hash_hmac('sha256', $stconHmac, $transticket, false));

                            if($consHashHmac != $HashKey && $conhash != $HashKey) {
                                continue;
                            } else {
                                $hasHashKeyNotMatched= false;
                            }
                    }


                if($hasHashKeyNotMatched) {

                    $message = __('Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t received.', 'woo-payment-gateway-for-taurus-datafast');
                    $message_type = 'error';
                    $pb_message = array(
                        'message' => $message,
                        'message_type' => $message_type
                    );
                    $this->generic_add_meta($order_id, '_taurusdatafast_message', $pb_message);
                    $this->generic_add_meta($order_id, '_taurusdatafast_message_debug', array($pb_message, $consHashHmac . '!=' . $HashKey));
                    //wc_add_notice(__('Payment error:', 'woo-payment-gateway-for-taurus-datafast') . $message, $message_type);
                    //Update the order status
                    $order->update_status('failed', '');
                    $checkout_url = $woocommerce->cart->get_checkout_url();
                    wp_redirect($checkout_url);
                    exit;
                 }
                else {

                    if ($ResponseCode == 0 || $ResponseCode == 8 || $ResponseCode == 10 || $ResponseCode == 16) {

                        if ($order->get_status() == 'processing') {

                            $order->add_order_note(__('Payment Via Taurus Datafast<br />Transaction ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID);

                            //Add customer order note
                            $order->add_order_note(__('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Datafast ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID, 1);

                            // Reduce stock levels
                            $order->reduce_order_stock();

                            // Empty cart
                            WC()->cart->empty_cart();

                            $message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.', 'woo-payment-gateway-for-taurus-datafast');
                            $message_type = 'success';
                            //wc_add_notice( $message, $message_type );
                        } else {

                            if ($order->has_downloadable_item()) {

                                 // check if the order has only downloadable items
                                 $hasOnlyDownloadable = true; 
                                 foreach ($order->get_items() as $key => $order_prod) {
                                     $p = $order_prod->get_product();
                                     if($p->is_downloadable() == false && $p->is_virtual() == false) {
                                         $hasOnlyDownloadable = false; 
                                     }
                                   }

                                   if($hasOnlyDownloadable) {
                                        //Update order status
                                        $order->update_status('completed', __('Payment received, your order is now complete.', 'woo-payment-gateway-for-taurus-datafast'));

                                        //Add admin order note
                                        $order->add_order_note(__('Payment Via Taurus Datafast<br />Transaction ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID);

                                        //Add customer order note
                                        $order->add_order_note(__('Payment Received.<br />Your order is now complete.<br />Datafast Transaction ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID, 1);

                                        $message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is now complete.', 'woo-payment-gateway-for-taurus-datafast');
                                        $message_type = 'success';
                                   } else {
                                        //Update order status
                                        $order->update_status('processing', __('Payment received, your order is currently being processed.', 'woo-payment-gateway-for-taurus-datafast'));

                                        //Add admin order note
                                        $order->add_order_note(__('Payment Via Taurus Datafast<br />Transaction ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID);

                                        //Add customer order note
                                        $order->add_order_note(__('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Taurus Datafast ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID, 1);

                                        $message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.', 'woo-payment-gateway-for-taurus-datafast');
                                        $message_type = 'success';
                                   }

                            } else {

                                //Update order status
                                $order->update_status('processing', __('Payment received, your order is currently being processed.', 'woo-payment-gateway-for-taurus-datafast'));

                                //Add admin order note
                                $order->add_order_note(__('Payment Via Taurus Datafast <br />Transaction ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID);

                                //Add customer order note
                                $order->add_order_note(__('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Taurus Datafast ID: ', 'woo-payment-gateway-for-taurus-datafast') . $SupportReferenceID, 1);

                                $message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.', 'woo-payment-gateway-for-taurus-datafast');
                                $message_type = 'success';
                            }

                            $pb_message = array(
                                'message' => $message,
                                'message_type' => $message_type
                            );

                            $this->generic_add_meta($order_id, '_taurusdatafast_message', $pb_message);
                            $this->generic_add_meta($order_id, '_taurusdatafast_message_debug', $pb_message);
                            // Reduce stock levels
                            $order->reduce_order_stock();

                            // Empty cart
                            WC()->cart->empty_cart();
                        }
                    } else if ($ResponseCode == 11) {

                        $message = __('Thank you for shopping with us.<br />Your transaction was previously received.<br />', 'woo-payment-gateway-for-taurus-datafast');
                        $message_type = 'success';


                        $pb_message = array(
                            'message' => $message,
                            'message_type' => $message_type
                        );
                        $this->generic_add_meta($order_id, '_taurusdatafast_message', $pb_message);
                        $this->generic_add_meta($order_id, '_taurusdatafast_message_debug', $pb_message);
                    } else { //Failed Response codes

                        $message = __('Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t received.', 'woo-payment-gateway-for-taurus-datafast');
                        $message_type = 'error';
                        $pb_message = array(
                            'message' => $message,
                            'message_type' => $message_type
                        );
                        $this->generic_add_meta($order_id, '_taurusdatafast_message', $pb_message);
                        $this->generic_add_meta($order_id, '_taurusdatafast_message_debug', $pb_message);
                        //Update the order status
                        $order->update_status('failed', '');
                    }
                }
            }
            if (isset($_REQUEST['peiraeus']) && ($_REQUEST['peiraeus'] == 'fail')) {

                if (isset($_REQUEST['MerchantReference'])) {
                    $order_id = filter_var($_REQUEST['MerchantReference'], FILTER_SANITIZE_STRING);
                    $order = new WC_Order($order_id);
                    $message = __('Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t received.', 'woo-payment-gateway-for-taurus-datafast');
                    $message_type = 'error';

                    $transaction_id = absint($_REQUEST['SupportReferenceID']);

                    //Add Customer Order Note
                    $order->add_order_note($message . '<br />Taurus Datafast Transaction ID: ' . $transaction_id, 1);

                    //Add Admin Order Note
                    $order->add_order_note($message . '<br />Taurus Datafast Transaction ID: ' . $transaction_id);


                    //Update the order status
                    $order->update_status('failed', '');

                    $pb_message = array(
                        'message' => $message,
                        'message_type' => $message_type
                    );

                    $this->generic_add_meta($order_id, '_taurusdatafast_message', $pb_message);
                    $this->generic_add_meta($order_id, '_taurusdatafast_message_debug', $pb_message);
                }
            }
            if (isset($_REQUEST['peiraeus']) && ($_REQUEST['peiraeus'] == 'cancel')) {


                $checkout_url = $woocommerce->cart->get_checkout_url();
                wp_redirect($checkout_url);
                exit;
            }
            if ($this->redirect_page_id == "-1") {
                $redirect_url = $this->get_return_url($order);
            } else {
                $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
                //For wooCoomerce 2.0
                $redirect_url = add_query_arg(array('msg' => urlencode($this->msg['message']), 'type' => $this->msg['class']), $redirect_url);
            }
            wp_redirect($redirect_url);

            exit;
        }

        function generic_add_meta($orderid, $key, $value) {
            $order = new WC_Order(sanitize_text_field($orderid));
            if (method_exists($order, 'add_meta_data') && method_exists($order, 'save_meta_data')) {
                $order->add_meta_data(sanitize_key($key), sanitize_text_field($value), true);
                $order->save_meta_data();
            } else {
                update_post_meta($orderid, sanitize_key($key), sanitize_text_field($value));
            }
        }
        
        // Datafast payment xxx3
        function datafastPayment($items, $total, $vat, $totalTarif12, $totalBase0, $email, $first_name, $second_name, $surname, $idCard, $transaction, $ip, $finger, $phone, $client_address, $client_country, $shipping_address, $shipping_country, $entityId, $accessToken, $payMerchantId, $transactionId) {

            
            $finger = urlencode($finger);
            $i = 0;
            $merchant = $payMerchantId . "_" . $transactionId;
            
            echo "<br />Merchant: $merchant";
            echo "<br />Authorization: Bearer $accessToken";

            $url = "https://test.oppwa.com/v1/checkouts";
            
        
            $vat = str_replace('.', '', $vat);
            $totalTarif12 = str_replace('.', '', $totalTarif12);
            $totalBase0 = str_replace('.', '', $totalBase0);
            $vatValue = str_pad($vat, 12, '0', STR_PAD_LEFT);
            $totalTarif12 = str_pad($totalTarif12, 12, '0', STR_PAD_LEFT);
            $totalBase0 = str_pad($totalBase0, 12, '0', STR_PAD_LEFT);


            $data = "authentication.entityId=" . $entityId .
                "&amount=" . $total . 
                "&currency=USD" .
                "&paymentType=DB" .
                "&customer.givenName=" . $first_name .
                "&customer.middleName=" . $second_name .
                "&customer.surname=" . $surname .
                "&customer.ip=" . $ip .
                "&customer.merchantCustomerId=000000000001" .
                "&merchantTransactionId=transaction_" . $transaction .
                "&customer.email=" . $email .
                "&customer.identificationDocType=IDCARD" .
                "&customer.identificationDocId=" . $idCard .
                "&customer.phone=" . $phone .
                "&billing.street1=" . $client_address .
                "&billing.country=" . $client_country .
                "&shipping.street1=" . $shipping_address .
                "&shipping.country=" . $shipping_country .
                //"&recurringType=INITIAL" .
                "&risk.parameters[USER_DATA2]=DATAFAST" .
                "&customParameters[" . $merchant . "]=00810030070103910004012" . $vatValue . "05100817913101052012" . $totalBase0 . "053012" . $totalTarif12;
            
            foreach ($items as $item) {
                $data .= "&cart.items[" . $i . "].name=" . $item->get_name();
                $data .= "&cart.items[" . $i . "].description=" . $item->get_name();
                $data .= "&cart.items[" . $i . "].price=" . round($item->get_total(),2);
                $data .= "&cart.items[" . $i . "].quantity=" . $item->get_quantity();
                
                $i++;
            }            
            $data .= "&testMode=EXTERNAL";
            
            echo "<br /><br /> DATA<br />";
            echo"<br /><br /> $data <br /><br />";


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $accessToken"));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $responseData = curl_exec($ch);

            if (curl_errno($ch))
                return curl_error($ch);

            curl_close($ch);

            $vresponseData = json_decode($responseData);
            return $vresponseData;
        }
        
        function check_transaction_status(){
            $presourcePath = $_GET['resourcePath'];
            $pentityId='(8a829418533cf31d01533d06f2ee06fa';
            
            $url = "https://test.oppwa.com" . $presourcePath;    $url .= "?entityId=$pentityId";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:Bearer  $accessToken"));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $responseData = curl_exec($ch);

            if (curl_errno($ch)) {
                return curl_error($ch);
            }

            curl_close($ch);
            
            return $responseData;
        }
    }

    function taurusdatafast_message() {
        $order_id = absint(get_query_var('order-received'));
        $order = new WC_Order($order_id);
        if (method_exists($order, 'get_payment_method')) {
            $payment_method = $order->get_payment_method();
        } else {
            $payment_method = $order->payment_method;
        }

        if (is_order_received_page() && ( 'taurusdatafast_gateway' == $payment_method )) {

            $taurusdatafast_message = '';
            if (method_exists($order, 'get_meta')) {
                $taurusdatafast_message = $order->get_meta('_taurusdatafast_message', true);
            } else {
                $taurusdatafast_message = get_post_meta($order_id, '_taurusdatafast_message', true);
            }
            if (!empty($taurusdatafast_message)) {
                $message = $taurusdatafast_message['message'];
                $message_type = $taurusdatafast_message['message_type'];
                if (method_exists($order, 'delete_meta_data')) {
                    $order->delete_meta_data('_taurusdatafast_message');
                    $order->save_meta_data();
                } else {
                    delete_post_meta($order_id, '_taurusdatafast_message');
                }

                wc_add_notice($message, $message_type);
            }
        }
    }

    add_action('wp', 'taurusdatafast_message');

    /**
     * Add Taurus Datafast Gateway to WC
     * */
    function woocommerce_add_taurusdatafast_gateway($methods) {
        $methods[] = 'WC_taurusdatafast_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_taurusdatafast_gateway');





    /**
     * Add Settings link to the plugin entry in the plugins menu for WC below 2.1
     * */
    if (version_compare(WOOCOMMERCE_VERSION, "2.1") <= 0) {

        add_filter('plugin_action_links', 'taurusdatafast_plugin_action_links', 10, 2);

        function taurusdatafast_plugin_action_links($links, $file) {
            static $this_plugin;

            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }

            if ($file == $this_plugin) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_taurusdatafast_Gateway">Settings</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

    }
    
    /**
     * Add Settings link to the plugin entry in the plugins menu for WC 2.1 and above
     * */ else {
        add_filter('plugin_action_links', 'taurusdatafast_plugin_action_links', 10, 2);

        function taurusdatafast_plugin_action_links($links, $file) {
            static $this_plugin;

            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }

            if ($file == $this_plugin) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=WC_taurusdatafast_Gateway">Settings</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

    }
}
