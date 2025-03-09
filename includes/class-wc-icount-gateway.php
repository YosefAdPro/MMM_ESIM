<?php
/**
 * Custom Payment Gateway for iCount API integration
 */

if (!defined('ABSPATH')) {
    exit;
}

// בדיקה שמחלקת WooCommerce קיימת
if (!class_exists('WC_Payment_Gateway')) {
    return;
}

class WC_iCount_Gateway extends WC_Payment_Gateway {

    /**
     * Setup payment gateway properties
     */
    public function __construct() {
        $this->id                   = 'icount_gateway';
        $this->icon                 = '';
        $this->has_fields           = true;
        $this->method_title         = 'סליקה באמצעות iCount';
        $this->method_description   = 'קבלת תשלום באמצעות iCount לחבילות eSIM';
        $this->supports             = array('products');
        
        // הגדרת ערכים קבועים - לא מגדרות
        $this->title                = 'תשלום בכרטיס אשראי';
        $this->description          = 'שלם באופן מאובטח באמצעות כרטיס אשראי';
        $this->enabled = 'yes'; // כפיית הפעלה תמיד
        
        // פעולות
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_api_wc_icount_gateway', array($this, 'process_icount_response'));
    }
	
	    /**
     * Check if gateway is available for use - גרסה פשוטה ביותר
     */
    public function is_available() {
    return true; // תמיד זמין
}
    
/**
 * Initialize gateway settings form fields
 */
public function init_form_fields() {
    $this->form_fields = array(
        'enabled' => array(
            'title'       => 'הפעלה/כיבוי',
            'type'        => 'checkbox',
            'label'       => 'הפעל סליקה באמצעות iCount',
            'default'     => 'yes'
        ),
        'title' => array(
            'title'       => 'כותרת',
            'type'        => 'text',
            'description' => 'הכותרת שתוצג ללקוח במסך התשלום',
            'default'     => 'תשלום בכרטיס אשראי',
        ),
        'description' => array(
            'title'       => 'תיאור',
            'type'        => 'textarea',
            'description' => 'התיאור שיוצג ללקוח בדף התשלום',
            'default'     => 'שלם באופן מאובטח באמצעות כרטיס אשראי',
        )
    );
}
    
    /**
     * Output payment fields
     */
    public function payment_fields() {
        echo wpautop(wp_kses_post($this->description));
        
        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form">';
        
        // Add a heading for credit card form
        echo '<div class="form-row form-row-wide">
            <h3>' . esc_html__('פרטי כרטיס אשראי', 'woocommerce') . '</h3>
        </div>';
        
        // Add credit card number field
        echo '<div class="form-row form-row-wide">
            <label for="' . esc_attr($this->id) . '-card-number">' . esc_html__('מספר כרטיס', 'woocommerce') . ' <span class="required">*</span></label>
            <input id="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="' . esc_attr($this->id) . '-card-number" />
        </div>';
        
        // Add credit card expiry field (month/year)
        echo '<div class="form-row form-row-first">
            <label for="' . esc_attr($this->id) . '-card-expiry">' . esc_html__('תוקף (MM/YY)', 'woocommerce') . ' <span class="required">*</span></label>
            <input id="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="MM / YY" name="' . esc_attr($this->id) . '-card-expiry" />
        </div>';
        
        // Add credit card CVC field
        echo '<div class="form-row form-row-last">
            <label for="' . esc_attr($this->id) . '-card-cvc">' . esc_html__('קוד אבטחה (CVC)', 'woocommerce') . ' <span class="required">*</span></label>
            <input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVC" name="' . esc_attr($this->id) . '-card-cvc" />
        </div>';
        
        // ID field
        echo '<div class="form-row form-row-wide">
            <label for="' . esc_attr($this->id) . '-card-holder-id">' . esc_html__('מספר תעודת זהות', 'woocommerce') . ' <span class="required">*</span></label>
            <input id="' . esc_attr($this->id) . '-card-holder-id" class="input-text" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" name="' . esc_attr($this->id) . '-card-holder-id" />
        </div>';
        
        // Add name on card field
        echo '<div class="form-row form-row-wide">
            <label for="' . esc_attr($this->id) . '-card-holder-name">' . esc_html__('שם בעל הכרטיס', 'woocommerce') . ' <span class="required">*</span></label>
            <input id="' . esc_attr($this->id) . '-card-holder-name" class="input-text" type="text" autocomplete="cc-name" name="' . esc_attr($this->id) . '-card-holder-name" />
        </div>';
        
        // Add installments field
        echo '<div class="form-row form-row-wide">
            <label for="' . esc_attr($this->id) . '-installments">' . esc_html__('תשלומים', 'woocommerce') . '</label>
            <select id="' . esc_attr($this->id) . '-installments" name="' . esc_attr($this->id) . '-installments">
                <option value="1">תשלום אחד</option>
                <option value="2">2 תשלומים</option>
                <option value="3">3 תשלומים</option>
                <option value="4">4 תשלומים</option>
                <option value="5">5 תשלומים</option>
                <option value="6">6 תשלומים</option>
                <option value="7">7 תשלומים</option>
                <option value="8">8 תשלומים</option>
                <option value="9">9 תשלומים</option>
                <option value="10">10 תשלומים</option>
                <option value="11">11 תשלומים</option>
                <option value="12">12 תשלומים</option>
            </select>
        </div>';
        
        echo '<div class="clear"></div>';
        echo '</fieldset>';
    }
    
    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        // Get order
        $order = wc_get_order($order_id);
        
        // Check for card data
        if (!isset($_POST[$this->id . '-card-number']) || 
            !isset($_POST[$this->id . '-card-expiry']) || 
            !isset($_POST[$this->id . '-card-cvc']) || 
            !isset($_POST[$this->id . '-card-holder-id']) ||
            !isset($_POST[$this->id . '-card-holder-name'])) {
            wc_add_notice('נא להזין את פרטי כרטיס האשראי', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Validate card number
        $card_number = sanitize_text_field($_POST[$this->id . '-card-number']);
        $card_number = preg_replace('/\D/', '', $card_number); // Remove non-digits
        
        if (strlen($card_number) < 12 || strlen($card_number) > 19) {
            wc_add_notice('מספר כרטיס האשראי אינו תקין', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Validate expiry date
        $expiry = sanitize_text_field($_POST[$this->id . '-card-expiry']);
        $expiry = preg_replace('/\D/', '', $expiry); // Remove non-digits
        
        if (strlen($expiry) !== 4) {
            wc_add_notice('תאריך תפוגה אינו תקין, נא להזין בפורמט MM/YY', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        $exp_month = substr($expiry, 0, 2);
        $exp_year = substr($expiry, 2, 2);
        
        if ($exp_month < 1 || $exp_month > 12) {
            wc_add_notice('חודש לא חוקי בתאריך תפוגה', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Validate CVC
        $cvc = sanitize_text_field($_POST[$this->id . '-card-cvc']);
        $cvc = preg_replace('/\D/', '', $cvc); // Remove non-digits
        
        if (strlen($cvc) < 3 || strlen($cvc) > 4) {
            wc_add_notice('קוד האבטחה אינו תקין', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Validate ID
        $holder_id = sanitize_text_field($_POST[$this->id . '-card-holder-id']);
        $holder_id = preg_replace('/\D/', '', $holder_id); // Remove non-digits
        
        if (strlen($holder_id) < 8 || strlen($holder_id) > 9) {
            wc_add_notice('מספר תעודת הזהות אינו תקין', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Get holder name
        $holder_name = sanitize_text_field($_POST[$this->id . '-card-holder-name']);
        
        if (empty($holder_name)) {
            wc_add_notice('נא להזין את שם בעל הכרטיס', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Get installments
        $installments = isset($_POST[$this->id . '-installments']) ? intval($_POST[$this->id . '-installments']) : 1;
        
        // Create order note with payment details (but mask sensitive data)
        $masked_card = substr($card_number, 0, 4) . ' XXXX XXXX ' . substr($card_number, -4);
        $order->add_order_note(sprintf(
            'תשלום התקבל באמצעות כרטיס אשראי (מספר: %s, תוקף: %s/%s, שם: %s, תשלומים: %d)',
            $masked_card,
            $exp_month,
            $exp_year,
            $holder_name,
            $installments
        ));
        
        // Create a iCount order
        $mobimatter_order_id = $this->create_mobimatter_order($order);
        
        if (!$mobimatter_order_id) {
            wc_add_notice('שגיאה ביצירת הזמנה במובימטר', 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Save order meta
        $order->update_meta_data('_mobimatter_order_id', $mobimatter_order_id);
        $order->save();
        
        // Process payment through iCount
        $payment_result = $this->process_icount_payment($order, array(
            'card_number' => $card_number,
            'exp_month' => $exp_month,
            'exp_year' => $exp_year,
            'cvc' => $cvc,
            'holder_id' => $holder_id,
            'holder_name' => $holder_name,
            'installments' => $installments
        ));
        
        if (!$payment_result['success']) {
            wc_add_notice('שגיאה בעיבוד התשלום: ' . $payment_result['message'], 'error');
            
            // Cancel Mobimatter order
            $this->cancel_mobimatter_order($mobimatter_order_id);
            
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
        
        // Save iCount document info
        if (isset($payment_result['doc_number'])) {
            $order->update_meta_data('_icount_doc_number', $payment_result['doc_number']);
        }
        
        // Complete the Mobimatter order now that payment is successful
        $esim_data = $this->complete_mobimatter_order($order, $mobimatter_order_id);
        
        if ($esim_data) {
            // Save eSIM data to order
            if (isset($esim_data['qrCodeImage'])) {
                $order->update_meta_data('_esim_qr_code', $esim_data['qrCodeImage']);
            }
            
            if (isset($esim_data['activationCode'])) {
                $order->update_meta_data('_esim_activation_code', $esim_data['activationCode']);
            }
            
            if (isset($esim_data['details'])) {
                $order->update_meta_data('_esim_details', json_encode($esim_data['details']));
            }
        }
        
        // Set as processing or completed
        $order->payment_complete();
        
        // Add success note
        $order->add_order_note('התשלום בוצע בהצלחה דרך iCount. מסמך מספר: ' . ($payment_result['doc_number'] ?? 'לא זמין'));
        
        // Send eSIM email
        AdPro_send_esim_details_email($order_id);
        
        // Return success
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
    
    /**
     * Create order in Mobimatter
     */
    private function create_mobimatter_order($order) {
        // Get order items
        $order_items = $order->get_items();
        if (empty($order_items)) {
            return false;
        }
        
        // Get first item
        $item = reset($order_items);
        
        // Verify eSIM package exists
        $package_id = $item->get_meta('esim_package_id');
        $country_iso = $item->get_meta('esim_country_iso');
        
        if (empty($package_id) || empty($country_iso)) {
            return false;
        }
        
        // Create Mobimatter order
        $api_key = get_option('AdPro_api_key');
        $merchant_id = get_option('AdPro_merchant_id');
        
        $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order';
        
        $order_data = [
            'productId' => $package_id,
            'quantity' => 1,
            'orderId' => 'wc_' . $order->get_id()
        ];
        
        $args = [
            'headers' => [
                'Accept' => 'text/plain',
                'merchantId' => $merchant_id,
                'api-key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($order_data),
            'method' => 'POST',
            'timeout' => 30
        ];
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            error_log('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code !== 200 || !isset($response_data['result']['id'])) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
            $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            error_log('שגיאה ביצירת הזמנה במובימטר: קוד ' . $response_code . ', הודעה: ' . $error_message);
            return false;
        }
        
        $mobimatter_order_id = $response_data['result']['id'];
        $order->add_order_note('נוצרה הזמנה במובימטר בהצלחה, מזהה: ' . $mobimatter_order_id);
        
        return $mobimatter_order_id;
    }
    
    /**
     * Process payment through iCount API
     */
    private function process_icount_payment($order, $card_data) {
        // Get iCount credentials
        $icount_company_id = get_option('AdPro_icount_company_id');
        $icount_user = get_option('AdPro_icount_user');
        $icount_pass = get_option('AdPro_icount_pass');
        
        if (empty($icount_company_id) || empty($icount_user) || empty($icount_pass)) {
            return [
                'success' => false,
                'message' => 'חסרים פרטי התחברות של iCount'
            ];
        }
        
        // Prepare customer data
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();
        
        // Collect product details for invoice
        $items = [];
        foreach ($order->get_items() as $item) {
            $country = $item->get_meta('מדינה') ?: '';
            $data_limit = $item->get_meta('נפח גלישה') ?: '';
            $validity_days = $item->get_meta('תקופת תוקף') ?: '';
            
            $description = "חבילת eSIM";
            if (!empty($data_limit)) {
                $description .= " {$data_limit}";
            }
            if (!empty($country)) {
                $description .= " ל{$country}";
            }
            if (!empty($validity_days)) {
                $description .= " ל-{$validity_days}";
            }
            
            $items[] = [
                'description' => $description,
                'unitprice' => $item->get_total() / $item->get_quantity(),
                'quantity' => $item->get_quantity()
            ];
        }
        
        // Prepare payment data for iCount
        $payment_data = [
            'cid' => $icount_company_id,
            'user' => $icount_user,
            'pass' => $icount_pass,
            'doctype' => 'invrec', // חשבונית-קבלה
            'client_name' => $customer_name,
            'email' => $customer_email,
            'phone' => $customer_phone,
            'lang' => 'he',
            'currency_code' => 'ILS',
            'items' => $items,
            'cc' => [
                'sum' => $order->get_total(),
                'card_type' => $this->get_card_type($card_data['card_number']),
                'card_number' => substr($card_data['card_number'], -4),
                'exp_year' => '20' . $card_data['exp_year'],
                'exp_month' => $card_data['exp_month'],
                'holder_id' => $card_data['holder_id'],
                'holder_name' => $card_data['holder_name'],
                'cvv' => $card_data['cvc'],
                'full_number' => $card_data['card_number'], // Used for processing
                'payments' => $card_data['installments']
            ],
            'send_email' => 1,
            'email_to_client' => 1,
            'custom_document_name' => "הזמנה #{$order->get_id()} - {$customer_name}"
        ];
        
        // Send request to iCount
        $response = wp_remote_post('https://api.icount.co.il/api/v3.php/doc/create', [
            'body' => json_encode($payment_data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $order->add_order_note('שגיאה בחיבור ל-iCount: ' . $error_message);
            error_log('שגיאה בחיבור ל-iCount: ' . $error_message);
            return [
                'success' => false,
                'message' => 'שגיאה בחיבור למערכת הסליקה: ' . $error_message
            ];
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if (!isset($response_data['status']) || $response_data['status'] !== 'success') {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
            $order->add_order_note('שגיאה ביצירת מסמך ב-iCount: ' . $error_message);
            error_log('שגיאה ביצירת מסמך ב-iCount: ' . $error_message . ' - ' . print_r($response_data, true));
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
        
        // Return success response
        return [
            'success' => true,
            'doc_number' => $response_data['doc_number'] ?? '',
            'message' => 'התשלום בוצע בהצלחה'
        ];
    }
    
    /**
     * Complete order in Mobimatter after successful payment
     */
    private function complete_mobimatter_order($order, $mobimatter_order_id) {
        $api_key = get_option('AdPro_api_key');
        $merchant_id = get_option('AdPro_merchant_id');
        
        $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order/complete';
        
        $complete_data = [
            'id' => $mobimatter_order_id
        ];
        
        $args = [
            'headers' => [
                'Accept' => 'text/plain',
                'merchantId' => $merchant_id,
                'api-key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($complete_data),
            'method' => 'POST',
            'timeout' => 30
        ];
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $order->add_order_note('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
            error_log('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code !== 200 || !isset($response_data['result'])) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
            $order->add_order_note('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
            error_log('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
            return false;
        }
        
        $order->add_order_note('הזמנה הושלמה בהצלחה במובימטר');
        
        return $response_data['result'];
    }
    
    /**
     * Cancel Mobimatter order if payment fails
     */
    private function cancel_mobimatter_order($mobimatter_order_id) {
        $api_key = get_option('AdPro_api_key');
        $merchant_id = get_option('AdPro_merchant_id');
        
        $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order/cancel';
        
        $cancel_data = [
            'id' => $mobimatter_order_id
        ];
        
        $args = [
            'headers' => [
                'Accept' => 'text/plain',
                'merchantId' => $merchant_id,
                'api-key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($cancel_data),
            'method' => 'POST',
            'timeout' => 30
        ];
        
        wp_remote_post($api_url, $args);
    }
    
    /**
     * Get card type based on card number
     */
    private function get_card_type($card_number) {
        // Get first digits
        $first_digit = substr($card_number, 0, 1);
        $first_two_digits = substr($card_number, 0, 2);
        
        // Basic card type detection
        if ($first_digit === '4') {
            return 'VISA';
        } elseif ($first_two_digits >= '51' && $first_two_digits <= '55') {
            return 'MASTERCARD';
        } elseif ($first_two_digits === '34' || $first_two_digits === '37') {
            return 'AMEX';
        } elseif ($first_two_digits === '30' || $first_two_digits === '36' || $first_two_digits === '38') {
            return 'DINERS';
        } else {
            return 'OTHER';
        }
    }

    /**
     * Check if gateway is available for use
     */
/**
 * Check if gateway is available for use
 */
/**
 * Check if gateway is available for use
 */

    
    /**
     * Output for the order received page.
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order->get_payment_method() !== $this->id) {
            return;
        }
        
        $qr_code = $order->get_meta('_esim_qr_code');
        $activation_code = $order->get_meta('_esim_activation_code');
        
        if (empty($qr_code) && empty($activation_code)) {
            echo '<div class="woocommerce-info">פרטי ה-eSIM מעובדים ויישלחו אליך בהקדם.</div>';
            return;
        }
        
        echo '<h2>פרטי ה-eSIM שלך</h2>';
        echo '<div class="esim-details">';
        
        if (!empty($qr_code)) {
            echo '<div class="esim-qr-code">';
            echo '<h3>קוד QR להפעלה</h3>';
            echo '<img src="' . esc_url($qr_code) . '" alt="eSIM QR Code">';
            echo '</div>';
        }
        
        if (!empty($activation_code)) {
            echo '<div class="esim-activation-code">';
            echo '<h3>קוד הפעלה</h3>';
            echo '<p><strong>' . esc_html($activation_code) . '</strong></p>';
            echo '</div>';
        }
        
        echo '<div class="esim-instructions">';
        echo '<h3>הוראות הפעלה</h3>';
        echo '<ol>';
        echo '<li>לך להגדרות > סלולרי > הוסף תוכנית סלולרית</li>';
        echo '<li>סרוק את קוד ה-QR או הזן את קוד ההפעלה ידנית</li>';
        echo '<li>אשר את התקנת ה-eSIM</li>';
        echo '<li>ודא שהנדידה (Roaming) מופעלת לפני הגעה ליעד</li>';
        echo '</ol>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Process a refund if supported.
     * @param  int    $order_id
     * @param  float  $amount
     * @param  string $reason
     * @return bool True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        // פונקציה זו תיושם בעתיד אם נדרש לתמוך בזיכויים
        return false;
    }
    
    /**
     * Output for the order receipt page.
     */
    public function receipt_page($order_id) {
        // פונקציה זו תיושם בעתיד אם נדרש לתמוך בתהליך תשלום דו-שלבי
        echo '<p>מעבד את התשלום שלך, אנא המתן...</p>';
    }
    
    /**
     * Process IPN callbacks from iCount if needed.
     */
    public function process_icount_response() {
        // פונקציה זו תיושם בעתיד אם נדרשת תמיכה בקבלת עדכונים מאובטחים מ-iCount
        http_response_code(200);
        exit;
    }
}

