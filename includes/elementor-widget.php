<?php
/**
 * Elementor eSIM Widget
 * הוסף קובץ זה: includes/elementor-widget.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class AdPro_eSIM_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'adpro_esim_packages';
    }

    public function get_title() {
        return 'חבילות eSIM דינמיות';
    }

    public function get_icon() {
        return 'eicon-globe';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        
        // תוכן
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'הגדרות תוכן',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // בחירת מדינה דינמית
        $this->add_control(
            'country_source',
            [
                'label' => 'מקור המדינה',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'url',
                'options' => [
                    'url' => 'מה-URL (דינמי)',
                    'manual' => 'בחירה ידנית',
                ],
            ]
        );

        // בחירת מדינה ידנית
        $countries_options = [];
        $countries_mapping = AdPro_get_countries_mapping();
        foreach ($countries_mapping as $hebrew => $data) {
            $countries_options[$data['slug']] = $hebrew;
        }

        $this->add_control(
            'selected_country',
            [
                'label' => 'בחר מדינה',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $countries_options,
                'condition' => [
                    'country_source' => 'manual',
                ],
            ]
        );

        // הגדרות תצוגה
        $this->add_control(
            'show_header',
            [
                'label' => 'הצג כותרת מדינה',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_filter',
            [
                'label' => 'הצג פילטרים',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_networks',
            [
                'label' => 'הצג רשתות סלולריות',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'packages_limit',
            [
                'label' => 'מספר חבילות מקסימלי',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 20,
            ]
        );

        $this->end_controls_section();

        // סגנון
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'עיצוב',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => 'פריסה',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => 'רשת',
                    'list' => 'רשימה',
                    'carousel' => 'קרוסלה',
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => 'עמודות',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'layout' => 'grid',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // קביעת המדינה
        $country_slug = '';
        if ($settings['country_source'] === 'url') {
            // קבל מדינה מה-URL
            $country_slug = get_query_var('AdPro_esim_country');
            if (empty($country_slug)) {
                // אם אין מדינה ב-URL, נסה לקבל מה-path
                $request_uri = $_SERVER['REQUEST_URI'];
                if (preg_match('/\/esim\/([^\/]+)/', $request_uri, $matches)) {
                    $country_slug = $matches[1];
                }
            }
        } else {
            $country_slug = $settings['selected_country'];
        }

        if (empty($country_slug)) {
            echo '<div class="elementor-alert elementor-alert-warning">לא נמצאה מדינה. ודא שהדף נטען בכתובת נכונה או בחר מדינה ידנית.</div>';
            return;
        }

        // קבל נתוני המדינה
        $country_data = AdPro_get_country_by_slug($country_slug);
        if (!$country_data) {
            echo '<div class="elementor-alert elementor-alert-danger">מדינה לא נמצאה: ' . esc_html($country_slug) . '</div>';
            return;
        }

        // קבל חבילות
        $packages = AdPro_esim_get_packages($country_data['iso']);
        if (empty($packages)) {
            echo '<div class="elementor-alert elementor-alert-info">אין חבילות זמינות עבור ' . esc_html($country_data['hebrew']) . '</div>';
            return;
        }

        // הגבל מספר חבילות
        if ($settings['packages_limit'] && count($packages) > $settings['packages_limit']) {
            $packages = array_slice($packages, 0, $settings['packages_limit']);
        }

        // טען CSS ו-JS
        wp_enqueue_style('AdPro-esim-style', ADPRO_ESIM_URL . 'public/assets/css/style.css');
        wp_enqueue_script('AdPro-esim-script', ADPRO_ESIM_URL . 'public/assets/js/frontend.js', ['jquery'], null, true);
        
        wp_localize_script('AdPro-esim-script', 'adminAjaxUrl', admin_url('admin-ajax.php'));
        wp_localize_script('AdPro-esim-script', 'AdPro_esim_ajax', [
            'site_url' => home_url(),
            'countries' => AdPro_get_countries_mapping(),
        ]);

        // הצג תוכן
        ?>
        <div class="adpro-esim-elementor-widget" data-layout="<?php echo esc_attr($settings['layout']); ?>" data-columns="<?php echo esc_attr($settings['columns']); ?>">
            
            <?php if ($settings['show_header'] === 'yes'): ?>
                <div class="country-header">
                    <h2>
                        <img src="https://flagcdn.com/48x36/<?php echo strtolower($country_data['iso']); ?>.png" alt="<?php echo esc_attr($country_data['hebrew']); ?>">
                        חבילות eSIM עבור <?php echo esc_html($country_data['hebrew']); ?>
                    </h2>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_filter'] === 'yes'): ?>
                <div class="package-filters">
                    <div class="filter-title-container">
                        <h3>סינון חבילות</h3>
                        <button id="reset-filters" class="reset-button">איפוס סינון</button>
                    </div>
                    
                    <button class="filter-mobile-toggle">פתח סינון מתקדם</button>
                    
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
            <?php endif; ?>

            <div class="packages-list layout-<?php echo esc_attr($settings['layout']); ?> columns-<?php echo esc_attr($settings['columns']); ?>">
                <?php foreach ($packages as $package): ?>
                    <div class="package package-clickable" data-package-id="<?php echo esc_attr($package['productId']); ?>">
                        <?php
                        // חילוץ נתונים
                        $data_limit = '';
                        $data_unit = '';
                        $validity_days = '';
                        $price = isset($package['retailPrice']) ? $package['retailPrice'] : '';
                        $currency = isset($package['currencyCode']) ? $package['currencyCode'] : '';
                        
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
                        
                        // כותרת מותאמת
                        $title = "חבילת גלישה ל" . $country_data['hebrew'];
                        if (!empty($data_limit) && !empty($data_unit)) {
                            $title .= " עם " . $data_limit . " " . $data_unit;
                        }
                        if (!empty($validity_days)) {
                            $title .= " למשך " . $validity_days . " ימים";
                        }
                        ?>
                        
                        <h3><?php echo esc_html($title); ?></h3>
                        
                        <div class="package-details">
                            <p class="price">
                                <?php
                                $currency_symbol = ($currency === 'USD') ? '$' : 
                                                   (($currency === 'EUR') ? '€' : 
                                                   (($currency === 'GBP') ? '£' : $currency));
                                echo '<span class="currency-icon">' . $currency_symbol . '</span> ' . esc_html($price);
                                ?>
                            </p>
                            
                            <div class="package-info-row">
                                <?php if (!empty($data_limit) && !empty($data_unit)): ?>
                                    <div class="info-item">
                                        <span class="info-label">נתונים:</span>
                                        <span class="info-value"><?php echo esc_html($data_limit . $data_unit); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($validity_days)): ?>
                                    <div class="info-item">
                                        <span class="info-label">תוקף:</span>
                                        <span class="info-value"><?php echo esc_html($validity_days); ?> ימים</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($settings['show_networks'] === 'yes'): ?>
                                <?php
                                $all_networks = AdPro_get_product_networks($package['productId']);
                                $country_networks = AdPro_filter_networks_by_country($all_networks, $country_data['iso']);
                                ?>
                                <?php if (!empty($country_networks)): ?>
                                    <div class="country-networks">
                                        <h4>רשתות נתמכות</h4>
                                        <div class="networks-grid">
                                            <?php foreach ($country_networks as $network): ?>
                                                <div class="network-item">
                                                    <span class="network-name">
                                                        <?php echo esc_html($network['brand']); ?>
                                                        <?php if (isset($network['is5G']) && $network['is5G']): ?>
                                                            <span class="network-badge">5G</span>
                                                        <?php elseif (isset($network['is4G']) && $network['is4G']): ?>
                                                            <span class="network-badge network-4g">4G</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="AdPro_process_package">
                            <input type="hidden" name="package_id" value="<?php echo esc_attr($package['productId']); ?>">
                            <input type="hidden" name="country" value="<?php echo esc_attr($country_data['hebrew']); ?>">
                            <button type="submit" class="buy-now">רכוש עכשיו</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- מודאל -->
            <div id="package-details-modal" class="package-modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <div id="package-modal-content"></div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            var packageData = <?php echo json_encode($packages); ?>;
            var countriesMapping = <?php echo json_encode(AdPro_get_countries_mapping()); ?>;
            var hebrewCountry = "<?php echo esc_js($country_data['hebrew']); ?>";
        </script>

        <style>
            .layout-grid.columns-1 .packages-list { grid-template-columns: 1fr; }
            .layout-grid.columns-2 .packages-list { grid-template-columns: repeat(2, 1fr); }
            .layout-grid.columns-3 .packages-list { grid-template-columns: repeat(3, 1fr); }
            .layout-grid.columns-4 .packages-list { grid-template-columns: repeat(4, 1fr); }
            .layout-list .packages-list { display: flex; flex-direction: column; }
            .layout-carousel .packages-list { display: flex; overflow-x: auto; gap: 20px; }
            .layout-carousel .package { min-width: 300px; }
        </style>
        <?php
    }
}

// רישום הווידג'ט
function register_adpro_esim_elementor_widgets($widgets_manager) {
    require_once(__DIR__ . '/elementor-widget.php');
    $widgets_manager->register(new \AdPro_eSIM_Elementor_Widget());
}
add_action('elementor/widgets/register', 'register_adpro_esim_elementor_widgets');