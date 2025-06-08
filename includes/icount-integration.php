<?php
/**
 * קובץ אינטגרציה עם תוסף iCount הרשמי
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}

/**
 * בדיקה אם תוסף iCount מותקן ופעיל
 */
function AdPro_check_icount_plugin_active() {
    return is_plugin_active('icount-payment-gateway/icount-payment-gateway.php');
}

/**
 * הודעה אם תוסף iCount לא מותקן
 */
function AdPro_icount_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('AdPro eSIM דורש את התוסף הרשמי של iCount. אנא התקן והפעל אותו.', 'AdPro-esim'); ?></p>
    </div>
    <?php
}

/**
 * רישום ווקומרס שער תשלום של iCount
 */
function AdPro_register_icount_gateway($methods) {
    if (AdPro_check_icount_plugin_active()) {
        // אם התוסף הרשמי של iCount פעיל, וודא ששער התשלום שלו רשום
        error_log('iCount official plugin is active - using its payment gateway');
    } else {
        // אם התוסף לא פעיל, הצג הודעת שגיאה
        add_action('admin_notices', 'AdPro_icount_missing_notice');
    }
    
    return $methods;
}

// הוסף את הסינון בעדיפות נמוכה יותר כדי לאפשר לתוסף הרשמי להירשם קודם
add_filter('woocommerce_payment_gateways', 'AdPro_register_icount_gateway', 30);

/**
 * הוסף את מפתחות ה-API של iCount לטופס התשלום
 */
function AdPro_add_icount_api_keys($order_id) {
    // השג את מפתחות ה-API מהגדרות התוסף
    $icount_api_key = get_option('AdPro_icount_api_key');
    $icount_company_id = get_option('AdPro_icount_company_id');
    $icount_user = get_option('AdPro_icount_user');
    $icount_pass = get_option('AdPro_icount_pass');
    
    // בדיקה אם התוסף הרשמי של iCount פעיל
    if (AdPro_check_icount_plugin_active()) {
        // אם יש צורך, הוסף את המפתחות להזמנה כמטא-דאטה
        $order = wc_get_order($order_id);
        
        if ($order) {
            // דוגמה לשימוש במפתחות המקוריים לתיעוד
            $order->update_meta_data('_AdPro_icount_company_id', $icount_company_id);
            $order->save();
        }
    }
}
add_action('woocommerce_checkout_order_processed', 'AdPro_add_icount_api_keys', 20, 1);

// הוספת אזנה לאירועי סטטוס הזמנה
add_action('woocommerce_order_status_changed', 'AdPro_process_order_on_status_change', 10, 4);
add_action('woocommerce_payment_complete', 'AdPro_process_order_on_payment_complete', 10, 1);

/**
 * טיפול בהזמנה כאשר התשלום הושלם
 */
function AdPro_process_order_on_payment_complete($order_id) {
    error_log('Payment completed for order #' . $order_id);
    
    // ניצור ונשלים את הזמנת eSIM
    AdPro_process_esim_order($order_id);
}

/**
 * טיפול בהזמנה כאשר יש שינוי סטטוס
 */
function AdPro_process_order_on_status_change($order_id, $old_status, $new_status, $order) {
    // רק אם השינוי הוא לסטטוסים של תשלום מוצלח
    if ($new_status === 'processing' || $new_status === 'completed') {
        error_log('Order #' . $order_id . ' status changed to ' . $new_status);
        
        // ניצור ונשלים את הזמנת eSIM
        AdPro_process_esim_order($order_id);
    }
}

/**
 * תהליך יצירה והשלמה של הזמנת eSIM
 */
/**
 * תהליך יצירה והשלמה של הזמנת eSIM
 */
function AdPro_process_esim_order($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        error_log('Order not found: #' . $order_id);
        return;
    }
    
    error_log('DEBUG - Processing eSIM for order #' . $order_id);
    
    // בדיקה אם זו הזמנת eSIM
    $has_esim = false;
    $esim_package_id = '';
    $esim_country_iso = '';
    
    foreach ($order->get_items() as $item) {
        $esim_package_id = $item->get_meta('esim_package_id');
        $esim_country_iso = $item->get_meta('esim_country_iso');
        
        if (!empty($esim_package_id)) {
            $has_esim = true;
            error_log('DEBUG - Found eSIM in order: package_id=' . $esim_package_id . ', country_iso=' . $esim_country_iso);
            break;
        }
    }
    
    if (!$has_esim) {
        error_log('Not an eSIM order - skipping order #' . $order_id);
        return;
    }
    
    // בדיקה אם כבר טיפלנו בהזמנה זו
    if ($order->meta_exists('_mobimatter_completed')) {
        error_log('Order #' . $order_id . ' already has eSIM details');
        return;
    }
    
    // בדיקה אם יש מזהה הזמנת מובימטר
    $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
    
    error_log('DEBUG - Existing MobiMatter order ID: ' . ($mobimatter_order_id ?: 'None'));
    
    // אם אין מזהה הזמנה, ניצור אחד
    if (empty($mobimatter_order_id)) {
        error_log('Creating new MobiMatter order for order #' . $order_id);
        
        // יצירת הזמנה במובימטר
        $api_key = get_option('AdPro_api_key');
        $merchant_id = get_option('AdPro_merchant_id');
        
        error_log('DEBUG - API credentials: merchantId=' . substr($merchant_id, 0, 5) . '..., api-key=' . substr($api_key, 0, 5) . '...');
        
        if (empty($api_key) || empty($merchant_id)) {
            error_log('Missing MobiMatter API credentials');
            return;
        }
        
        $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order';
        
        $order_data = [
            'productId' => $esim_package_id,
            'quantity' => 1,
            'orderId' => 'wc_' . $order_id
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
        
        error_log('DEBUG - Sending MobiMatter order request: ' . json_encode($order_data));
        error_log('DEBUG - API URL: ' . $api_url);
        
        $response = wp_remote_post($api_url, $args);
        
        // לוג מלא של התגובה
        error_log('DEBUG - MobiMatter order full response: ' . print_r($response, true));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            error_log('Error creating MobiMatter order: ' . $error_message);
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        error_log('DEBUG - MobiMatter API Response Code: ' . $response_code);
        error_log('DEBUG - MobiMatter API Response Body: ' . $response_body);
        error_log('DEBUG - MobiMatter API Response Decoded: ' . print_r($response_data, true));
        
        // תיקון הבדיקה כדי לקבל orderId או id
        if ($response_code !== 200) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
            $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            error_log('Error creating MobiMatter order: ' . $error_message . ', Response: ' . $response_body);
            return;
        }
        
        // בדיקה אם התוצאה קיימת
        if (!isset($response_data['result'])) {
            $error_message = 'חסר שדה result בתשובה';
            $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            error_log('Error creating MobiMatter order: ' . $error_message . ', Response: ' . $response_body);
            return;
        }
        
        // מציאת מזהה ההזמנה במגוון אפשרויות
        $mobimatter_order_id = null;
        if (isset($response_data['result']['id'])) {
            $mobimatter_order_id = $response_data['result']['id'];
            error_log('DEBUG - Found MobiMatter order ID in result.id: ' . $mobimatter_order_id);
        } 
        elseif (isset($response_data['result']['orderId'])) {
            $mobimatter_order_id = $response_data['result']['orderId'];
            error_log('DEBUG - Found MobiMatter order ID in result.orderId: ' . $mobimatter_order_id);
        }
        elseif (is_string($response_data['result'])) {
            $mobimatter_order_id = $response_data['result'];
            error_log('DEBUG - Using result string as MobiMatter order ID: ' . $mobimatter_order_id);
        }
        else {
            // ניסיון לחפש בתוך התוצאה את הערך הראשון שנראה כמו מזהה
            foreach ($response_data['result'] as $key => $value) {
                if (is_string($value) && (stripos($key, 'id') !== false || stripos($key, 'order') !== false)) {
                    $mobimatter_order_id = $value;
                    error_log('DEBUG - Found possible MobiMatter order ID in result.' . $key . ': ' . $mobimatter_order_id);
                    break;
                }
            }
        }
        
        if (empty($mobimatter_order_id)) {
            $error_message = 'לא נמצא מזהה הזמנה בתשובה';
            $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
            error_log('Error creating MobiMatter order: ' . $error_message . ', Response: ' . $response_body);
            return;
        }
            
        $order->update_meta_data('_mobimatter_order_id', $mobimatter_order_id);
        $order->add_order_note('נוצרה הזמנה במובימטר בהצלחה, מזהה: ' . $mobimatter_order_id);
        $order->save();
        
        error_log('Successfully created MobiMatter order ID: ' . $mobimatter_order_id);
    }
    
// כעת השלם את ההזמנה במובימטר
error_log('Completing MobiMatter order ID: ' . $mobimatter_order_id);

$api_key = get_option('AdPro_api_key');
$merchant_id = get_option('AdPro_merchant_id');

$api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order/complete';

// שימוש ב-orderId כפי שנדרש בתיעוד API
$complete_data = [
    'orderId' => $mobimatter_order_id,
    'notes' => 'Order completed via AdPro eSIM plugin'
];

$args = [
    'headers' => [
        'Accept' => 'text/plain',
        'merchantId' => $merchant_id,
        'api-key' => $api_key,
        'Content-Type' => 'application/json'
    ],
    'body' => json_encode($complete_data),
    'method' => 'PUT',  // שימוש ב-PUT במקום POST
    'timeout' => 30
];

error_log('DEBUG - Sending MobiMatter complete request: ' . json_encode($complete_data));
error_log('DEBUG - Complete API URL: ' . $api_url);

$response = wp_remote_request($api_url, $args);  // שימוש ב-wp_remote_request במקום wp_remote_post
    
    // לוג מלא של התגובה
    error_log('DEBUG - MobiMatter complete full response: ' . print_r($response, true));
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $order->add_order_note('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
        error_log('Error completing MobiMatter order: ' . $error_message);
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
    
    error_log('DEBUG - MobiMatter complete Response Code: ' . $response_code);
    error_log('DEBUG - MobiMatter complete Response Body: ' . $response_body);
    error_log('DEBUG - MobiMatter complete Response Decoded: ' . print_r($response_data, true));
    
    if ($response_code !== 200) {
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
        $order->add_order_note('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
        error_log('Error completing MobiMatter order: ' . $error_message);
        return;
    }
    
    if (!isset($response_data['result'])) {
        $error_message = 'חסר שדה result בתשובה';
        $order->add_order_note('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
        error_log('Error completing MobiMatter order: ' . $error_message);
        return;
    }
    
    // שמירת פרטי ה-eSIM - נבדוק מספר אפשרויות שונות למיקום המידע
 // שמירת פרטי ה-eSIM מהתגובה
$qr_code_found = false;
$activation_code_found = false;

// בדיקה אם יש פרטי lineItemDetails בתשובה
if (isset($response_data['result']['orderLineItem']['lineItemDetails']) && 
    is_array($response_data['result']['orderLineItem']['lineItemDetails'])) {
    
    $line_item_details = $response_data['result']['orderLineItem']['lineItemDetails'];
    
    // עבור על כל פרטי השורה
    foreach ($line_item_details as $detail) {
        // חיפוש קוד QR
        if (isset($detail['name']) && $detail['name'] === 'QR_CODE' && !empty($detail['value'])) {
            $order->update_meta_data('_esim_qr_code', $detail['value']);
            error_log('DEBUG - Saved QR code from lineItemDetails');
            $qr_code_found = true;
        }
        
        // חיפוש קוד הפעלה
        if (isset($detail['name']) && $detail['name'] === 'ACTIVATION_CODE' && !empty($detail['value'])) {
            $order->update_meta_data('_esim_activation_code', $detail['value']);
            error_log('DEBUG - Saved activation code from lineItemDetails: ' . $detail['value']);
            $activation_code_found = true;
        }
    }
    
    // שמירת כל פרטי ה-eSIM
    $order->update_meta_data('_esim_details', json_encode($line_item_details));
    error_log('DEBUG - Saved all lineItemDetails as _esim_details');
} 
else {
    // גיבוי - במקרה שהמבנה שונה
    error_log('DEBUG - lineItemDetails not found in expected structure, saving full result');
    $order->update_meta_data('_esim_details', json_encode($response_data['result']));
}
    
    // סימון שההזמנה הושלמה במובימטר
    $order->update_meta_data('_mobimatter_completed', true);
    $order->add_order_note('הזמנה הושלמה בהצלחה במובימטר - פרטי ה-eSIM זמינים כעת');
    $order->save();
    
    error_log('Successfully completed MobiMatter order, eSIM details saved for order #' . $order_id);
    
    // שליחת מייל ללקוח עם פרטי ה-eSIM
    if (function_exists('AdPro_send_esim_details_email')) {
        AdPro_send_esim_details_email($order_id);
        error_log('DEBUG - Sent eSIM details email to customer');
    } else {
        error_log('DEBUG - AdPro_send_esim_details_email function not found');
    }
}