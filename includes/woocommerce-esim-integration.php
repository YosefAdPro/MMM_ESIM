<?php
/**
 * פונקציות האינטגרציה של WooCommerce עם מובימטר ו-iCount
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}

/**
 * יצירה או קבלה של מוצר דינמי eSIM
 * 
 * @return int מזהה המוצר
 */
 
 

function AdPro_get_or_create_esim_product() {
    $sku = 'dynamic_esim_package';
    $product_id = wc_get_product_id_by_sku($sku);
    
    if ($product_id) {
        return $product_id;
    }
    
    // יצירת מוצר דינמי בפעם הראשונה בלבד
    $product = new WC_Product_Simple();
    $product->set_name('חבילת eSIM דינמית');
    $product->set_status('publish');
    $product->set_catalog_visibility('hidden');
    $product->set_sku($sku);
    $product->set_sold_individually(true);
    $product->set_virtual(true); // מוצר וירטואלי - אין משלוח
    
    $product_id = $product->save();
    
    return $product_id;
}

/**
 * טיפול בתהליך הרכישה מדף החבילות
 */
// עדכן את הקוד בקובץ woocommerce-esim-integration.php בתהליך בחירת חבילה
/**
 * טיפול בתהליך הרכישה מדף החבילות כולל המרת מטבע לשקלים
 */
function AdPro_process_package_selection() {
    // בדיקה אם WooCommerce פעיל וזמין
    if (!function_exists('WC') || !class_exists('WooCommerce')) {
        wp_redirect(home_url('/esim?error=woocommerce_not_active'));
        exit;
    }
    
    // וודא שיש הפעלת סשן
    if (!WC()->is_rest_api_request() && !is_admin()) {
        if (!WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }
    
    // בדיקת פרמטרים
    if (!isset($_POST['package_id']) || !isset($_POST['country'])) {
        wp_redirect(home_url('/esim?error=missing_parameters'));
        exit;
    }
    
    // ניקוי קלטים
    $package_id = sanitize_text_field($_POST['package_id']);
    $hebrew_country = sanitize_text_field($_POST['country']);
    
    // קבלת מידע על המדינה
    $countries_mapping = AdPro_get_countries_mapping();
    if (!isset($countries_mapping[$hebrew_country])) {
        wp_redirect(home_url('/esim?error=invalid_country'));
        exit;
    }
    
    $country_data = $countries_mapping[$hebrew_country];
    $iso_code = $country_data['iso'];
    $english_country = $country_data['english'];
    
    // קבלת פרטי חבילה מ-API של מובימטר
    $package = AdPro_get_package_by_id($iso_code, $package_id);
    if (!$package) {
        wp_redirect(home_url('/esim?error=package_not_found'));
        exit;
    }
    
    // יצירה או קבלה של מוצר דינמי
    $product_id = AdPro_get_or_create_esim_product();
    $product = wc_get_product($product_id);
    
    // חילוץ פרטי חבילה רלוונטיים
    $data_limit = '';
    $data_unit = '';
    $validity_days = '';
    
    if (isset($package['productDetails']) && is_array($package['productDetails'])) {
        foreach ($package['productDetails'] as $detail) {
            if ($detail['name'] === 'PLAN_DATA_LIMIT' && !empty($detail['value'])) {
                $data_limit = $detail['value'];
            }
            if ($detail['name'] === 'PLAN_DATA_UNIT' && !empty($detail['value'])) {
                $data_unit = $detail['value'];
            }
            if ($detail['name'] === 'PLAN_VALIDITY' && !empty($detail['value'])) {
                $validity_days = round(intval($detail['value']) / 24);
            }
        }
    }
    
    // עדכון שם ומחיר בהתאם לחבילה הנוכחית
    $title = "חבילת eSIM";
    if (!empty($data_limit) && !empty($data_unit)) {
        $title .= ": {$data_limit}{$data_unit}";
    }
    $title .= " - {$hebrew_country}";
    
    if (isset($package['title']) && !empty($package['title'])) {
        $title .= " ({$package['title']})";
    }
    
    $product->set_name($title);
    
    // המרת מחיר מדולר לשקל אם צריך
    if (isset($package['retailPrice']) && !empty($package['retailPrice'])) {
        $price = floatval($package['retailPrice']);
        $currency = isset($package['currencyCode']) ? $package['currencyCode'] : 'USD';
        
        // המרה לשקלים אם המחיר אינו בשקלים
        if ($currency !== 'ILS') {
            // קבלת שערי חליפין
            $exchange_rates = AdPro_get_exchange_rates();
            
            // חישוב המחיר בשקלים
            if ($currency === 'USD') {
                // המרה ישירה מדולר לשקל לפי השער
                $price_in_ils = $price * $exchange_rates['ILS'];
            } elseif (isset($exchange_rates[$currency])) {
                // המרה דרך דולר כמטבע בסיס
                $price_in_usd = $price / $exchange_rates[$currency];
                $price_in_ils = $price_in_usd * $exchange_rates['ILS'];
            } else {
                // אם אין שער חליפין, נשתמש בדולרים
                $price_in_ils = $price * $exchange_rates['ILS'];
            }
            
            // עיגול המחיר למספר עם שתי ספרות אחרי הנקודה
            $price = round($price_in_ils, 2);
        }
        
        $product->set_price($price);
        $product->set_regular_price($price);
    }
    
    $product->save();
    
    // במקום להשתמש ישירות ב-WC()->cart, נשתמש ב-cookies לשמירת המידע
    // והפניה לדף מיוחד שיוסיף את המוצר לעגלה
    $cart_item_data = [
        'esim_package_id' => $package_id,
        'esim_country' => $hebrew_country,
        'esim_country_iso' => $iso_code,
        'esim_country_english' => $english_country,
        'esim_package_title' => $package['title'] ?? $title,
        'esim_data_limit' => $data_limit,
        'esim_data_unit' => $data_unit,
        'esim_validity_days' => $validity_days,
        'esim_package_data' => json_encode($package),
        'esim_original_currency' => $currency,
        'esim_original_price' => isset($package['retailPrice']) ? $package['retailPrice'] : '',
        'unique_key' => md5(microtime().rand()) // מניעת איחוד פריטים
    ];
    
    // שמירת נתוני עגלה בסשן או טרנזאקציה זמנית
    $transaction_id = 'esim_cart_' . md5(microtime().rand());
    set_transient($transaction_id, $cart_item_data, 60 * 10); // שמירה ל-10 דקות
    
    // לוג לדיבאג
    error_log('Setting cart transaction: ' . $transaction_id);
    error_log('Cart data: ' . json_encode($cart_item_data));
    
    // הפניה לדף "הוסף לעגלה" מיוחד שיטפל בחלק של WooCommerce
    wp_redirect(home_url('/esim-add-to-cart?transaction=' . $transaction_id));
    exit;
}
add_action('admin_post_AdPro_process_package', 'AdPro_process_package_selection');
add_action('admin_post_nopriv_AdPro_process_package', 'AdPro_process_package_selection');

/**
 * הצגת המידע המותאם על החבילה בעגלה
 */
/**
 * הצגת המידע המותאם על החבילה בעגלה כולל מידע על המרת מטבע
 */
function AdPro_display_cart_item_custom_data($item_data, $cart_item) {
    if (isset($cart_item['esim_country'])) {
        // נשתמש באימוג'י דגל במקום תמונת HTML
        $flag_emoji = '';
        if (isset($cart_item['esim_country_iso'])) {
            $iso_code = strtoupper($cart_item['esim_country_iso']);
            // המרת קוד ISO למשהו שדומה לאימוג'י דגל
            // (כל אות גדולה ב-ISO הופכת לאימוג'י דגל אזורי)
            $flag_emoji = implode('', array_map(function($char) {
                // המרת כל אות ל- regional indicator symbol
                return mb_chr(ord($char) - ord('A') + 0x1F1E6);
            }, str_split($iso_code)));
        }
        
        $item_data[] = [
            'key' => 'מדינה',
            'value' => $flag_emoji . ' ' . $cart_item['esim_country']
        ];
    }
    
    if (isset($cart_item['esim_data_limit']) && isset($cart_item['esim_data_unit'])) {
        $item_data[] = [
            'key' => 'נפח גלישה',
            'value' => $cart_item['esim_data_limit'] . ' ' . $cart_item['esim_data_unit']
        ];
    }
    
    if (isset($cart_item['esim_validity_days'])) {
        $item_data[] = [
            'key' => 'תקופת תוקף',
            'value' => $cart_item['esim_validity_days'] . ' ימים'
        ];
    }
    
    // הוספת מידע על המרת מטבע אם רלוונטי
    if (isset($cart_item['esim_original_currency']) && isset($cart_item['esim_original_price']) &&
        $cart_item['esim_original_currency'] !== 'ILS') {
        
        // הצגת המחיר המקורי והמטבע
        $original_currency = $cart_item['esim_original_currency'];
        $original_price = $cart_item['esim_original_price'];
        
        // בחירת הסימן המתאים למטבע
        $currency_symbol = '$'; // ברירת מחדל לדולר
        if ($original_currency === 'EUR') {
            $currency_symbol = '€';
        } elseif ($original_currency === 'GBP') {
            $currency_symbol = '£';
        }
        
        $item_data[] = [
            'key' => 'מחיר מקורי',
            'value' => $currency_symbol . $original_price . ' (' . $original_currency . ')'
        ];
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'AdPro_display_cart_item_custom_data', 10, 2);

/**
 * העברת המידע המותאם מהעגלה להזמנה
 */
function AdPro_add_order_item_meta($item, $cart_item_key, $values, $order) {
    // נשמור את המידע המקורי, אבל נסתיר אותו מהתצוגה הציבורית
    foreach ($values as $key => $value) {
        if (strpos($key, 'esim_') === 0) {
            $item->add_meta_data($key, $value, true);
        }
    }
    
    // נוסיף את השדות בעברית שיוצגו ללקוח
    if (isset($values['esim_country'])) {
        $flag_html = '';
        if (isset($values['esim_country_iso'])) {
            $iso_code = strtolower($values['esim_country_iso']);
            $flag_html = '<img src="https://flagcdn.com/16x12/' . esc_attr($iso_code) . '.png" alt="דגל" style="vertical-align: middle; margin-right: 5px;">';
        }
        
        $item->add_meta_data('מדינה', $flag_html . $values['esim_country'], false);
    }
    
    if (isset($values['esim_data_limit']) && isset($values['esim_data_unit'])) {
        $item->add_meta_data('נפח גלישה', $values['esim_data_limit'] . ' ' . $values['esim_data_unit'], false);
    }
    
    if (isset($values['esim_validity_days'])) {
        $item->add_meta_data('תקופת תוקף', $values['esim_validity_days'] . ' ימים', false);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'AdPro_add_order_item_meta', 10, 4);


// אפשר תגי HTML בשדות מטה-דאטה
function AdPro_allow_html_in_meta_display($formatted_meta) {
    foreach ($formatted_meta as $key => $meta) {
        if ($meta->key == 'מדינה') {
            $formatted_meta[$key]->display = $meta->value;
        }
    }
    return $formatted_meta;
}
add_filter('woocommerce_order_item_get_formatted_meta_data', 'AdPro_allow_html_in_meta_display', 20, 1);

// הוספת מסנן שמסיר שדות טכניים מתצוגת ההזמנה
function AdPro_filter_order_item_meta($formatted_meta, $item) {
    $filtered_meta = array();
    
    // לולאה על כל המטא-דאטה המפורמט
    foreach ($formatted_meta as $meta) {
        // בדיקה אם זה שדה טכני של esim שאנחנו רוצים להסתיר
        if (strpos($meta->key, 'esim_') !== 0) {
            // זה לא שדה שאנחנו רוצים להסתיר - נשמור אותו
            $filtered_meta[] = $meta;
        }
    }
    
    return $filtered_meta;
}
add_filter('woocommerce_order_item_get_formatted_meta_data', 'AdPro_filter_order_item_meta', 10, 2);

/**
 * יצירת הזמנה במובימטר לפני השלמת התשלום
 */
function AdPro_create_mobimatter_order($order_id) {
    $order = wc_get_order($order_id);
    
    // סטטוס תקין לבדיקה לפני יצירת ההזמנה
    if ($order->get_status() !== 'pending') {
        return;
    }
    
    $order_items = $order->get_items();
    if (empty($order_items)) {
        return;
    }
    
    // בדיקה של הפריט הראשון
    $item = reset($order_items);
    
    // וידוא שיש מידע של חבילת eSIM
    $package_id = $item->get_meta('esim_package_id');
    $country_iso = $item->get_meta('esim_country_iso');
    
    if (empty($package_id) || empty($country_iso)) {
        return;
    }
    
    // יצירת הזמנה במובימטר
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order';
    
    $order_data = [
        'productId' => $package_id,
        'quantity' => 1,
        'orderId' => 'wc_' . $order_id // סימון ייחודי של מקור ההזמנה
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
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
    
    if ($response_code !== 200 || !isset($response_data['result']['id'])) {
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
        $order->add_order_note('שגיאה ביצירת הזמנה במובימטר: ' . $error_message);
        error_log('שגיאה ביצירת הזמנה במובימטר: קוד ' . $response_code . ', הודעה: ' . $error_message);
        return;
    }
    
    // שמירת מזהה ההזמנה במובימטר
    $mobimatter_order_id = $response_data['result']['id'];
    $order->update_meta_data('_mobimatter_order_id', $mobimatter_order_id);
    $order->add_order_note('נוצרה הזמנה במובימטר בהצלחה, מזהה: ' . $mobimatter_order_id);
    $order->save();
}
add_action('woocommerce_checkout_order_created', 'AdPro_create_mobimatter_order', 10, 1);

/**
 * הפניה ל-iCount לתשלום לאחר יצירת ההזמנה
 */
function AdPro_redirect_to_icount_payment($order_id) {
    $order = wc_get_order($order_id);
    
    // בדיקה אם ההזמנה מחכה לתשלום
    if ($order->get_status() !== 'pending') {
        return;
    }
    
    // בדיקה אם ההזמנה נוצרה במובימטר
    $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
    if (empty($mobimatter_order_id)) {
        $order->add_order_note('לא נמצא מזהה הזמנה במובימטר');
        return;
    }
    
    // פרטי API של iCount
    $icount_api_key = get_option('AdPro_icount_api_key');
    $icount_company_id = get_option('AdPro_icount_company_id');
    
    if (empty($icount_api_key) || empty($icount_company_id)) {
        $order->add_order_note('חסרים פרטי API של iCount');
        wc_add_notice('שגיאה בהגדרות מערכת התשלומים', 'error');
        return;
    }
	
	
	
    
    // הכנת נתונים לתשלום
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_email = $order->get_billing_email();
    $customer_phone = $order->get_billing_phone();
    
    // איסוף פרטי מוצרים להצגה בחשבונית
    $items = [];
    foreach ($order->get_items() as $item) {
        $country = $item->get_meta('esim_country') ?: '';
        $data_limit = $item->get_meta('esim_data_limit') ?: '';
        $data_unit = $item->get_meta('esim_data_unit') ?: '';
        $validity_days = $item->get_meta('esim_validity_days') ?: '';
        
        $description = "חבילת eSIM";
        if (!empty($data_limit) && !empty($data_unit)) {
            $description .= " {$data_limit}{$data_unit}";
        }
        if (!empty($country)) {
            $description .= " ל{$country}";
        }
        if (!empty($validity_days)) {
            $description .= " ל-{$validity_days} ימים";
        }
        
        $items[] = [
            'description' => $description,
            'quantity' => $item->get_quantity(),
            'price' => $item->get_total() / $item->get_quantity(),
            'currency' => 'ILS'
        ];
    }
    
    // הכנת נתונים לתשלום ב-iCount
    $payment_data = [
        'cid' => $icount_company_id,
        'api_token' => $icount_api_key,
        'type' => 'invoice_receipt', // חשבונית קבלה בפעולה אחת
        'client_name' => $customer_name,
        'client_email' => $customer_email,
        'client_phone' => $customer_phone,
        'items' => $items,
        'description' => "הזמנה #{$order_id}",
        'success_url' => add_query_arg(['action' => 'AdPro_icount_success', 'order_id' => $order_id], home_url('/wc-api/AdPro_icount_callback')),
        'error_url' => add_query_arg(['action' => 'AdPro_icount_error', 'order_id' => $order_id], home_url('/wc-api/AdPro_icount_callback')),
    ];
    
    // שליחת בקשה ל-iCount
    $response = wp_remote_post('https://api.icount.co.il/api/v1/payment-page', [
        'body' => json_encode($payment_data),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $order->add_order_note('שגיאה בחיבור ל-iCount: ' . $error_message);
        wc_add_notice('שגיאה בחיבור למערכת הסליקה: ' . $error_message, 'error');
        return;
    }
    
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
    
    if (!isset($response_data['status']) || $response_data['status'] !== 'success' || !isset($response_data['payment_url'])) {
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
        $order->add_order_note('שגיאה ביצירת עמוד תשלום ב-iCount: ' . $error_message);
        wc_add_notice('שגיאה בהכנת עמוד התשלום: ' . $error_message, 'error');
        return;
    }
    
    // שמירת פרטי תשלום ב-iCount
    if (isset($response_data['doc_no'])) {
        $order->update_meta_data('_icount_doc_no', $response_data['doc_no']);
    }
    
    $order->add_order_note('הועבר לתשלום ב-iCount');
    $order->save();
    
    // הפניה לעמוד התשלום של iCount
    wp_redirect($response_data['payment_url']);
    exit;
}
add_action('woocommerce_thankyou', 'AdPro_redirect_to_icount_payment', 5);

/**
 * טיפול בתשובה מ-iCount
 */
function AdPro_handle_icount_callback() {
    if (!isset($_GET['action']) || !isset($_GET['order_id'])) {
        wp_redirect(home_url());
        exit;
    }
    
    $action = sanitize_text_field($_GET['action']);
    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_redirect(home_url());
        exit;
    }
    
    if ($action === 'AdPro_icount_success') {
        // תשלום הצליח - השלמת הזמנה במובימטר
        $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
        
        if ($mobimatter_order_id) {
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
                
                // הפנייה לדף שגיאה
                wp_redirect(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')) . '?esim_error=mobimatter_complete');
                exit;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);
            
            if ($response_code !== 200 || !isset($response_data['result'])) {
                $error_message = isset($response_data['message']) ? $response_data['message'] : 'שגיאה לא ידועה';
                $order->add_order_note('שגיאה בהשלמת הזמנה במובימטר: ' . $error_message);
                
                // הפנייה לדף שגיאה
                wp_redirect(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')) . '?esim_error=mobimatter_complete');
                exit;
            }
            
            // שמירת פרטי ה-eSIM
            if (isset($response_data['result']['qrCodeImage'])) {
                $order->update_meta_data('_esim_qr_code', $response_data['result']['qrCodeImage']);
            }
            
            if (isset($response_data['result']['activationCode'])) {
                $order->update_meta_data('_esim_activation_code', $response_data['result']['activationCode']);
            }
            
            if (isset($response_data['result']['details'])) {
                $order->update_meta_data('_esim_details', json_encode($response_data['result']['details']));
            }
            
            // עדכון סטטוס ההזמנה
            $order->update_status('completed', 'ההזמנה הושלמה בהצלחה במובימטר');
            $order->save();
            
            // שליחת מייל ללקוח עם פרטי ה-eSIM
            AdPro_send_esim_details_email($order_id);
            
            // הפנייה לדף התודה
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        } else {
            // לא נמצא מזהה הזמנה במובימטר
            $order->add_order_note('תשלום התקבל, אך לא נמצא מזהה הזמנה במובימטר');
            
            // הפנייה לדף שגיאה
            wp_redirect(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')) . '?esim_error=missing_mobimatter_id');
            exit;
        }
    } elseif ($action === 'AdPro_icount_error') {
        // תשלום נכשל - ביטול הזמנה
        $order->update_status('failed', 'התשלום נכשל');
        
        // ביטול הזמנה במובימטר
        $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
        
        if ($mobimatter_order_id) {
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
        
        // הפנייה לדף שגיאה
        wp_redirect(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount')) . '?esim_error=payment_failed');
        exit;
    } else {
        // פעולה לא מוכרת
        wp_redirect(home_url());
        exit;
    }
}
add_action('wc-api_AdPro_icount_callback', 'AdPro_handle_icount_callback');

/**
 * שליחת מייל עם פרטי eSIM לאחר השלמת הזמנה
 */
function AdPro_send_esim_details_email($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    $customer_email = $order->get_billing_email();
    if (empty($customer_email)) {
        return;
    }
    
    // קבלת פרטי eSIM
    $qr_code_url = $order->get_meta('_esim_qr_code');
    $activation_code = $order->get_meta('_esim_activation_code');
    $esim_details = json_decode($order->get_meta('_esim_details'), true);
    
    // חילוץ פרטי חבילה מפריטי ההזמנה
    $country = '';
    $data_package = '';
    $validity = '';
    
    foreach ($order->get_items() as $item) {
        $country = $item->get_meta('esim_country') ?: '';
        $data_limit = $item->get_meta('esim_data_limit') ?: '';
        $data_unit = $item->get_meta('esim_data_unit') ?: '';
        $validity_days = $item->get_meta('esim_validity_days') ?: '';
        
        if (!empty($data_limit) && !empty($data_unit)) {
            $data_package = $data_limit . $data_unit;
        }
        
        if (!empty($validity_days)) {
            $validity = $validity_days . ' ימים';
        }
        
        break; // נתמוך רק בפריט אחד כרגע
    }
    
    // בניית תוכן המייל
    $subject = 'פרטי ה-eSIM שלך - הזמנה #' . $order->get_id();
    
    $message = '<div dir="rtl" style="text-align: right; font-family: Arial, sans-serif;">';
    $message .= '<h2 style="color: #0073aa;">תודה על הזמנתך!</h2>';
    
    if (!empty($country)) {
        $message .= '<p>להלן פרטי ה-eSIM שרכשת עבור <strong>' . esc_html($country) . '</strong>:</p>';
    } else {
        $message .= '<p>להלן פרטי ה-eSIM שרכשת:</p>';
    }
    
    if (!empty($data_package)) {
        $message .= '<p>נפח נתונים: <strong>' . esc_html($data_package) . '</strong></p>';
    }
    
    if (!empty($validity)) {
        $message .= '<p>תקופת תוקף: <strong>' . esc_html($validity) . '</strong></p>';
    }
    
    if (!empty($activation_code)) {
        $message .= '<p>קוד הפעלה: <strong>' . esc_html($activation_code) . '</strong></p>';
    }
    
    if (!empty($qr_code_url)) {
        $message .= '<div style="text-align: center; margin: 20px 0;">';
        $message .= '<p>סרוק את קוד ה-QR להפעלת ה-eSIM:</p>';
        $message .= '<img src="' . esc_url($qr_code_url) . '" alt="QR Code" style="max-width: 300px;">';
        $message .= '</div>';
    }
    
    $message .= '<div style="background-color: #f8f8f8; padding: 15px; border-radius: 5px; margin-top: 20px;">';
    $message .= '<h3 style="color: #0073aa;">הוראות הפעלה:</h3>';
    $message .= '<ol>';
    $message .= '<li>לך להגדרות > סלולרי > הוסף תוכנית סלולרית</li>';
    $message .= '<li>סרוק את קוד ה-QR המצורף או הזן את קוד ההפעלה ידנית</li>';
    $message .= '<li>אשר את התקנת ה-eSIM</li>';
    $message .= '<li>ודא שהנדידה (Roaming) מופעלת לפני הגעה ליעד</li>';
    $message .= '</ol>';
    $message .= '</div>';
    
    // מידע נוסף על החבילה, אם קיים
    if (!empty($esim_details)) {
        $message .= '<div style="margin-top: 20px;">';
        $message .= '<h3 style="color: #0073aa;">פרטים נוספים:</h3>';
        $message .= '<ul>';
        
        foreach ($esim_details as $key => $value) {
            if (!empty($value) && is_string($value)) {
                $message .= '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
            }
        }
        
        $message .= '</ul>';
        $message .= '</div>';
    }
    
    $message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 14px; color: #666;">';
    $message .= '<p>לצפייה בפרטי ההזמנה המלאים, <a href="' . esc_url(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount'))) . '">כנס לאזור האישי</a>.</p>';
    $message .= '<p>לתמיכה טכנית, אנא צור קשר בטלפון: <a href="tel:+97227222222">072-2222222</a></p>';
    $message .= '</div>';
    
    $message .= '</div>';
    
    // הגדרת תוכן HTML
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // שליחת המייל
    wp_mail($customer_email, $subject, $message, $headers);
    
    // הוספת הערה להזמנה
    $order->add_order_note('נשלח מייל עם פרטי ה-eSIM ללקוח');
}

/**
 * הוספת תצוגת פרטי eSIM בדף הזמנה באזור האישי
 */
function AdPro_display_esim_details_in_order($order) {
    // בדיקה אם יש פרטי eSIM
    $qr_code_url = $order->get_meta('_esim_qr_code');
    
    if (empty($qr_code_url)) {
        return;
    }
    
    $activation_code = $order->get_meta('_esim_activation_code');
    $esim_details = json_decode($order->get_meta('_esim_details'), true);
    
    // חילוץ פרטי חבילה מפריטי ההזמנה
    $country = '';
    $data_package = '';
    $validity = '';
    
    foreach ($order->get_items() as $item) {
        $country = $item->get_meta('esim_country') ?: '';
        $data_limit = $item->get_meta('esim_data_limit') ?: '';
        $data_unit = $item->get_meta('esim_data_unit') ?: '';
        $validity_days = $item->get_meta('esim_validity_days') ?: '';
        
        if (!empty($data_limit) && !empty($data_unit)) {
            $data_package = $data_limit . $data_unit;
        }
        
        if (!empty($validity_days)) {
            $validity = $validity_days . ' ימים';
        }
        
        break; // נתמוך רק בפריט אחד כרגע
    }
    
    ?>
    <section class="esim-details-section">
        <h2>פרטי ה-eSIM</h2>
        
        <div class="esim-details-container">
            <?php if (!empty($country) || !empty($data_package) || !empty($validity)) : ?>
                <div class="esim-package-info">
                    <h3>פרטי החבילה</h3>
                    <ul>
                        <?php if (!empty($country)) : ?>
                            <li><strong>מדינה:</strong> <?php echo esc_html($country); ?></li>
                        <?php endif; ?>
                        
                        <?php if (!empty($data_package)) : ?>
                            <li><strong>נפח נתונים:</strong> <?php echo esc_html($data_package); ?></li>
                        <?php endif; ?>
                        
                        <?php if (!empty($validity)) : ?>
                            <li><strong>תקופת תוקף:</strong> <?php echo esc_html($validity); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="esim-activation-info">
                <h3>פרטי הפעלה</h3>
                
                <?php if (!empty($activation_code)) : ?>
                    <p><strong>קוד הפעלה:</strong> <?php echo esc_html($activation_code); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($qr_code_url)) : ?>
                    <div class="esim-qr-code">
                        <p><strong>קוד QR להפעלה:</strong></p>
                        <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code">
                    </div>
                <?php endif; ?>
                
                <div class="esim-instructions">
                    <h4>הוראות הפעלה:</h4>
                    <ol>
                        <li>לך להגדרות > סלולרי > הוסף תוכנית סלולרית</li>
                        <li>סרוק את קוד ה-QR או הזן את קוד ההפעלה ידנית</li>
                        <li>אשר את התקנת ה-eSIM</li>
                        <li>ודא שהנדידה (Roaming) מופעלת לפני הגעה ליעד</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    
    <style>
        .esim-details-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f8f8;
            border-radius: 5px;
        }
        
        .esim-details-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .esim-package-info, .esim-activation-info {
            flex: 1;
            min-width: 250px;
        }
        
        .esim-qr-code {
            margin: 15px 0;
            text-align: center;
        }
        
        .esim-qr-code img {
            max-width: 200px;
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
        }
        
        .esim-instructions {
            margin-top: 20px;
        }
        
        .esim-instructions ol {
            padding-right: 20px;
        }
        
        @media (max-width: 768px) {
            .esim-details-container {
                flex-direction: column;
            }
        }
    </style>
    <?php
}
add_action('woocommerce_order_details_after_order_table', 'AdPro_display_esim_details_in_order');

/**
 * הוספת תצוגת חבילות eSIM בדף התודה (Thank You)
 */
function AdPro_display_esim_details_on_thankyou($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // בדיקה אם יש פרטי eSIM
    $qr_code_url = $order->get_meta('_esim_qr_code');
    
    if (empty($qr_code_url)) {
        // אם אין קוד QR, כנראה שהתהליך עדיין לא הסתיים
        ?>
        <div class="esim-processing">
            <h2>ה-eSIM שלך מתעבד...</h2>
            <p>אנו מעבדים את הזמנתך. פרטי ה-eSIM ישלחו אליך במייל בקרוב.</p>
            <p>תוכל גם למצוא את פרטי ה-eSIM <a href="<?php echo esc_url(wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount'))); ?>">באזור האישי</a> שלך.</p>
        </div>
        <?php
        return;
    }
    
    $activation_code = $order->get_meta('_esim_activation_code');
    
    // חילוץ פרטי חבילה מפריטי ההזמנה
    $country = '';
    $data_package = '';
    $validity = '';
    
    foreach ($order->get_items() as $item) {
        $country = $item->get_meta('esim_country') ?: '';
        $data_limit = $item->get_meta('esim_data_limit') ?: '';
        $data_unit = $item->get_meta('esim_data_unit') ?: '';
        $validity_days = $item->get_meta('esim_validity_days') ?: '';
        
        if (!empty($data_limit) && !empty($data_unit)) {
            $data_package = $data_limit . $data_unit;
        }
        
        if (!empty($validity_days)) {
            $validity = $validity_days . ' ימים';
        }
        
        break; // נתמוך רק בפריט אחד כרגע
    }
    
    ?>
    <div class="esim-success-message">
        <h2>ה-eSIM שלך מוכן!</h2>
        
        <?php if (!empty($country)) : ?>
            <p>תודה על רכישת חבילת eSIM עבור <?php echo esc_html($country); ?>.</p>
        <?php else : ?>
            <p>תודה על רכישת חבילת eSIM.</p>
        <?php endif; ?>
        
        <?php if (!empty($data_package) || !empty($validity)) : ?>
            <div class="esim-package-details">
                <?php if (!empty($data_package)) : ?>
                    <span class="esim-data"><?php echo esc_html($data_package); ?></span>
                <?php endif; ?>
                
                <?php if (!empty($validity)) : ?>
                    <span class="esim-validity">לתקופה של <?php echo esc_html($validity); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="esim-qr-container">
            <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code">
            
            <?php if (!empty($activation_code)) : ?>
                <p class="esim-activation-code">קוד הפעלה: <strong><?php echo esc_html($activation_code); ?></strong></p>
            <?php endif; ?>
        </div>
        
        <div class="esim-instructions">
            <h3>הוראות הפעלה:</h3>
            <ol>
                <li>לך להגדרות > סלולרי > הוסף תוכנית סלולרית</li>
                <li>סרוק את קוד ה-QR המוצג למעלה או הזן את קוד ההפעלה ידנית</li>
                <li>אשר את התקנת ה-eSIM</li>
                <li>ודא שהנדידה (Roaming) מופעלת לפני הגעה ליעד</li>
            </ol>
        </div>
        
        <p class="esim-email-notice">שלחנו לך גם מייל עם פרטי ה-eSIM.</p>
    </div>
    
    <style>
        .esim-success-message {
            background-color: #f8f8f8;
            border-radius: 8px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        
        .esim-success-message h2 {
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .esim-package-details {
            margin: 15px 0;
            font-size: 16px;
        }
        
        .esim-data {
            font-weight: bold;
            margin-left: 10px;
            margin-right: 10px;
        }
        
        .esim-validity {
            color: #555;
        }
        
        .esim-qr-container {
            margin: 25px auto;
            max-width: 250px;
        }
        
        .esim-qr-container img {
            max-width: 100%;
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        
        .esim-activation-code {
            margin-top: 10px;
            font-family: monospace;
            background: #f0f0f0;
            padding: 5px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .esim-instructions {
            text-align: right;
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .esim-instructions h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .esim-instructions ol {
            padding-right: 20px;
            margin-bottom: 0;
        }
        
        .esim-instructions li {
            margin-bottom: 10px;
        }
        
        .esim-email-notice {
            margin-top: 25px;
            color: #666;
            font-style: italic;
        }
        
        .esim-processing {
            background-color: #fff8e5;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .esim-processing h2 {
            color: #f7a700;
        }
    </style>
    <?php
}
add_action('woocommerce_thankyou', 'AdPro_display_esim_details_on_thankyou', 20);

/**
 * עדכון טופס הרכישה בדף חבילות eSIM לעבודה עם WooCommerce
 */
function AdPro_update_purchase_form($form_html, $package, $hebrew_country) {
    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="AdPro_process_package">
        <input type="hidden" name="package_id" value="<?php echo esc_attr($package['productId']); ?>">
        <input type="hidden" name="country" value="<?php echo esc_attr($hebrew_country); ?>">
        <button type="submit" class="buy-now">רכוש עכשיו</button>
    </form>
    <?php
    return ob_get_clean();
}
add_filter('AdPro_esim_purchase_form', 'AdPro_update_purchase_form', 10, 3);