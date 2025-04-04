<?php
/**
 * הגדרות ניהול התוסף
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}

/**
 * הוספת כפתור עדכון ידני של חבילות בממשק הניהול
 */
function AdPro_add_manual_update_button() {
    // תוסף להגדרות הקיימות
    add_action('admin_footer', function() {
        // בדוק שאנחנו בדף הנכון
        if (!isset($_GET['page']) || $_GET['page'] !== 'AdPro-esim-settings') {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // הוסף כפתור לסעיף "פעולות נוספות"
            $('h2:contains("פעולות נוספות")').next('p').after(`
                <p>
                    <a href="#" id="update-esim-packages" class="button">
                        עדכן חבילות eSIM מה-API
                    </a>
                    <span id="update-result"></span>
                    <span class="description">מעדכן את כל החבילות הזמינות ממקור ה-API והספקים.</span>
                </p>
            `);
            
            // טיפול בלחיצה על הכפתור
            $('#update-esim-packages').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('האם אתה בטוח שברצונך לעדכן את כל חבילות ה-eSIM? זה עשוי לקחת זמן מה.')) {
                    return;
                }
                
                var $button = $(this);
                var $result = $('#update-result');
                
                $button.prop('disabled', true).text('מעדכן...');
                $result.html('<span style="color: #aaa;">מעדכן חבילות, אנא המתן...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'AdPro_manual_update_packages'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<span style="color: green;">✓ החבילות עודכנו בהצלחה!</span>');
                        } else {
                            $result.html('<span style="color: red;">✗ שגיאה: ' + response.data + '</span>');
                        }
                        $button.prop('disabled', false).text('עדכן חבילות eSIM מה-API');
                    },
                    error: function() {
                        $result.html('<span style="color: red;">✗ שגיאה בשליחת הבקשה</span>');
                        $button.prop('disabled', false).text('עדכן חבילות eSIM מה-API');
                    }
                });
            });
        });
        </script>
        <?php
    });
    
    // הוספת אג'קס לעדכון ידני
    add_action('wp_ajax_AdPro_manual_update_packages', function() {
        // בדיקת הרשאות
        if (!current_user_can('manage_options')) {
            wp_send_json_error('אין לך הרשאות לבצע פעולה זו');
            return;
        }
        
        // ניסיון לעדכן חבילות
        $result = AdPro_manual_update_packages();
        
        if ($result) {
            wp_send_json_success('החבילות עודכנו בהצלחה');
        } else {
            wp_send_json_error('אירעה שגיאה בעדכון החבילות. בדוק בלוג השגיאות לפרטים נוספים.');
        }
    });
}


// הפעלת הפונקציה
add_action('admin_init', 'AdPro_add_manual_update_button');

/**
 * הוספת דף סטטיסטיקות מדינות וחבילות
 */
function AdPro_add_database_stats_page() {
    add_submenu_page(
        'AdPro-esim-settings',
        'סטטיסטיקות מסד נתונים',
        'סטטיסטיקות DB',
        'manage_options',
        'AdPro-esim-db-stats',
        'AdPro_display_database_stats'
    );
}
add_action('admin_menu', 'AdPro_add_database_stats_page', 35);

/**
 * הצגת סטטיסטיקות מסד הנתונים
 */
function AdPro_display_database_stats() {
    global $wpdb;
    
    $table_packages = $wpdb->prefix . 'adpro_esim_packages';
    $table_countries = $wpdb->prefix . 'adpro_esim_countries';
    $table_networks = $wpdb->prefix . 'adpro_esim_networks';
    
    // בדיקה אם הטבלאות קיימות
    $packages_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_packages'") == $table_packages;
    $countries_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_countries'") == $table_countries;
    $networks_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_networks'") == $table_networks;
    
    // קבלת סטטיסטיקות
    $packages_count = $packages_table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_packages") : 0;
    $countries_count = $countries_table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_countries") : 0;
    $active_countries_count = $countries_table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_countries WHERE has_packages = 1") : 0;
    $networks_count = $networks_table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_networks") : 0;
    
    // סטטיסטיקות נוספות
    $providers_count = $packages_table_exists ? $wpdb->get_var("SELECT COUNT(DISTINCT provider_id) FROM $table_packages") : 0;
    $cheapest_package = $packages_table_exists ? $wpdb->get_row("SELECT * FROM $table_packages ORDER BY retail_price ASC LIMIT 1") : null;
    $most_expensive_package = $packages_table_exists ? $wpdb->get_row("SELECT * FROM $table_packages ORDER BY retail_price DESC LIMIT 1") : null;
    
    // מדינות הכי פופולריות (עם הכי הרבה חבילות)
    $popular_countries = $packages_table_exists ? $wpdb->get_results("
        SELECT country_iso, COUNT(*) as packages_count 
        FROM $table_packages 
        GROUP BY country_iso 
        ORDER BY packages_count DESC 
        LIMIT 10
    ") : [];
    
    // ספקים הכי פופולריים
    $popular_providers = $packages_table_exists ? $wpdb->get_results("
        SELECT provider_id, provider_name, COUNT(*) as packages_count 
        FROM $table_packages 
        GROUP BY provider_id 
        ORDER BY packages_count DESC 
        LIMIT 10
    ") : [];
    
    // בדיקת זמן העדכון האחרון
    $last_update = get_option('adpro_packages_last_update', 0);
    $last_update_formatted = $last_update > 0 ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_update) : 'לא ידוע';
    
    ?>
    <div class="wrap">
        <h1>סטטיסטיקות מסד נתונים eSIM</h1>
        
        <?php if (!$packages_table_exists || !$countries_table_exists || !$networks_table_exists) : ?>
            <div class="notice notice-error">
                <p>אחת או יותר מטבלאות מסד הנתונים אינה קיימת. אנא בצע עדכון של התוסף או הפעל מחדש כדי ליצור את הטבלאות.</p>
            </div>
        <?php endif; ?>
        
        <div class="stats-summary" style="display: flex; gap: 20px; margin: 20px 0;">
            <div class="stat-card" style="flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
                <h3>סה"כ חבילות</h3>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo number_format($packages_count); ?></div>
            </div>
            
            <div class="stat-card" style="flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
                <h3>מדינות פעילות</h3>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo number_format($active_countries_count); ?> / <?php echo number_format($countries_count); ?></div>
            </div>
            
            <div class="stat-card" style="flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
                <h3>רשתות סלולריות</h3>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo number_format($networks_count); ?></div>
            </div>
            
            <div class="stat-card" style="flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
                <h3>ספקים</h3>
                <div class="stat-value" style="font-size: 24px; font-weight: bold;"><?php echo number_format($providers_count); ?></div>
            </div>
        </div>
        
        <div class="notice notice-info" style="margin-top: 20px;">
            <p><strong>עדכון אחרון:</strong> <?php echo esc_html($last_update_formatted); ?></p>
        </div>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
            <div style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h2>מדינות פופולריות</h2>
                
                <?php if (!empty($popular_countries)) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>מדינה</th>
                                <th>מספר חבילות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_countries as $country) : 
                                // חיפוש שם המדינה לפי ISO
                                $country_name = $country->country_iso;
                                $countries_mapping = AdPro_get_countries_mapping();
                                foreach ($countries_mapping as $hebrew => $data) {
                                    if ($data['iso'] === $country->country_iso) {
                                        $country_name = $hebrew;
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($country->country_iso)) : ?>
                                            <img src="https://flagcdn.com/16x12/<?php echo strtolower($country->country_iso); ?>.png" alt="<?php echo esc_attr($country->country_iso); ?>" style="vertical-align: middle; margin-left: 5px;">
                                        <?php endif; ?>
                                        <?php echo esc_html($country_name); ?>
                                    </td>
                                    <td><?php echo number_format($country->packages_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>אין נתונים זמינים.</p>
                <?php endif; ?>
            </div>
            
            <div style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h2>ספקים פופולריים</h2>
                
                <?php if (!empty($popular_providers)) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ספק</th>
                                <th>מספר חבילות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_providers as $provider) : ?>
                                <tr>
                                    <td><?php echo esc_html($provider->provider_name); ?></td>
                                    <td><?php echo number_format($provider->packages_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>אין נתונים זמינים.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($cheapest_package && $most_expensive_package) : ?>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <div style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h2>החבילה הזולה ביותר</h2>
                    <p>
                        <strong>מדינה:</strong> 
                        <?php 
                        $country_name = $cheapest_package->country_iso;
                        foreach ($countries_mapping as $hebrew => $data) {
                            if ($data['iso'] === $cheapest_package->country_iso) {
                                $country_name = $hebrew;
                                break;
                            }
                        }
                        echo esc_html($country_name);
                        ?>
                    </p>
                    <p><strong>ספק:</strong> <?php echo esc_html($cheapest_package->provider_name); ?></p>
                    <p><strong>כותרת:</strong> <?php echo esc_html($cheapest_package->title); ?></p>
                    <p><strong>נתונים:</strong> <?php echo esc_html($cheapest_package->data_limit . ' ' . $cheapest_package->data_unit); ?></p>
                    <p><strong>תוקף:</strong> <?php echo esc_html($cheapest_package->validity_days . ' ימים'); ?></p>
                    <p><strong>מחיר:</strong> <?php echo esc_html($cheapest_package->retail_price . ' ' . $cheapest_package->currency_code); ?></p>
                </div>
                
                <div style="flex: 1; min-width: 300px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h2>החבילה היקרה ביותר</h2>
                    <p>
                        <strong>מדינה:</strong> 
                        <?php 
                        $country_name = $most_expensive_package->country_iso;
                        foreach ($countries_mapping as $hebrew => $data) {
                            if ($data['iso'] === $most_expensive_package->country_iso) {
                                $country_name = $hebrew;
                                break;
                            }
                        }
                        echo esc_html($country_name);
                        ?>
                    </p>
                    <p><strong>ספק:</strong> <?php echo esc_html($most_expensive_package->provider_name); ?></p>
                    <p><strong>כותרת:</strong> <?php echo esc_html($most_expensive_package->title); ?></p>
                    <p><strong>נתונים:</strong> <?php echo esc_html($most_expensive_package->data_limit . ' ' . $most_expensive_package->data_unit); ?></p>
                    <p><strong>תוקף:</strong> <?php echo esc_html($most_expensive_package->validity_days . ' ימים'); ?></p>
                    <p><strong>מחיר:</strong> <?php echo esc_html($most_expensive_package->retail_price . ' ' . $most_expensive_package->currency_code); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <h2>פעולות תחזוקה</h2>
            
            <p>
                <a href="#" id="update-packages-manually" class="button button-primary">עדכן חבילות מה-API</a>
                <span id="update-status"></span>
            </p>
            
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#update-packages-manually').on('click', function(e) {
                        e.preventDefault();
                        
                        if (!confirm('האם אתה בטוח שברצונך לעדכן את כל החבילות? פעולה זו תמשך זמן מה.')) {
                            return;
                        }
                        
                        var $button = $(this);
                        var $status = $('#update-status');
                        
                        $button.prop('disabled', true).text('מעדכן...');
                        $status.html('<span style="color: blue;">מעדכן חבילות, אנא המתן...</span>');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'AdPro_manual_update_packages'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $status.html('<span style="color: green;">✓ העדכון הסתיים בהצלחה!</span>');
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 2000);
                                } else {
                                    $status.html('<span style="color: red;">✗ שגיאה: ' + response.data + '</span>');
                                    $button.prop('disabled', false).text('עדכן חבילות מה-API');
                                }
                            },
                            error: function() {
                                $status.html('<span style="color: red;">✗ שגיאה בתקשורת עם השרת</span>');
                                $button.prop('disabled', false).text('עדכן חבילות מה-API');
                            }
                        });
                    });
                });
            </script>
        </div>
    </div>
    <?php
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
    
    // הגדרות iCount
    register_setting('AdPro_esim_api_settings', 'AdPro_icount_company_id');
    register_setting('AdPro_esim_api_settings', 'AdPro_icount_user');
    register_setting('AdPro_esim_api_settings', 'AdPro_icount_pass');
    register_setting('AdPro_esim_api_settings', 'AdPro_icount_api_key');
    
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
}
add_action('admin_init', 'AdPro_esim_settings_init');

/**
 * פונקציות callbacks לסקציות
 */
function AdPro_esim_api_section_callback() {
    echo '<p>הזן את פרטי ההתחברות ל-API של מובימטר.</p>';
    
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

/**
 * הוספת שדות הגדרות לתוסף iCount רשמי
 */
function AdPro_add_icount_api_settings_section() {
    echo '<h2>הגדרות iCount</h2>';
    echo '<p>הגדרות אלו משמשות לתמיכה באינטגרציה עם תוסף iCount הרשמי.</p>';
    
    // האם התוסף הרשמי של iCount מותקן?
    $icount_plugin = 'icount-payment-gateway/icount-payment-gateway.php'; // התאם לשם האמיתי
    if (is_plugin_active($icount_plugin)) {
        echo '<div class="notice notice-success inline"><p>תוסף iCount הרשמי מותקן ופעיל.</p></div>';
        
        // האם הגדרות התוסף הרשמי מוגדרות כראוי?
        // כאן יש להתאים את הבדיקה לפי הגדרות התוסף הרשמי
        if (get_option('woocommerce_icount_settings') || get_option('icount_api_key')) {
            echo '<div class="notice notice-info inline"><p>נראה שהגדרות iCount כבר מוגדרות בתוסף הרשמי.</p></div>';
        } else {
            echo '<div class="notice notice-warning inline"><p>אנא הגדר את תוסף iCount הרשמי בהגדרות וווקומרס > תשלומים.</p></div>';
        }
    } else {
        echo '<div class="notice notice-warning inline"><p>תוסף iCount הרשמי אינו מותקן. <a href="' . admin_url('plugin-install.php?s=icount&tab=search&type=term') . '">התקן עכשיו</a>.</p></div>';
    }
    
    // בכל מקרה, הצג את הגדרות ה-API כגיבוי
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">מפתח API של iCount:</th>
            <td>
                <input type="text" name="AdPro_icount_api_key" value="<?php echo esc_attr(get_option('AdPro_icount_api_key', '')); ?>" class="regular-text">
                <p class="description">מפתח ה-API זהה לזה שהוגדר בתוסף הרשמי אם מותקן.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">מזהה חברה:</th>
            <td>
                <input type="text" name="AdPro_icount_company_id" value="<?php echo esc_attr(get_option('AdPro_icount_company_id', '')); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">שם משתמש:</th>
            <td>
                <input type="text" name="AdPro_icount_user" value="<?php echo esc_attr(get_option('AdPro_icount_user', '')); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">סיסמה:</th>
            <td>
                <input type="password" name="AdPro_icount_pass" value="<?php echo esc_attr(get_option('AdPro_icount_pass', '')); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    <?php
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
         <?php
        // הצגת תאריך עדכון אחרון של חבילות
        $last_update = get_option('adpro_packages_last_update');
        if ($last_update) {
            $last_update_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_update);
            ?>
            <div class="notice notice-info inline">
                <p>
                    <strong>עדכון חבילות אחרון:</strong> <?php echo esc_html($last_update_date); ?>
                    <?php
                    // חישוב זמן שחלף
                    $time_diff = time() - $last_update;
                    $hours_diff = round($time_diff / 3600);
                    
                    if ($hours_diff < 24) {
                        echo ' <span style="color: green;">(לפני ' . esc_html($hours_diff) . ' שעות)</span>';
                    } else {
                        $days_diff = round($hours_diff / 24);
                        echo ' <span style="color: ' . ($days_diff > 2 ? 'red' : 'orange') . '">(לפני ' . esc_html($days_diff) . ' ימים)</span>';
                    }
                    ?>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="notice notice-warning inline">
                <p>לא נמצא תיעוד של עדכון חבילות. ייתכן שה-CRON טרם הופעל.</p>
            </div>
            <?php
        }
        ?>
        
        <form method="post" action="options.php">
            <?php
			

            settings_fields('AdPro_esim_api_settings');
            do_settings_sections('AdPro-esim-settings');
            
            // הוסף הגדרות iCount
            AdPro_add_icount_api_settings_section();
            
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
            <a href="#" id="test-icount-integration" class="button">בדוק חיבור לתוסף iCount הרשמי</a>
            <span id="icount-integration-test-result"></span>
        </p>
        
        <script>
            jQuery(document).ready(function($) {
                // סקריפט לבדיקת חיבור למובימטר
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
                
                // סקריפט לבדיקת חיבור ל-iCount
                $('#test-icount-integration').on('click', function(e) {
                    e.preventDefault();
                    var $result = $('#icount-integration-test-result');
                    
                    $result.html('<span style="color: #aaa;">בודק חיבור...</span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'AdPro_test_icount_api_integration'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<span style="color: green;">✓ ' + response.data + '</span>');
                            } else {
                                $result.html('<span style="color: red;">✗ שגיאה: ' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            $result.html('<span style="color: red;">✗ שגיאה בבדיקה</span>');
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
 * בדיקת חיבור לתוסף iCount הרשמי
 */
function AdPro_test_icount_api_integration() {
    // בדיקה אם התוסף הרשמי של iCount מותקן
    $icount_plugin = 'icount-payment-gateway/icount-payment-gateway.php'; // התאם לשם האמיתי
    
    if (!is_plugin_active($icount_plugin)) {
        wp_send_json_error('תוסף iCount הרשמי אינו מותקן.');
        return;
    }
    
    // בדיקה אם הגדרות התוסף הרשמי מוגדרות
    $icount_settings = get_option('woocommerce_icount_settings'); // התאם לשם האמיתי של ההגדרות
    
    if (!$icount_settings || empty($icount_settings['api_key'])) {
        // נסה לבדוק הגדרות חלופיות
        $api_key = get_option('icount_api_key') ?: get_option('AdPro_icount_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error('הגדרות iCount חסרות. אנא הגדר את התוסף הרשמי.');
            return;
        }
    }
    
    // בדיקת חיבור לשרת iCount באמצעות פונקציית API מתוסף רשמי
    // כאן יש להתאים לפי הפונקציות הזמינות בתוסף הרשמי
    
    if (function_exists('icount_test_api_connection')) {
        $result = icount_test_api_connection();
        
        if ($result === true || (is_array($result) && isset($result['success']) && $result['success'])) {
            wp_send_json_success('החיבור ל-iCount נבדק בהצלחה! התוסף הרשמי מוגדר כראוי.');
        } else {
            $error_message = is_string($result) ? $result : 'שגיאה לא ידועה בחיבור.';
            if (is_array($result) && isset($result['message'])) {
                $error_message = $result['message'];
            }
            wp_send_json_error('שגיאה בחיבור ל-iCount: ' . $error_message);
        }
        return;
    }
    
    // אם אין פונקציית בדיקה זמינה, נסה לבדוק ידנית
    // יש להתאים את הלוגיקה לפי API של iCount
    $company_id = $icount_settings['company_id'] ?? get_option('AdPro_icount_company_id');
    $user = $icount_settings['username'] ?? get_option('AdPro_icount_user');
    $pass = $icount_settings['password'] ?? get_option('AdPro_icount_pass');
    
    if (empty($company_id) || empty($user) || empty($pass)) {
        wp_send_json_error('חסרים פרטי התחברות ל-iCount. בדוק הגדרות.');
        return;
    }
    
    // בניית בקשת בדיקה בסיסית - יש להתאים לפי API של iCount
    $api_url = 'https://api.icount.co.il/api/v3.php/auth/login';
    
    $request_data = [
        'cid' => $company_id,
        'user' => $user,
        'pass' => $pass,
        'otp' => '' // חלק מפרוטוקול האימות אם צריך
    ];
    
    $response = wp_remote_post($api_url, [
        'body' => $request_data,
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('שגיאת חיבור: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
    
    if ($response_code !== 200) {
        $error_message = isset($response_data['reason']) ? $response_data['reason'] : 'קוד שגיאה: ' . $response_code;
        wp_send_json_error('שגיאה בתגובת השרת: ' . $error_message);
        return;
    }
    
    if (isset($response_data['status']) && $response_data['status'] === true) {
        wp_send_json_success('החיבור ל-iCount בוצע בהצלחה!');
    } else {
        $error_message = isset($response_data['reason']) ? $response_data['reason'] : 'שגיאה לא ידועה';
        wp_send_json_error('שגיאה באימות: ' . $error_message);
    }
}

// הוסף את פונקציית הבדיקה לאג'קס
add_action('wp_ajax_AdPro_test_icount_api_integration', 'AdPro_test_icount_api_integration');

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
    // מערך לאחסון כל הספקים
    $providers = [];
    $hidden_providers = get_option('AdPro_hidden_providers', []);
    
    if (!is_array($hidden_providers)) {
        $hidden_providers = array();
    }
    
    // שמירת שינויים
    if (isset($_POST['save_providers']) && check_admin_referer('AdPro_providers_nonce')) {
        $hidden_providers = isset($_POST['hidden_providers']) ? $_POST['hidden_providers'] : [];
        update_option('AdPro_hidden_providers', $hidden_providers);
        echo '<div class="notice notice-success"><p>הגדרות הספקים נשמרו בהצלחה!</p></div>';
    }
    
    // קבלת פרטי API
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    // איסוף הספקים מקבצי נתונים מקומיים או מה-API
    $all_providers = [];
    
    // נסה לאסוף ספקים מהקבצים המקומיים
    $data_dir = ADPRO_ESIM_PATH . 'data/';
    $countries_list_file = $data_dir . 'countries_list.json';
    
    if (file_exists($countries_list_file)) {
        $countries_list = json_decode(file_get_contents($countries_list_file), true);
        
        foreach ($countries_list as $iso) {
            $country_file = $data_dir . strtolower($iso) . '.json';
            
            if (file_exists($country_file)) {
                $packages = json_decode(file_get_contents($country_file), true);
                if (is_array($packages)) {
                    foreach ($packages as $package) {
                        if (isset($package['providerId']) && isset($package['providerName'])) {
                            $all_providers[$package['providerId']] = [
                                'id' => $package['providerId'],
                                'name' => $package['providerName'],
                                'hidden' => in_array($package['providerId'], $hidden_providers)
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // אם לא מצאנו ספקים בקבצים המקומיים, ננסה לקבל מה-API
    if (empty($all_providers) && !empty($api_key) && !empty($merchant_id)) {
        $packages = AdPro_esim_get_packages();
        
        foreach ($packages as $package) {
            if (isset($package['providerId']) && isset($package['providerName'])) {
                $all_providers[$package['providerId']] = [
                    'id' => $package['providerId'],
                    'name' => $package['providerName'],
                    'hidden' => in_array($package['providerId'], $hidden_providers)
                ];
            }
        }
    }
    
    // מיון הספקים לפי שם
    uasort($all_providers, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>הסתר או הצג ספקים שונים באתר. סמן את הספקים שברצונך להסתיר.</p>
        
        <?php if (empty($api_key) || empty($merchant_id)) : ?>
            <div class="notice notice-warning">
                <p>חסרים פרטי התחברות ל-API. <a href="<?php echo admin_url('admin.php?page=AdPro-esim-settings'); ?>">הגדר את פרטי ה-API</a> כדי לראות רשימת ספקים.</p>
            </div>
        <?php elseif (empty($all_providers)) : ?>
            <div class="notice notice-warning">
                <p>לא נמצאו ספקים. וודא שיש חיבור תקין ל-API או שיש קבצי נתונים מקומיים.</p>
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
                        <?php foreach ($all_providers as $provider) : ?>
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
    
    // קבלת כל הנתונים של הזמנות eSIM שהושלמו
    $orders_query = "
        SELECT 
            p.ID as order_id,
            p.post_date as order_date,
            p.post_status as order_status,
            wc_om1.meta_value as customer_email,
            wc_om2.meta_value as customer_name,
            oim.meta_value as esim_country,
            oim2.meta_value as esim_package_id,
            oim3.meta_value as esim_data_limit,
            oim4.meta_value as esim_data_unit,
            oim5.meta_value as esim_validity_days,
            om.meta_value as order_total,
            om2.meta_value as mobimatter_order_id,
            om3.meta_value as esim_activation_code
        FROM 
            {$wpdb->posts} p
        JOIN 
            {$wpdb->postmeta} om ON p.ID = om.post_id AND om.meta_key = '_order_total'
        LEFT JOIN 
            {$wpdb->postmeta} om2 ON p.ID = om2.post_id AND om2.meta_key = '_mobimatter_order_id'
        LEFT JOIN 
            {$wpdb->postmeta} om3 ON p.ID = om3.post_id AND om3.meta_key = '_esim_activation_code'
        LEFT JOIN 
            {$wpdb->postmeta} wc_om1 ON p.ID = wc_om1.post_id AND wc_om1.meta_key = '_billing_email'
        LEFT JOIN 
            {$wpdb->postmeta} wc_om2 ON p.ID = wc_om2.post_id AND wc_om2.meta_key = '_billing_first_name'
        JOIN 
            {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
        JOIN 
            {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = 'esim_country'
        LEFT JOIN 
            {$wpdb->prefix}woocommerce_order_itemmeta oim2 ON oi.order_item_id = oim2.order_item_id AND oim2.meta_key = 'esim_package_id'
        LEFT JOIN 
            {$wpdb->prefix}woocommerce_order_itemmeta oim3 ON oi.order_item_id = oim3.order_item_id AND oim3.meta_key = 'esim_data_limit'
        LEFT JOIN 
            {$wpdb->prefix}woocommerce_order_itemmeta oim4 ON oi.order_item_id = oim4.order_item_id AND oim4.meta_key = 'esim_data_unit'
        LEFT JOIN 
            {$wpdb->prefix}woocommerce_order_itemmeta oim5 ON oi.order_item_id = oim5.order_item_id AND oim5.meta_key = 'esim_validity_days'
        WHERE 
            p.post_type = 'shop_order'
            AND (p.post_status = 'wc-completed' OR p.post_status = 'wc-processing')
        ORDER BY
            p.post_date DESC
    ";
    
    $orders = $wpdb->get_results($orders_query);
    
    // ארגון נתונים לפי מדינות ותאריכים
    $countries_stats = [];
    $monthly_stats = [];
    $total_revenue = 0;
    $total_orders = 0;
    $data_volumes = [];
    
    foreach ($orders as $order) {
        $country = $order->esim_country;
        $revenue = floatval($order->order_total);
        $month = date('Y-m', strtotime($order->order_date));
        $data_limit = $order->esim_data_limit ? $order->esim_data_limit : '0';
        $data_unit = $order->esim_data_unit ? $order->esim_data_unit : 'GB';
        
        $data_volumes[] = [
            'limit' => $data_limit,
            'unit' => $data_unit
        ];
        
        $total_revenue += $revenue;
        $total_orders++;
        
        // סטטיסטיקות לפי מדינה
        if (!isset($countries_stats[$country])) {
            $countries_stats[$country] = [
                'orders' => 0,
                'revenue' => 0
            ];
        }
        
        $countries_stats[$country]['orders']++;
        $countries_stats[$country]['revenue'] += $revenue;
        
        // סטטיסטיקות לפי חודש
        if (!isset($monthly_stats[$month])) {
            $monthly_stats[$month] = [
                'orders' => 0,
                'revenue' => 0
            ];
        }
        
        $monthly_stats[$month]['orders']++;
        $monthly_stats[$month]['revenue'] += $revenue;
    }
    
    // מיון לפי הכנסות (מהגבוה לנמוך)
    uasort($countries_stats, function($a, $b) {
        return $b['revenue'] <=> $a['revenue'];
    });
    
    // מיון לפי תאריך (מהחדש לישן)
    ksort($monthly_stats);
    $monthly_stats = array_reverse($monthly_stats, true);
    
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
            
            <?php if (!empty($monthly_stats)) : ?>
                <div class="stats-details">
                    <h2>מכירות לפי חודש</h2>
                    
                    <div class="stats-chart">
                        <canvas id="monthlyRevenueChart" height="100"></canvas>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>חודש</th>
                                <th>הזמנות</th>
                                <th>הכנסות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $month => $stats) : ?>
                                <tr>
                                    <td><?php echo date_i18n('F Y', strtotime($month . '-01')); ?></td>
                                    <td><?php echo $stats['orders']; ?></td>
                                    <td>₪<?php echo number_format($stats['revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($countries_stats)) : ?>
                <div class="stats-details">
                    <h2>סטטיסטיקות לפי מדינה</h2>
                    
                    <div class="stats-chart">
                        <canvas id="countriesRevenueChart" height="200"></canvas>
                    </div>
                    
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
                            <?php foreach ($countries_stats as $country => $stats) : 
                                $country_iso = '';
                                // חיפוש קוד ISO של המדינה
                                $countries_mapping = AdPro_get_countries_mapping();
                                foreach ($countries_mapping as $hebrew => $data) {
                                    if ($hebrew === $country) {
                                        $country_iso = strtolower($data['iso']);
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($country_iso) : ?>
                                            <img src="https://flagcdn.com/16x12/<?php echo $country_iso; ?>.png" alt="<?php echo esc_attr($country); ?>" style="vertical-align: middle; margin-left: 5px;"> 
                                        <?php endif; ?>
                                        <?php echo esc_html($country); ?>
                                    </td>
                                    <td><?php echo $stats['orders']; ?></td>
                                    <td>₪<?php echo number_format($stats['revenue'], 2); ?></td>
                                    <td>₪<?php echo number_format($stats['revenue'] / $stats['orders'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="stats-details">
                <h2>פירוט הזמנות eSIM</h2>
                
                <?php if (!empty($orders)) : ?>
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <input type="text" id="orderSearch" placeholder="חיפוש..." class="regular-text">
                        </div>
                        <div class="alignright">
                            <button id="exportOrders" class="button">ייצוא לאקסל</button>
                        </div>
                        <br class="clear">
                    </div>
                
                    <table class="wp-list-table widefat fixed striped orders-table">
                        <thead>
                            <tr>
                                <th>מס' הזמנה</th>
                                <th>תאריך</th>
                                <th>לקוח</th>
                                <th>מדינה</th>
                                <th>חבילה</th>
                                <th>נפח גלישה</th>
                                <th>תוקף</th>
                                <th>קוד הפעלה</th>
                                <th>סכום</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order) : 
                                $order_status_label = '';
                                switch ($order->order_status) {
                                    case 'wc-completed':
                                        $order_status_label = '<span class="status-completed">הושלם</span>';
                                        break;
                                    case 'wc-processing':
                                        $order_status_label = '<span class="status-processing">בביצוע</span>';
                                        break;
                                    default:
                                        $order_status_label = $order->order_status;
                                }
                            ?>
                                <tr data-order-id="<?php echo esc_attr($order->order_id); ?>">
                                    <td>
                                        <?php echo $order->order_id; ?>
                                        <div class="row-actions">
                                            <span><a href="<?php echo admin_url('post.php?post=' . $order->order_id . '&action=edit'); ?>">צפה</a></span>
                                        </div>
                                    </td>
                                    <td><?php echo date_i18n('d/m/Y H:i', strtotime($order->order_date)); ?></td>
                                    <td>
                                        <?php echo esc_html($order->customer_name); ?>
                                        <div class="row-actions">
                                            <span><a href="mailto:<?php echo esc_attr($order->customer_email); ?>"><?php echo esc_html($order->customer_email); ?></a></span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($order->esim_country); ?></td>
                                    <td><?php echo esc_html($order->esim_package_id); ?></td>
                                    <td><?php echo $order->esim_data_limit ? esc_html($order->esim_data_limit . ' ' . $order->esim_data_unit) : '-'; ?></td>
                                    <td><?php echo $order->esim_validity_days ? esc_html($order->esim_validity_days . ' ימים') : '-'; ?></td>
                                    <td><?php echo esc_html($order->esim_activation_code); ?></td>
                                    <td>₪<?php echo number_format(floatval($order->order_total), 2); ?></td>
                                    <td>
                                        <a href="#" class="button view-esim-details" data-order-id="<?php echo esc_attr($order->order_id); ?>">פרטי eSIM</a>
                                        <a href="#" class="button view-esim-usage" data-mobimatter-id="<?php echo esc_attr($order->mobimatter_order_id); ?>">נתוני שימוש</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div id="esim-details-modal" class="esim-modal">
                        <div class="esim-modal-content">
                            <span class="esim-modal-close">×</span>
                            <h2>פרטי ה-eSIM</h2>
                            <div id="esim-details-content"></div>
                        </div>
                    </div>
                    
                    <div id="esim-usage-modal" class="esim-modal">
                        <div class="esim-modal-content">
                            <span class="esim-modal-close">×</span>
                            <h2>נתוני שימוש</h2>
                            <div id="esim-usage-content"></div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="notice notice-info">
                        <p>אין עדיין נתוני מכירות.</p>
                    </div>
                <?php endif; ?>
            </div>
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
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-weight: normal;
            font-size: 14px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        
        .stats-details {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-details h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 18px;
        }
        
        .stats-chart {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        
        .status-completed {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-processing {
            display: inline-block;
            background-color: #2196F3;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .orders-table td {
            vertical-align: middle;
        }
        
        /* מודלים */
        .esim-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .esim-modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .esim-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .esim-modal-close:hover {
            color: black;
        }
        
        @media (max-width: 768px) {
            .stats-summary {
                flex-direction: column;
            }
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    jQuery(document).ready(function($) {
        // חיפוש הזמנות
        $('#orderSearch').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.orders-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // צפייה בפרטי eSIM
        $('.view-esim-details').on('click', function(e) {
            e.preventDefault();
            var orderId = $(this).data('order-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'adpro_get_esim_details',
                    order_id: orderId,
                    nonce: '<?php echo wp_create_nonce('adpro_admin_esim_details'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#esim-details-content').html(response.data);
                        $('#esim-details-modal').show();
                    } else {
                        alert('שגיאה: ' + response.data);
                    }
                }
            });
        });
        
        // צפייה בנתוני שימוש
        $('.view-esim-usage').on('click', function(e) {
            e.preventDefault();
            var mobimatterId = $(this).data('mobimatter-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'adpro_get_esim_usage',
                    mobimatter_id: mobimatterId,
                    nonce: '<?php echo wp_create_nonce('adpro_admin_esim_usage'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#esim-usage-content').html(response.data);
                        $('#esim-usage-modal').show();
                    } else {
                        alert('שגיאה: ' + response.data);
                    }
                }
            });
        });
        
        // סגירת מודלים
        $('.esim-modal-close').on('click', function() {
            $('.esim-modal').hide();
        });
        
        // סגירת מודל בלחיצה מחוץ לתוכן
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('esim-modal')) {
                $('.esim-modal').hide();
            }
        });
        
        // ייצוא לאקסל
        $('#exportOrders').on('click', function() {
            // יצירת טבלת אקסל
            var table = document.querySelector('.orders-table');
            var csv = [];
            var rows = table.querySelectorAll('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (var j = 0; j < cols.length - 1; j++) { // דילוג על עמודת הפעולות
                    var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/\s\s+/g, ' ');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            var csvText = csv.join('\n');
            var csvFile = new Blob(["\uFEFF" + csvText], {type: 'text/csv;charset=utf-8;'});
            
            // יצירת קישור להורדה
            var downloadLink = document.createElement('a');
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.download = 'esim_orders_' + new Date().toISOString().slice(0, 10) + '.csv';
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        });
        
        <?php if (!empty($monthly_stats)) : ?>
        // גרף הכנסות לפי חודש
        var monthlyData = {
            labels: [<?php 
                $labels = [];
                foreach (array_keys($monthly_stats) as $month) {
                    $labels[] = "'" . date_i18n('M Y', strtotime($month . '-01')) . "'";
                }
                echo implode(', ', $labels);
            ?>],
            datasets: [{
                label: 'הכנסות',
                data: [<?php 
                    $revenues = [];
                    foreach ($monthly_stats as $stats) {
                        $revenues[] = round($stats['revenue'], 2);
                    }
                    echo implode(', ', $revenues);
                ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };
        
        var monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        var monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: monthlyData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if (!empty($countries_stats)) : ?>
        // גרף הכנסות לפי מדינה
        var countriesData = {
            labels: [<?php 
                $labels = [];
                $count = 0;
                foreach (array_keys($countries_stats) as $country) {
                    if ($count < 7) { // הגבלה ל-7 המדינות המובילות
                        $labels[] = "'" . $country . "'";
                        $count++;
                    }
                }
                echo implode(', ', $labels);
            ?>],
            datasets: [{
                label: 'הכנסות',
                data: [<?php 
                    $revenues = [];
                    $count = 0;
                    foreach ($countries_stats as $stats) {
                        if ($count < 7) { // הגבלה ל-7 המדינות המובילות
                            $revenues[] = round($stats['revenue'], 2);
                            $count++;
                        }
                    }
                    echo implode(', ', $revenues);
                ?>],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(199, 199, 199, 0.6)'
                ],
                borderWidth: 1
            }]
        };
        
        var countriesCtx = document.getElementById('countriesRevenueChart').getContext('2d');
        var countriesChart = new Chart(countriesCtx, {
            type: 'pie',
            data: countriesData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        <?php endif; ?>
    });
    </script>
    <?php
}