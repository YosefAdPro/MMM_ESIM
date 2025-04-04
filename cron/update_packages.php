<?php
// Load WordPress environment without rendering a page
define('WP_USE_THEMES', false);
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Increase memory limit to handle large datasets
ini_set('memory_limit', '512M');

// Make sure only CLI can run this
if (php_sapi_name() !== 'cli') {
    // Web access - verify secret key
    if (!isset($_GET['secret']) || $_GET['secret'] !== 'your_secret_key_here') {
        die('Access denied.');
    }
}

// Function to log messages
function log_message($message) {
    echo date('[Y-m-d H:i:s]') . " $message\n";
    error_log($message);
}

log_message("Starting eSIM packages update...");

/**
 * פונקציה לעדכון חבילות eSIM מה-API ושמירה במסד נתונים
 */
function AdPro_fetch_packages_to_database() {
    global $wpdb;
    
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (empty($api_key) || empty($merchant_id)) {
        error_log("AdPro eSIM: חסרים פרטי התחברות ל-API");
        return false;
    }
    
    // Build API URL - request all packages at once
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products?category=esim_realtime';
    
    // Set up request arguments
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key,
        ],
        'timeout' => 60, // Longer timeout for the larger request
    ];
    
    log_message("AdPro eSIM: מתחיל עדכון חבילות");
    
    // Make API request
    $response = wp_remote_get($api_url, $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        log_message("AdPro eSIM API Error: " . $response->get_error_message());
        return false;
    }
    
    // Parse response
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Check response validity
    if ($response_code !== 200 || !isset($data['statusCode']) || $data['statusCode'] !== 200) {
        log_message("AdPro eSIM: תגובה לא תקינה מה-API: קוד " . $response_code);
        return false;
    }
    
    // Get all packages from response
    $all_packages = isset($data['result']) ? $data['result'] : [];
    $total_packages = count($all_packages);
    
    if (empty($all_packages)) {
        log_message("AdPro eSIM: לא נמצאו חבילות ב-API");
        return false;
    }
    
    log_message("AdPro eSIM: התקבלו $total_packages חבילות מה-API");
    
    // בדיקה האם הטבלאות קיימות, אם לא - יצירתן
    $table_packages = $wpdb->prefix . 'adpro_esim_packages';
    $table_countries = $wpdb->prefix . 'adpro_esim_countries';
    $table_networks = $wpdb->prefix . 'adpro_esim_networks';
    
    // בדיקה אם הטבלאות קיימות
    $packages_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_packages'") == $table_packages;
    $countries_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_countries'") == $table_countries;
    $networks_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_networks'") == $table_networks;
    
    // אם הטבלאות לא קיימות, יצירתן
    if (!$packages_table_exists || !$countries_table_exists || !$networks_table_exists) {
        log_message("Creating database tables...");
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // טבלה לאחסון חבילות eSIM
        if (!$packages_table_exists) {
            $sql_packages = "CREATE TABLE $table_packages (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                product_id varchar(128) NOT NULL,
                country_iso varchar(10) NOT NULL,
                provider_id varchar(128) NOT NULL,
                provider_name varchar(128) DEFAULT NULL,
                title varchar(255) DEFAULT NULL,
                retail_price decimal(10,2) DEFAULT NULL,
                currency_code varchar(10) DEFAULT NULL,
                data_limit varchar(50) DEFAULT NULL,
                data_unit varchar(20) DEFAULT NULL,
                validity_days int(11) DEFAULT NULL,
                product_details longtext DEFAULT NULL,
                countries longtext DEFAULT NULL,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY product_id (product_id),
                KEY country_iso (country_iso),
                KEY provider_id (provider_id)
            ) $charset_collate;";
            
            $wpdb->query($sql_packages);
            log_message("Created packages table");
        }
        
        // טבלה לאחסון מדינות נתמכות
        if (!$countries_table_exists) {
            $sql_countries = "CREATE TABLE $table_countries (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                iso varchar(10) NOT NULL,
                english_name varchar(128) NOT NULL,
                hebrew_name varchar(128) NOT NULL,
                slug varchar(128) DEFAULT NULL,
                has_packages tinyint(1) DEFAULT 0,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY iso (iso)
            ) $charset_collate;";
            
            $wpdb->query($sql_countries);
            log_message("Created countries table");
        }
        
        // טבלה לאחסון רשתות סלולריות
        if (!$networks_table_exists) {
            $sql_networks = "CREATE TABLE $table_networks (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                product_id varchar(128) NOT NULL,
                country_iso varchar(10) NOT NULL,
                network_brand varchar(128) DEFAULT NULL,
                network_id varchar(128) DEFAULT NULL,
                is_4g tinyint(1) DEFAULT 0,
                is_5g tinyint(1) DEFAULT 0,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY product_id (product_id),
                KEY country_iso (country_iso)
            ) $charset_collate;";
            
            $wpdb->query($sql_networks);
            log_message("Created networks table");
        }
    }
    
    // מערך לאחסון מדינות ייחודיות
    $unique_countries = [];
    
    // מחיקת כל החבילות הקיימות והרשתות לפני הוספת חדשות
    $wpdb->query("TRUNCATE TABLE $table_packages");
    log_message("Truncated packages table");
    
    $wpdb->query("TRUNCATE TABLE $table_networks");
    log_message("Truncated networks table");
    
    // עדכון כל החבילות במסד הנתונים
    $packages_saved = 0;
    $batch_size = 100; // כמה חבילות לשמור בכל פעם
    $packages_batch = [];
    
    foreach ($all_packages as $package) {
        // חילוץ מידע חיוני
        $product_id = isset($package['productId']) ? $package['productId'] : '';
        $provider_id = isset($package['providerId']) ? $package['providerId'] : '';
        $provider_name = isset($package['providerName']) ? $package['providerName'] : '';
        $title = isset($package['title']) ? $package['title'] : '';
        $retail_price = isset($package['retailPrice']) ? floatval($package['retailPrice']) : 0;
        $currency_code = isset($package['currencyCode']) ? $package['currencyCode'] : '';
        $product_details = isset($package['productDetails']) ? json_encode($package['productDetails']) : null;
        $countries_list = isset($package['countries']) ? json_encode($package['countries']) : null;
        
        // שמירת כל המדינות להן החבילה תקפה
        if (isset($package['countries']) && is_array($package['countries'])) {
            foreach ($package['countries'] as $country_iso) {
                // שמירת המדינה הראשונה כמדינה העיקרית של החבילה
                if (!isset($primary_country)) {
                    $primary_country = $country_iso;
                }
                
                // הוספה למערך המדינות הייחודיות
                $unique_countries[$country_iso] = true;
            }
        }
        
        // אם אין מדינה ראשית, דלג על החבילה
        if (!isset($primary_country)) {
            continue;
        }
        
        // חילוץ פרטי חבילה נוספים
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
        
        // הוספה למערך הנתונים
        $packages_batch[] = [
            'product_id' => $product_id,
            'country_iso' => $primary_country,
            'provider_id' => $provider_id,
            'provider_name' => $provider_name,
            'title' => $title,
            'retail_price' => $retail_price,
            'currency_code' => $currency_code,
            'data_limit' => $data_limit,
            'data_unit' => $data_unit,
            'validity_days' => $validity_days,
            'product_details' => $product_details,
            'countries' => $countries_list,
            'last_updated' => current_time('mysql')
        ];
        
        // איפוס המשתנה לחבילה הבאה
        unset($primary_country);
        
        // אם הגענו לגודל המנה או זו החבילה האחרונה
        if (count($packages_batch) >= $batch_size || $package === end($all_packages)) {
            // הוספת הנתונים במנה אחת
            foreach ($packages_batch as $item) {
                $wpdb->insert($table_packages, $item);
                
                if ($wpdb->insert_id) {
                    $packages_saved++;
                }
            }
            
            // איפוס המנה
            $packages_batch = [];
            
            log_message("Saved $packages_saved packages so far");
        }
    }
    
    log_message("AdPro eSIM: נשמרו $packages_saved חבילות במסד הנתונים");
    
    // עדכון טבלת המדינות
    $countries_mapping = function_exists('AdPro_get_countries_mapping') ? AdPro_get_countries_mapping() : [];
    $countries_updated = 0;
    
    // עדכן את כל המדינות כלא פעילות תחילה
    $wpdb->query("UPDATE $table_countries SET has_packages = 0");
    
    foreach ($unique_countries as $iso => $value) {
        // חיפוש המדינה במיפוי
        $hebrew_name = '';
        $english_name = '';
        $slug = '';
        
        if (!empty($countries_mapping)) {
            foreach ($countries_mapping as $hebrew => $data) {
                if ($data['iso'] === $iso) {
                    $hebrew_name = $hebrew;
                    $english_name = $data['english'];
                    $slug = $data['slug'];
                    break;
                }
            }
        }
        
        // אם לא נמצאה במיפוי, השתמש ב-ISO
        if (empty($hebrew_name)) {
            $hebrew_name = $iso;
            $english_name = $iso;
            $slug = strtolower($iso);
        }
        
        // בדוק אם המדינה כבר קיימת במסד הנתונים
        $existing_country = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_countries WHERE iso = %s",
            $iso
        ));
        
        if ($existing_country) {
            // עדכון מדינה קיימת
            $wpdb->update(
                $table_countries,
                [
                    'english_name' => $english_name,
                    'hebrew_name' => $hebrew_name,
                    'slug' => $slug,
                    'has_packages' => 1,
                    'last_updated' => current_time('mysql')
                ],
                ['iso' => $iso]
            );
        } else {
            // הוספת מדינה חדשה
            $wpdb->insert(
                $table_countries,
                [
                    'iso' => $iso,
                    'english_name' => $english_name,
                    'hebrew_name' => $hebrew_name,
                    'slug' => $slug,
                    'has_packages' => 1,
                    'last_updated' => current_time('mysql')
                ]
            );
        }
        
        $countries_updated++;
    }
    
    log_message("AdPro eSIM: עודכנו $countries_updated מדינות במסד הנתונים");
    
    // עדכון זמן עדכון אחרון
    update_option('adpro_packages_last_update', time());
    
    return true;
}

// יצירת טבלאות אם הן לא קיימות
AdPro_fetch_packages_to_database();

log_message("eSIM packages update completed");