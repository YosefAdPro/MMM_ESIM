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
 * בדיקה אם תוסף iCount רשמי מותקן ופעיל
 */
function AdPro_check_icount_plugin() {
    // שם הקובץ הראשי של תוסף iCount - יש להתאים לשם האמיתי של התוסף
    $icount_plugin = 'icount-payment-gateway/icount-payment-gateway.php'; 
    
    if (!is_plugin_active($icount_plugin)) {
        add_action('admin_notices', 'AdPro_icount_plugin_missing_notice');
    }
}
add_action('admin_init', 'AdPro_check_icount_plugin');

function AdPro_icount_plugin_missing_notice() {
    ?>
    <div class="notice notice-warning">
        <p><?php _e('AdPro eSIM מומלץ להתקין את התוסף הרשמי של iCount לסליקת אשראי.', 'AdPro-esim'); ?></p>
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
  
    require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
    require_once plugin_dir_path(__FILE__) . 'includes/api-handler.php';
    require_once plugin_dir_path(__FILE__) . 'includes/countries-mapping.php';
    require_once plugin_dir_path(__FILE__) . 'public/frontend.php';
    require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-esim-integration.php';
	require_once plugin_dir_path(__FILE__) . 'includes/database-tables.php';
    
    // טעינת קובץ האינטגרציה החדש עם תוסף iCount רשמי
    require_once plugin_dir_path(__FILE__) . 'includes/icount-integration.php';
	
	    if (is_admin()) {
        require_once plugin_dir_path(__FILE__) . 'admin/troubleshooter.php';
    }
}, 5);

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

/**
 * פונקציה לקבלת נתוני שימוש של eSIM
 * 
 * @param string $order_id מזהה הזמנה
 * @return array נתוני שימוש
 */
function AdPro_get_esim_usage_data($order_id) {
 /*   // תמיד החזר נתוני דוגמה בשלב הפיתוח
    return [
        "ussdCode" => null,
        "esim" => [
            "status" => "Ready",
            "smdpCode" => "Enable, Executed-Success",
            "installationDate" => "2025-02-23T08:10:22",
            "location" => [
                "updated" => "2025-03-02T19:59:00",
                "country" => "PL",
                "network" => "Plus"
            ],
            "kycStatus" => null,
            "iccid" => "8948010000010937167",
            "phoneNumber" => null,
            "puk" => "60532154",
            "isSuspended" => null,
            "wallet" => [
                "balanceHkd" => 0
            ]
        ],
        "packages" => [
            [
                "name" => "Ukraine and Moldova 10 GB",
                "associatedProductId" => "8fb580a0-466e-4844-b48c-63e5c4f43f57",
                "activationDate" => "2025-02-23T08:26:18",
                "expirationDate" => "2025-02-25T08:26:18",
                "totalAllowanceMb" => 12288,
                "totalAllowanceMin" => null,
                "usedMin" => null,
                "usedMb" => 2000
            ]
        ]
    ];
 */   
    // הקוד המקורי לא ירוץ כי יש return למעלה
    if (empty($order_id)) {
        return [];
    }
	
	
    
    // הגדרת מפתח מטמון
    $cache_key = 'esim_usage_' . $order_id;
    
    // בדיקה אם המידע נמצא במטמון
    $cached_data = get_transient($cache_key);
    
    if (false !== $cached_data) {
        return $cached_data;
    }
    
    // שליחת בקשת API
    $api_url = 'https://myesim.info/usage?orderId=' . urlencode($order_id);
    
    $response = wp_remote_get($api_url, [
        'timeout' => 15,
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
    
    if (is_wp_error($response)) {
        error_log('Error fetching eSIM usage data: ' . $response->get_error_message());
        return [];
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        error_log('Error in eSIM usage API response: ' . $response_code);
        return [];
    }
    
    $data = json_decode($response_body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error parsing eSIM usage JSON: ' . json_last_error_msg());
        return [];
    }
    
    // שמירת המידע במטמון ל-30 דקות
    set_transient($cache_key, $data, 30 * MINUTE_IN_SECONDS);
    
    return $data;
}

/**
 * תצוגת חבילות eSIM באזור האישי
 * קוד זה מחליף את הפונקציה המקורית AdPro_esim_account_content
 */
/**
 * תצוגת חבילות eSIM באזור האישי - גרסה מעודכנת ומשופרת
 * קוד זה מחליף את הפונקציה המקורית AdPro_esim_account_content
 */
function AdPro_esim_account_content() {
    $user_id = get_current_user_id();
    
    // טעינת הסגנונות והסקריפטים
    wp_enqueue_style('AdPro-esim-myaccount', ADPRO_ESIM_URL . 'public/assets/css/myaccount.css');
    wp_enqueue_script('AdPro-esim-account', ADPRO_ESIM_URL . 'public/assets/js/myaccount.js', array('jquery'), null, true);
    
    // בדיקה אם להציג חבילות שפג תוקפן (ברירת מחדל: לא)
    $show_expired = isset($_GET['show_expired']) ? true : false;
    
    // קבלת רכישות מהמטא-דאטה של המשתמש (הישן)
    $purchases = get_user_meta($user_id, 'AdPro_esim_purchase', false);
    
    // קבלת ההזמנות האחרונות עם חבילות eSIM
    $args = [
        'customer_id' => $user_id,
        'status' => ['completed', 'processing'],
        'limit' => 50,
    ];
    $orders = wc_get_orders($args);
    $pending_activation_orders = []; // חבילות שממתינות להפעלה
    $active_orders = []; // חבילות פעילות
    $expired_orders = []; // חבילות שפג תוקפן
    $active_count = 0;
    $pending_count = 0;
    $expired_count = 0;
    
    // נתונים שימושיים
    $current_date = new DateTime();
    
    // בדיקה אם יש חבילות eSIM בהזמנות
    foreach ($orders as $order) {
        $has_esim = false;
        $country = '';
        $package_id = '';
        $package_title = '';
        $data_limit = '';
        $data_unit = '';
        $validity_days = '';
        
        // בדיקה אם יש פריטים עם מטא-דאטה של eSIM
        foreach ($order->get_items() as $item) {
            if ($item->get_meta('esim_package_id')) {
                $has_esim = true;
                $country = $item->get_meta('esim_country');
                $package_id = $item->get_meta('esim_package_id');
                $data_limit = $item->get_meta('esim_data_limit');
                $data_unit = $item->get_meta('esim_data_unit');
                $validity_days = $item->get_meta('esim_validity_days');
                $package_title = $item->get_name();
                break;
            }
        }
        
        // בדיקה אם יש eSIM ויש קוד QR, הוסף לרשימה
        if ($has_esim && $order->get_meta('_esim_qr_code')) {
            $order_data = [
                'order_id' => $order->get_id(),
                'mobimatter_id' => $order->get_meta('_mobimatter_order_id'),
                'country' => $country,
                'package_id' => $package_id,
                'package_title' => $package_title,
                'data_limit' => $data_limit,
                'data_unit' => $data_unit,
                'validity_days' => $validity_days,
                'purchase_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'qr_code' => $order->get_meta('_esim_qr_code'),
                'activation_code' => $order->get_meta('_esim_activation_code')
            ];
            
            // קבלת נתוני שימוש עבור החבילה
            $usage_data = AdPro_get_esim_usage_data($order_data['mobimatter_id']);
            
            // בדיקת הסטטוס של החבילה
            $is_activated = false;
            $is_expired = false;
            
            if (!empty($usage_data) && isset($usage_data['esim']['status'])) {
                // חבילה מופעלת אם הסטטוס הוא Installed, Enabled, או Activated
                if (in_array($usage_data['esim']['status'], ['Installed', 'Enabled', 'Activated'])) {
                    $is_activated = true;
                }
                
                // בדיקה אם החבילה פגת תוקף
                if (!empty($usage_data['packages'])) {
                    $package = $usage_data['packages'][0];
                    if (isset($package['expirationDate']) && !empty($package['expirationDate'])) {
                        $exp_date = new DateTime($package['expirationDate']);
                        if ($current_date > $exp_date) {
                            $is_expired = true;
                            $expired_count++;
                        } elseif ($is_activated) {
                            $active_count++;
                        } else {
                            $pending_count++;
                        }
                    } elseif (!$is_activated) {
                        $pending_count++;
                    } else {
                        $active_count++;
                    }
                } elseif (!$is_activated) {
                    $pending_count++;
                }
            } else {
                // אם אין נתוני שימוש או סטטוס, נניח שהחבילה ממתינה להפעלה
                $pending_count++;
            }
            
            // הוספת נתוני השימוש לחבילה
            $order_data['usage_data'] = $usage_data;
            $order_data['is_activated'] = $is_activated;
            $order_data['is_expired'] = $is_expired;
            
            // הוספה לרשימה המתאימה
            if ($is_expired) {
                $expired_orders[] = $order_data;
            } elseif ($is_activated) {
                $active_orders[] = $order_data;
            } else {
                $pending_activation_orders[] = $order_data;
            }
        }
    }
    
    // כותרת ומידע כללי
    ?>
    <div class="esim-my-packages">
        <h2>חבילות eSIM שלי</h2>
        
        <!-- מידע כללי על מספר החבילות -->
        <div class="esim-packages-summary">
            <div class="summary-box active">
                <span class="count"><?php echo $active_count; ?></span>
                <span class="label">חבילות פעילות</span>
            </div>
            <div class="summary-box pending">
                <span class="count"><?php echo $pending_count; ?></span>
                <span class="label">ממתינות להפעלה</span>
            </div>
            <div class="summary-box expired">
                <span class="count"><?php echo $expired_count; ?></span>
                <span class="label">פג תוקף</span>
            </div>
        </div>
        
        <!-- סינון חבילות -->
        <div class="esim-packages-filter">
            <?php if ($expired_count > 0): ?>
                <?php if (!$show_expired): ?>
                    <a href="?show_expired=1" class="show-expired-button">הצג חבילות שפג תוקפן (<?php echo $expired_count; ?>)</a>
                <?php else: ?>
                    <a href="?" class="hide-expired-button">הסתר חבילות שפג תוקפן</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($pending_activation_orders) || !empty($active_orders) || (!empty($expired_orders) && $show_expired)): ?>
            <div class="esim-packages-list">
                
                <!-- חבילות ממתינות להפעלה -->
                <?php if (!empty($pending_activation_orders)): ?>
                    <h3 class="section-title">חבילות ממתינות להפעלה</h3>
                    <div class="pending-packages-grid">
                        <?php foreach ($pending_activation_orders as $order): ?>
                            <div class="esim-package-card pending">
                                <!-- סרגל סטטוס עם אייקון -->
                                <div class="status-ribbon pending">
                                    <span class="status-icon">⏱️</span>
                                    <span class="status-text">ממתין להפעלה</span>
                                </div>
                                
                                <div class="esim-package-header">
                                    <h3><?php echo esc_html($order['package_title']); ?></h3>
                                    <div class="esim-package-country">
                                        <?php echo esc_html($order['country']); ?>
                                    </div>
                                </div>
                                
                                <div class="esim-package-details">
                                    <!-- הצגת רק מידע חיוני לחבילות ממתינות להפעלה -->
                                    <div class="esim-package-info">
                                        <div class="esim-package-data">
                                            <strong>נפח גלישה:</strong> 
                                            <?php 
                                            if (!empty($order['data_limit']) && !empty($order['data_unit'])) {
                                                echo esc_html($order['data_limit'] . ' ' . $order['data_unit']);
                                            } elseif (!empty($order['usage_data']['packages'][0]['totalAllowanceMb'])) {
                                                $total_mb = $order['usage_data']['packages'][0]['totalAllowanceMb'];
                                                echo esc_html(round($total_mb / 1024, 1) . ' GB');
                                            } else {
                                                echo 'לא ידוע';
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="esim-package-validity">
                                            <strong>תוקף:</strong> 
                                            <?php 
                                            if (!empty($order['validity_days'])) {
                                                echo esc_html($order['validity_days'] . ' ימים');
                                            } else {
                                                echo 'לא ידוע';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- כפתור הפעלה בולט -->
                                <div class="activation-button-container">
                                    <button class="activation-button" 
                                            data-qr="<?php echo esc_attr($order['qr_code']); ?>"
                                            data-code="<?php echo esc_attr($order['activation_code']); ?>"
                                            data-order-id="<?php echo esc_attr($order['order_id']); ?>"
                                            data-package="<?php echo esc_attr($order['package_title']); ?>">
                                        הפעל עכשיו
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- חבילות פעילות -->
                <?php if (!empty($active_orders)): ?>
                    <h3 class="section-title">חבילות פעילות</h3>
                    <div class="active-packages-grid">
                    <?php foreach ($active_orders as $order): 
                        // עיבוד נתוני השימוש
                        $usage_data = $order['usage_data'];
                        $country_code = '';
                        $network = '';
                        $expiration_date = '';
                        $total_mb = 0;
                        $used_mb = 0;
                        $status = '';
                        $days_left = 0;
                        
                        if (!empty($usage_data)) {
                            // חילוץ נתוני שימוש
                            if (isset($usage_data['esim']['status'])) {
                                $status = $usage_data['esim']['status'];
                            }
                            
                            if (isset($usage_data['esim']['location']['country'])) {
                                $country_code = $usage_data['esim']['location']['country'];
                            }
                            
                            if (isset($usage_data['esim']['location']['network'])) {
                                $network = $usage_data['esim']['location']['network'];
                            }
                            
                            if (!empty($usage_data['packages'])) {
                                $package = $usage_data['packages'][0]; // לוקח את החבילה הראשונה
                                
                                if (isset($package['expirationDate'])) {
                                    $exp_date = new DateTime($package['expirationDate']);
                                    $expiration_date = $exp_date->format('d/m/Y');
                                    
                                    // חישוב ימים שנותרו
                                    $days_left = $current_date->diff($exp_date)->days;
                                }
                                
                                if (isset($package['totalAllowanceMb'])) {
                                    $total_mb = $package['totalAllowanceMb'];
                                }
                                
                                if (isset($package['usedMb'])) {
                                    $used_mb = $package['usedMb'];
                                }
                            }
                        }
                        
                        // חישוב אחוז ניצול
                        $usage_percent = ($total_mb > 0) ? min(100, round(($used_mb / $total_mb) * 100)) : 0;
                    ?>
                        <div class="esim-package-card active">
                            <!-- סרגל סטטוס עם אייקון -->
                            <div class="status-ribbon active">
                                <span class="status-icon">✓</span>
                                <span class="status-text">פעיל</span>
                            </div>
                            
                            <div class="esim-package-header">
                                <h3><?php echo esc_html($order['package_title']); ?></h3>
                                <div class="esim-package-country">
                                    <?php echo esc_html($order['country']); ?>
                                </div>
                            </div>
                            
                            <div class="esim-package-details">
                                <div class="esim-package-info">
                                    <div class="esim-package-data">
                                        <strong>נפח גלישה:</strong> 
                                        <?php 
                                        if (!empty($order['data_limit']) && !empty($order['data_unit'])) {
                                            echo esc_html($order['data_limit'] . ' ' . $order['data_unit']);
                                        } elseif ($total_mb > 0) {
                                            echo esc_html(round($total_mb / 1024, 1) . ' GB');
                                        } else {
                                            echo 'לא ידוע';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="esim-package-validity">
                                        <strong>תוקף עד:</strong> 
                                        <?php 
                                        if (!empty($expiration_date)) {
                                            echo esc_html($expiration_date);
                                            if (isset($days_left)) {
                                                echo ' <span class="days-left">(' . $days_left . ' ימים)</span>';
                                            }
                                        } elseif (!empty($order['validity_days'])) {
                                            echo esc_html($order['validity_days'] . ' ימים');
                                        } else {
                                            echo 'לא ידוע';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="esim-package-code">
                                        <strong>קוד הפעלה:</strong> 
                                        <span class="activation-code"><?php echo esc_html($order['activation_code']); ?></span>
                                        <button class="view-qr-button" 
                                                data-qr="<?php echo esc_attr($order['qr_code']); ?>"
                                                data-code="<?php echo esc_attr($order['activation_code']); ?>">
                                            הצג QR
                                        </button>
                                    </div>
                                    
                                    <?php if (!empty($status)) : ?>
                                    <div class="esim-package-status">
                                        <strong>סטטוס:</strong> 
                                        <span class="status-badge <?php echo sanitize_title($status); ?>">
                                            <?php
                                            $status_text = '';
                                            switch ($status) {
                                                case 'Installed':
                                                    $status_text = 'מותקן';
                                                    break;
                                                case 'Enabled':
                                                    $status_text = 'מופעל';
                                                    break;
                                                case 'Activated':
                                                    $status_text = 'פעיל';
                                                    break;
                                                case 'Ready':
                                                    $status_text = 'מוכן';
                                                    break;
                                                default:
                                                    $status_text = $status;
                                                    break;
                                            }
                                            echo esc_html($status_text);
                                            ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($country_code) && !empty($network)) : ?>
                                    <div class="esim-package-location">
                                        <strong>רישום אחרון:</strong>
                                        <?php if (!empty($country_code)) : ?>
                                            <span class="country-flag">
                                                <img src="https://flagcdn.com/16x12/<?php echo strtolower($country_code); ?>.png" alt="<?php echo esc_attr($country_code); ?>">
                                            </span>
                                        <?php endif; ?>
                                        <?php echo esc_html($network); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($total_mb > 0) : ?>
                            <div class="esim-package-usage">
                                <div class="usage-title">ניצול חבילה:</div>
                                <div class="usage-bar-container">
                                    <div class="usage-bar" style="width: <?php echo esc_attr($usage_percent); ?>%"></div>
                                </div>
                                <div class="usage-stats">
                                    <span class="used"><?php echo round($used_mb / 1024, 2); ?> GB</span>
                                    <span class="total"><?php echo round($total_mb / 1024, 2); ?> GB</span>
                                    <span class="percent"><?php echo $usage_percent; ?>%</span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- חבילות שפג תוקפן -->
                <?php if ($show_expired && !empty($expired_orders)) : ?>
                    <h3 class="section-title">חבילות שפג תוקפן</h3>
                    <div class="expired-packages-grid">
                    <?php foreach ($expired_orders as $order): 
                        // עיבוד נתוני שימוש דומה לחבילות פעילות
                        $usage_data = $order['usage_data'];
                        $country_code = '';
                        $network = '';
                        $expiration_date = '';
                        $total_mb = 0;
                        $used_mb = 0;
                        $status = '';
                        
                        if (!empty($usage_data)) {
                            // אותו קוד כמו בחבילות פעילות לחילוץ נתונים
                            if (isset($usage_data['esim']['status'])) {
                                $status = $usage_data['esim']['status'];
                            }
                            
                            if (isset($usage_data['esim']['location']['country'])) {
                                $country_code = $usage_data['esim']['location']['country'];
                            }
                            
                            if (isset($usage_data['esim']['location']['network'])) {
                                $network = $usage_data['esim']['location']['network'];
                            }
                            
                            if (!empty($usage_data['packages'])) {
                                $package = $usage_data['packages'][0];
                                
                                if (isset($package['expirationDate'])) {
                                    $exp_date = new DateTime($package['expirationDate']);
                                    $expiration_date = $exp_date->format('d/m/Y');
                                }
                                
                                if (isset($package['totalAllowanceMb'])) {
                                    $total_mb = $package['totalAllowanceMb'];
                                }
                                
                                if (isset($package['usedMb'])) {
                                    $used_mb = $package['usedMb'];
                                }
                            }
                        }
                        
                        // חישוב אחוז ניצול
                        $usage_percent = ($total_mb > 0) ? min(100, round(($used_mb / $total_mb) * 100)) : 0;
                    ?>
                        <div class="esim-package-card expired">
                            <div class="expired-badge">פג תוקף</div>
                            
                            <div class="esim-package-header">
                                <h3><?php echo esc_html($order['package_title']); ?></h3>
                                <div class="esim-package-country">
                                    <?php echo esc_html($order['country']); ?>
                                </div>
                            </div>
                            
                            <div class="esim-package-details">
                                <div class="esim-package-info">
                                    <!-- תוכן דומה לחבילות פעילות עם התאמות -->
                                    <div class="esim-package-data">
                                        <strong>נפח גלישה:</strong> 
                                        <?php 
                                        if (!empty($order['data_limit']) && !empty($order['data_unit'])) {
                                            echo esc_html($order['data_limit'] . ' ' . $order['data_unit']);
                                        } elseif ($total_mb > 0) {
                                            echo esc_html(round($total_mb / 1024, 1) . ' GB');
                                        } else {
                                            echo 'לא ידוע';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="esim-package-validity">
                                        <strong>פג תוקף:</strong> 
                                        <?php 
                                        if (!empty($expiration_date)) {
                                            echo esc_html($expiration_date);
                                        } elseif (!empty($order['validity_days'])) {
                                            echo esc_html($order['validity_days'] . ' ימים');
                                        } else {
                                            echo 'לא ידוע';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($total_mb > 0) : ?>
                            <div class="esim-package-usage">
                                <div class="usage-title">ניצול חבילה:</div>
                                <div class="usage-bar-container">
                                    <div class="usage-bar expired" style="width: <?php echo esc_attr($usage_percent); ?>%"></div>
                                </div>
                                <div class="usage-stats">
                                    <span class="used"><?php echo round($used_mb / 1024, 2); ?> GB</span>
                                    <span class="total"><?php echo round($total_mb / 1024, 2); ?> GB</span>
                                    <span class="percent"><?php echo $usage_percent; ?>%</span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- מודאל להפעלת חבילה -->
            <div id="activation-modal" class="esim-modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>הפעלת חבילת eSIM</h2>
                    <div id="activation-modal-content">
                        <!-- תוכן דינמי יוזן כאן ע"י JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- מודאל להצגת קוד QR בגדול -->
            <div id="qr-modal" class="esim-modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>קוד QR להפעלה</h2>
                    <div id="qr-modal-content">
                        <!-- תוכן דינמי יוזן כאן ע"י JavaScript -->
                    </div>
                </div>
            </div>
            
        <?php else : ?>
            <p>לא נמצאו רכישות eSIM.</p>
        <?php endif; ?>
    </div>
    <?php
}
add_action('woocommerce_account_AdPro-esim_endpoint', 'AdPro_esim_account_content');

/**
 * הוספת סקריפטים וסגנונות
 */
function AdPro_enqueue_scripts() {
    // סגנונות למסך חשבון אישי
    wp_register_style('AdPro-esim-myaccount', ADPRO_ESIM_URL . 'public/assets/css/myaccount.css');
    
    // סגנונות לטופס תשלום
    if (is_checkout()) {
        wp_enqueue_style('AdPro-payment-styles', ADPRO_ESIM_URL . 'public/assets/css/payment.css');
        wp_enqueue_script('AdPro-payment-scripts', ADPRO_ESIM_URL . 'public/assets/js/payment.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'AdPro_enqueue_scripts');

/**
 * הוספת רכישת eSIM למטא-דאטה של המשתמש
 */
function AdPro_add_esim_purchase_to_user_meta($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // בדיקה אם מדובר בהזמנת eSIM
    $has_esim = false;
    $country = '';
    $package_id = '';
    
    foreach ($order->get_items() as $item) {
        if ($item->get_meta('esim_package_id')) {
            $has_esim = true;
            $country = $item->get_meta('esim_country');
            $package_id = $item->get_meta('esim_package_id');
            break;
        }
    }
    
    if (!$has_esim) {
        return;
    }
    
    // בדיקה אם יש קוד QR ומזהה הזמנת מובימטר
    $qr_code = $order->get_meta('_esim_qr_code');
    $activation_code = $order->get_meta('_esim_activation_code');
    $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
    
    if (empty($qr_code) || empty($mobimatter_order_id)) {
        return;
    }
    
    // הוספת הרכישה לרשימת הרכישות של המשתמש
    $user_id = $order->get_user_id();
    
    if ($user_id) {
        $purchase_data = [
            'order_id' => $mobimatter_order_id,
            'country' => $country,
            'package_id' => $package_id,
            'purchase_date' => date('Y-m-d H:i:s'),
            'qr_code' => $qr_code,
            'activation_code' => $activation_code
        ];
        
        add_user_meta($user_id, 'AdPro_esim_purchase', $purchase_data);
     //   error_log('DEBUG - Added purchase data to user meta for order #' . $order_id);
    }
}
add_action('woocommerce_order_status_completed', 'AdPro_add_esim_purchase_to_user_meta', 20);
add_action('woocommerce_payment_complete', 'AdPro_add_esim_purchase_to_user_meta', 20);

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