<?php
function AdPro_esim_display_frontend() {
    wp_enqueue_style('AdPro-esim-style', ADPRO_ESIM_URL . 'public/assets/css/style.css');
    wp_enqueue_script('AdPro-esim-script', ADPRO_ESIM_URL . 'public/assets/js/frontend.js', ['jquery'], null, true);
   
    
    $countries_mapping = AdPro_get_countries_mapping();
    wp_localize_script('AdPro-esim-script', 'AdPro_esim_ajax', [
        'site_url' => home_url(),
        'countries' => $countries_mapping,
    ]);

    ?>
    <div id="AdPro-esim-container">
         <div class="search-container">
            <h2>חיפוש חבילות eSIM לפי מדינה</h2>
            <div class="search-box">
                <input type="text" id="country-search" placeholder="הקלד שם מדינה..." autocomplete="off">
                <div id="country-suggestions" class="suggestions"></div>
            </div>
            
            <div class="popular-countries">
                <h3>מדינות פופולריות</h3>
                <div class="country-grid">
                    <?php
                    // במקום מערך קבוע, נשתמש באפשרות מהגדרות
					$popular_countries = get_option('AdPro_popular_countries', []);

                    
                    foreach ($popular_countries as $country) {
                        if (isset($countries_mapping[$country])) {
                            $data = $countries_mapping[$country];
                            echo '<a href="' . home_url('/esim/' . $data['slug']) . '" class="country-item">';
                            echo '<img src="https://flagcdn.com/48x36/' . strtolower($data['iso']) . '.png" alt="' . esc_attr($country) . '">';
                            echo '<span>' . esc_html($country) . '</span>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}