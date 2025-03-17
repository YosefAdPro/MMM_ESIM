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
 * קבלת חבילות eSIM לפי מדינה
 * 
 * @param string $country קוד מדינה
 * @return array מערך של חבילות
 */
 
function AdPro_esim_get_packages($country = '') {
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

// סינון חבילות כפולות לאחר העיגול (אם צריך)
$packages = AdPro_filter_duplicate_packages($packages);

// החזרת החבילות המעודכנות
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
    
    error_log("No valid JSON data found, falling back to API/cache");
    
    // הקוד הישן כגיבוי...
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
    // קבלת כל החבילות למדינה
    $packages = AdPro_esim_get_packages($country);
    
    // חיפוש החבילה לפי מזהה
    foreach ($packages as $package) {
        if (isset($package['productId']) && $package['productId'] === $package_id) {
            return $package;
        }
    }
    
    return null;
}

/**
 * קבלת המחיר הנמוך ביותר עבור מדינה
 * 
 * @param string $country_iso קוד ISO של המדינה
 * @return array|boolean מערך עם המחיר והמטבע או false אם אין נתונים
 */
function AdPro_get_min_price_for_country($country_iso) {
    // בדיקה אם יש במטמון
    $cache_key = 'AdPro_min_price_' . $country_iso;
    $cached_price = get_transient($cache_key);
    
    if ($cached_price !== false) {
        return $cached_price;
    }
    
    // קבלת כל החבילות למדינה
    $packages = AdPro_esim_get_packages($country_iso);
    
    if (empty($packages)) {
        return false;
    }
    
    $min_price = PHP_FLOAT_MAX;
    $currency = '';
    
    // חיפוש המחיר הנמוך ביותר
    foreach ($packages as $package) {
        if (isset($package['retailPrice']) && is_numeric($package['retailPrice'])) {
            $price = floatval($package['retailPrice']);
            
            if ($price < $min_price) {
                $min_price = $price;
                $currency = isset($package['currencyCode']) ? $package['currencyCode'] : '';
            }
        }
    }
    
    if ($min_price == PHP_FLOAT_MAX) {
        return false;
    }
    
    $result = [
        'price' => $min_price,
        'currency' => $currency
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
	
	    error_log('Requesting networks for product: ' . $product_id);
// לוג פרטי הבקשה לדיבוג
error_log('API URL: ' . $api_url);
error_log('API Headers: ' . json_encode($args['headers']));
    // בדיקה אם יש במטמון
    $cache_key = 'AdPro_product_networks_' . md5($product_id);
    $cached_networks = get_transient($cache_key);
    
    // אם יש במטמון וזה לא מצב דיבאג, החזר את המטמון
    if ($cached_networks !== false && !isset($_GET['no_cache'])) {
        return $cached_networks;
    }

    // קבל פרטי התחברות מהגדרות התוסף
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');

    // וודא שיש פרטי התחברות
    if (empty($api_key) || empty($merchant_id) || empty($product_id)) {
        return [];
    }

    // בנה את כתובת ה-API
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products/' . $product_id . '/networks';

    // בנה את פרטי הבקשה
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key,
        ],
        'timeout' => 30,
    ];

    // שלח את הבקשה ל-API
    $response = wp_remote_get($api_url, $args);
    
    // בדוק אם הייתה שגיאה
    if (is_wp_error($response)) {
        error_log('AdPro eSIM API Error (Networks): ' . $response->get_error_message());
        return [];
    }

    // קבל את תוכן התגובה ופענח JSON
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // בדוק שהתגובה תקינה
    if (!isset($data['statusCode']) || $data['statusCode'] !== 200) {
        error_log('AdPro eSIM API Error (Networks): Invalid response - ' . json_encode($data));
        return [];
    }

    // קבל את הרשתות מהתגובה
    $networks = isset($data['result']) ? $data['result'] : [];
    
    // שמור במטמון וגם החזר
    set_transient($cache_key, $networks, 12 * HOUR_IN_SECONDS); // שמור במטמון ל-12 שעות
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