<?php
/**
 * שורטקוד להצגת חבילות eSIM ספציפיות לפי מזהים
 */
function AdPro_specific_packages_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product_ids' => '',     // רשימת מזהי מוצרים מופרדים בפסיקים
        'show_header' => 'no',   // האם להציג כותרת עליונה
        'show_filter' => 'no',   // האם להציג אפשרויות סינון
        'show_networks' => 'yes', // האם להציג רשתות סלולר נתמכות
        'title' => '',           // כותרת מותאמת אישית
    ), $atts);
    
    // בדיקה אם צוינו מזהי מוצרים
    if (empty($atts['product_ids'])) {
        return '<p>יש לציין מזהי חבילות בשורטקוד.</p>';
    }
    
    // פיצול המחרוזת לרשימת מזהים (מופרדים בפסיקים)
    $product_ids = array_map('trim', explode(',', $atts['product_ids']));
    
    // איסוף החבילות הספציפיות
    $packages = array();
    $hebrew_country = '';
    $iso_code = '';
    
    foreach ($product_ids as $product_id) {
        // ננסה למצוא את המוצר בכל המדינות
        $package = AdPro_get_package_by_id('', $product_id);
        
        if ($package) {
            $packages[] = $package;
            
            // אם לא נקבעה מדינה, נקבע אותה מהחבילה הראשונה שנמצאה
            if (empty($hebrew_country) && !empty($package['countries'][0])) {
                $main_country_iso = $package['countries'][0];
                
                // אם מדובר בקוד ISO מורכב כמו US-CA, ניקח רק את החלק הראשון
                if (strpos($main_country_iso, '-') !== false) {
                    $parts = explode('-', $main_country_iso);
                    $main_country_iso = $parts[0];
                }
                
                $iso_code = $main_country_iso;
                
                // חיפוש שם המדינה לפי קוד ISO
                foreach (AdPro_get_countries_mapping() as $heb => $data) {
                    if ($data['iso'] === $main_country_iso) {
                        $hebrew_country = $heb;
                        break;
                    }
                }
            }
        }
    }
    
    // אם לא נמצאו חבילות, נחזיר הודעה
    if (empty($packages)) {
        return '<p>לא נמצאו חבילות עם המזהים שצוינו.</p>';
    }
    
    // טעינת סגנונות וסקריפטים
    wp_enqueue_style('AdPro-esim-style', ADPRO_ESIM_URL . 'public/assets/css/style.css');
    wp_enqueue_script('AdPro-esim-script', ADPRO_ESIM_URL . 'public/assets/js/frontend.js', array('jquery'), null, true);
    
    // הוספת URL לניהול AJAX
    wp_localize_script('AdPro-esim-script', 'adminAjaxUrl', admin_url('admin-ajax.php'));
    wp_localize_script('AdPro-esim-script', 'AdPro_esim_ajax', [
        'site_url' => home_url(),
        'countries' => AdPro_get_countries_mapping(),
    ]);
    
    // התחל לצבור פלט
    ob_start();
    
    // כותרת מותאמת אישית (אם צוינה)
    if (!empty($atts['title'])) {
        echo '<h2 class="custom-packages-title">' . esc_html($atts['title']) . '</h2>';
    }
    
    // כותרת המדינה (אם נבחרה האפשרות)
    if ($atts['show_header'] === 'yes' && !empty($hebrew_country)) {
        ?>
        <div class="country-header">
            <h1>
                <img src="https://flagcdn.com/48x36/<?php echo strtolower($iso_code); ?>.png" alt="<?php echo esc_attr($hebrew_country); ?>">
                חבילות eSIM עבור <?php echo esc_html($hebrew_country); ?>
            </h1>
        </div>
        <?php
    }
    
    // פילטר חבילות (אם נבחרה האפשרות)
    if ($atts['show_filter'] === 'yes') {
        ?>
        <div class="package-filters">
            <div class="filter-title-container">
                <h3>סינון חבילות</h3>
                <button id="reset-filters" class="reset-button">איפוס סינון</button>
            </div>
            
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
                </div>
            </div>
        </div>
        <?php
    }
    
    // רשימת חבילות
    ?>
    <div class="packages-list">
        <?php foreach ($packages as $package) : ?>
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
                
                // חילוץ המדינה העיקרית של החבילה (אם יש)
                $package_main_country = '';
                $main_country_iso = '';
                if (!empty($package['countries'][0])) {
                    $main_country_iso = $package['countries'][0];
                    if (strpos($main_country_iso, '-') !== false) {
                        $parts = explode('-', $main_country_iso);
                        $main_country_iso = $parts[0];
                    }
                    
                    foreach (AdPro_get_countries_mapping() as $heb => $data) {
                        if ($data['iso'] === $main_country_iso) {
                            $package_main_country = $heb;
                            break;
                        }
                    }
                }
                
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
                
                // אם חסר כותרת, השתמש במזהה המוצר
                if (empty($package_title)) {
                    $package_title = isset($package['productId']) ? $package['productId'] : 'חבילת eSIM';
                }
                
                // יצירת כותרת מותאמת אישית
                $custom_title = '';
                $countries_count = isset($package['countries']) ? count($package['countries']) : 0;

                // הוספת שם המדינה
                if (!empty($package_main_country)) {
                    $custom_title .= "חבילת גלישה ל" . esc_html($package_main_country);
                    
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
                    </div>
                    
                    <div class="package-description">
                        <?php
                        $description = "חבילת גלישה";
                        if (!empty($package_main_country)) {
                            $description .= " ל" . esc_html($package_main_country);
                        }
                        if (!empty($data_limit) && !empty($validity_days)) {
                            $description .= " עם " . $data_limit . " " . $data_unit . " למשך " . $validity_days . " ימים.";
                        }
                        ?>
                        <p><?php echo esc_html($description); ?></p>
                    </div>
                    
                    <?php
                    // תצוגת רשתות סלולריות נתמכות (אם נבחרה האפשרות)
                    if ($atts['show_networks'] === 'yes' && !empty($main_country_iso)) :
                        $product_id = $package['productId'];
                        $all_networks = AdPro_get_product_networks($product_id);
                        $country_networks = AdPro_filter_networks_by_country($all_networks, $main_country_iso);
                    
                        if (!empty($country_networks)) : 
                    ?>
                            <div class="country-networks">
                                <h3>רשתות סלולר נתמכות ב<?php echo esc_html($package_main_country); ?></h3>
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
                    <?php 
                        endif;
                    endif;
                    ?>
                    
                    <?php if (!empty($supported_countries) && count($supported_countries) > 0) : ?>
                        <div class="supported-countries">
                            <h3>מדינות נתמכות</h3>
                            <div class="country-flags">
                                <?php
                                // מגביל למספר מוגדר של דגלים
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
                    <input type="hidden" name="country" value="<?php echo esc_attr($package_main_country); ?>">
                    <button type="submit" class="buy-now">רכוש עכשיו</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- מודאל חבילות -->
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
    <?php
    
    return ob_get_clean();
}
add_shortcode('esim_specific_packages', 'AdPro_specific_packages_shortcode');