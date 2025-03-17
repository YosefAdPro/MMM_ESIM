<?php
// קוד דיבאג מורחב
if (isset($_GET['debug']) && current_user_can('manage_options')) {
    echo '<h1>Debug Info</h1>';
    echo '<p>Template loaded: ' . __FILE__ . '</p>';
    echo '<p>Country param: ' . get_query_var('AdPro_esim_country') . '</p>';
    
    $country_data = AdPro_get_country_by_slug(get_query_var('AdPro_esim_country'));
    echo '<h2>Country data:</h2>';
    echo '<pre>' . print_r($country_data, true) . '</pre>';
    
    if ($country_data) {
        $api_key = get_option('AdPro_api_key');
        $merchant_id = get_option('AdPro_merchant_id');
        
        echo '<h2>API Credentials:</h2>';
        echo '<p>API Key: ' . (empty($api_key) ? 'MISSING' : substr($api_key, 0, 5) . '...') . '</p>';
        echo '<p>Merchant ID: ' . (empty($merchant_id) ? 'MISSING' : substr($merchant_id, 0, 5) . '...') . '</p>';

        // בניית כתובת ה-API לדיבאג
        $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/products';
        $query_params = [];
        if (!empty($country_data['iso'])) {
            $query_params['country'] = $country_data['iso'];
        }

        // הוספת פרמטר הקטגוריה
        $query_params['category'] = 'esim_realtime';

        if (!empty($query_params)) {
            $api_url .= '?' . http_build_query($query_params);
        }
                
        echo '<h2>API Request:</h2>';
        echo '<p>URL: ' . $api_url . '</p>';
        echo '<p>Headers: merchantId=' . $merchant_id . ', api-key=' . substr($api_key, 0, 5) . '..., Accept=text/plain</p>';
        
        // ביצוע קריאת API ידנית לדיבאג
        $args = [
            'headers' => [
                'Accept' => 'text/plain',
                'merchantId' => $merchant_id,
                'api-key' => $api_key,
            ],
            'timeout' => 30,
        ];
        
        echo '<h2>API Response:</h2>';
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            echo '<p style="color: red;">Error: ' . $response->get_error_message() . '</p>';
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            echo '<p>Status Code: ' . $response_code . '</p>';
            echo '<p>Response Headers:</p><pre>' . print_r(wp_remote_retrieve_headers($response), true) . '</pre>';
            echo '<p>Response Body:</p><pre>' . htmlspecialchars($response_body) . '</pre>';
            
            $data = json_decode($response_body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<p>Parsed Response:</p><pre>' . print_r($data, true) . '</pre>';
            } else {
                echo '<p style="color: red;">JSON Parsing Error: ' . json_last_error_msg() . '</p>';
            }
        }
        
        // קבלת חבילות מהפונקציה הרגילה
        $packages = AdPro_esim_get_packages($country_data['iso']);
        echo '<h2>Function Output:</h2>';
        echo '<pre>' . print_r($packages, true) . '</pre>';
    }
    
    exit;
}

get_header();

$country_param = strtolower(get_query_var('AdPro_esim_country'));
$country_data = AdPro_get_country_by_slug($country_param);
$saved_content = get_option('AdPro_esim_country_content', []);

// טעינת סגנונות ספציפיים לדף
wp_enqueue_style('AdPro-esim-style', ADPRO_ESIM_URL . 'public/assets/css/style.css');

wp_enqueue_script('AdPro-esim-script', ADPRO_ESIM_URL . 'public/assets/js/frontend.js', array('jquery'), null, true);

// הוספת URL לניהול AJAX
wp_localize_script('AdPro-esim-script', 'adminAjaxUrl', admin_url('admin-ajax.php'));
wp_localize_script('AdPro-esim-script', 'AdPro_esim_ajax', [
    'site_url' => home_url(),
    'countries' => AdPro_get_countries_mapping(),
]);

if ($country_data) {
    $hebrew_country = $country_data['hebrew'];
    $english_country = $country_data['english'];
    $iso_code = $country_data['iso'];
    $packages = AdPro_esim_get_packages($country_data['iso']);

    // שיפור SEO
    add_filter('wp_title', function() use ($hebrew_country) {
        return 'חבילות eSIM עבור ' . $hebrew_country . ' | ' . get_bloginfo('name');
    });
    
    add_action('wp_head', function() use ($hebrew_country) {
        echo '<meta name="description" content="גלה חבילות eSIM זולות ואמינות עבור ' . esc_attr($hebrew_country) . '. רכישה מאובטחת וקבלה מיידית!">';
        echo '<meta name="keywords" content="eSIM ' . esc_attr($hebrew_country) . ', חבילות גלישה, חבילות eSIM, סים וירטואלי">';
    });
    ?>
    <div id="AdPro-esim-country-page">
        <div class="country-header">
            <h1>
                <img src="https://flagcdn.com/48x36/<?php echo strtolower($iso_code); ?>.png" alt="<?php echo esc_attr($hebrew_country); ?>">
                חבילות eSIM עבור <?php echo esc_html($hebrew_country); ?>
            </h1>
        </div>
        
        <?php if (!empty($saved_content[$hebrew_country]['image']) || !empty($saved_content[$hebrew_country]['text'])) : ?>
            <div class="country-custom-content">
                <?php if (!empty($saved_content[$hebrew_country]['image'])) : ?>
                    <img src="<?php echo esc_url($saved_content[$hebrew_country]['image']); ?>" alt="<?php echo esc_attr($hebrew_country); ?>" class="country-image">
                <?php endif; ?>
                <?php if (!empty($saved_content[$hebrew_country]['text'])) : ?>
                    <div class="country-text"><?php echo wp_kses_post($saved_content[$hebrew_country]['text']); ?></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="country-default-content">
                <p>גלה את חבילות ה-eSIM הטובות ביותר ל<?php echo esc_html($hebrew_country); ?>. חבילות גלישה לכל סוגי הנסיעות במחירים אטרקטיביים.</p>
            </div>
        <?php endif; ?>

        <!-- פילטר חבילות -->
 <!-- פילטר חבילות - קוד מתוקן עם סינון מחיר -->
<?php if ($packages && !empty($packages)) : ?>
    <div class="package-filters">
        <h3>סינון חבילות</h3>
        
        <!-- כפתור סינון למובייל -->
        <button type="button" class="filter-mobile-toggle">פתח סינון מתקדם</button>
        
        <!-- תוכן הסינון -->
        <div class="filter-content">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="data-filter">כמות נתונים:</label>
                    <select id="data-filter">
                        <option value="all">הכל</option>
                        <option value="1-5">1GB - 5GB</option>
                        <option value="5-10">5GB - 10GB</option>
                        <option value="10-20">10GB - 20GB</option>
                        <option value="20-50">20GB - 50GB</option>
                        <option value="50+">50GB ומעלה</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="duration-filter">זמן:</label>
                    <select id="duration-filter">
                        <option value="all">הכל</option>
                        <option value="1-7">עד שבוע</option>
                        <option value="7-14">1-2 שבועות</option>
                        <option value="14-30">עד חודש</option>
                        <option value="30-90">1-3 חודשים</option>
                        <option value="90+">3+ חודשים</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="price-filter">מחיר:</label>
                    <select id="price-filter">
                        <option value="all">הכל</option>
                        <option value="low-to-high">מהזול ליקר</option>
                        <option value="high-to-low">מהיקר לזול</option>
                    </select>
                </div>
                <button id="reset-filters" class="reset-button">איפוס סינון</button>
            </div>
        </div>
    </div>
<?php endif; ?>

        <?php if ($packages && !empty($packages)) : ?>
            <div class="packages-list">
                <?php foreach ($packages as $package) : ?>
                    <!-- נוסיף מחלקה package-clickable לפתיחת המודאל -->
                    <div class="package package-clickable" data-package-id="<?php echo esc_attr($package['productId']); ?>">
                        <?php
                        // חילוץ נתוני החבילה
                        $package_title = '';
                        $data_limit = '';
                        $data_unit = '';
                        $validity_days = '';
                        $price = isset($package['retailPrice']) ? $package['retailPrice'] : '';
                        $currency = isset($package['currencyCode']) ? $package['currencyCode'] : '';
                        $supported_countries = isset($package['countries']) ? $package['countries'] : [];
                        
                        // הוצאת פרטי החבילה מהמערך
                        if (isset($package['productDetails']) && is_array($package['productDetails'])) {
                            foreach ($package['productDetails'] as $detail) {
                                if ($detail['name'] === 'PLAN_TITLE' && !empty($detail['value'])) {
                                    $package_title = $detail['value'];
                                }
                                if ($detail['name'] === 'PLAN_DATA_LIMIT' && !empty($detail['value'])) {
                                    $data_limit = $detail['value'];
                                }
                                if ($detail['name'] === 'PLAN_DATA_UNIT' && !empty($detail['value'])) {
                                    $data_unit = $detail['value'];
                                }
                                if ($detail['name'] === 'PLAN_VALIDITY' && !empty($detail['value'])) {
                                    // המרת דקות לימים
                                    $validity_days = round(intval($detail['value']) / 24);
                                }
                            }
                        }
						
//						echo '<div class="package-id-info">';
//echo 'כותרת: ' . esc_html($package_title) . ' | מזהה: ' . esc_html($package['productId']);
//echo '</div>';
                        
                        // אם חסר כותרת, השתמש במזהה המוצר
                        if (empty($package_title)) {
                            $package_title = isset($package['productId']) ? $package['productId'] : 'חבילת eSIM';
                        }
                        ?>
                        
                        <?php
// יצירת כותרת מותאמת אישית
$custom_title = '';
$countries_count = isset($package['countries']) ? count($package['countries']) : 0;

// הוספת שם המדינה
if (!empty($hebrew_country)) {
    $custom_title .= "חבילת גלישה ל" . esc_html($hebrew_country);
    
    // הוספת מספר מדינות נוספות אם יש יותר ממדינה אחת
    if ($countries_count > 1) {
        $additional_countries = $countries_count - 1;
        $custom_title .= " ועוד " . $additional_countries . " מדינות";
    }
} else {
    // כותרת עם רק מספר מדינות אם אין מדינה ספציפית
    $custom_title .= "חבילת גלישה ל-" . $countries_count . " מדינות";
}

// הוספת נפח הנתונים
if (!empty($data_limit) && !empty($data_unit)) {
    $custom_title .= " עם " . $data_limit . " " . $data_unit;
}

// הוספת תקופת התוקף
if (!empty($validity_days)) {
    $custom_title .= " למשך " . $validity_days . " ימים";
}
?>
<h2><?php echo $custom_title; ?></h2>
                        
<div class="package-details">
    <!-- עבור הצגת אייקון דולר במקום USD -->
<p class="price">
    <?php
    // בדיקה אם המטבע הוא דולר
    if ($currency === 'USD') {
        echo '<span class="currency-icon">$</span> ';  // אייקון דולר
    } elseif ($currency === 'EUR') {
        echo '<span class="currency-icon">€</span> ';  // אייקון יורו
    } elseif ($currency === 'GBP') {
        echo '<span class="currency-icon">£</span> ';  // אייקון ליש"ט
    } else {
        echo '<span class="currency-name">' . esc_html($currency) . '</span> ';  // קוד המטבע המקורי
    }
    
    echo esc_html($price);
    ?>
</p>    
    <!-- הצגת מידע חבילה בשורה אחת -->
    <div class="package-info-row">
        <?php if (!empty($data_limit) && !empty($data_unit)) : ?>
            <div class="info-item">
                <span class="info-label">נתונים:</span>
                <span class="info-value"><?php echo esc_html($data_limit . '' . $data_unit); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($validity_days)) : ?>
            <div class="info-item">
                <span class="info-label">תוקף:</span>
                <span class="info-value"><?php echo esc_html($validity_days); ?> ימים</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($package['providerName'])) : ?>
            <div class="info-item">
                <span class="info-label">ספק:</span>
                <span class="info-value"><?php echo esc_html($package['providerName']); ?></span>
            </div>
        <?php endif; ?>
    </div>
                            <div class="package-description">
                                <?php
                                $description = "חבילת גלישה ל" . esc_html($hebrew_country);
                                if (!empty($data_limit) && !empty($validity_days)) {
                                    $description .= " עם " . $data_limit . " " . $data_unit . " למשך " . $validity_days . " ימים.";
                                }
                                ?>
                                <p><?php echo esc_html($description); ?></p>
                            </div>
                            
                            <?php
                            // קבלת רשתות ספציפיות למדינה זו
                            $product_id = $package['productId'];
                            $all_networks = AdPro_get_product_networks($product_id);
                            $country_networks = AdPro_filter_networks_by_country($all_networks, $country_data['iso']);
                            ?>

<?php if (!empty($country_networks)) : ?>
    <div class="country-networks">
        <h3>רשתות סלולר נתמכות ב<?php echo esc_html($hebrew_country); ?></h3>
        <div class="networks-grid">
            <?php foreach ($country_networks as $network) : ?>
                <div class="network-item">
                    <span class="network-name">
                        <?php echo esc_html($network['brand']); ?>
                        <?php if (isset($network['is5G']) && $network['is5G']) : ?>
                            <span class="network-badge">5G</span>
                        <?php elseif (isset($network['is4G']) && $network['is4G']) : ?>
                            <span class="network-badge network-4g">4G</span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
                            
                            <?php if (!empty($supported_countries) && count($supported_countries) > 0) : ?>
                                <div class="supported-countries">
                                    <h3>מדינות נתמכות</h3>
                                    <div class="country-flags">
                                        <?php
                                        // מגביל למספר מוגדר של דגלים כדי לא להציף את הדף
                                        $max_flags = 20;
                                        $count = 0;
                                        
                                        foreach ($supported_countries as $country_code) :
                                            if ($count >= $max_flags) {
                                                echo '<div class="more-countries">+' . (count($supported_countries) - $max_flags) . ' מדינות נוספות</div>';
                                                break;
                                            }
                                            
                                            // התמודדות עם קודי מדינה מיוחדים כמו US-HI
                                            $display_code = $country_code;
                                            if (strpos($country_code, '-') !== false) {
                                                $parts = explode('-', $country_code);
                                                $display_code = $parts[0];
                                            }
                                            
                                            $count++;
                                        ?>
                                            <div class="country-flag" title="<?php echo esc_attr($country_code); ?>">
                                                <img src="https://flagcdn.com/24x18/<?php echo strtolower($display_code); ?>.png" alt="<?php echo esc_attr($country_code); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="AdPro_process_package">
    <input type="hidden" name="package_id" value="<?php echo esc_attr($package['productId']); ?>">
    <input type="hidden" name="country" value="<?php echo esc_attr($hebrew_country); ?>">
    <button type="submit" class="buy-now">רכוש עכשיו</button>
</form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="no-packages">
                <p>לא נמצאו חבילות זמינות עבור <?php echo esc_html($hebrew_country); ?> כרגע.</p>
                <p>אנא נסה שוב מאוחר יותר או בחר מדינה אחרת.</p>
            </div>
        <?php endif; ?>

        <!-- נוסיף מודאל חדש לפרטי חבילה מלאים -->
        <div id="package-details-modal" class="package-modal" style="display: none;">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <div id="package-modal-content">
                    <!-- התוכן יוזן דינמית ע"י JavaScript -->
                </div>
            </div>
        </div>

        <!-- נשמור את נתוני החבילות בקובץ JavaScript -->
        <script type="text/javascript">
            var packageData = <?php echo json_encode($packages); ?>;
            var countriesMapping = <?php echo json_encode(AdPro_get_countries_mapping()); ?>;
            var hebrewCountry = "<?php echo esc_js($hebrew_country); ?>";
        </script>
    </div>
    <?php
} else {
    ?>
    <div class="country-not-found">
        <h1>מדינה לא נמצאה</h1>
        <p>המדינה המבוקשת אינה נמצאת במערכת או אינה נתמכת.</p>
        <a href="<?php echo home_url(); ?>" class="button">חזרה לדף הבית</a>
    </div>
    <?php
}

get_footer();