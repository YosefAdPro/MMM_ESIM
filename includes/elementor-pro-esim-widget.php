<?php
/**
 * ווידג'ט eSIM מתקדם לאלמנטור פרו
 * קובץ: includes/elementor-pro-esim-widget.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// וידוא שאלמנטור נטען לפני יצירת הווידג'ט
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class AdPro_eSIM_Pro_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'adpro_esim_pro_packages';
    }

    public function get_title() {
        return 'eSIM Packages Pro';
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['adpro-esim'];
    }

    public function get_script_depends() {
        return ['adpro-esim-pro-widget'];
    }

    public function get_style_depends() {
        return ['adpro-esim-pro-widget'];
    }

    protected function register_controls() {
        
        // ========== תוכן ==========
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'תוכן',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // מקור נתונים
        $this->add_control(
            'data_source',
            [
                'label' => 'מקור נתונים',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'dynamic',
                'options' => [
                    'dynamic' => 'דינמי (לפי URL)',
                    'specific' => 'מדינה ספציפית',
                    'multiple' => 'מדינות מרובות',
                    'custom' => 'חבילות ספציפיות',
                ],
            ]
        );

        // בחירת מדינה ספציפית
        $countries_options = [];
        $countries_mapping = AdPro_get_countries_mapping();
        foreach ($countries_mapping as $hebrew => $data) {
            $countries_options[$data['iso']] = $hebrew;
        }

        $this->add_control(
            'selected_country',
            [
                'label' => 'בחר מדינה',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $countries_options,
                'condition' => [
                    'data_source' => 'specific',
                ],
            ]
        );

        // מדינות מרובות
        $this->add_control(
            'selected_countries',
            [
                'label' => 'בחר מדינות',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $countries_options,
                'condition' => [
                    'data_source' => 'multiple',
                ],
            ]
        );

        // חבילות ספציפיות
        $this->add_control(
            'package_ids',
            [
                'label' => 'מזהי חבילות',
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => 'הזן מזהי חבילות מופרדים בפסיקים...',
                'condition' => [
                    'data_source' => 'custom',
                ],
            ]
        );

        // מגבלת חבילות
        $this->add_control(
            'packages_limit',
            [
                'label' => 'מספר חבילות מקסימלי',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 12,
            ]
        );

        $this->end_controls_section();

        // ========== פריסה ==========
        $this->start_controls_section(
            'layout_section',
            [
                'label' => 'פריסה',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout_type',
            [
                'label' => 'סוג פריסה',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'masonry',
                'options' => [
                    'grid' => 'רשת סטנדרטית',
                    'masonry' => 'רשת מזוריק',
                    'list' => 'רשימה',
                    'carousel' => 'קרוסלה',
                    'featured' => 'מובלט + רשת',
                ],
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => 'עמודות',
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
                    '6' => '6',
                ],
                'condition' => [
                    'layout_type!' => ['list', 'carousel'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .esim-packages-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => 'רווח בין פריטים',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 30,
                ],
                'selectors' => [
                    '{{WRAPPER}} .esim-packages-grid' => 'gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .esim-packages-list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ========== תכונות ==========
        $this->start_controls_section(
            'features_section',
            [
                'label' => 'תכונות',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_filters',
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
            'show_sorting',
            [
                'label' => 'הצג מיון',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label' => 'הצג חיפוש',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'enable_popup',
            [
                'label' => 'מודאל פרטים',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'enable_comparison',
            [
                'label' => 'השוואת חבילות',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'enable_favorites',
            [
                'label' => 'מועדפים',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        // ========== סגנון כרטיס ==========
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => 'סגנון כרטיס',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_style',
            [
                'label' => 'סגנון כרטיס',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'modern',
                'options' => [
                    'classic' => 'קלאסי',
                    'modern' => 'מודרני',
                    'minimal' => 'מינימלי',
                    'premium' => 'פרמיום',
                    'gradient' => 'גרדיאנט',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'card_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .esim-package-card',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .esim-package-card',
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label' => 'עיגול פינות',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 16,
                    'right' => 16,
                    'bottom' => 16,
                    'left' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .esim-package-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'selector' => '{{WRAPPER}} .esim-package-card',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 8,
                            'blur' => 25,
                            'spread' => 0,
                            'color' => 'rgba(0,0,0,0.1)',
                        ],
                    ],
                ],
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label' => 'ריווח פנימי',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .esim-package-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Hover Effects
        $this->add_control(
            'hover_animation',
            [
                'label' => 'אנימציית Hover',
                'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->end_controls_section();

        // ========== סגנון מחיר ==========
        $this->start_controls_section(
            'price_style_section',
            [
                'label' => 'סגנון מחיר',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .package-price',
                'fields_options' => [
                    'typography' => ['default' => 'yes'],
                    'font_size' => ['default' => ['size' => 32, 'unit' => 'px']],
                    'font_weight' => ['default' => '700'],
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => 'צבע מחיר',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FF6B35',
                'selectors' => [
                    '{{WRAPPER}} .package-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'price_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .package-price-container',
            ]
        );

        $this->end_controls_section();

        // ========== סגנון כפתור ==========
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => 'סגנון כפתור',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .package-buy-button',
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        // Normal State
        $this->start_controls_tab(
            'button_normal',
            [
                'label' => 'רגיל',
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => 'צבע טקסט',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#FFFFFF',
                'selectors' => [
                    '{{WRAPPER}} .package-buy-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .package-buy-button',
                'fields_options' => [
                    'background' => [
                        'default' => 'classic',
                    ],
                    'color' => [
                        'default' => '#4F46E5',
                    ],
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'button_hover',
            [
                'label' => 'Hover',
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => 'צבע טקסט',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .package-buy-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_hover_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .package-buy-button:hover',
            ]
        );

        $this->add_control(
            'button_hover_transform',
            [
                'label' => 'אפקט Hover',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'translateY(-2px)',
                'options' => [
                    'none' => 'ללא',
                    'translateY(-2px)' => 'הרמה',
                    'scale(1.05)' => 'הגדלה',
                    'rotateX(10deg)' => 'סיבוב X',
                ],
                'selectors' => [
                    '{{WRAPPER}} .package-buy-button:hover' => 'transform: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => 'ריווח פנימי',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .package-buy-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => 'עיגול פינות',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .package-buy-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ========== אנימציות ==========
        $this->start_controls_section(
            'animations_section',
            [
                'label' => 'אנימציות',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'entrance_animation',
            [
                'label' => 'אנימציית כניסה',
                'type' => \Elementor\Controls_Manager::ANIMATION,
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'entrance_animation_delay',
            [
                'label' => 'עיכוב (ms)',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 5000,
                'step' => 100,
                'condition' => [
                    'entrance_animation!' => '',
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'stagger_animation',
            [
                'label' => 'אנימציה מדורגת',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'כן',
                'label_off' => 'לא',
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'entrance_animation!' => '',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // קבלת חבילות לפי הגדרות
        $packages = $this->get_packages_by_settings($settings);
        
        if (empty($packages)) {
            echo '<div class="esim-no-packages">לא נמצאו חבילות</div>';
            return;
        }

        // טעינת assets
        $this->enqueue_widget_assets();
        
        // תחילת הרינדור
        $this->render_widget_container_start($settings);
        
        // פילטרים וחיפוש
        if ($settings['show_filters'] === 'yes' || $settings['show_search'] === 'yes') {
            $this->render_filters_section($settings);
        }
        
        // רשימת חבילות
        $this->render_packages_grid($packages, $settings);
        
        // מודאל (אם מופעל)
        if ($settings['enable_popup'] === 'yes') {
            $this->render_package_modal();
        }
        
        $this->render_widget_container_end($settings);
        
        // JavaScript
        $this->render_widget_script($packages, $settings);
    }

    private function get_packages_by_settings($settings) {
        $packages = [];
        
        switch ($settings['data_source']) {
            case 'dynamic':
                $country_param = get_query_var('AdPro_esim_country');
                if ($country_param) {
                    $country_data = AdPro_get_country_by_slug($country_param);
                    if ($country_data) {
                        $packages = AdPro_esim_get_packages($country_data['iso']);
                    }
                }
                break;
                
            case 'specific':
                if (!empty($settings['selected_country'])) {
                    $packages = AdPro_esim_get_packages($settings['selected_country']);
                }
                break;
                
            case 'multiple':
                if (!empty($settings['selected_countries'])) {
                    foreach ($settings['selected_countries'] as $country_iso) {
                        $country_packages = AdPro_esim_get_packages($country_iso);
                        $packages = array_merge($packages, $country_packages);
                    }
                }
                break;
                
            case 'custom':
                if (!empty($settings['package_ids'])) {
                    $package_ids = array_map('trim', explode(',', $settings['package_ids']));
                    foreach ($package_ids as $package_id) {
                        $package = AdPro_get_package_by_id('', $package_id);
                        if ($package) {
                            $packages[] = $package;
                        }
                    }
                }
                break;
        }
        
        // הגבלת מספר חבילות
        if (!empty($packages) && $settings['packages_limit']) {
            $packages = array_slice($packages, 0, $settings['packages_limit']);
        }
        
        return $packages;
    }

    private function enqueue_widget_assets() {
        wp_enqueue_style(
            'adpro-esim-pro-widget',
            ADPRO_ESIM_URL . 'public/assets/css/elementor-pro-widget.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'adpro-esim-pro-widget',
            ADPRO_ESIM_URL . 'public/assets/js/elementor-pro-widget.js',
            ['jquery', 'elementor-frontend'],
            '1.0.0',
            true
        );
    }

    private function render_widget_container_start($settings) {
        $classes = [
            'adpro-esim-pro-widget',
            'layout-' . $settings['layout_type'],
            'card-style-' . $settings['card_style']
        ];
        
        if (!empty($settings['hover_animation'])) {
            $classes[] = 'elementor-animation-' . $settings['hover_animation'];
        }
        
        echo '<div class="' . implode(' ', $classes) . '">';
    }

    private function render_filters_section($settings) {
        ?>
        <div class="esim-filters-section">
            <?php if ($settings['show_search'] === 'yes'): ?>
                <div class="esim-search-box">
                    <input type="text" id="esim-search" placeholder="חיפוש חבילות...">
                    <i class="eicon-search" aria-hidden="true"></i>
                </div>
            <?php endif; ?>
            
            <?php if ($settings['show_filters'] === 'yes'): ?>
                <div class="esim-filters-grid">
                    <div class="filter-group">
                        <label>נפח נתונים</label>
                        <select id="data-filter">
                            <option value="all">הכל</option>
                            <option value="1-5">1-5 GB</option>
                            <option value="5-10">5-10 GB</option>
                            <option value="10-20">10-20 GB</option>
                            <option value="20+">20+ GB</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>תקופת תוקף</label>
                        <select id="duration-filter">
                            <option value="all">הכל</option>
                            <option value="1-7">עד שבוע</option>
                            <option value="7-30">עד חודש</option>
                            <option value="30+">חודש ומעלה</option>
                        </select>
                    </div>
                    
                    <?php if ($settings['show_sorting'] === 'yes'): ?>
                        <div class="filter-group">
                            <label>מיון לפי</label>
                            <select id="sort-filter">
                                <option value="price-low">מחיר נמוך</option>
                                <option value="price-high">מחיר גבוה</option>
                                <option value="data-high">הכי הרבה נתונים</option>
                                <option value="duration-long">תוקף ארוך</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="filter-actions">
                        <button type="button" id="reset-filters" class="reset-btn">
                            <i class="eicon-close" aria-hidden="true"></i>
                            איפוס
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_packages_grid($packages, $settings) {
        $grid_classes = [
            'esim-packages-grid',
            'layout-' . $settings['layout_type']
        ];
        
        ?>
        <div class="<?php echo implode(' ', $grid_classes); ?>">
            <?php foreach ($packages as $index => $package): ?>
                <?php $this->render_package_card($package, $settings, $index); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_package_card($package, $settings, $index = 0) {
        // חילוץ נתוני חבילה
        $package_data = $this->extract_package_data($package);
        
        $card_classes = [
            'esim-package-card',
            'card-style-' . $settings['card_style']
        ];
        
        if (!empty($settings['entrance_animation'])) {
            $card_classes[] = 'elementor-invisible';
            
            $animation_delay = 0;
            if ($settings['stagger_animation'] === 'yes') {
                $animation_delay = $index * 100 + intval($settings['entrance_animation_delay']);
            } else {
                $animation_delay = intval($settings['entrance_animation_delay']);
            }
        }
        
        ?>
        <div class="<?php echo implode(' ', $card_classes); ?>" 
             data-package-id="<?php echo esc_attr($package['productId']); ?>"
             <?php if (!empty($settings['entrance_animation'])): ?>
                data-settings='{"_animation":"<?php echo $settings['entrance_animation']; ?>","_animation_delay":<?php echo $animation_delay; ?>}'
             <?php endif; ?>>
            
            <?php if ($settings['enable_favorites'] === 'yes'): ?>
                <div class="package-favorite">
                    <i class="eicon-heart-o" aria-hidden="true"></i>
                </div>
            <?php endif; ?>
            
            <?php if ($settings['enable_comparison'] === 'yes'): ?>
                <div class="package-compare">
                    <i class="eicon-select" aria-hidden="true"></i>
                </div>
            <?php endif; ?>
            
            <div class="package-header">
                <div class="package-country">
                    <img src="https://flagcdn.com/32x24/<?php echo strtolower($package_data['country_iso']); ?>.png" 
                         alt="<?php echo esc_attr($package_data['country_name']); ?>">
                    <span><?php echo esc_html($package_data['country_name']); ?></span>
                </div>
                
                <?php if ($package_data['countries_count'] > 1): ?>
                    <div class="package-multi-country">
                        +<?php echo $package_data['countries_count'] - 1; ?> מדינות
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="package-body">
                <h3 class="package-title"><?php echo esc_html($package_data['title']); ?></h3>
                
                <div class="package-price-container">
                    <div class="package-price">
                        <span class="currency"><?php echo $package_data['currency_symbol']; ?></span>
                        <span class="amount"><?php echo $package_data['price']; ?></span>
                    </div>
                    <?php if (!empty($package_data['original_price']) && $package_data['original_price'] != $package_data['price']): ?>
                        <div class="package-original-price">
                            <?php echo $package_data['currency_symbol'] . $package_data['original_price']; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="package-specs">
                    <?php if (!empty($package_data['data_limit'])): ?>
                        <div class="spec-item data-spec">
                            <i class="eicon-database" aria-hidden="true"></i>
                            <span><?php echo $package_data['data_limit'] . ' ' . $package_data['data_unit']; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($package_data['validity_days'])): ?>
                        <div class="spec-item duration-spec">
                            <i class="eicon-clock" aria-hidden="true"></i>
                            <span><?php echo $package_data['validity_days']; ?> ימים</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($package_data['provider_name'])): ?>
                        <div class="spec-item provider-spec">
                            <i class="eicon-user-circle-o" aria-hidden="true"></i>
                            <span><?php echo esc_html($package_data['provider_name']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($package_data['networks'])): ?>
                    <div class="package-networks">
                        <div class="networks-label">רשתות נתמכות:</div>
                        <div class="networks-list">
                            <?php foreach (array_slice($package_data['networks'], 0, 3) as $network): ?>
                                <span class="network-badge">
                                    <?php echo esc_html($network['brand']); ?>
                                    <?php if ($network['is5G']): ?>
                                        <span class="tech-badge">5G</span>
                                    <?php elseif ($network['is4G']): ?>
                                        <span class="tech-badge">4G</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                            
                            <?php if (count($package_data['networks']) > 3): ?>
                                <span class="networks-more">+<?php echo count($package_data['networks']) - 3; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="package-footer">
                <?php if ($settings['enable_popup'] === 'yes'): ?>
                    <button type="button" class="package-details-btn" data-package-id="<?php echo esc_attr($package['productId']); ?>">
                        <i class="eicon-preview-medium" aria-hidden="true"></i>
                        פרטים נוספים
                    </button>
                <?php endif; ?>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="package-purchase-form">
                    <input type="hidden" name="action" value="AdPro_process_package">
                    <input type="hidden" name="package_id" value="<?php echo esc_attr($package['productId']); ?>">
                    <input type="hidden" name="country" value="<?php echo esc_attr($package_data['country_name']); ?>">
                    
                    <button type="submit" class="package-buy-button">
                        <i class="eicon-cart" aria-hidden="true"></i>
                        <span>רכוש עכשיו</span>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }

    private function extract_package_data($package) {
        $data = [
            'title' => '',
            'price' => 0,
            'original_price' => 0,
			'currency_symbol' => '$',
            'data_unit' => '',
            'validity_days' => '',
            'provider_name' => '',
            'country_name' => '',
            'country_iso' => '',
            'countries_count' => 0,
            'networks' => []
        ];
        
        // חילוץ פרטי חבילה
        if (isset($package['productDetails']) && is_array($package['productDetails'])) {
            foreach ($package['productDetails'] as $detail) {
                switch ($detail['name']) {
                    case 'PLAN_TITLE':
                        $data['title'] = $detail['value'];
                        break;
                    case 'PLAN_DATA_LIMIT':
                        $data['data_limit'] = $detail['value'];
                        break;
                    case 'PLAN_DATA_UNIT':
                        $data['data_unit'] = $detail['value'];
                        break;
                    case 'PLAN_VALIDITY':
                        $data['validity_days'] = round(intval($detail['value']) / 24);
                        break;
                }
            }
        }
        
        // מחיר ומטבע
        $data['price'] = isset($package['retailPrice']) ? $package['retailPrice'] : 0;
        $currency = isset($package['currencyCode']) ? $package['currencyCode'] : 'USD';
        
        switch ($currency) {
            case 'USD':
                $data['currency_symbol'] = '$';
                break;
            case 'EUR':
                $data['currency_symbol'] = '€';
                break;
            case 'GBP':
                $data['currency_symbol'] = '£';
                break;
            default:
                $data['currency_symbol'] = $currency;
        }
        
        // ספק
        $data['provider_name'] = isset($package['providerName']) ? $package['providerName'] : '';
        
        // מדינות
        if (isset($package['countries']) && is_array($package['countries'])) {
            $data['countries_count'] = count($package['countries']);
            
            if (!empty($package['countries'][0])) {
                $main_country_iso = $package['countries'][0];
                if (strpos($main_country_iso, '-') !== false) {
                    $main_country_iso = explode('-', $main_country_iso)[0];
                }
                
                $data['country_iso'] = $main_country_iso;
                
                // חיפוש שם המדינה
                $countries_mapping = AdPro_get_countries_mapping();
                foreach ($countries_mapping as $hebrew => $country_data) {
                    if ($country_data['iso'] === $main_country_iso) {
                        $data['country_name'] = $hebrew;
                        break;
                    }
                }
            }
        }
        
        // רשתות (אם זמין)
        if (function_exists('AdPro_get_product_networks')) {
            $all_networks = AdPro_get_product_networks($package['productId']);
            if (!empty($all_networks) && !empty($data['country_iso'])) {
                $data['networks'] = AdPro_filter_networks_by_country($all_networks, $data['country_iso']);
            }
        }
        
        // כותרת אוטומטית אם חסרה
        if (empty($data['title'])) {
            $data['title'] = 'חבילת eSIM';
            if (!empty($data['country_name'])) {
                $data['title'] .= ' - ' . $data['country_name'];
            }
            if (!empty($data['data_limit']) && !empty($data['data_unit'])) {
                $data['title'] .= ' ' . $data['data_limit'] . $data['data_unit'];
            }
        }
        
        return $data;
    }

    private function render_package_modal() {
        ?>
        <div id="esim-package-modal" class="esim-modal elementor-invisible">
            <div class="modal-overlay"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title"></h2>
                    <button type="button" class="modal-close">
                        <i class="eicon-close" aria-hidden="true"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="modal-package-info">
                        <div class="info-section price-section">
                            <div class="modal-price"></div>
                            <div class="modal-specs"></div>
                        </div>
                        
                        <div class="info-section networks-section">
                            <h4>רשתות נתמכות</h4>
                            <div class="modal-networks"></div>
                        </div>
                        
                        <div class="info-section countries-section">
                            <h4>מדינות נתמכות</h4>
                            <div class="modal-countries"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="modal-actions">
                        <button type="button" class="modal-close-btn">סגור</button>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="modal-purchase-form">
                            <input type="hidden" name="action" value="AdPro_process_package">
                            <input type="hidden" name="package_id" class="modal-package-id">
                            <input type="hidden" name="country" class="modal-country">
                            
                            <button type="submit" class="modal-buy-button">
                                <i class="eicon-cart" aria-hidden="true"></i>
                                רכוש עכשיו
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_widget_container_end($settings) {
        echo '</div>';
    }

    private function render_widget_script($packages, $settings) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // נתוני חבילות
            window.esimPackagesData = <?php echo json_encode($packages); ?>;
            window.esimWidgetSettings = <?php echo json_encode($settings); ?>;
            
            // אתחול הווידג'ט
            if (typeof AdProESIMProWidget !== 'undefined') {
                new AdProESIMProWidget(<?php echo json_encode([
                    'selector' => '.adpro-esim-pro-widget',
                    'packages' => $packages,
                    'settings' => $settings
                ]); ?>);
            }
        });
        </script>
        <?php
    }
}