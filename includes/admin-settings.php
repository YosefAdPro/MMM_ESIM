<?php
/**
 * הגדרות ניהול התוסף
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}

/**
 * הוספת פריטי תפריט לניהול התוסף
 */
function AdPro_esim_admin_menu() {
    add_menu_page(
        'ניהול eSIM',
        'eSIM',
        'manage_options',
        'AdPro-esim-settings',
        'AdPro_esim_settings_page',
        'dashicons-smartphone',
        30
    );
    
    add_submenu_page(
        'AdPro-esim-settings',
        'הגדרות API',
        'הגדרות API',
        'manage_options',
        'AdPro-esim-settings',
        'AdPro_esim_settings_page'
    );
    
    add_submenu_page(
        'AdPro-esim-settings',
        'עריכת תוכן מדינות',
        'תוכן מדינות',
        'manage_options',
        'AdPro-esim-country-content',
        'AdPro_esim_country_content_page'
    );
    
    add_submenu_page(
        'AdPro-esim-settings',
        'ניהול ספקים',
        'ניהול ספקים',
        'manage_options',
        'AdPro-esim-providers',
        'AdPro_esim_providers_page'
    );
    
    add_submenu_page(
        'AdPro-esim-settings',
        'סטטיסטיקות',
        'סטטיסטיקות',
        'manage_options',
        'AdPro-esim-stats',
        'AdPro_esim_stats_page'
    );
}
add_action('admin_menu', 'AdPro_esim_admin_menu');

/**
 * רישום הגדרות התוסף
 */
function AdPro_esim_settings_init() {
    // קבוצת הגדרות API
    register_setting('AdPro_esim_api_settings', 'AdPro_api_key');
    register_setting('AdPro_esim_api_settings', 'AdPro_merchant_id');
    register_setting('AdPro_esim_api_settings', 'AdPro_icount_company_id');
	register_setting('AdPro_esim_api_settings', 'AdPro_icount_user');
	register_setting('AdPro_esim_api_settings', 'AdPro_icount_pass');
    
    // קבוצת הגדרות מסחר
    register_setting('AdPro_esim_commerce_settings', 'AdPro_hidden_providers', [
        'type' => 'array',
        'sanitize_callback' => function($input) {
            if (!is_array($input)) {
                return [];
            }
            return array_map('sanitize_text_field', $input);
        },
        'default' => []
    ]);
    
    register_setting('AdPro_esim_commerce_settings', 'AdPro_popular_countries', [
        'type' => 'array',
        'sanitize_callback' => function($input) {
            if (!is_array($input)) {
                return [];
            }
            return array_map('sanitize_text_field', $input);
        },
        'default' => []
    ]);
    
    // הוספת סקציות
    add_settings_section(
        'AdPro_esim_api_section',
        'הגדרות API',
        'AdPro_esim_api_section_callback',
        'AdPro-esim-settings'
    );
    
    add_settings_section(
        'AdPro_esim_commerce_section',
        'הגדרות מסחר',
        'AdPro_esim_commerce_section_callback',
        'AdPro-esim-settings'
    );
    
    // הוספת שדות
    add_settings_field(
        'AdPro_api_key_field',
        'מפתח API של מובימטר',
        'AdPro_api_key_field_callback',
        'AdPro-esim-settings',
        'AdPro_esim_api_section'
    );
    
    add_settings_field(
        'AdPro_merchant_id_field',
        'מזהה סוחר של מובימטר',
        'AdPro_merchant_id_field_callback',
        'AdPro-esim-settings',
        'AdPro_esim_api_section'
    );
    
add_settings_field(
    'AdPro_icount_user_field',
    'שם משתמש iCount',
    'AdPro_icount_user_field_callback',
    'AdPro-esim-settings',
    'AdPro_esim_api_section'
);

add_settings_field(
    'AdPro_icount_pass_field',
    'סיסמת iCount',
    'AdPro_icount_pass_field_callback',
    'AdPro-esim-settings',
    'AdPro_esim_api_section'
);
    
    add_settings_field(
        'AdPro_icount_company_id_field',
        'מזהה חברה של iCount',
        'AdPro_icount_company_id_field_callback',
        'AdPro-esim-settings',
        'AdPro_esim_api_section'
    );
}
add_action('admin_init', 'AdPro_esim_settings_init');

/**
 * פונקציות callbacks לסקציות
 */
function AdPro_esim_api_section_callback() {
    echo '<p>הזן את פרטי ההתחברות ל-API של מובימטר ו-iCount.</p>';
    
    // בדיקת מפתח API
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (!empty($api_key) && !empty($merchant_id)) {
        if (AdPro_validate_api_key()) {
            echo '<div class="notice notice-success inline"><p>חיבור ל-API של מובימטר פעיל ותקין.</p></div>';
        } else {
            echo '<div class="notice notice-error inline"><p>שגיאה בחיבור ל-API של מובימטר. בדוק את פרטי ההתחברות.</p></div>';
        }
    }
}

function AdPro_esim_commerce_section_callback() {
    echo '<p>הגדרות נוספות עבור המסחר באתר.</p>';
}

/**
 * פונקציות callbacks לשדות
 */
function AdPro_api_key_field_callback() {
    $api_key = get_option('AdPro_api_key');
    echo "<input type='text' name='AdPro_api_key' value='" . esc_attr($api_key) . "' class='regular-text'>";
    echo "<p class='description'>המפתח מסופק על ידי מובימטר.</p>";
}

function AdPro_merchant_id_field_callback() {
    $merchant_id = get_option('AdPro_merchant_id');
    echo "<input type='text' name='AdPro_merchant_id' value='" . esc_attr($merchant_id) . "' class='regular-text'>";
    echo "<p class='description'>מזהה הסוחר מסופק על ידי מובימטר.</p>";
}

function AdPro_icount_user_field_callback() {
    $user = get_option('AdPro_icount_user');
    echo "<input type='text' name='AdPro_icount_user' value='" . esc_attr($user) . "' class='regular-text'>";
    echo "<p class='description'>שם המשתמש של חשבון iCount.</p>";
}

function AdPro_icount_pass_field_callback() {
    $pass = get_option('AdPro_icount_pass');
    echo "<input type='password' name='AdPro_icount_pass' value='" . esc_attr($pass) . "' class='regular-text'>";
    echo "<p class='description'>הסיסמה של חשבון iCount.</p>";
}

function AdPro_icount_company_id_field_callback() {
    $company_id = get_option('AdPro_icount_company_id');
    echo "<input type='text' name='AdPro_icount_company_id' value='" . esc_attr($company_id) . "' class='regular-text'>";
    echo "<p class='description'>מזהה החברה מסופק על ידי iCount.</p>";
}

/**
 * דף הגדרות ראשי
 */
function AdPro_esim_settings_page() {
    // ניקוי מטמון אם נדרש
    if (isset($_GET['clear_cache']) && check_admin_referer('AdPro_clear_cache')) {
        AdPro_clear_api_cache();
        echo '<div class="notice notice-success"><p>המטמון נוקה בהצלחה!</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('AdPro_esim_api_settings');
            do_settings_sections('AdPro-esim-settings');
            submit_button('שמור הגדרות');
            ?>
        </form>
        
        <hr>
        
        <h2>פעולות נוספות</h2>
        <p>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=AdPro-esim-settings&clear_cache=1'), 'AdPro_clear_cache'); ?>" class="button">
                נקה מטמון API
            </a>
            <span class="description">מנקה את המטמון של תוצאות API ומאלץ טעינה מחדש של נתונים.</span>
        </p>
        
        <hr>
        
        <h2>בדיקת חיבור</h2>
        <p>
            <a href="#" id="test-mobimatter-api" class="button">בדוק חיבור ל-API של מובימטר</a>
            <span id="api-test-result"></span>
        </p>
        
        <h2>בדיקת חיבור iCount</h2>
        <p>
            <a href="#" id="test-icount-api" class="button">בדוק חיבור ל-API של iCount</a>
            <span id="icount-test-result"></span>
        </p>
        
<script>
    jQuery(document).ready(function($) {
        // סקריפט לבדיקת חיבור למובימטר - נשאר כפי שהיה
        $('#test-mobimatter-api').on('click', function(e) {
            e.preventDefault();
            var $result = $('#api-test-result');
            
            $result.html('<span style="color: #aaa;">בודק חיבור...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'AdPro_test_mobimatter_api'
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color: green;">✓ החיבור תקין!</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ שגיאה: ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span style="color: red;">✗ שגיאה בבדיקה</span>');
                }
            });
        });
        
        // סקריפט משופר לבדיקת חיבור ל-iCount
        $('#test-icount-api').on('click', function(e) {
            e.preventDefault();
            var $result = $('#icount-test-result');
            var $debugInfo = $('#icount-debug-info');
            
            $result.html('<span style="color: #aaa;">בודק חיבור...</span>');
            
            // מחיקת מידע דיבאג קודם אם קיים
            if ($debugInfo.length) {
                $debugInfo.remove();
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'AdPro_test_icount_api'
                },
                success: function(response) {
                    // בדיקה אם התשובה מכילה אובייקט או מחרוזת
                    var message = '';
                    var debugData = null;
                    
                    if (typeof response.data === 'object' && response.data !== null) {
                        message = response.data.message || '';
                        debugData = response.data.debug;
                    } else {
                        message = response.data || '';
                    }
                    
                    if (response.success) {
                        $result.html('<span style="color: green;">✓ ' + message + '</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ שגיאה: ' + message + '</span>');
                    }
                    
                    // הצגת מידע דיבאג אם קיים
                    if (debugData) {
                        var $debugDiv = $('<div id="icount-debug-info" style="margin-top: 15px; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow: auto;"></div>');
                        
                        var debugHtml = '<h4 style="margin-top: 0;">פרטי דיבאג:</h4>';
                        
                        // בקשה
                        if (debugData.request) {
                            debugHtml += '<h5>בקשה:</h5>';
                            debugHtml += '<pre style="background-color: #fff; padding: 10px; border-radius: 4px; overflow: auto; max-height: 150px;">' + 
                                        JSON.stringify(debugData.request, null, 2) + 
                                        '</pre>';
                        }
                        
                        // פרטי cURL
                        if (debugData.curl_info) {
                            debugHtml += '<h5>פרטי cURL:</h5>';
                            debugHtml += '<pre style="background-color: #fff; padding: 10px; border-radius: 4px; overflow: auto; max-height: 150px;">' + 
                                        JSON.stringify(debugData.curl_info, null, 2) + 
                                        '</pre>';
                        }
                        
                        // תגובה
                        if (debugData.response) {
                            debugHtml += '<h5>תגובה:</h5>';
                            debugHtml += '<pre style="background-color: #fff; padding: 10px; border-radius: 4px; overflow: auto; max-height: 150px;">' + 
                                        JSON.stringify(debugData.response, null, 2) + 
                                        '</pre>';
                            
                            // אם יש גוף תגובה לא מפוענח, מציג אותו כטקסט גולמי
                            if (debugData.response.body_raw) {
                                debugHtml += '<h5>תגובה גולמית:</h5>';
                                debugHtml += '<pre style="background-color: #fff; padding: 10px; border-radius: 4px; overflow: auto; max-height: 150px;">' + 
                                            debugData.response.body_raw + 
                                            '</pre>';
                            }
                        }
                        
                        $debugDiv.html(debugHtml);
                        $result.after($debugDiv);
                    }
                },
                error: function(xhr, status, error) {
                    $result.html('<span style="color: red;">✗ שגיאה בבדיקה: ' + error + '</span>');
                    
                    // הצגת פרטי השגיאה
                    var $debugDiv = $('<div id="icount-debug-info" style="margin-top: 15px; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;"></div>');
                    $debugDiv.html('<h4 style="margin-top: 0;">פרטי שגיאה:</h4><pre>' + JSON.stringify({xhr: xhr.status, status: status, error: error}, null, 2) + '</pre>');
                    $result.after($debugDiv);
                }
            });
        });
    });
</script>
    </div>
    <?php
}

/**
 * AJAX לבדיקת חיבור לשרת API
 */
function AdPro_test_mobimatter_api_callback() {
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (empty($api_key) || empty($merchant_id)) {
        wp_send_json_error('חסרים פרטי התחברות. יש להזין מפתח API ומזהה סוחר.');
        return;
    }
    
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
        wp_send_json_error('שגיאת חיבור: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $error_message = isset($data['message']) ? $data['message'] : 'קוד שגיאה: ' . $response_code;
        
        wp_send_json_error($error_message);
        return;
    }
    
    wp_send_json_success('החיבור בוצע בהצלחה');
}
add_action('wp_ajax_AdPro_test_mobimatter_api', 'AdPro_test_mobimatter_api_callback');

/**
 * AJAX לבדיקת חיבור לשרת iCount
 */
/**
 * AJAX לבדיקת חיבור לשרת iCount
 */
/**
 * AJAX לבדיקת חיבור לשרת iCount
 */
function AdPro_test_icount_api_callback() {
    // קבלת פרטי ההתחברות מההגדרות
    $company_id = get_option('AdPro_icount_company_id');
    $user = get_option('AdPro_icount_user');
    $pass = get_option('AdPro_icount_pass');
    
    if (empty($company_id) || empty($user) || empty($pass)) {
        wp_send_json_error('חסרים פרטי התחברות. יש להזין מזהה חברה, שם משתמש וסיסמה.');
        return;
    }
    
    // בניית נתוני הבקשה לפי הפורמט הנכון
    $api_url = 'https://api.icount.co.il/api/v3.php/auth/login';
    
    // שימוש ב-cURL במקום wp_remote_post
    $curl = curl_init();
    
    // בניית המידע לדיבאג (מסתיר את הסיסמה)
    $debug_info = [
        'request' => [
            'url' => $api_url,
            'method' => 'POST',
            'body' => [
                'cid' => $company_id,
                'user' => $user,
                'pass' => '***חסוי***',
                'otp' => ''  // חלק מפרוטוקול האימות אם צריך
            ]
        ],
        'response' => null
    ];
    
    // הגדרת אפשרויות cURL
    curl_setopt_array($curl, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'cid' => $company_id,
            'user' => $user,
            'pass' => $pass,
            'otp' => ''  // התיעוד מציין פרמטר זה, אבל ניתן לו ערך ריק
        ]
    ]);
    
    // ביצוע הבקשה
    $response_body = curl_exec($curl);
    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    // שמירת הנתונים לדיבאג
    $debug_info['curl_info'] = [
        'error' => $error,
        'response_code' => $response_code
    ];
    
    // בדיקה אם הייתה שגיאה ב-cURL
    if ($error) {
        curl_close($curl);
        wp_send_json_error([
            'message' => 'שגיאת חיבור cURL: ' . $error,
            'debug' => $debug_info
        ]);
        return;
    }
    
    // ניסיון לפרש את התגובה כ-JSON
    $data = json_decode($response_body, true);
    
    // שמירת נתוני התגובה לדיבאג
    $debug_info['response'] = [
        'code' => $response_code,
        'body_raw' => $response_body,
        'body_parsed' => $data
    ];
    
    // סגירת החיבור
    curl_close($curl);
    
    // נוסיף לוג לדיבאג
    error_log('iCount API request: ' . json_encode($debug_info['request']));
    error_log('iCount API response: ' . json_encode($debug_info['response']));
    
    // בדיקה אם התגובה תקינה
    if ($response_code !== 200) {
        $error_message = 'קוד שגיאה: ' . $response_code;
        wp_send_json_error([
            'message' => $error_message,
            'debug' => $debug_info
        ]);
        return;
    }
    
    // בדיקת תגובה מוצלחת לפי שדה status ו-sid
    if (isset($data['status']) && $data['status'] === true && isset($data['sid'])) {
        // התחברות הצליחה
        wp_send_json_success([
            'message' => 'החיבור בוצע בהצלחה',
            'debug' => $debug_info
        ]);
        return;
    } else {
        // התחברות נכשלה, בדיקה אם יש הודעת שגיאה
        $error_message = isset($data['reason']) ? $data['reason'] : 'שגיאה לא ידועה בהתחברות';
        wp_send_json_error([
            'message' => $error_message,
            'debug' => $debug_info
        ]);
        return;
    }
}
add_action('wp_ajax_AdPro_test_icount_api', 'AdPro_test_icount_api_callback');

/**
 * דף ניהול תוכן מדינות
 */
function AdPro_esim_country_content_page() {
    $countries_mapping = AdPro_get_countries_mapping();
    $saved_content = get_option('AdPro_esim_country_content', []);

    if (isset($_POST['save_country_content']) && check_admin_referer('AdPro_esim_country_content_nonce')) {
        $updated_content = [];
        foreach ($_POST['country_content'] as $country => $content) {
            $updated_content[$country] = [
                'text' => wp_kses_post($content['text']),
                'image' => esc_url_raw($content['image'])
            ];
        }
        update_option('AdPro_esim_country_content', $updated_content);
        echo '<div class="updated"><p>השינויים נשמרו בהצלחה!</p></div>';
        $saved_content = $updated_content;
    }
    ?>
    <div class="wrap">
        <h1>עריכת תוכן מדינות</h1>
        <p>כאן תוכל להוסיף תוכן מותאם אישית לדפי המדינות השונים.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('AdPro_esim_country_content_nonce'); ?>
            
            <?php if (empty($countries_mapping)) : ?>
                <div class="notice notice-warning">
                    <p>לא נמצאו מדינות. וודא שיש חיבור תקין ל-API.</p>
                </div>
            <?php else : ?>
                <div class="country-content-tabs">
                    <div class="country-tabs-navigation">
                        <?php 
                        $first = true;
                        foreach ($countries_mapping as $hebrew => $data) : 
                        ?>
                            <a href="#country-tab-<?php echo sanitize_title($hebrew); ?>" class="country-tab <?php echo $first ? 'active' : ''; ?>">
                                <img src="https://flagcdn.com/16x12/<?php echo strtolower($data['iso']); ?>.png" alt="<?php echo esc_attr($hebrew); ?>">
                                <?php echo esc_html($hebrew); ?>
                            </a>
                        <?php 
                            $first = false;
                        endforeach; 
                        ?>
                    </div>
                    
                    <div class="country-tabs-content">
                        <?php 
                        $first = true;
                        foreach ($countries_mapping as $hebrew => $data) : 
                            $country_key = sanitize_title($hebrew);
                        ?>
                            <div id="country-tab-<?php echo $country_key; ?>" class="country-tab-content <?php echo $first ? 'active' : ''; ?>">
                                <h2><?php echo esc_html($hebrew); ?></h2>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">תמונה (כתובת URL):</th>
                                        <td>
                                            <input type="url" name="country_content[<?php echo esc_attr($hebrew); ?>][image]" value="<?php echo esc_attr($saved_content[$hebrew]['image'] ?? ''); ?>" class="regular-text">
                                            <button type="button" class="button media-upload-button" data-target="country_content[<?php echo esc_attr($hebrew); ?>][image]">בחר תמונה</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">תוכן מותאם:</th>
                                        <td>
                                            <?php
                                            wp_editor(
                                                $saved_content[$hebrew]['text'] ?? '',
                                                'country_content_' . $country_key,
                                                [
                                                    'textarea_name' => 'country_content[' . esc_attr($hebrew) . '][text]',
                                                    'textarea_rows' => 10,
                                                    'media_buttons' => true,
                                                ]
                                            );
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php 
                            $first = false;
                        endforeach; 
                        ?>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="hidden" name="save_country_content" value="1">
                    <?php submit_button('שמור שינויים', 'primary', 'submit', false); ?>
                </p>
            <?php endif; ?>
        </form>
        
        <style>
            .country-content-tabs {
                margin-top: 20px;
                border: 1px solid #ccc;
                background: #fff;
            }
            
            .country-tabs-navigation {
                display: flex;
                flex-wrap: wrap;
                gap: 2px;
                background: #f5f5f5;
                padding: 10px 10px 0;
                border-bottom: 1px solid #ccc;
            }
            
            .country-tab {
                display: flex;
                align-items: center;
                gap: 5px;
                padding: 8px 12px;
                background: #e5e5e5;
                text-decoration: none;
                color: #333;
                border-radius: 4px 4px 0 0;
                border: 1px solid #ccc;
                border-bottom: none;
                font-size: 13px;
            }
            
            .country-tab.active {
                background: #fff;
                position: relative;
                bottom: -1px;
                border-bottom: 1px solid #fff;
            }
            
            .country-tab:hover {
                background: #f0f0f0;
            }
            
            .country-tab.active:hover {
                background: #fff;
            }
            
            .country-tab-content {
                display: none;
                padding: 20px;
            }
            
            .country-tab-content.active {
                display: block;
            }
            
            .media-upload-button {
                margin-left: 10px !important;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                // טאבים של מדינות
                $('.country-tab').on('click', function(e) {
                    e.preventDefault();
                    var tabId = $(this).attr('href');
                    
                    $('.country-tab').removeClass('active');
                    $('.country-tab-content').removeClass('active');
                    
                    $(this).addClass('active');
                    $(tabId).addClass('active');
                });
                
                // העלאת תמונות
                $('.media-upload-button').on('click', function(e) {
                    e.preventDefault();
                    
                    var targetInput = $(this).data('target');
                    var $input = $('input[name="' + targetInput + '"]');
                    
                    var mediaUploader = wp.media({
                        title: 'בחר תמונה',
                        button: {
                            text: 'שימוש בתמונה זו'
                        },
                        multiple: false
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $input.val(attachment.url);
                    });
                    
                    mediaUploader.open();
                });
            });
        </script>
    </div>
    <?php
}

/**
 * דף ניהול ספקים
 */
function AdPro_esim_providers_page() {
    // קבלת רשימת ספקים על ידי פניה ל-API ושליפת נתונים ייחודיים
    $providers = [];
    $hidden_providers = get_option('AdPro_hidden_providers', []);
    
    // שמירת שינויים
    if (isset($_POST['save_providers']) && check_admin_referer('AdPro_providers_nonce')) {
        $hidden_providers = isset($_POST['hidden_providers']) ? $_POST['hidden_providers'] : [];
        update_option('AdPro_hidden_providers', $hidden_providers);
        echo '<div class="notice notice-success"><p>הגדרות הספקים נשמרו בהצלחה!</p></div>';
    }
    
    // קבלת נתוני ספקים מהשרת
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (!empty($api_key) && !empty($merchant_id)) {
        $packages = AdPro_esim_get_packages();
        
        // חילוץ ספקים ייחודיים
        foreach ($packages as $package) {
            if (isset($package['providerId']) && isset($package['providerName'])) {
                $providers[$package['providerId']] = [
                    'id' => $package['providerId'],
                    'name' => $package['providerName'],
                    'hidden' => in_array($package['providerId'], $hidden_providers)
                ];
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>ניהול ספקים</h1>
        <p>הסתר או הצג ספקים שונים באתר.</p>
        
        <?php if (empty($api_key) || empty($merchant_id)) : ?>
            <div class="notice notice-warning">
                <p>חסרים פרטי התחברות ל-API. <a href="<?php echo admin_url('admin.php?page=AdPro-esim-settings'); ?>">הגדר את פרטי ה-API</a> כדי לראות רשימת ספקים.</p>
            </div>
        <?php elseif (empty($providers)) : ?>
            <div class="notice notice-warning">
                <p>לא נמצאו ספקים. וודא שיש חיבור תקין ל-API.</p>
            </div>
        <?php else : ?>
            <form method="post" action="">
                <?php wp_nonce_field('AdPro_providers_nonce'); ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>הצג/הסתר</th>
                            <th>שם הספק</th>
                            <th>מזהה</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($providers as $provider) : ?>
                            <tr>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="hidden_providers[]" value="<?php echo esc_attr($provider['id']); ?>" <?php checked($provider['hidden']); ?>>
                                        <span class="slider round"></span>
                                        <span class="switch-label"><?php echo $provider['hidden'] ? 'מוסתר' : 'מוצג'; ?></span>
                                    </label>
                                </td>
                                <td><?php echo esc_html($provider['name']); ?></td>
                                <td><?php echo esc_html($provider['id']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="hidden" name="save_providers" value="1">
                    <?php submit_button('שמור הגדרות', 'primary', 'submit', false); ?>
                </p>
            </form>
            
            <style>
                /* מתג הצגה/הסתרה */
                .switch {
                    position: relative;
                    display: inline-block;
                    width: 50px;
                    height: 24px;
                }
                
                .switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                
                .slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                }
                
                .slider:before {
                    position: absolute;
                    content: "";
                    height: 16px;
                    width: 16px;
                    left: 4px;
                    bottom: 4px;
                    background-color: white;
                    transition: .4s;
                }
                
                input:checked + .slider {
                    background-color: #f44336;
                }
                
                input:focus + .slider {
                    box-shadow: 0 0 1px #f44336;
                }
                
                input:checked + .slider:before {
                    transform: translateX(26px);
                }
                
                .slider.round {
                    border-radius: 24px;
                }
                
                .slider.round:before {
                    border-radius: 50%;
                }
                
                .switch-label {
                    display: inline-block;
                    margin-left: 60px;
                    font-size: 14px;
                }
                
                input:checked + .slider + .switch-label {
                    color: #f44336;
                }
            </style>
            
            <script>
                jQuery(document).ready(function($) {
                    // עדכון תווית המתג
                    $('input[type="checkbox"]').on('change', function() {
                        var $label = $(this).siblings('.switch-label');
                        
                        if ($(this).is(':checked')) {
                            $label.text('מוסתר');
                        } else {
                            $label.text('מוצג');
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * דף סטטיסטיקות ונתונים
 */
function AdPro_esim_stats_page() {
    // חישוב סטטיסטיקות מכירה ונתונים על חבילות eSIM
    global $wpdb;
    
    // קבלת נתוני הזמנות
    $orders_query = "
        SELECT 
            p.ID as order_id,
            p.post_date as order_date,
            oim.meta_value as esim_country,
            om.meta_value as order_total
        FROM 
            {$wpdb->posts} p
        JOIN 
            {$wpdb->postmeta} om ON p.ID = om.post_id
        JOIN 
            {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        JOIN 
            {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE 
            p.post_type = 'shop_order'
            AND p.post_status = 'wc-completed'
            AND om.meta_key = '_order_total'
            AND oim.meta_key = 'esim_country'
        ORDER BY
            p.post_date DESC
    ";
    
    $orders = $wpdb->get_results($orders_query);
    
    // ארגון נתונים לפי מדינות
    $countries_stats = [];
    $total_revenue = 0;
    $total_orders = 0;
    
    foreach ($orders as $order) {
        $country = $order->esim_country;
        $revenue = floatval($order->order_total);
        $total_revenue += $revenue;
        $total_orders++;
        
        if (!isset($countries_stats[$country])) {
            $countries_stats[$country] = [
                'orders' => 0,
                'revenue' => 0
            ];
        }
        
        $countries_stats[$country]['orders']++;
        $countries_stats[$country]['revenue'] += $revenue;
    }
    
    // מיון לפי הכנסות (מהגבוה לנמוך)
    uasort($countries_stats, function($a, $b) {
        return $b['revenue'] <=> $a['revenue'];
    });
    
    ?>
    <div class="wrap">
        <h1>סטטיסטיקות eSIM</h1>
        
        <div class="stats-container">
            <div class="stats-summary">
                <div class="stat-card">
                    <h3>סה"כ הזמנות</h3>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>סה"כ הכנסות</h3>
                    <div class="stat-value">₪<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>הכנסה ממוצעת להזמנה</h3>
                    <div class="stat-value">₪<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?></div>
                </div>
            </div>
            
            <?php if (!empty($countries_stats)) : ?>
                <div class="stats-details">
                    <h2>סטטיסטיקות לפי מדינה</h2>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>מדינה</th>
                                <th>הזמנות</th>
                                <th>הכנסות</th>
                                <th>הכנסה ממוצעת</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($countries_stats as $country => $stats) : ?>
                                <tr>
                                    <td><?php echo esc_html($country); ?></td>
                                    <td><?php echo $stats['orders']; ?></td>
                                    <td>₪<?php echo number_format($stats['revenue'], 2); ?></td>
                                    <td>₪<?php echo number_format($stats['revenue'] / $stats['orders'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="notice notice-info">
                    <p>אין עדיין נתוני מכירות.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .stats-container {
            margin-top: 20px;
        }
        
        .stats-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background: white;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-weight: normal;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .stats-details {
            background: white;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        
        .stats-details h2 {
            margin-top: 0;
        }
        
        @media (max-width: 768px) {
            .stats-summary {
                flex-direction: column;
            }
        }
    </style>
    <?php
}