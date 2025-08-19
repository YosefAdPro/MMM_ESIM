<?php
/**
 * Widget מתקדם לאלמנטור - דפי מדינות eSIM
 */

if (!defined('ABSPATH')) {
    exit;
}

class AdPro_eSIM_Country_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'adpro_esim_country_page';
    }

    public function get_title() {
        return 'eSIM Country Page';
    }

    public function get_icon() {
        return 'eicon-globe';
    }

    public function get_categories() {
        return ['adpro-esim'];
    }

    public function get_keywords() {
        return ['esim', 'country', 'packages', 'adpro'];
    }

    protected function register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'תוכן',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_country_header',
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
            'custom_title_template',
            [
                'label' => 'תבנית כותרת מותאמת',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'חבילות eSIM עבור {country}',
                'placeholder' => 'השתמש ב-{country} עבור שם המדינה',
                'condition' => [
                    'show_country_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_country_flag',
            [
                'label' => 'הצג דגל מדינה',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'show_country_header' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_country_content',
            [
                'label' => 'הצג תוכן מותאם למדינה',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'fallback_content',
            [
                'label' => 'תוכן ברירת מחדל',
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => 'גלה את חבילות ה-eSIM הטובות ביותר ל{country}. חבילות גלישה לכל סוגי הנסיעות במחירים אטרקטיביים.',
                'condition' => [
                    'show_country_content' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Packages Section
        $this->start_controls_section(
            'packages_section',
            [
                'label' => 'חבילות',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_packages_filter',
            [
                'label' => 'הצג פילטר חבילות',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'packages_layout',
            [
                'label' => 'פריסת חבילות',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => 'רשת',
                    'list' => 'רשימה',
                    'masonry' => 'מזוריק',
                ],
            ]
        );

        $this->add_responsive_control(
            'packages_columns',
            [
                'label' => 'מספר עמודות',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ],
                'condition' => [
                    'packages_layout' => ['grid', 'masonry'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .packages-list' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_control(
            'packages_limit',
            [
                'label' => 'מגבלת חבילות',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 20,
            ]
        );

        $this->add_control(
            'show_package_networks',
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
            'show_supported_countries',
            [
                'label' => 'הצג מדינות נתמכות',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section - Header
        $this->start_controls_section(
            'style_header_section',
            [
                'label' => 'עיצוב כותרת',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_country_header' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'header_typography',
                'selector' => '{{WRAPPER}} .elementor-country-header h1',
            ]
        );

        $this->add_control(
            'header_color',
            [
                'label' => 'צבע טקסט',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-country-header h1' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'header_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .elementor-country-header',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'header_border',
                'selector' => '{{WRAPPER}} .elementor-country-header',
            ]
        );

        $this->add_responsive_control(
            'header_padding',
            [
                'label' => 'ריווח פנימי',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-country-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'header_margin',
            [
                'label' => 'ריווח חיצוני',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-country-header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Packages
        $this->start_controls_section(
            'style_packages_section',
            [
                'label' => 'עיצוב חבילות',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'packages_gap',
            [
                'label' => 'רווח בין חבילות',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .packages-list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'package_border',
                'selector' => '{{WRAPPER}} .package',
            ]
        );

        $this->add_responsive_control(
            'package_border_radius',
            [
                'label' => 'עיגול פינות',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .package' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'package_shadow',
                'selector' => '{{WRAPPER}} .package',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // קבלת פרטי המדינה מה-URL
        $country_param = strtolower(get_query_var('AdPro_esim_country'));
        
        if (empty($country_param)) {
            // אם אנחנו בעורך אלמנטור, הצג דוגמה
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $country_param = 'israel';
                echo '<div class="elementor-alert elementor-alert-info">תצוגת עריכה: מציג דוגמה עבור ישראל</div>';
            } else {
                echo '<div class="elementor-alert elementor-alert-warning">שגיאה: לא נמצא פרמטר מדינה ב-URL</div>';
                return;
            }
        }

        $country_data = AdPro_get_country_by_slug($country_param);
        
        if (!$country_data) {
            echo '<div class="elementor-alert elementor-alert-danger">מדינה לא נמצאה: ' . esc_html($country_param) . '</div>';
            return;
        }

        // קבלת חבילות
        $packages = AdPro_esim_get_packages($country_data['iso']);
        
        // הגבלת מספר חבילות
        if ($settings['packages_limit'] && count($packages) > $settings['packages_limit']) {
            $packages = array_slice($packages, 0, $settings['packages_limit']);
        }

        // טעינת assets
        wp_enqueue_style('AdPro-esim-style', ADPRO_ESIM_URL . 'public/assets/css/style.css');
        wp_enqueue_script('AdPro-esim-script', ADPRO_ESIM_URL . 'public/assets/js/frontend.js', ['jquery'], null, true);
        
        wp_localize_script('AdPro-esim-script', 'adminAjaxUrl', admin_url('admin-ajax.php'));
        wp_localize_script('AdPro-esim-script', 'AdPro_esim_ajax', [
            'site_url' => home_url(),
            'countries' => AdPro_get_countries_mapping(),
        ]);

        // תחילת הרינדור
        ?>
        <div class="elementor-adpro-esim-country">
            
            <?php if ($settings['show_country_header'] === 'yes'): ?>
                <div class="elementor-country-header">
                    <h1>
                        <?php if ($settings['show_country_flag'] === 'yes'): ?>
                            <img src="https://flagcdn.com/48x36/<?php echo strtolower($country_data['iso']); ?>.png" 
                                 alt="<?php echo esc_attr($country_data['hebrew']); ?>"
                                 class="country-flag">
                        <?php endif; ?>
                        <?php 
                        $title = str_replace('{country}', $country_data['hebrew'], $settings['custom_title_template']);
                        echo esc_html($title); 
                        ?>
                    </h1>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_country_content'] === 'yes'): ?>
                <div class="elementor-country-content">
                    <?php
                    $saved_content = get_option('AdPro_esim_country_content', []);
                    $hebrew_country = $country_data['hebrew'];
                    
                    if (!empty($saved_content[$hebrew_country]['image']) || !empty($saved_content[$hebrew_country]['text'])) {
                        // תוכן מותאם קיים
                        if (!empty($saved_content[$hebrew_country]['image'])) {
                            echo '<img src="' . esc_url($saved_content[$hebrew_country]['image']) . '" alt="' . esc_attr($hebrew_country) . '" class="country-custom-image">';
                        }
                        if (!empty($saved_content[$hebrew_country]['text'])) {
                            echo '<div class="country-custom-text">' . wp_kses_post($saved_content[$hebrew_country]['text']) . '</div>';
                        }
                    } else {
                        // תוכן ברירת מחדל
                        $fallback = str_replace('{country}', $country_data['hebrew'], $settings['fallback_content']);
                        echo '<div class="country-default-content">' . wp_kses_post($fallback) . '</div>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_packages_filter'] === 'yes' && !empty($packages)): ?>
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

            <?php if (!empty($packages)): ?>
                <div class="packages-list layout-<?php echo esc_attr($settings['packages_layout']); ?>">
                    <?php foreach ($packages as $package): ?>
                        <div class="package package-clickable" data-package-id="<?php echo esc_attr($package['productId']); ?>">
                            <?php
                            // חילוץ נתוני החבילה
                            $data_limit = '';
                            $data_unit = '';
                            $validity_days = '';
                            $price = isset($package['retailPrice']) ? $package['retailPrice'] : '';
                            $currency = isset($package['currencyCode']) ? $package['currencyCode'] : '';
                            $supported_countries = isset($package['countries']) ? $package['countries'] : [];
                            
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
                            $custom_title = "חבילת גלישה ל" . $country_data['hebrew'];
                            if (count($supported_countries) > 1) {
                                $custom_title .= " ועוד " . (count($supported_countries) - 1) . " מדינות";
                            }
                            if (!empty($data_limit) && !empty($data_unit)) {
                                $custom_title .= " עם " . $data_limit . " " . $data_unit;
                            }
                            if (!empty($validity_days)) {
                                $custom_title .= " למשך " . $validity_days . " ימים";
                            }
                            ?>
                            
                            <h2><?php echo esc_html($custom_title); ?></h2>
                            
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

                                <?php if ($settings['show_package_networks'] === 'yes'): ?>
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

                                <?php if ($settings['show_supported_countries'] === 'yes' && !empty($supported_countries)): ?>
                                    <div class="supported-countries">
                                        <h4>מדינות נתמכות</h4>
                                        <div class="country-flags">
                                            <?php
                                            $max_flags = 15;
                                            $count = 0;
                                            foreach ($supported_countries as $country_code):
                                                if ($count >= $max_flags) {
                                                    echo '<div class="more-countries">+' . (count($supported_countries) - $max_flags) . ' נוספות</div>';
                                                    break;
                                                }
                                                $display_code = explode('-', $country_code)[0];
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
                                <input type="hidden" name="country" value="<?php echo esc_attr($country_data['hebrew']); ?>">
                                <button type="submit" class="buy-now">רכוש עכשיו</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-packages">
                    <p>לא נמצאו חבילות זמינות עבור <?php echo esc_html($country_data['hebrew']); ?> כרגע.</p>
                </div>
            <?php endif; ?>

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
        <?php
    }
}