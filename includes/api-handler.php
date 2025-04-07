<?php
/**
 * טיפול ב-API של מובימטר
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}

// הגדר שערי חליפין קבועים (אפשר לעדכן ידנית לפי הצורך)
/**
 * פונקציה לקבלת שערי חליפין עדכניים
 * 
 * @return array מערך של שערי חליפין
 */
function AdPro_get_exchange_rates() {
    // נסה לקבל שערי חליפין מהמטמון
    $exchange_rates = get_transient('adpro_exchange_rates');
    
    // אם אין במטמון, קבל שערים עדכניים
    if (false === $exchange_rates) {
        // נסה לקבל שערים מ-API חיצוני
        $api_url = 'https://open.er-api.com/v6/latest/USD';
        $response = wp_remote_get($api_url, ['timeout' => 10]);
        
        if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($data['rates'])) {
                $exchange_rates = [
                    'USD' => 1,      // דולר (בסיס)
                    'ILS' => $data['rates']['ILS'] ?? 3.7,    // שקל ישראלי
                    'EUR' => $data['rates']['EUR'] ?? 0.92,   // אירו
                    'GBP' => $data['rates']['GBP'] ?? 0.79    // ליש"ט
                ];
                
                // שמור במטמון ל-24 שעות
                set_transient('adpro_exchange_rates', $exchange_rates, 24 * HOUR_IN_SECONDS);
                
                // תיעוד ההצלחה
                error_log('AdPro eSIM: Updated exchange rates from API');
                
                return $exchange_rates;
            }
        }
        
        // אם ה-API נכשל, השתמש בערכים קבועים
        $exchange_rates = [
            'USD' => 1,      // דולר (בסיס)
            'ILS' => 3.7,    // שקל ישראלי
            'EUR' => 0.92,   // אירו
            'GBP' => 0.79    // ליש"ט
        ];
        
        // שמירה במטמון ל-6 שעות (זמן קצר יותר כי אלו ערכי ברירת מחדל)
        set_transient('adpro_exchange_rates', $exchange_rates, 6 * HOUR_IN_SECONDS);
        
        // תיעוד השימוש בערכי ברירת מחדל
        error_log('AdPro eSIM: Using default exchange rates');
    }
    
    return $exchange_rates;
}


/**
 * פונקציה משופרת לסינון חבילות דומות ולהשאיר רק את הזולות ביותר
 * 
 * @param array $packages מערך של חבילות
 * @return array מערך מסונן של חבילות
 */
function AdPro_filter_duplicate_packages($packages) {
    if (empty($packages) || !is_array($packages)) {
        return $packages;
    }
    
    // יצירת מערך לאחסון חבילות ייחודיות
    $unique_packages = [];
    
    // מערך עזר לבדיקת חבילות דומות
    $package_fingerprints = [];
    
    // מידע דיבוג
    $debug_info = [];
    
    foreach ($packages as $package) {
        // בדיקת תקינות המידע
        if (!isset($package['productDetails']) || !is_array($package['productDetails']) || 
            !isset($package['retailPrice']) || !isset($package['countries'])) {
            continue;
        }
        
        // חילוץ פרטי החבילה
        $data_limit = '';
        $data_unit = '';
        $validity_days = '';
        $provider_name = isset($package['providerName']) ? $package['providerName'] : '';
        $countries = isset($package['countries']) ? $package['countries'] : [];
        $countries_count = count($countries);
        $main_country = !empty($countries) ? $countries[0] : '';
        
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
        
        // יצירת טביעת אצבע ייחודית - משתמשת בנתונים, תוקף, מדינה ראשית ומספר המדינות
        $fingerprint = "{$data_limit}_{$data_unit}_{$validity_days}_{$main_country}_{$countries_count}";
        
        // שמירת מידע לדיבוג
        $debug = [
            'id' => $package['productId'],
            'title' => isset($package['title']) ? $package['title'] : '',
            'provider' => $provider_name,
            'data' => "{$data_limit} {$data_unit}",
            'validity' => "{$validity_days} days",
            'price' => $package['retailPrice'],
            'countries_count' => $countries_count,
            'fingerprint' => $fingerprint
        ];
        
        // בדיקה אם ראינו כבר חבילה דומה
        if (isset($package_fingerprints[$fingerprint])) {
            // השוואת מחירים
            $existing_index = $package_fingerprints[$fingerprint];
            $existing_package = $unique_packages[$existing_index];
            
            // שמירת מידע דיבוג
            if (!isset($debug_info[$fingerprint])) {
                $debug_info[$fingerprint] = ['packages' => [], 'chosen' => null];
            }
            $debug_info[$fingerprint]['packages'][] = $debug;
            
            // אם החבילה הנוכחית זולה יותר, החלף את הקיימת
            if (floatval($package['retailPrice']) < floatval($existing_package['retailPrice'])) {
                $debug_info[$fingerprint]['chosen'] = $package['productId'] . ' (cheaper)';
                $unique_packages[$existing_index] = $package;
            } else {
                $debug_info[$fingerprint]['chosen'] = $existing_package['productId'] . ' (kept)';
            }
        } else {
            // זו חבילה חדשה - הוסף אותה למערך
            $index = count($unique_packages);
            $unique_packages[] = $package;
            $package_fingerprints[$fingerprint] = $index;
            
            // שמירת מידע דיבוג
            $debug_info[$fingerprint] = ['packages' => [$debug], 'chosen' => $package['productId'] . ' (first)'];
        }
    }
    
    // שמירת מידע הדיבוג
    update_option('AdPro_duplicate_filter_debug', $debug_info);
    
    // מיון לפי מחיר מהזול ליקר
    usort($unique_packages, function($a, $b) {
        return floatval($a['retailPrice']) - floatval($b['retailPrice']);
    });
    
    // החזר מערך של חבילות ייחודיות
    return array_values($unique_packages);
}

/**
 * פונקציה לתצוגת דיבוג של סינון חבילות דומות
 */
function AdPro_display_duplicate_filter_debug() {
    if (!current_user_can('manage_options') || !isset($_GET['debug_filter'])) {
        return;
    }
    
    $debug_info = get_option('AdPro_duplicate_filter_debug', []);
    
    echo '<div style="background: #f0f8ff; padding: 15px; margin: 20px 0; border-left: 5px solid #0073aa;">';
    echo '<h2>סינון חבילות דומות - מידע דיבוג</h2>';
    
    if (empty($debug_info)) {
        echo '<p>אין מידע דיבוג זמין. ייתכן שהפונקציה AdPro_filter_duplicate_packages טרם רצה.</p>';
        echo '</div>';
        return;
    }
    
    echo '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
    echo '<tr style="background-color: #eee;">';
    echo '<th style="text-align: right; padding: 8px; border: 1px solid #ddd;">טביעת אצבע</th>';
    echo '<th style="text-align: right; padding: 8px; border: 1px solid #ddd;">חבילות דומות</th>';
    echo '<th style="text-align: right; padding: 8px; border: 1px solid #ddd;">חבילה שנבחרה</th>';
    echo '</tr>';
    
    foreach ($debug_info as $fingerprint => $info) {
        echo '<tr>';
        echo '<td style="vertical-align: top; padding: 8px; border: 1px solid #ddd;">' . esc_html($fingerprint) . '</td>';
        
        echo '<td style="vertical-align: top; padding: 8px; border: 1px solid #ddd;">';
        foreach ($info['packages'] as $pkg) {
            echo '<div style="margin-bottom: 10px; padding: 8px; border: 1px solid #eee; background: ' . ($info['chosen'] == $pkg['id'] . ' (cheaper)' || $info['chosen'] == $pkg['id'] . ' (first)' || $info['chosen'] == $pkg['id'] . ' (kept)' ? '#e8f5e9' : '#fff') . ';">';
            echo '<strong>מזהה:</strong> ' . esc_html($pkg['id']) . '<br>';
            echo '<strong>כותרת:</strong> ' . esc_html($pkg['title']) . '<br>';
            echo '<strong>ספק:</strong> ' . esc_html($pkg['provider']) . '<br>';
            echo '<strong>נתונים:</strong> ' . esc_html($pkg['data']) . '<br>';
            echo '<strong>תוקף:</strong> ' . esc_html($pkg['validity']) . '<br>';
            echo '<strong>מחיר:</strong> ' . esc_html($pkg['price']) . '<br>';
            echo '<strong>מספר מדינות:</strong> ' . esc_html($pkg['countries_count']) . '<br>';
            echo '</div>';
        }
        echo '</td>';
        
        echo '<td style="vertical-align: top; padding: 8px; border: 1px solid #ddd;">' . esc_html($info['chosen']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
}

// הוספת פונקציית הדיבוג לתחתית הדף
add_action('wp_footer', 'AdPro_display_duplicate_filter_debug');


/**
 * פונקציה לעיגול חכם של מחירים לתצוגה
 * 
 * @param float $price המחיר המקורי
 * @return string המחיר המעוגל בצורה חכמה
 */
function AdPro_get_smart_display_price($price) {
    // עיגול המחיר לספרה עשרונית אחת
    $rounded = round($price, 1);
    
    // בדיקה אם המספר המעוגל הוא בעצם מספר שלם
    if (floor($rounded) == $rounded) {
        // אם כן, הצג ללא נקודה עשרונית
        return number_format(floor($rounded), 0);
    } else {
        // אחרת, הצג עם ספרה עשרונית אחת
        return number_format($rounded, 1);
    }
}



/**
 * פונקציה משופרת לקבלת חבילות eSIM ממסד הנתונים לפי מדינה.
 * הפונקציה מחפשת חבילות שהמדינה מופיעה גם בשדה country_iso וגם בשדה countries
 * 
 * @param string $country קוד מדינה
 * @return array מערך של חבילות
 */
function AdPro_esim_get_packages($country = '') {
    global $wpdb;
    
    // בדוק אם טבלת החבילות קיימת
    $table_packages = $wpdb->prefix . 'adpro_esim_packages';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_packages'") == $table_packages;
    
    // אם הטבלה לא קיימת, ננסה להשתמש בקבצי JSON כגיבוי
    if (!$table_exists) {
        error_log("Table $table_packages does not exist, falling back to JSON files");
        return AdPro_esim_get_packages_from_json($country);
    }
    
    // מערך להחזרה
    $packages = [];
    
    // סינון ספקים מוסתרים
    $hidden_providers = get_option('AdPro_hidden_providers', []);
    $hidden_providers_sql = '';
    
    if (!empty($hidden_providers) && is_array($hidden_providers)) {
        $placeholders = implode(',', array_fill(0, count($hidden_providers), '%s'));
        $hidden_providers_sql = $wpdb->prepare(" AND provider_id NOT IN ($placeholders) ", $hidden_providers);
    }
    
    // שאילתה לפי מדינה אם צוינה - עם שינוי משמעותי שמחפש גם במערך countries
    if (!empty($country)) {
        // הכנה של תבנית JSON לחיפוש בשדה countries
        $country_pattern = "%\"$country\"%";
        
        // שאילתה משופרת שמחפשת גם בשדה country_iso וגם בתוך מערך countries
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_packages 
            WHERE (country_iso = %s OR countries LIKE %s) 
            $hidden_providers_sql 
            ORDER BY retail_price ASC",
            $country, $country_pattern
        );
    } else {
        // שאילתה לכל החבילות
        $sql = "SELECT * FROM $table_packages WHERE 1=1 $hidden_providers_sql ORDER BY retail_price ASC";
    }
    
    // ביצוע השאילתה
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (empty($results)) {
        error_log("No packages found in DB for $country, attempting to fetch from API or cache");
        
        // נסה לקבל ממטמון
        $cache_key = 'AdPro_esim_packages_' . md5($country);
        $cached_packages = get_transient($cache_key);
        
        if ($cached_packages !== false) {
            error_log("Returning packages from cache");
            return $cached_packages;
        }
        
        // אם אין גם במטמון, נסה ישירות מה-API
        return AdPro_esim_get_packages_from_api($country);
    }
    
    // עיבוד התוצאות למבנה דומה למה שחזר מקבצי JSON
    foreach ($results as $row) {
        $package = [
            'productId' => $row['product_id'],
            'providerId' => $row['provider_id'],
            'providerName' => $row['provider_name'],
            'title' => $row['title'],
            'retailPrice' => $row['retail_price'],
            'currencyCode' => $row['currency_code'],
            'countries' => json_decode($row['countries'], true) ?: [],
            'productDetails' => json_decode($row['product_details'], true) ?: [],
            // תוספת של שדות מעובדים לשמירת תאימות
            'processed_data' => [
                'data_limit' => $row['data_limit'],
                'data_unit' => $row['data_unit'],
                'validity_days' => $row['validity_days'],
                'title' => $row['title']
            ]
        ];
        
        // סינון חבילות עם מגבלת מהירות
        if (isset($package['productDetails']) && is_array($package['productDetails'])) {
            $has_speed_limit = false;
            
            foreach ($package['productDetails'] as $detail) {
                if ($detail['name'] === 'SPEED') {
                    $speed_value = strtolower($detail['value']);
                    
                    if (stripos($speed_value, 'mbps') !== false || 
                        $speed_value === 'limited' || 
                        stripos($speed_value, '7') !== false) {
                        $has_speed_limit = true;
                        break;
                    }
                }
            }
            
            if ($has_speed_limit) {
                continue; // דלג על חבילות עם מגבלת מהירות
            }
        }
        
        // הוספת שדה עזר לעיגול מחירים
        $original_price = $row['retail_price'];
        $rounded_price = (float)AdPro_get_smart_display_price(floatval($original_price));
        $display_price = AdPro_get_smart_display_price(floatval($original_price));
        
        $package['original_price'] = $original_price;
        $package['display_price'] = $display_price;
        $package['retailPrice'] = $rounded_price;
        
        $packages[] = $package;
    }
    
    // מיון לפי מחיר
    usort($packages, function($a, $b) {
        return floatval($a['retailPrice']) - floatval($b['retailPrice']);
    });
    
    // שמירה בקאש
    $cache_key = 'AdPro_esim_packages_' . md5($country);
    set_transient($cache_key, $packages, HOUR_IN_SECONDS);
    
    return $packages;
}

/**
 * פונקציית גיבוי לקבלת חבילות מקבצי JSON
 * זהה לפונקציה המקורית טרם השדרוג למסד נתונים
 */
function AdPro_esim_get_packages_from_json($country = '') {
    // אם ביקשו מדינה ספציפית
    if (!empty($country)) {
        // בדיקה אם יש קובץ ספציפי למדינה
        $country_file = ADPRO_ESIM_PATH . 'data/' . strtolower($country) . '.json';
        
        if (file_exists($country_file)) {
            $json_data = file_get_contents($country_file);
            $packages = json_decode($json_data, true);
            
            if ($packages !== null) {
                // סינון חבילות עם מגבלת מהירות
                $packages = AdPro_filter_speed_restricted_packages($packages);
                // סינון חבילות כפולות - השאר רק את הזולות ביותר
                $packages = AdPro_filter_duplicate_packages($packages);            
                // סינון ספקים מוסתרים
                $hidden_providers = get_option('AdPro_hidden_providers', []);
                if (!empty($hidden_providers)) {
                    $packages = array_filter($packages, function($package) use ($hidden_providers) {
                        return !isset($package['providerId']) || !in_array($package['providerId'], $hidden_providers);
                    });
                    $packages = array_values($packages);
                }
                // עיגול חכם של המחירים לתצוגה
                foreach ($packages as &$package) {
                    if (isset($package['retailPrice'])) {
                        // שמירת המחיר המקורי לפני העיגול למקרה שנצטרך אותו
                        $package['original_price'] = $package['retailPrice'];
                        
                        // עיגול המחיר בצורה חכמה - המרה למספר
                        $rounded_price = (float)AdPro_get_smart_display_price(floatval($package['retailPrice']));
                        $package['display_price'] = AdPro_get_smart_display_price(floatval($package['retailPrice']));
                        
                        // עדכון המחיר ב-package
                        $package['retailPrice'] = $rounded_price;
                    }
                }
                return $packages;
            }
        }
    } 
    // אם ביקשו את כל החבילות, ננסה לאסוף מכל הקבצים
    else {
        // קבלת רשימת המדינות
        $countries_list_file = ADPRO_ESIM_PATH . 'data/countries_list.json';
        
        if (file_exists($countries_list_file)) {
            $countries_list = json_decode(file_get_contents($countries_list_file), true);
            $all_packages = [];
            
            // איסוף החבילות מכל המדינות
            foreach ($countries_list as $iso) {
                $country_file = ADPRO_ESIM_PATH . 'data/' . strtolower($iso) . '.json';
                
                if (file_exists($country_file)) {
                    $country_packages = json_decode(file_get_contents($country_file), true);
                    if (is_array($country_packages)) {
                        $all_packages = array_merge($all_packages, $country_packages);
                    }
                }
            }
            
            if (!empty($all_packages)) {
                // סינון חבילות עם מגבלת מהירות
                $all_packages = AdPro_filter_speed_restricted_packages($all_packages);
                // סינון חבילות כפולות - השאר רק את הזולות ביותר
                $all_packages = AdPro_filter_duplicate_packages($all_packages);
                // סינון ספקים מוסתרים
                $hidden_providers = get_option('AdPro_hidden_providers', []);
                if (!empty($hidden_providers)) {
                    $all_packages = array_filter($all_packages, function($package) use ($hidden_providers) {
                        return !isset($package['providerId']) || !in_array($package['providerId'], $hidden_providers);
                    });
                    $all_packages = array_values($all_packages);
                }
                
                return $all_packages;
            }
        }
    }
    
    // אם לא נמצאו קבצי JSON, ננסה לקבל ישירות מה-API
    return AdPro_esim_get_packages_from_api($country);
}

/**
 * פונקציית גיבוי לקבלת חבילות ישירות מה-API
 */
function AdPro_esim_get_packages_from_api($country = '') {
    // בדיקה אם יש במטמון
    $cache_key = 'AdPro_esim_packages_' . md5($country);
    $cached_packages = get_transient($cache_key);
    
    // אם יש במטמון וזה לא מצב דיבאג, החזר את המטמון
    if ($cached_packages !== false && !isset($_GET['no_cache'])) {
        error_log("Returning packages from WordPress transient cache");
        return $cached_packages;
    }

    // קבל פרטי התחברות מהגדרות התוסף
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');

    // וודא שיש פרטי התחברות
    if (empty($api_key) || empty($merchant_id)) {
        error_log('AdPro eSIM: Missing API credentials');
        return [];
    }

    // בנה את כתובת ה-API
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products';
    $query_params = [];
    
    // הוסף פרמטר מדינה אם יש
    if (!empty($country)) {
        $query_params['country'] = $country;
    }
    
    // הוספת פרמטר הקטגוריה
    $query_params['category'] = 'esim_realtime';
    
    // הוסף פרמטרים לכתובת
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    // בנה את פרטי הבקשה
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key,
        ],
        'timeout' => 30, // הגדל את זמן התגובה המקסימלי
    ];

    error_log("Fetching packages directly from API: $api_url");

    // שלח את הבקשה ל-API
    $response = wp_remote_get($api_url, $args);
    
    // בדוק אם הייתה שגיאה
    if (is_wp_error($response)) {
        error_log('AdPro eSIM API Error: ' . $response->get_error_message());
        return [];
    }

    // קבל את תוכן התגובה ופענח JSON
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // בדוק שהתגובה תקינה
    if (!isset($data['statusCode']) || $data['statusCode'] !== 200) {
        error_log('AdPro eSIM API Error: Invalid response - ' . json_encode($data));
        return [];
    }

    // קבל את החבילות מהתגובה
    $packages = isset($data['result']) ? $data['result'] : [];
    
    // סינון חבילות עם מגבלת מהירות
    $packages = AdPro_filter_speed_restricted_packages($packages);
    
    // סינון חבילות כפולות - השאר רק את הזולות ביותר
    $packages = AdPro_filter_duplicate_packages($packages);
    
    // סינון ספקים שסומנו להסתרה
    $hidden_providers = get_option('AdPro_hidden_providers', []);
    
    // סנן את החבילות לפי ספקים מוסתרים
    if (!empty($hidden_providers)) {
        $packages = array_filter($packages, function($package) use ($hidden_providers) {
            return !isset($package['providerId']) || !in_array($package['providerId'], $hidden_providers);
        });
        
        // המר בחזרה למערך רגיל (לא אסוציאטיבי)
        $packages = array_values($packages);
    }
    
    // הוסף מידע מעובד לחבילות
    foreach ($packages as &$package) {
        // חילוץ פרטי חבילה רלוונטיים
        $package['processed_data'] = [
            'data_limit' => '',
            'data_unit' => '',
            'validity_days' => '',
            'title' => isset($package['title']) ? $package['title'] : ''
        ];
        
        if (isset($package['productDetails']) && is_array($package['productDetails'])) {
            foreach ($package['productDetails'] as $detail) {
                if ($detail['name'] === 'PLAN_TITLE' && !empty($detail['value'])) {
                    $package['processed_data']['title'] = $detail['value'];
                }
                if ($detail['name'] === 'PLAN_DATA_LIMIT' && !empty($detail['value'])) {
                    $package['processed_data']['data_limit'] = $detail['value'];
                }
                if ($detail['name'] === 'PLAN_DATA_UNIT' && !empty($detail['value'])) {
                    $package['processed_data']['data_unit'] = $detail['value'];
                }
                if ($detail['name'] === 'PLAN_VALIDITY' && !empty($detail['value'])) {
                    // המרת דקות לימים
                    $package['processed_data']['validity_days'] = round(intval($detail['value']) / 24);
                }
            }
        }
    }
    
    // שמור במטמון וגם החזר
    set_transient($cache_key, $packages, 1 * HOUR_IN_SECONDS); // שמור במטמון לשעה
    
    error_log("Fetched and cached " . count($packages) . " packages from API");
    return $packages;
}







/**
 * פונקציה לסינון חבילות עם מגבלת מהירות
 * 
 * @param array $packages מערך החבילות לסינון
 * @return array מערך חבילות מסונן
 */
function AdPro_filter_speed_restricted_packages($packages) {
    if (empty($packages) || !is_array($packages)) {
        return $packages;
    }
    
    $filtered_packages = array_filter($packages, function($package) {
        // בדיקה אם יש פרטי מוצר
        if (!isset($package['productDetails']) || !is_array($package['productDetails'])) {
            return true;
        }
        
        foreach ($package['productDetails'] as $detail) {
            // חפש את שדה המהירות
            if ($detail['name'] === 'SPEED') {
                $speed_value = strtolower($detail['value']);
                
                // הסתר חבילות עם מגבלת מהירות (Mbps או Limited)
                if (stripos($speed_value, 'mbps') !== false || 
                    $speed_value === 'limited' || 
                    stripos($speed_value, '7') !== false) {
                    return false;
                }
            }
        }
        
        return true;
    });
    
    // המר בחזרה למערך רגיל
    return array_values($filtered_packages);
}

/**
 * קבלת חבילה ספציפית לפי מזהה
 * 
 * @param string $country קוד מדינה
 * @param string $package_id מזהה חבילה
 * @return array|null פרטי החבילה או null אם לא נמצא
 */
function AdPro_get_package_by_id($country, $package_id) {
    global $wpdb;
    
    $table_packages = $wpdb->prefix . 'adpro_esim_packages';
    
    $sql = $wpdb->prepare(
        "SELECT * FROM $table_packages WHERE product_id = %s",
        $package_id
    );
    
    // אם צוינה מדינה, הוסף גם אותה לתנאי
    if (!empty($country)) {
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_packages WHERE product_id = %s AND country_iso = %s",
            $package_id, $country
        );
    }
    
    $row = $wpdb->get_row($sql, ARRAY_A);
    
    if (empty($row)) {
        // אם לא נמצא, נסה לחפש בכל החבילות
        $packages = AdPro_esim_get_packages($country);
        
        foreach ($packages as $package) {
            if (isset($package['productId']) && $package['productId'] === $package_id) {
                return $package;
            }
        }
        
        return null;
    }
    
    // המרה למבנה הישן
    $package = [
        'productId' => $row['product_id'],
        'providerId' => $row['provider_id'],
        'providerName' => $row['provider_name'],
        'title' => $row['title'],
        'retailPrice' => $row['retail_price'],
        'currencyCode' => $row['currency_code'],
        'countries' => json_decode($row['countries'], true) ?: [],
        'productDetails' => json_decode($row['product_details'], true) ?: [],
        // תוספת של שדות מעובדים
        'processed_data' => [
            'data_limit' => $row['data_limit'],
            'data_unit' => $row['data_unit'],
            'validity_days' => $row['validity_days'],
            'title' => $row['title']
        ]
    ];
    
    return $package;
}

/**
 * קבלת המחיר הנמוך ביותר עבור מדינה
 * 
 * @param string $country_iso קוד ISO של המדינה
 * @return array|boolean מערך עם המחיר והמטבע או false אם אין נתונים
 */
function AdPro_get_min_price_for_country($country_iso) {
    global $wpdb;
    
    // בדיקה אם יש במטמון
    $cache_key = 'AdPro_min_price_' . $country_iso;
    $cached_price = get_transient($cache_key);
    
    if ($cached_price !== false) {
        return $cached_price;
    }
    
    $table_packages = $wpdb->prefix . 'adpro_esim_packages';
    
    // קבל את המחיר הנמוך ביותר
    $sql = $wpdb->prepare(
        "SELECT MIN(retail_price) as min_price, currency_code 
         FROM $table_packages 
         WHERE country_iso = %s
         GROUP BY currency_code
         ORDER BY min_price ASC
         LIMIT 1",
        $country_iso
    );
    
    $row = $wpdb->get_row($sql);
    
    if (!$row) {
        return false;
    }
    
    $result = [
        'price' => floatval($row->min_price),
        'currency' => $row->currency_code
    ];
    
    // שמירה במטמון ל-3 שעות
    set_transient($cache_key, $result, 3 * HOUR_IN_SECONDS);
    
    return $result;
}

/**
 * בדיקת תוקף מפתח API של מובימטר
 * 
 * @return boolean האם המפתח תקין
 */
function AdPro_validate_api_key() {
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (empty($api_key) || empty($merchant_id)) {
        return false;
    }
    
    // ניסיון קריאה בסיסית ל-API
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products?limit=1';
    
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key,
        ],
        'timeout' => 10,
    ];
    
    $response = wp_remote_get($api_url, $args);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    return $response_code === 200;
}

/**
 * קבלת רשתות סלולריות נתמכות למוצר מסוים
 * 
 * @param string $product_id מזהה המוצר
 * @return array מערך של רשתות סלולריות
 */
function AdPro_get_product_networks($product_id) {
    global $wpdb;
    
    // בדיקה אם יש במטמון
    $cache_key = 'AdPro_product_networks_' . md5($product_id);
    $cached_networks = get_transient($cache_key);
    
    // אם יש במטמון וזה לא מצב דיבאג, החזר את המטמון
    if ($cached_networks !== false && !isset($_GET['no_cache'])) {
        return $cached_networks;
    }
    
    $table_networks = $wpdb->prefix . 'adpro_esim_networks';
    
    // בדוק אם יש רשתות במסד הנתונים
    $sql = $wpdb->prepare(
        "SELECT * FROM $table_networks WHERE product_id = %s",
        $product_id
    );
    
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if (!empty($results)) {
        // המרה למבנה תואם API
        $networks = [];
        
        foreach ($results as $row) {
            $networks[] = [
                'countryCode' => $row['country_iso'],
                'brand' => $row['network_brand'],
                'networkId' => $row['network_id'],
                'is4G' => (bool)$row['is_4g'],
                'is5G' => (bool)$row['is_5g']
            ];
        }
        
        // שמור במטמון וגם החזר
        set_transient($cache_key, $networks, 12 * HOUR_IN_SECONDS);
        return $networks;
    }
    
    // אם אין רשתות במסד הנתונים, קבל מה-API
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (empty($api_key) || empty($merchant_id) || empty($product_id)) {
        return [];
    }
    
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products/' . $product_id . '/networks';
    
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key,
        ],
        'timeout' => 30,
    ];
    
    $response = wp_remote_get($api_url, $args);
    
    if (is_wp_error($response)) {
        error_log('AdPro eSIM API Error (Networks): ' . $response->get_error_message());
        return [];
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);
    
    if ($response_code !== 200 || !isset($data['statusCode']) || $data['statusCode'] !== 200) {
        error_log('AdPro eSIM API Error (Networks): Invalid response - ' . json_encode($data));
        return [];
    }
    
    // קבל את הרשתות מהתגובה
    $networks = isset($data['result']) ? $data['result'] : [];
    
    // שמור את הרשתות גם במסד הנתונים
    if (!empty($networks)) {
        $table_networks = $wpdb->prefix . 'adpro_esim_networks';
        
        foreach ($networks as $network) {
            $network_data = [
                'product_id' => $product_id,
                'country_iso' => $network['countryCode'],
                'network_brand' => $network['brand'],
                'network_id' => $network['networkId'],
                'is_4g' => isset($network['is4G']) && $network['is4G'] ? 1 : 0,
                'is_5g' => isset($network['is5G']) && $network['is5G'] ? 1 : 0,
                'last_updated' => current_time('mysql')
            ];
            
            $wpdb->insert($table_networks, $network_data);
        }
    }
    
    // שמור במטמון וגם החזר
    set_transient($cache_key, $networks, 12 * HOUR_IN_SECONDS);
    return $networks;
}

/**
 * סינון רשתות סלולריות לפי מדינה
 * 
 * @param array $networks מערך של רשתות סלולריות
 * @param string $country_iso קוד ISO של המדינה
 * @return array מערך מסונן של רשתות סלולריות
 */
function AdPro_filter_networks_by_country($networks, $country_code) {
    if (empty($networks) || empty($country_code)) {
        return [];
    }
    
    $filtered_networks = [];
    foreach ($networks as $network) {
        if (isset($network['countryCode']) && $network['countryCode'] === $country_code) {
            $filtered_networks[] = $network;
        }
    }
    
    return $filtered_networks;
}


/**
 * ניקוי המטמון של API
 */
function AdPro_clear_api_cache() {
    global $wpdb;
    
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_AdPro_esim_packages_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_AdPro_esim_packages_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_AdPro_min_price_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_AdPro_min_price_%'");
    
    return true;
}