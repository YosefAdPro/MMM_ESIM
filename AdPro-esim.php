<?php
/*
Plugin Name: AdPro eSIM
Description: תוסף למכירת eSIM דרך API של AdPro עם ממשק ניהול ומשתמש.
Version: 1.0
Author: [שמך]
*/

if (!defined('ABSPATH')) {
    exit;
}

// הגדרת קבועים
define('ADPRO_ESIM_URL', plugin_dir_url(__FILE__));
define('ADPRO_ESIM_PATH', plugin_dir_path(__FILE__));

/**
 * וידוא שוווקומרס פעיל לפני שימוש בתוסף
 */
function AdPro_check_woocommerce_active() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'AdPro_woocommerce_missing_notice');
        return false;
    }
    return true;
}
add_action('plugins_loaded', 'AdPro_check_woocommerce_active', 1);

function AdPro_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('AdPro eSIM requires WooCommerce to be installed and active.', 'AdPro-esim'); ?></p>
    </div>
    <?php
}

/**
 * טעינת קבצי התוסף רק אחרי ש-WooCommerce זמין
 */
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // טעינת קבצי התוסף - רק אחרי ש-WooCommerce זמין
    require_once plugin_dir_path(__FILE__) . 'includes/debugging.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
    require_once plugin_dir_path(__FILE__) . 'includes/api-handler.php';
    require_once plugin_dir_path(__FILE__) . 'includes/countries-mapping.php';
    require_once plugin_dir_path(__FILE__) . 'public/frontend.php';
    require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-esim-integration.php';
    require_once plugin_dir_path(__FILE__) . 'includes/emergency-payment.php';
}, 5);

/**
 * רישום שער התשלום iCount
 */
function AdPro_register_payment_gateways($gateways) {
    if (!class_exists('WC_Payment_Gateway')) {
        error_log('WooCommerce Payment Gateway class not found');
        return $gateways;
    }
    
    if (!class_exists('WC_iCount_Gateway')) {
        require_once ADPRO_ESIM_PATH . 'includes/class-wc-icount-gateway.php';
    }
    
    $gateways[] = new WC_iCount_Gateway();
    error_log('Successfully registered iCount gateway');
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'AdPro_register_payment_gateways', 20);

/**
 * וודא ש-WooCommerce נטען לפני ניסיון להשתמש בו
 */
function AdPro_init_payment_gateway() {
    // בדוק אם WooCommerce פעיל
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // וודא שיש גישה ל-WC()
    if (!function_exists('WC')) {
        return;
    }
}
add_action('plugins_loaded', 'AdPro_init_payment_gateway', 20);

/**
 * הוספת כללי שכתוב לניתובים מותאמים
 */
function AdPro_esim_rewrite_rule() {
    add_rewrite_rule(
        '^esim/([^/]+)/?$',
        'index.php?AdPro_esim_country=$matches[1]',
        'top'
    );
}
add_action('init', 'AdPro_esim_rewrite_rule');

function AdPro_esim_query_vars($vars) {
    $vars[] = 'AdPro_esim_country';
    return $vars;
}
add_filter('query_vars', 'AdPro_esim_query_vars');

function AdPro_esim_template_include($template) {
    if (get_query_var('AdPro_esim_country')) {
        $country_param = strtolower(get_query_var('AdPro_esim_country'));
        if (isset($_GET['success'])) {
            return plugin_dir_path(__FILE__) . 'public/success-template.php';
        }
        if (isset($_GET['error'])) {
            return plugin_dir_path(__FILE__) . 'public/error-template.php';
        }
        $template_path = plugin_dir_path(__FILE__) . 'public/esim-country-template.php';
        if (file_exists($template_path)) {
            return $template_path;
        }
    }
    return $template;
}
add_filter('template_include', 'AdPro_esim_template_include');

function AdPro_register_esim_endpoints() {
    add_rewrite_endpoint('esim-add-to-cart', EP_ROOT);
    
    // Check if the endpoint is accessed and load the template
    if (isset($_GET['transaction'])) {
        include(plugin_dir_path(__FILE__) . 'public/esim-add-to-cart.php');
        exit;
    }
}
add_action('init', 'AdPro_register_esim_endpoints');

// קוד קצר לממשק המשתמש
function AdPro_esim_shortcode() {
    ob_start();
    AdPro_esim_display_frontend();
    return ob_get_clean();
}
add_shortcode('AdPro_esim', 'AdPro_esim_shortcode');

/**
 * עדכון תקופתי של חבילות
 */
function AdPro_fetch_packages_periodically() {
    $countries_mapping = AdPro_get_countries_mapping();
    foreach ($countries_mapping as $hebrew => $data) {
        AdPro_esim_get_packages($data['iso']);
    }
}
add_action('AdPro_fetch_packages_event', 'AdPro_fetch_packages_periodically');

if (!wp_next_scheduled('AdPro_fetch_packages_event')) {
    wp_schedule_event(time(), 'daily', 'AdPro_fetch_packages_event');
}

/**
 * אזור אישי - הוספת נקודת קצה
 */
function AdPro_esim_add_account_endpoint() {
    add_rewrite_endpoint('AdPro-esim', EP_ROOT | EP_PAGES);
}
add_action('init', 'AdPro_esim_add_account_endpoint');

function AdPro_esim_account_menu_items($items) {
    $items['AdPro-esim'] = 'חבילות eSIM שלי';
    return $items;
}
add_filter('woocommerce_account_menu_items', 'AdPro_esim_account_menu_items');

function AdPro_esim_account_content() {
    $user_id = get_current_user_id();
    $purchases = get_user_meta($user_id, 'AdPro_esim_purchase', false);
    ?>
    <h2>חבילות eSIM שלי</h2>
    <?php if ($purchases) : ?>
        <table>
            <tr>
                <th>מדינה</th>
                <th>חבילה</th>
                <th>תאריך רכישה</th>
                <th>QR</th>
                <th>גלישה נותרה</th>
            </tr>
            <?php foreach ($purchases as $purchase) : ?>
                <?php
                $api_key = get_option('AdPro_api_key');
                $response = wp_remote_get("https://api.AdPro.com/v1/orders/info?order_id={$purchase['order_id']}", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                    ],
                ]);
                $usage = is_wp_error($response) ? [] : json_decode(wp_remote_retrieve_body($response), true);
                ?>
                <tr>
                    <td><?php echo esc_html($purchase['country']); ?></td>
                    <td><?php echo esc_html($purchase['package_id']); ?></td>
                    <td><?php echo esc_html($purchase['purchase_date']); ?></td>
                    <td><img src="<?php echo esc_url($purchase['qr_code']); ?>" alt="QR Code"></td>
                    <td><?php echo esc_html($usage['data_remaining'] ?? 'לא זמין'); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else : ?>
        <p>לא נמצאו רכישות.</p>
    <?php endif; ?>
    <?php
}
add_action('woocommerce_account_AdPro-esim_endpoint', 'AdPro_esim_account_content');

/**
 * הוספת סקריפטים וסגנונות לטופס התשלום
 */
function AdPro_enqueue_payment_scripts() {
    if (is_checkout()) {
        wp_enqueue_style('AdPro-payment-styles', ADPRO_ESIM_URL . 'public/assets/css/payment.css');
        wp_enqueue_script('AdPro-payment-scripts', ADPRO_ESIM_URL . 'public/assets/js/payment.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'AdPro_enqueue_payment_scripts');

/**
 * פעולות הרשמה/הסרה של התוסף
 */
register_activation_hook(__FILE__, 'AdPro_flush_all_rules');
function AdPro_flush_all_rules() {
    AdPro_esim_rewrite_rule();
    AdPro_register_esim_endpoints();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// פתרון עוקף - רישום ישיר של שער התשלום ב-checkout
add_action('woocommerce_checkout_before_customer_details', function() {
    if (!class_exists('WC_Payment_Gateway')) {
        error_log('WC_Payment_Gateway class not available on checkout page');
        return;
    }
    
    if (!class_exists('WC_iCount_Gateway')) {
        require_once ADPRO_ESIM_PATH . 'includes/class-wc-icount-gateway.php';
    }
    
    // וודא שהשער פעיל
    global $wc_icount_gateway_active;
    if (!isset($wc_icount_gateway_active) || !$wc_icount_gateway_active) {
        // צור שער ידנית
        $gateway = new WC_iCount_Gateway();
        
        // הוסף לרשימת שערי התשלום הזמינים
        if (function_exists('WC') && isset(WC()->payment_gateways) && method_exists(WC()->payment_gateways, 'payment_gateways')) {
            $available_gateways = WC()->payment_gateways->payment_gateways();
            $available_gateways['icount_gateway'] = $gateway;
            
            // עדכן את הרשימה
            WC()->payment_gateways->payment_gateways = $available_gateways;
            
            $wc_icount_gateway_active = true;
            error_log('iCount gateway manually added to available gateways during checkout');
        }
    }
});