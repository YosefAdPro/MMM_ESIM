<?php
/**
 * Template Name: eSIM Add to Cart
 *
 * Handle adding eSIM products to cart when WooCommerce is fully loaded
 */

// Make sure WooCommerce is fully loaded first
if (!defined('ABSPATH')) {
    exit;
}

// Ensure PHP session is started for guest users
if (!WC()->is_rest_api_request() && !is_admin()) {
    if (!WC()->session->has_session()) {
        WC()->session->set_customer_session_cookie(true);
    }
}

get_header();
?>

<div class="esim-processing">
    <h1>מעבד את הבקשה...</h1>
    <p>המערכת מעבדת את בקשתך, נא להמתין...</p>
</div>

<script type="text/javascript">
    // Redirect to checkout after page load
    window.onload = function() {
        // Wait briefly to ensure the page is fully loaded
        setTimeout(function() {
            window.location.href = '<?php echo esc_url(wc_get_checkout_url()); ?>';
        }, 1000);
    }
</script>

<?php
// Process the cart data if transaction ID is provided
// בדוק ועדכן את הקוד בקובץ esim-add-to-cart.php

// בדוק שהנתונים מועברים נכון לעגלה
if (isset($_GET['transaction'])) {
    $transaction_id = sanitize_text_field($_GET['transaction']);
    $cart_item_data = get_transient($transaction_id);
    
    if ($cart_item_data) {
        // עכשיו אפשר להשתמש בפונקציות של WooCommerce בבטחה
        if (function_exists('WC') && WC()) {
            // וודא שהעגלה מאותחלת
            if (!WC()->cart) {
                WC()->cart = new WC_Cart();
            }
            
            // הרוקן את העגלה קודם
            WC()->cart->empty_cart();
            
            // קבל את מזהה המוצר
            $product_id = AdPro_get_or_create_esim_product();
            
            // הוסף לעגלה
            $cart_key = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
            
            // הוסף לוג האם ההוספה לעגלה הצליחה
            if ($cart_key) {
                error_log('eSIM product added to cart successfully: ' . $cart_key);
                error_log('Cart item data: ' . json_encode($cart_item_data));
            } else {
                error_log('Failed to add eSIM product to cart!');
            }
            
            // נקה את המידע הזמני
            delete_transient($transaction_id);
        }
    } else {
        error_log('Transaction data not found for ID: ' . $transaction_id);
    }
}

get_footer();