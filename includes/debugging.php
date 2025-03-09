<?php
/**
 * Debugging functions for AdPro eSIM plugin
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}



// פעולה לבדיקת שערי תשלום לפני פרטי לקוח
add_action('woocommerce_checkout_before_customer_details', function() {
    global $woocommerce;
    error_log('Available payment methods: ' . print_r(WC()->payment_gateways->get_available_payment_gateways(), true));
});

// בדיקת שדות תשלום - הוסף את זה לפונקציה payment_fields בקובץ class-wc-icount-gateway.php
// function payment_fields() {
//     error_log('Drawing payment fields for iCount gateway');
//     ...


// בדיקה ישירה של gateway ב-debugging.php
add_action('init', function() {
    if (isset($_GET['test_icount']) && current_user_can('manage_options')) {
        if (!class_exists('WC_iCount_Gateway')) {
            require_once ADPRO_ESIM_PATH . 'includes/class-wc-icount-gateway.php';
        }
        
        $gateway = new WC_iCount_Gateway();
        echo "<pre>";
        echo "Gateway ID: " . $gateway->id . "\n";
        echo "Gateway Enabled: " . $gateway->enabled . "\n";
        echo "Is Available: " . ($gateway->is_available() ? 'Yes' : 'No') . "\n";
        echo "</pre>";
        exit;
    }
});


// בדיקת מסננים
add_action('plugins_loaded', function() {
    error_log('Checking if payment_gateways filter exists: ' . (has_filter('woocommerce_payment_gateways') ? 'Yes' : 'No'));
});

add_action('woocommerce_payment_gateways', function($gateways) {
    error_log('Payment gateways before our filter: ' . print_r($gateways, true));
    return $gateways;
}, 5); // priority 5 runs before most other filters




add_action('woocommerce_before_checkout_form', function() {
    $cart_items = WC()->cart->get_cart();
    error_log('Items in cart: ' . count($cart_items));
    
    foreach ($cart_items as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        error_log('Product in cart: ' . $product->get_name() . ', Type: ' . $product->get_type());
    }
    
    // בדוק אילו שערי תשלום תומכים במוצרים בעגלה
    if (class_exists('WC_iCount_Gateway')) {
        $gateway = new WC_iCount_Gateway();
        error_log('iCount gateway supports products: ' . print_r($gateway->supports, true));
    }
});


add_action('woocommerce_before_checkout_form', function() {
    $customer_country = WC()->customer->get_billing_country();
    error_log('Customer country: ' . $customer_country);
    
    if (class_exists('WC_iCount_Gateway')) {
        $gateway = new WC_iCount_Gateway();
        $countries = method_exists($gateway, 'get_option') ? $gateway->get_option('countries', array()) : array();
        error_log('iCount gateway countries: ' . print_r($countries, true));
    }
});





// הוסף את הקוד הזה לקובץ debugging.php

/**
 * בדיקת תוכן עגלה לפני הקופה
 */
add_action('woocommerce_before_checkout_form', function() {
    error_log('===== CHECKOUT DEBUG =====');
    
    // בדיקת שער תשלום
    if (class_exists('WC_iCount_Gateway')) {
        $gateway = new WC_iCount_Gateway();
        error_log('iCount Gateway Class Exists. Enabled: ' . $gateway->enabled);
        error_log('iCount Gateway Available: ' . ($gateway->is_available() ? 'Yes' : 'No'));
    } else {
        error_log('iCount Gateway Class MISSING!');
    }
    
    // בדיקת תכולת עגלה
    if (WC()->cart) {
        error_log('Cart Items: ' . count(WC()->cart->get_cart()));
        
        foreach (WC()->cart->get_cart() as $key => $item) {
            error_log('Item: ' . json_encode([
                'product_name' => $item['data']->get_name(),
                'esim_package_id' => isset($item['esim_package_id']) ? $item['esim_package_id'] : 'Not Set',
                'esim_country' => isset($item['esim_country']) ? $item['esim_country'] : 'Not Set'
            ]));
        }
    } else {
        error_log('Cart Not Initialized!');
    }
    
    // בדיקת שערי תשלום זמינים
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    error_log('Available Payment Gateways: ' . implode(', ', array_keys($available_gateways)));
    
    // בדיקת הגדרות
    error_log('iCount Settings: ' . json_encode([
        'company_id' => get_option('AdPro_icount_company_id', 'Not Set'),
        'user' => get_option('AdPro_icount_user', 'Not Set'),
        'api_key' => !empty(get_option('AdPro_api_key')) ? 'Set' : 'Not Set',
        'merchant_id' => !empty(get_option('AdPro_merchant_id')) ? 'Set' : 'Not Set'
    ]));
}, 5);





add_action('woocommerce_checkout_before_customer_details', function() {
    // בדיקת שערי תשלום פעילים
    $payment_gateways = WC()->payment_gateways->payment_gateways();
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    error_log('=== PAYMENT GATEWAYS DEBUG ===');
    error_log('ALL REGISTERED GATEWAYS: ' . print_r(array_keys($payment_gateways), true));
    error_log('AVAILABLE GATEWAYS: ' . print_r(array_keys($available_gateways), true));
    
    // בדיקה ספציפית של שער iCount
    if (isset($payment_gateways['icount_gateway'])) {
        $gateway = $payment_gateways['icount_gateway'];
        error_log('iCount Gateway Found:');
        error_log(' - ID: ' . $gateway->id);
        error_log(' - Enabled: ' . $gateway->enabled);
        error_log(' - Available: ' . ($gateway->is_available() ? 'Yes' : 'No'));
        error_log(' - Has fields: ' . ($gateway->has_fields ? 'Yes' : 'No'));
        error_log(' - Supports: ' . print_r($gateway->supports, true));
    } else {
        error_log('iCount Gateway NOT FOUND in registered gateways!');
    }
});


add_action('woocommerce_checkout_before_customer_details', function() {
    // בדיקת מוצרים בעגלה
    $cart = WC()->cart;
    error_log('=== CART CONTENTS DEBUG ===');
    error_log('Cart Total: ' . $cart->get_cart_contents_total());
    error_log('Cart Items: ' . $cart->get_cart_contents_count());
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        error_log('Product: ' . $product->get_name());
        error_log(' - Type: ' . $product->get_type());
        error_log(' - Virtual: ' . ($product->is_virtual() ? 'Yes' : 'No'));
        
        // בדיקת מטא דאטה
        foreach ($cart_item as $key => $value) {
            if (strpos($key, 'esim_') === 0) {
                if (is_array($value) || is_object($value)) {
                    error_log(' - Meta ' . $key . ': ' . json_encode($value));
                } else {
                    error_log(' - Meta ' . $key . ': ' . $value);
                }
            }
        }
    }
});








// הוסף בקובץ debugging.php
add_action('woocommerce_review_order_before_payment', function() {
    error_log('==== PAYMENT METHODS DISPLAY DEBUG ====');
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    foreach ($available_gateways as $gateway_id => $gateway) {
        error_log("Gateway: $gateway_id, Title: {$gateway->title}, Enabled: {$gateway->enabled}");
        
        // בדוק אם יש לשער שיטות תצוגה
        if (method_exists($gateway, 'payment_fields')) {
            error_log("$gateway_id has payment_fields method");
        }
    }
});





add_action('wp_footer', function() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('Available payment methods:');
                $('.wc_payment_methods li').each(function() {
                    console.log($(this).attr('class'));
                });
                
                // כפה הצגה של שער iCount
                if ($('.payment_method_icount_gateway').length) {
                    console.log('iCount gateway exists in DOM');
                    $('.payment_method_icount_gateway').show();
                } else {
                    console.log('iCount gateway NOT found in DOM');
                }
            });
        </script>
        <?php
    }
});


add_action('woocommerce_review_order_before_payment', function() {
    error_log('==== PAYMENT METHODS DISPLAY DEBUG ====');
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    error_log('Available gateways count: ' . count($available_gateways));
    foreach ($available_gateways as $gateway_id => $gateway) {
        error_log("Gateway: $gateway_id, Title: {$gateway->title}, Enabled: {$gateway->enabled}");
    }
    
    // בדוק אם יש שער iCount
    if (isset($available_gateways['icount_gateway'])) {
        error_log('iCount IS available at template level');
    } else {
        error_log('iCount is NOT available at template level');
    }
});



add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
    // רק בדף הקופה
    if (!is_checkout()) {
        return $available_gateways;
    }
    
    // בדיקה אם יש מוצר eSIM בעגלה
    $has_esim = false;
    if (WC()->cart && !WC()->cart->is_empty()) {
        foreach (WC()->cart->get_cart() as $item) {
            if (isset($item['esim_package_id'])) {
                $has_esim = true;
                break;
            }
        }
    }
    
    // הכרח את שער התשלום iCount אם יש מוצר eSIM
    if ($has_esim) {
        if (!class_exists('WC_iCount_Gateway')) {
            require_once ADPRO_ESIM_PATH . 'includes/class-wc-icount-gateway.php';
        }
        
        $icount = new WC_iCount_Gateway();
        $icount->enabled = 'yes'; // הפעל בכוח
        $available_gateways['icount_gateway'] = $icount;
        
        error_log('iCount gateway forcefully added to checkout - debugging.php');
    }
    
    return $available_gateways;
}, 9999);


// בדיקה ישירה של התשלום - הוסף לתחילת הקובץ debugging.php
add_action('wp_footer', function() {
    if (is_checkout()) {
        // וודא שהעגלה מאותחלת
        if (!WC()->cart) {
            return;
        }
        
        // בדוק אם יש מוצר eSIM בעגלה
        $has_esim = false;
        foreach (WC()->cart->get_cart() as $item) {
            if (isset($item['esim_package_id'])) {
                $has_esim = true;
                break;
            }
        }

        // הצג את מצב השער
        echo '<div style="position: fixed; bottom: 10px; right: 10px; background: white; border: 1px solid #333; padding: 10px; z-index: 9999; direction: ltr; text-align: left;">';
        echo '<h4>iCount Gateway Debug:</h4>';
        
        // בדוק אם השער קיים
        if (class_exists('WC_iCount_Gateway')) {
            echo '<p>Class exists: Yes</p>';
            
            // נסה ליצור אובייקט
            try {
                $gateway = new WC_iCount_Gateway();
                echo '<p>Instance created: Yes</p>';
                echo '<p>Enabled: ' . $gateway->enabled . '</p>';
                echo '<p>is_available(): ' . ($gateway->is_available() ? 'Yes' : 'No') . '</p>';
            } catch (Exception $e) {
                echo '<p>Error creating instance: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p>Class exists: No</p>';
        }
        
        // בדוק אם השער רשום
        $available = WC()->payment_gateways->get_available_payment_gateways();
        echo '<p>iCount in available gateways: ' . (isset($available['icount_gateway']) ? 'Yes' : 'No') . '</p>';
        
        // בדוק אם יש מוצר eSIM
        echo '<p>Has eSIM product: ' . ($has_esim ? 'Yes' : 'No') . '</p>';
        
        echo '</div>';
    }
});


// כפייה באמצעות JavaScript - הוסף לסוף הקובץ debugging.php
add_action('wp_footer', function() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('iCount payment method check running...');
            
            // בדוק אם שער התשלום קיים אך מוסתר
            if ($('#payment_method_icount_gateway').length) {
                console.log('iCount payment method exists in DOM');
                
                // נסה להציג אותו בכוח
                $('#payment_method_icount_gateway').prop('checked', true).closest('li.payment_method_icount_gateway').show();
                
                // בדוק אם יש רק שער אחד
                if ($('.wc_payment_methods li').length === 1) {
                    console.log('Only one payment method exists');
                    // אם יש רק שער אחד, וודא שהוא מסומן
                    $('#payment_method_icount_gateway').prop('checked', true);
                }

                // נסה ליצור את שער התשלום אם הוא חסר לחלוטין
                if ($('.wc_payment_methods li').length === 0) {
                    console.log('No payment methods exist, creating iCount gateway');
                    var paymentHtml = '<li class="payment_method_icount_gateway">' +
                        '<input id="payment_method_icount_gateway" type="radio" class="input-radio" name="payment_method" value="icount_gateway" checked="checked" />' +
                        '<label for="payment_method_icount_gateway">תשלום בכרטיס אשראי</label>' +
                        '<div class="payment_box payment_method_icount_gateway"><p>שלם באופן מאובטח באמצעות כרטיס אשראי</p></div>' +
                        '</li>';
                    $('.wc_payment_methods').html(paymentHtml);
                }
            } else {
                console.log('iCount payment method NOT found in DOM, trying to add it');
                
                // נסה להוסיף את שער התשלום ידנית
                var paymentHtml = '<li class="payment_method_icount_gateway">' +
                    '<input id="payment_method_icount_gateway" type="radio" class="input-radio" name="payment_method" value="icount_gateway" checked="checked" />' +
                    '<label for="payment_method_icount_gateway">תשלום בכרטיס אשראי</label>' +
                    '<div class="payment_box payment_method_icount_gateway"><p>שלם באופן מאובטח באמצעות כרטיס אשראי</p></div>' +
                    '</li>';
                $('.wc_payment_methods').append(paymentHtml);
            }
        });
        </script>
        <?php
    }
});


// דריסה מוחלטת של שערי תשלום
add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
    if (is_checkout()) {
        // נקה את כל שערי התשלום
        $available_gateways = array();
        
        // הוסף רק את שער iCount
        if (!class_exists('WC_iCount_Gateway')) {
            require_once ADPRO_ESIM_PATH . 'includes/class-wc-icount-gateway.php';
        }
        
        $icount = new WC_iCount_Gateway();
        $icount->enabled = 'yes'; // הפעל בכוח
        $available_gateways['icount_gateway'] = $icount;
        
        error_log('iCount gateway REPLACING ALL gateways at checkout');
    }
    
    return $available_gateways;
}, 99999); // עדיפות מאוד גבוהה



// בדיקת טעינת WooCommerce
add_action('plugins_loaded', function() {
    error_log('WooCommerce class exists: ' . (class_exists('WooCommerce') ? 'Yes' : 'No'));
    error_log('WC_Payment_Gateway class exists: ' . (class_exists('WC_Payment_Gateway') ? 'Yes' : 'No'));
}, 30);

// כפיית שער התשלום רק כאשר WooCommerce זמין
add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
    // בדוק שאנחנו בעמוד התשלום
    if (is_checkout()) {
        error_log('Checkout page detected: attempting to add iCount gateway');
        
        // בדוק אם WooCommerce מוכן
        if (!class_exists('WC_Payment_Gateway')) {
            error_log('WC_Payment_Gateway class not found - aborting');
            return $available_gateways;
        }
        
        // טען את מחלקת שער התשלום
        if (!class_exists('WC_iCount_Gateway')) {
            require_once ADPRO_ESIM_PATH . 'includes/class-wc-icount-gateway.php';
        }
        
        // הוסף את שער iCount אם הוא לא קיים כבר
        if (!isset($available_gateways['icount_gateway'])) {
            $icount = new WC_iCount_Gateway();
            $icount->enabled = 'yes';
            $available_gateways['icount_gateway'] = $icount;
            error_log('iCount gateway added successfully');
        } else {
            error_log('iCount gateway already exists in available gateways');
        }
    }
    
    return $available_gateways;
}, 100);

// כפייה ישירה של טופס התשלום באמצעות JavaScript
add_action('wp_footer', function() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // הסר את הודעת השגיאה שמופיעה בראש העמוד
            $('.woocommerce-error:contains("אין אמצעי תשלום")').remove();
            
            // בדוק אם טופס התשלום כבר קיים
            if ($('#payment').length) {
                console.log('Payment form exists, forcing iCount gateway');
                
                // נסה למצוא את טופס iCount
                if ($('.payment_method_icount_gateway').length === 0) {
                    console.log('iCount method not found, appending it manually');
                    
                    // בנה את טופס התשלום ידנית
                    var paymentFormHtml = `
                        <li class="wc_payment_method payment_method_icount_gateway">
                            <input id="payment_method_icount_gateway" type="radio" class="input-radio" name="payment_method" value="icount_gateway" checked="checked" data-order_button_text="">
                            <label for="payment_method_icount_gateway">תשלום בכרטיס אשראי</label>
                            <div class="payment_box payment_method_icount_gateway" style="display:block;">
                                <p>שלם באופן מאובטח באמצעות כרטיס אשראי</p>
                                
                                <fieldset id="wc-icount_gateway-cc-form" class="wc-credit-card-form wc-payment-form">
                                    <div class="form-row form-row-wide">
                                        <h3>פרטי כרטיס אשראי</h3>
                                    </div>
                                    
                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-card-number">מספר כרטיס <span class="required">*</span></label>
                                        <input id="icount_gateway-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="icount_gateway-card-number">
                                    </div>

                                    <div class="form-row form-row-first">
                                        <label for="icount_gateway-card-expiry">תוקף (MM/YY) <span class="required">*</span></label>
                                        <input id="icount_gateway-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="MM / YY" name="icount_gateway-card-expiry">
                                    </div>

                                    <div class="form-row form-row-last">
                                        <label for="icount_gateway-card-cvc">קוד אבטחה (CVC) <span class="required">*</span></label>
                                        <input id="icount_gateway-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVC" name="icount_gateway-card-cvc">
                                    </div>

                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-card-holder-id">מספר תעודת זהות <span class="required">*</span></label>
                                        <input id="icount_gateway-card-holder-id" class="input-text" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" name="icount_gateway-card-holder-id">
                                    </div>

                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-card-holder-name">שם בעל הכרטיס <span class="required">*</span></label>
                                        <input id="icount_gateway-card-holder-name" class="input-text" type="text" autocomplete="cc-name" name="icount_gateway-card-holder-name">
                                    </div>

                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-installments">תשלומים</label>
                                        <select id="icount_gateway-installments" name="icount_gateway-installments">
                                            <option value="1">תשלום אחד</option>
                                            <option value="2">2 תשלומים</option>
                                            <option value="3">3 תשלומים</option>
                                            <option value="4">4 תשלומים</option>
                                            <option value="5">5 תשלומים</option>
                                            <option value="6">6 תשלומים</option>
                                            <option value="7">7 תשלומים</option>
                                            <option value="8">8 תשלומים</option>
                                            <option value="9">9 תשלומים</option>
                                            <option value="10">10 תשלומים</option>
                                            <option value="11">11 תשלומים</option>
                                            <option value="12">12 תשלומים</option>
                                        </select>
                                    </div>
                                    <div class="clear"></div>
                                </fieldset>
                            </div>
                        </li>
                    `;
                    
                    // בדוק אם יש כבר רשימת שערי תשלום
                    if ($('ul.wc_payment_methods').length) {
                        // נקה את הרשימה ממה שקיים
                        $('ul.wc_payment_methods').empty();
                        // הוסף את שער iCount
                        $('ul.wc_payment_methods').append(paymentFormHtml);
                    } else {
                        // אם אין רשימה, צור אותה
                        $('#payment').prepend('<ul class="wc_payment_methods payment_methods methods">' + paymentFormHtml + '</ul>');
                    }
                    
                    // הפעל שוב את הסקריפט של התשלום
                    if (typeof AdPro_enqueue_payment_scripts === 'function') {
                        AdPro_enqueue_payment_scripts();
                    }
                } else {
                    console.log('iCount method exists, making sure it is visible and selected');
                    $('.payment_method_icount_gateway').show();
                    $('#payment_method_icount_gateway').prop('checked', true);
                    $('.payment_box.payment_method_icount_gateway').show();
                }
            } else {
                console.log('Payment form does not exist, creating a complete form');
                
                // אם אין אלמנט תשלום בכלל, צור את כל הטופס
                var completeFormHtml = `
                <div id="payment" class="woocommerce-checkout-payment">
                    <ul class="wc_payment_methods payment_methods methods">
                        <li class="wc_payment_method payment_method_icount_gateway">
                            <input id="payment_method_icount_gateway" type="radio" class="input-radio" name="payment_method" value="icount_gateway" checked="checked" data-order_button_text="">
                            <label for="payment_method_icount_gateway">תשלום בכרטיס אשראי</label>
                            <div class="payment_box payment_method_icount_gateway" style="display:block;">
                                <p>שלם באופן מאובטח באמצעות כרטיס אשראי</p>
                                
                                <fieldset id="wc-icount_gateway-cc-form" class="wc-credit-card-form wc-payment-form">
                                    <div class="form-row form-row-wide">
                                        <h3>פרטי כרטיס אשראי</h3>
                                    </div>
                                    
                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-card-number">מספר כרטיס <span class="required">*</span></label>
                                        <input id="icount_gateway-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="icount_gateway-card-number">
                                    </div>

                                    <div class="form-row form-row-first">
                                        <label for="icount_gateway-card-expiry">תוקף (MM/YY) <span class="required">*</span></label>
                                        <input id="icount_gateway-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="MM / YY" name="icount_gateway-card-expiry">
                                    </div>

                                    <div class="form-row form-row-last">
                                        <label for="icount_gateway-card-cvc">קוד אבטחה (CVC) <span class="required">*</span></label>
                                        <input id="icount_gateway-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVC" name="icount_gateway-card-cvc">
                                    </div>

                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-card-holder-id">מספר תעודת זהות <span class="required">*</span></label>
                                        <input id="icount_gateway-card-holder-id" class="input-text" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" name="icount_gateway-card-holder-id">
                                    </div>

                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-card-holder-name">שם בעל הכרטיס <span class="required">*</span></label>
                                        <input id="icount_gateway-card-holder-name" class="input-text" type="text" autocomplete="cc-name" name="icount_gateway-card-holder-name">
                                    </div>

                                    <div class="form-row form-row-wide">
                                        <label for="icount_gateway-installments">תשלומים</label>
                                        <select id="icount_gateway-installments" name="icount_gateway-installments">
                                            <option value="1">תשלום אחד</option>
                                            <option value="2">2 תשלומים</option>
                                            <option value="3">3 תשלומים</option>
                                            <option value="4">4 תשלומים</option>
                                            <option value="5">5 תשלומים</option>
                                            <option value="6">6 תשלומים</option>
                                            <option value="7">7 תשלומים</option>
                                            <option value="8">8 תשלומים</option>
                                            <option value="9">9 תשלומים</option>
                                            <option value="10">10 תשלומים</option>
                                            <option value="11">11 תשלומים</option>
                                            <option value="12">12 תשלומים</option>
                                        </select>
                                    </div>
                                    <div class="clear"></div>
                                </fieldset>
                            </div>
                        </li>
                    </ul>
                    <div class="form-row place-order">
                        <noscript>
                            מכשיר שלך אינו תומך בפעולות JavaScript, אך הדבר נדרש לעיבוד התשלום. אנא אפשר JavaScript בהגדרות הדפדפן שלך.
                        </noscript>
                        <button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="לביצוע ההזמנה" data-value="לביצוע ההזמנה">לביצוע ההזמנה</button>
                    </div>
                </div>
                `;
                
                // הוסף את הטופס המלא לחלק המתאים בדף
                $('.woocommerce-checkout-review-order').append(completeFormHtml);
            }
            
            // הסתר את הודעת השגיאה על השערים הלא זמינים
            $('.woocommerce-NoticeGroup-checkout').hide();
            
            // לוודא שטופס התשלום תמיד מוצג
            setTimeout(function() {
                $('.payment_box.payment_method_icount_gateway').show();
                $('#payment').show();
            }, 500);
        });
        </script>
        <?php
    }
});