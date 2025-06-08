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



