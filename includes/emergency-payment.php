<?php
/**
 * קובץ פתרון חירום לשער תשלום
 */

if (!defined('ABSPATH')) {
    exit;
}

// הוסף תצוגת תשלום חירום לדף התשלום
add_action('woocommerce_review_order_before_submit', function() {
    ?>
    <div id="emergency-payment-form" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f8f8f8;">
        <h3 style="margin-top: 0;">פרטי כרטיס אשראי</h3>
        <p>שלם באופן מאובטח באמצעות כרטיס אשראי</p>
        
        <div class="form-row" style="margin-bottom: 10px;">
            <label for="emergency-card-number" style="display: block; margin-bottom: 5px; font-weight: bold;">מספר כרטיס <span class="required">*</span></label>
            <input id="emergency-card-number" class="input-text" style="width: 100%; padding: 8px;" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="icount_gateway-card-number">
        </div>
        
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <div class="form-row" style="flex: 1;">
                <label for="emergency-card-expiry" style="display: block; margin-bottom: 5px; font-weight: bold;">תוקף (MM/YY) <span class="required">*</span></label>
                <input id="emergency-card-expiry" class="input-text" style="width: 100%; padding: 8px;" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="MM / YY" name="icount_gateway-card-expiry">
            </div>
            
            <div class="form-row" style="flex: 1;">
                <label for="emergency-card-cvc" style="display: block; margin-bottom: 5px; font-weight: bold;">קוד אבטחה (CVC) <span class="required">*</span></label>
                <input id="emergency-card-cvc" class="input-text" style="width: 100%; padding: 8px;" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVC" name="icount_gateway-card-cvc">
            </div>
        </div>
        
        <div class="form-row" style="margin-bottom: 10px;">
            <label for="emergency-card-holder-id" style="display: block; margin-bottom: 5px; font-weight: bold;">מספר תעודת זהות <span class="required">*</span></label>
            <input id="emergency-card-holder-id" class="input-text" style="width: 100%; padding: 8px;" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" name="icount_gateway-card-holder-id">
        </div>
        
        <div class="form-row" style="margin-bottom: 10px;">
            <label for="emergency-card-holder-name" style="display: block; margin-bottom: 5px; font-weight: bold;">שם בעל הכרטיס <span class="required">*</span></label>
            <input id="emergency-card-holder-name" class="input-text" style="width: 100%; padding: 8px;" type="text" autocomplete="cc-name" name="icount_gateway-card-holder-name">
        </div>
        
        <div class="form-row" style="margin-bottom: 10px;">
            <label for="emergency-installments" style="display: block; margin-bottom: 5px; font-weight: bold;">תשלומים</label>
            <select id="emergency-installments" style="width: 100%; padding: 8px;" name="icount_gateway-installments">
                <option value="1">תשלום אחד</option>
                <option value="2">2 תשלומים</option>
                <option value="3">3 תשלומים</option>
                <option value="4">4 תשלומים</option>
                <option value="5">5 תשלומים</option>
                <option value="6">6 תשלומים</option>
            </select>
        </div>
        
        <input type="hidden" name="payment_method" value="icount_gateway">
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // הסר את הודעות השגיאה
        $('.woocommerce-error:contains("אין אמצעי תשלום")').remove();
        $('.woocommerce-NoticeGroup-checkout').hide();
        
        // הסתר את האזור הרגיל של התשלום
        $('#payment').hide();
        
        // עיצוב השדות
        $('#emergency-card-number').on('keyup', function() {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            $(this).val(formattedValue);
        });
        
        // עיצוב תוקף
        $('#emergency-card-expiry').on('keyup', function() {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            
            if (value.length > 2) {
                value = value.substr(0, 2) + ' / ' + value.substr(2, 2);
            }
            
            $(this).val(value);
        });
        
        // רק מספרים בשדות מספריים
        $('#emergency-card-number, #emergency-card-expiry, #emergency-card-cvc, #emergency-card-holder-id').on('keypress', function(e) {
            if (e.which < 48 || e.which > 57) {
                return false;
            }
        });
    });
    </script>
    <?php
});

// וודא שהתשלום מעובד נכון
add_action('woocommerce_checkout_process', function() {
    // בדיקת קלט של פרטי כרטיס
    if (!isset($_POST['icount_gateway-card-number']) || 
        !isset($_POST['icount_gateway-card-expiry']) || 
        !isset($_POST['icount_gateway-card-cvc']) || 
        !isset($_POST['icount_gateway-card-holder-id']) ||
        !isset($_POST['icount_gateway-card-holder-name'])) {
        wc_add_notice('נא להזין את פרטי כרטיס האשראי', 'error');
        return;
    }
    
    // בדיקת תקינות מספר כרטיס
    $card_number = sanitize_text_field($_POST['icount_gateway-card-number']);
    $card_number = preg_replace('/\D/', '', $card_number);
    
    if (strlen($card_number) < 12 || strlen($card_number) > 19) {
        wc_add_notice('מספר כרטיס האשראי אינו תקין', 'error');
        return;
    }
    
    // בדיקת תקינות תוקף
    $expiry = sanitize_text_field($_POST['icount_gateway-card-expiry']);
    $expiry = preg_replace('/\D/', '', $expiry);
    
    if (strlen($expiry) !== 4) {
        wc_add_notice('תאריך תפוגה אינו תקין, נא להזין בפורמט MM/YY', 'error');
        return;
    }
    
    // בדיקת תקינות CVC
    $cvc = sanitize_text_field($_POST['icount_gateway-card-cvc']);
    $cvc = preg_replace('/\D/', '', $cvc);
    
    if (strlen($cvc) < 3 || strlen($cvc) > 4) {
        wc_add_notice('קוד האבטחה אינו תקין', 'error');
        return;
    }
    
    // בדיקת תקינות ת.ז.
    $holder_id = sanitize_text_field($_POST['icount_gateway-card-holder-id']);
    $holder_id = preg_replace('/\D/', '', $holder_id);
    
    if (strlen($holder_id) < 8 || strlen($holder_id) > 9) {
        wc_add_notice('מספר תעודת הזהות אינו תקין', 'error');
        return;
    }
    
    // בדיקת תקינות שם בעל הכרטיס
    $holder_name = sanitize_text_field($_POST['icount_gateway-card-holder-name']);
    
    if (empty($holder_name)) {
        wc_add_notice('נא להזין את שם בעל הכרטיס', 'error');
        return;
    }
    
    // קבע את שיטת התשלום
    $_POST['payment_method'] = 'icount_gateway';
});

// הוסף לפנקציה שמעבדת את התשלום - לאחר יצירת ההזמנה
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    // שמור את פרטי התשלום בהזמנה
    if (isset($_POST['icount_gateway-card-number'])) {
        $card_number = sanitize_text_field($_POST['icount_gateway-card-number']);
        $card_number = preg_replace('/\D/', '', $card_number);
        $masked_card = substr($card_number, 0, 4) . ' XXXX XXXX ' . substr($card_number, -4);
        
        $expiry = sanitize_text_field($_POST['icount_gateway-card-expiry']);
        $expiry = preg_replace('/\D/', '', $expiry);
        $exp_month = substr($expiry, 0, 2);
        $exp_year = substr($expiry, 2, 2);
        
        $holder_name = sanitize_text_field($_POST['icount_gateway-card-holder-name']);
        
        $installments = isset($_POST['icount_gateway-installments']) ? intval($_POST['icount_gateway-installments']) : 1;
        
        // הוסף הערה להזמנה עם פרטי התשלום
        $order->add_order_note(sprintf(
            'תשלום התקבל באמצעות כרטיס אשראי (מספר: %s, תוקף: %s/%s, שם: %s, תשלומים: %d)',
            $masked_card,
            $exp_month,
            $exp_year,
            $holder_name,
            $installments
        ));
        
        // שמור מידע נוסף להזמנה
        $order->update_meta_data('_payment_method', 'icount_gateway');
        $order->update_meta_data('_payment_method_title', 'תשלום בכרטיס אשראי');
        
        // שמור את הפרטים המטושטשים
        $order->update_meta_data('_cc_last4', substr($card_number, -4));
        $order->update_meta_data('_cc_expiry', $exp_month . '/' . $exp_year);
        $order->update_meta_data('_cc_installments', $installments);
        
        $order->save();
    }
}, 10, 3);


// הסר את ההתראה על היעדר שערי תשלום
add_filter('woocommerce_no_available_payment_methods_message', function($message) {
    // בדוק אם יש מוצר eSIM בעגלה
    $has_esim = false;
    if (WC()->cart && !WC()->cart->is_empty()) {
        foreach (WC()->cart->get_cart() as $item) {
            if (isset($item['esim_package_id'])) {
                $has_esim = true;
                break;
            }
        }
    }
    
    if ($has_esim) {
        // אפשר להמשיך עם תשלום החירום
        return '';
    }
    
    // אחרת החזר את ההודעה המקורית
    return $message;
});