<?php
/**
 * כלי אבחון ותיקון הזמנות eSIM עבור מנהל האתר
 */

if (!defined('ABSPATH')) {
    exit; // יציאה אם הגישה ישירה
}

// הוספת דף הכלי לתפריט הניהול
function AdPro_add_esim_troubleshooter_page() {
    add_submenu_page(
        'AdPro-esim-settings',
        'אבחון וטיפול בהזמנות',
        'אבחון וטיפול בהזמנות',
        'manage_options',
        'AdPro-esim-troubleshooter',
        'AdPro_esim_troubleshooter_page'
    );
}
add_action('admin_menu', 'AdPro_add_esim_troubleshooter_page', 30);

// פונקציית AJAX לעיבוד מחדש של הזמנת eSIM
function AdPro_ajax_reprocess_esim_order() {
    // וידוא הרשאות
    if (!current_user_can('manage_options')) {
        wp_send_json_error('אין לך הרשאות לבצע פעולה זו');
        return;
    }
    
    // בדיקת nonce לאבטחה
    if (!check_ajax_referer('adpro_esim_troubleshooter', 'security', false)) {
        wp_send_json_error('בעיית אבטחה, נא לרענן את הדף ולנסות שוב');
        return;
    }
    
    // וידוא שיש מזהה הזמנה
    if (empty($_POST['order_id'])) {
        wp_send_json_error('חסר מזהה הזמנה');
        return;
    }
    
    $order_id = intval($_POST['order_id']);
    
    // שימוש בפונקציה החדשה
    $result = AdPro_process_complete_esim_order($order_id);
    
    if ($result) {
        wp_send_json_success('ההזמנה עובדה בהצלחה. פרטי eSIM נשמרו בהזמנה.');
    } else {
        wp_send_json_error('אירעה שגיאה בעיבוד ההזמנה. בדוק בלוג השגיאות לפרטים נוספים.');
    }
}
add_action('wp_ajax_adpro_reprocess_esim_order', 'AdPro_ajax_reprocess_esim_order');

// פונקציית AJAX לשליחה מחדש של מייל עם פרטי eSIM
function AdPro_ajax_resend_esim_email() {
    // וידוא הרשאות
    if (!current_user_can('manage_options')) {
        wp_send_json_error('אין לך הרשאות לבצע פעולה זו');
        return;
    }
    
    // בדיקת nonce לאבטחה
    if (!check_ajax_referer('adpro_esim_troubleshooter', 'security', false)) {
        wp_send_json_error('בעיית אבטחה, נא לרענן את הדף ולנסות שוב');
        return;
    }
    
    // וידוא שיש מזהה הזמנה
    if (empty($_POST['order_id'])) {
        wp_send_json_error('חסר מזהה הזמנה');
        return;
    }
    
    $order_id = intval($_POST['order_id']);
    
    // נסה לשלוח מייל מחדש
    $result = AdPro_send_esim_details_email($order_id);
    
    if ($result) {
        wp_send_json_success('המייל נשלח בהצלחה');
    } else {
        wp_send_json_error('אירעה שגיאה בשליחת המייל. בדוק שיש פרטי eSIM בהזמנה וכתובת מייל תקינה.');
    }
}
add_action('wp_ajax_adpro_resend_esim_email', 'AdPro_ajax_resend_esim_email');

// פונקציית AJAX לביטול הזמנת מובימטר
function AdPro_ajax_cancel_mobimatter_order() {
    // וידוא הרשאות
    if (!current_user_can('manage_options')) {
        wp_send_json_error('אין לך הרשאות לבצע פעולה זו');
        return;
    }
    
    // בדיקת nonce לאבטחה
    if (!check_ajax_referer('adpro_esim_troubleshooter', 'security', false)) {
        wp_send_json_error('בעיית אבטחה, נא לרענן את הדף ולנסות שוב');
        return;
    }
    
    // וידוא שיש מזהה הזמנה
    if (empty($_POST['order_id'])) {
        wp_send_json_error('חסר מזהה הזמנה');
        return;
    }
    
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error('הזמנה לא נמצאה');
        return;
    }
    
    // בדיקה אם יש מזהה הזמנת מובימטר
    $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
    
    if (empty($mobimatter_order_id)) {
        wp_send_json_error('לא נמצא מזהה הזמנת מובימטר בהזמנה זו');
        return;
    }
    
    // ביטול ההזמנה במובימטר
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (empty($api_key) || empty($merchant_id)) {
        wp_send_json_error('חסרים פרטי API של מובימטר');
        return;
    }
    
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order/cancel';
    
    $cancel_data = [
        'orderId' => $mobimatter_order_id
    ];
    
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($cancel_data),
        'method' => 'PUT',
        'timeout' => 30
    ];
    
    $response = wp_remote_request($api_url, $args);
    
    if (is_wp_error($response)) {
        wp_send_json_error('שגיאת תקשורת: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code === 200) {
        $order->add_order_note('הזמנת מובימטר בוטלה ידנית על ידי מנהל המערכת');
        $order->update_meta_data('_mobimatter_cancelled', 'yes');
        $order->save();
        
        wp_send_json_success('הזמנת מובימטר בוטלה בהצלחה');
    } else {
        wp_send_json_error('שגיאה בביטול הזמנת מובימטר: קוד ' . $response_code . ', תשובה: ' . $response_body);
    }
}
add_action('wp_ajax_adpro_cancel_mobimatter_order', 'AdPro_ajax_cancel_mobimatter_order');

// פונקציית AJAX לקבלת פרטי הזמנת מובימטר
function AdPro_ajax_get_mobimatter_order_details() {
    // וידוא הרשאות
    if (!current_user_can('manage_options')) {
        wp_send_json_error('אין לך הרשאות לבצע פעולה זו');
        return;
    }
    
    // בדיקת nonce לאבטחה
    if (!check_ajax_referer('adpro_esim_troubleshooter', 'security', false)) {
        wp_send_json_error('בעיית אבטחה, נא לרענן את הדף ולנסות שוב');
        return;
    }
    
    // וידוא שיש מזהה הזמנה
    if (empty($_POST['order_id'])) {
        wp_send_json_error('חסר מזהה הזמנה');
        return;
    }
    
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error('הזמנה לא נמצאה');
        return;
    }
    
    // בדיקה אם יש מזהה הזמנת מובימטר
    $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
    
    if (empty($mobimatter_order_id)) {
        wp_send_json_error('לא נמצא מזהה הזמנת מובימטר בהזמנה זו');
        return;
    }
    
    // קבלת פרטי API
    $api_key = get_option('AdPro_api_key');
    $merchant_id = get_option('AdPro_merchant_id');
    
    if (empty($api_key) || empty($merchant_id)) {
        wp_send_json_error('חסרים פרטי API של מובימטר');
        return;
    }
    
    // קבלת פרטי ההזמנה ממובימטר
    $api_url = 'https://api.mobimatter.com/mobimatter/api/v2/order/' . urlencode($mobimatter_order_id);
    
    $args = [
        'headers' => [
            'Accept' => 'text/plain',
            'merchantId' => $merchant_id,
            'api-key' => $api_key
        ],
        'method' => 'GET',
        'timeout' => 30
    ];
    
    $response = wp_remote_request($api_url, $args);
    
    if (is_wp_error($response)) {
        wp_send_json_error('שגיאת תקשורת: ' . $response->get_error_message());
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
    
    if ($response_code !== 200) {
        wp_send_json_error('שגיאה בקבלת פרטי הזמנת מובימטר: קוד ' . $response_code . ', תשובה: ' . $response_body);
        return;
    }
    
    // החזרת פרטי ההזמנה
    wp_send_json_success($response_data);
}
add_action('wp_ajax_adpro_get_mobimatter_order_details', 'AdPro_ajax_get_mobimatter_order_details');

/**
 * דף אבחון וטיפול בהזמנות eSIM
 */
function AdPro_esim_troubleshooter_page() {
    // בדיקה אם ישנה הזמנה ספציפית לבדיקה
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    
    // הכנת נתונים שימושיים
    $recent_esim_orders = AdPro_get_recent_esim_orders();
    $problem_orders = AdPro_get_problem_esim_orders();
    
    // CSS סגנונות לדף
    ?>
    <style>
        .adpro-troubleshooter {
            max-width: 1200px;
            margin: 20px auto;
        }
        
        .adpro-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .adpro-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .adpro-section h2 {
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .adpro-orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .adpro-orders-table th {
            text-align: right;
            background: #f5f5f5;
        }
        
        .adpro-orders-table th, 
        .adpro-orders-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .adpro-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .adpro-status-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .adpro-status-warning {
            background-color: #fcf8e3;
            color: #8a6d3b;
        }
        
        .adpro-status-error {
            background-color: #f2dede;
            color: #a94442;
        }
        
        .adpro-actions {
            display: flex;
            gap: 5px;
        }
        
        .adpro-button {
            padding: 5px 10px;
            cursor: pointer;
            border: none;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .adpro-button-primary {
            background-color: #0073aa;
            color: white;
        }
        
        .adpro-button-secondary {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
        }
        
        .adpro-button-warning {
            background-color: #f0ad4e;
            color: white;
        }
        
        .adpro-button-danger {
            background-color: #d9534f;
            color: white;
        }
        
        .adpro-button:hover {
            opacity: 0.85;
        }
        
        .adpro-detail-panel {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
            display: none;
        }
        
        .adpro-order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .adpro-detail-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .adpro-detail-card h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }
        
        .adpro-detail-list {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }
        
        .adpro-detail-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .adpro-detail-list li:last-child {
            border-bottom: none;
        }
        
        .adpro-meta-key {
            font-weight: 600;
            color: #444;
        }
        
        .adpro-meta-value {
            background: #f5f5f5;
            padding: 3px 6px;
            border-radius: 3px;
            font-family: monospace;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .adpro-qr-code {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 5px;
            background: white;
        }
        
        .adpro-response-log {
            background: #2b2b2b;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .adpro-log-success {
            color: #a6e22e;
        }
        
        .adpro-log-error {
            color: #f92672;
        }
        
        .adpro-log-info {
            color: #66d9ef;
        }
        
        .adpro-search-form {
            display: flex;
            margin-bottom: 20px;
        }
        
        .adpro-search-input {
            flex-grow: 1;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
        }
        
        .adpro-search-button {
            padding: 8px 15px;
            background-color: #0073aa;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
    </style>
    
    <div class="wrap adpro-troubleshooter">
        <div class="adpro-title">
            <h1>אבחון וטיפול בהזמנות eSIM</h1>
            
            <form action="" method="get" class="adpro-search-form">
                <input type="hidden" name="page" value="AdPro-esim-troubleshooter">
                <input type="number" name="order_id" placeholder="הזן מספר הזמנה..." class="adpro-search-input">
                <button type="submit" class="adpro-search-button">חפש</button>
            </form>
        </div>
        
        <?php if ($order_id > 0) : ?>
            <?php 
                $order = wc_get_order($order_id);
                if ($order) {
                    AdPro_display_single_order_details($order);
                } else {
                    echo '<div class="notice notice-error"><p>הזמנה מספר ' . esc_html($order_id) . ' לא נמצאה.</p></div>';
                }
            ?>
        <?php else : ?>
            
            <!-- הזמנות בעייתיות -->
            <div class="adpro-section">
                <h2>הזמנות עם בעיות</h2>
                
                <?php if (empty($problem_orders)) : ?>
                    <p>לא נמצאו הזמנות בעייתיות. 👍</p>
                <?php else : ?>
                    <table class="adpro-orders-table">
                        <thead>
                            <tr>
                                <th>מספר הזמנה</th>
                                <th>תאריך</th>
                                <th>לקוח</th>
                                <th>סטטוס</th>
                                <th>בעיה</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($problem_orders as $problem_order) : ?>
                                <tr>
                                    <td><a href="?page=AdPro-esim-troubleshooter&order_id=<?php echo $problem_order['id']; ?>">#<?php echo $problem_order['id']; ?></a></td>
                                    <td><?php echo $problem_order['date']; ?></td>
                                    <td><?php echo $problem_order['customer']; ?></td>
                                    <td><?php echo $problem_order['status']; ?></td>
                                    <td>
                                        <span class="adpro-status adpro-status-<?php echo $problem_order['problem_type']; ?>">
                                            <?php echo $problem_order['problem']; ?>
                                        </span>
                                    </td>
                                    <td class="adpro-actions">
                                        <a href="?page=AdPro-esim-troubleshooter&order_id=<?php echo $problem_order['id']; ?>" class="adpro-button adpro-button-primary">פרטים</a>
                                        <?php if ($problem_order['problem_type'] === 'error') : ?>
                                            <button class="adpro-button adpro-button-warning reprocess-order-btn" data-order-id="<?php echo $problem_order['id']; ?>">עבד מחדש</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- הזמנות אחרונות -->
            <div class="adpro-section">
                <h2>הזמנות eSIM אחרונות</h2>
                
                <table class="adpro-orders-table">
                    <thead>
                        <tr>
                            <th>מספר הזמנה</th>
                            <th>תאריך</th>
                            <th>לקוח</th>
                            <th>מדינה</th>
                            <th>סטטוס</th>
                            <th>פרטי eSIM</th>
                            <th>פעולות</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_esim_orders)) : ?>
                            <tr>
                                <td colspan="7">לא נמצאו הזמנות eSIM.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($recent_esim_orders as $order_data) : ?>
                                <tr>
                                    <td><a href="?page=AdPro-esim-troubleshooter&order_id=<?php echo $order_data['id']; ?>">#<?php echo $order_data['id']; ?></a></td>
                                    <td><?php echo $order_data['date']; ?></td>
                                    <td><?php echo $order_data['customer']; ?></td>
                                    <td><?php echo $order_data['country']; ?></td>
                                    <td><?php echo $order_data['status']; ?></td>
                                    <td>
                                        <?php if ($order_data['has_esim_details']) : ?>
                                            <span class="adpro-status adpro-status-success">✓ יש פרטי eSIM</span>
                                        <?php else : ?>
                                            <span class="adpro-status adpro-status-error">✗ אין פרטי eSIM</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="adpro-actions">
                                        <a href="?page=AdPro-esim-troubleshooter&order_id=<?php echo $order_data['id']; ?>" class="adpro-button adpro-button-primary">פרטים</a>
                                        <?php if (!$order_data['has_esim_details']) : ?>
                                            <button class="adpro-button adpro-button-warning reprocess-order-btn" data-order-id="<?php echo $order_data['id']; ?>">עבד מחדש</button>
                                        <?php else : ?>
                                            <button class="adpro-button adpro-button-secondary resend-email-btn" data-order-id="<?php echo $order_data['id']; ?>">שלח מייל שוב</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
                
        <?php endif; ?>
    </div>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // עיבוד מחדש של הזמנה
            $('.reprocess-order-btn').on('click', function() {
                var orderId = $(this).data('order-id');
                var $button = $(this);
                
                $button.prop('disabled', true).text('מעבד...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adpro_reprocess_esim_order',
                        order_id: orderId,
                        security: '<?php echo wp_create_nonce('adpro_esim_troubleshooter'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('ההזמנה עובדה בהצלחה. פרטי eSIM נשמרו בהזמנה.');
                            location.reload();
                        } else {
                            alert('שגיאה: ' + response.data);
                            $button.prop('disabled', false).text('עבד מחדש');
                        }
                    },
                    error: function() {
                        alert('אירעה שגיאה בשליחת הבקשה');
                        $button.prop('disabled', false).text('עבד מחדש');
                    }
                });
            });
            
            // שליחה מחדש של מייל
            $('.resend-email-btn').on('click', function() {
                var orderId = $(this).data('order-id');
                var $button = $(this);
                
                $button.prop('disabled', true).text('שולח...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adpro_resend_esim_email',
                        order_id: orderId,
                        security: '<?php echo wp_create_nonce('adpro_esim_troubleshooter'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('המייל נשלח בהצלחה.');
                        } else {
                            alert('שגיאה: ' + response.data);
                        }
                        $button.prop('disabled', false).text('שלח מייל שוב');
                    },
                    error: function() {
                        alert('אירעה שגיאה בשליחת הבקשה');
                        $button.prop('disabled', false).text('שלח מייל שוב');
                    }
                });
            });
            
            // ביטול הזמנת מובימטר
            $('.cancel-mobimatter-btn').on('click', function() {
                if (!confirm('האם אתה בטוח שברצונך לבטל את הזמנת מובימטר זו? פעולה זו אינה הפיכה!')) {
                    return;
                }
                
                var orderId = $(this).data('order-id');
                var $button = $(this);
                
                $button.prop('disabled', true).text('מבטל...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adpro_cancel_mobimatter_order',
                        order_id: orderId,
                        security: '<?php echo wp_create_nonce('adpro_esim_troubleshooter'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('הזמנת מובימטר בוטלה בהצלחה.');
                            location.reload();
                        } else {
                            alert('שגיאה: ' + response.data);
                            $button.prop('disabled', false).text('בטל הזמנה');
                        }
                    },
                    error: function() {
                        alert('אירעה שגיאה בשליחת הבקשה');
                        $button.prop('disabled', false).text('בטל הזמנה');
                    }
                });
            });
            
            // קבלת פרטי הזמנת מובימטר מה-API
            $('.get-mobimatter-details-btn').on('click', function() {
                var orderId = $(this).data('order-id');
                var $button = $(this);
                var $resultArea = $('#mobimatter-api-details');
                
                $button.prop('disabled', true).text('מקבל נתונים...');
                $resultArea.html('<p>טוען נתונים מה-API...</p>').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adpro_get_mobimatter_order_details',
                        order_id: orderId,
                        security: '<?php echo wp_create_nonce('adpro_esim_troubleshooter'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $resultArea.html('<pre class="adpro-response-log">' + JSON.stringify(response.data, null, 2) + '</pre>');
                        } else {
                            $resultArea.html('<p class="adpro-log-error">שגיאה: ' + response.data + '</p>');
                        }
                        $button.prop('disabled', false).text('קבל פרטי API');
                    },
                    error: function() {
                        $resultArea.html('<p class="adpro-log-error">אירעה שגיאה בשליחת הבקשה</p>');
                        $button.prop('disabled', false).text('קבל פרטי API');
                    }
                });
            });
            
            // הצגה/הסתרה של פאנלים
            $('.toggle-panel-btn').on('click', function() {
                var targetId = $(this).data('target');
                $('#' + targetId).toggle();
            });
        });
    </script>
    <?php
}

/**
 * הצגת פרטים מלאים של הזמנה יחידה
 * 
 * @param WC_Order $order אובייקט ההזמנה
 */
function AdPro_display_single_order_details($order) {
    $order_id = $order->get_id();
    $status = $order->get_status();
    $payment_method = $order->get_payment_method_title();
    $date_created = $order->get_date_created()->date_i18n('d/m/Y H:i:s');
    
    // קבלת פרטי לקוח
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_email = $order->get_billing_email();
    $customer_phone = $order->get_billing_phone();
    
    // בדיקה אם זו הזמנת eSIM ושליפת פרטים נוספים
    $has_esim = false;
    $esim_details = [
        'country' => '',
        'package_id' => '',
        'data_limit' => '',
        'data_unit' => '',
        'validity_days' => ''
    ];
    
    foreach ($order->get_items() as $item) {
        $esim_package_id = $item->get_meta('esim_package_id');
        if (!empty($esim_package_id)) {
            $has_esim = true;
            $esim_details['country'] = $item->get_meta('esim_country') ?: '';
            $esim_details['package_id'] = $esim_package_id;
            $esim_details['data_limit'] = $item->get_meta('esim_data_limit') ?: '';
            $esim_details['data_unit'] = $item->get_meta('esim_data_unit') ?: '';
            $esim_details['validity_days'] = $item->get_meta('esim_validity_days') ?: '';
            break;
        }
    }
    
    if (!$has_esim) {
        echo '<div class="notice notice-error"><p>הזמנה מספר ' . esc_html($order_id) . ' אינה הזמנת eSIM.</p></div>';
        echo '<p><a href="?page=AdPro-esim-troubleshooter" class="button">&larr; חזרה לרשימת ההזמנות</a></p>';
        return;
    }
    
    // קבלת פרטי הזמנת מובימטר
    $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
    $mobimatter_completed = $order->get_meta('_mobimatter_completed');
    $mobimatter_cancelled = $order->get_meta('_mobimatter_cancelled');
    
    // קבלת פרטי eSIM
    $qr_code = $order->get_meta('_esim_qr_code');
    $activation_code = $order->get_meta('_esim_activation_code');
    $esim_details_json = $order->get_meta('_esim_details');
    $has_esim_details = !empty($qr_code) || !empty($activation_code);
    
    // קבלת הערות ההזמנה
    $order_notes = AdPro_get_order_notes($order_id);
    
    // קבלת מטא-דאטה של ההזמנה
    $order_meta = AdPro_get_order_meta($order_id);
    ?>
    <div class="adpro-section">
        <div class="adpro-title">
            <h2>פרטי הזמנה #<?php echo $order_id; ?></h2>
            <a href="?page=AdPro-esim-troubleshooter" class="button">&larr; חזרה לרשימת ההזמנות</a>
        </div>
        
        <div class="adpro-order-details">
            <!-- פרטי הזמנה בסיסיים -->
            <div class="adpro-detail-card">
                <h3>פרטי הזמנה</h3>
                <ul class="adpro-detail-list">
                    <li><span class="adpro-meta-key">תאריך:</span> <?php echo esc_html($date_created); ?></li>
                    <li><span class="adpro-meta-key">סטטוס:</span> <?php echo esc_html(wc_get_order_status_name($status)); ?></li>
                    <li><span class="adpro-meta-key">סכום:</span> <?php echo $order->get_formatted_order_total(); ?></li>
                    <li><span class="adpro-meta-key">שיטת תשלום:</span> <?php echo esc_html($payment_method); ?></li>
                    <li><span class="adpro-meta-key">מזהה הזמנה במובימטר:</span> <?php echo !empty($mobimatter_order_id) ? esc_html($mobimatter_order_id) : '<span class="adpro-status adpro-status-error">לא נמצא</span>'; ?></li>
                    <li><span class="adpro-meta-key">סטטוס הזמנת מובימטר:</span> 
                        <?php 
                        if ($mobimatter_cancelled === 'yes') {
                            echo '<span class="adpro-status adpro-status-error">מבוטלת</span>';
                        } elseif ($mobimatter_completed === 'yes') {
                            echo '<span class="adpro-status adpro-status-success">הושלמה</span>';
                        } elseif (!empty($mobimatter_order_id)) {
                            echo '<span class="adpro-status adpro-status-warning">בהמתנה</span>';
                        } else {
                            echo '<span class="adpro-status adpro-status-error">לא קיימת</span>';
                        }
                        ?>
                    </li>
                </ul>
            </div>
            
            <!-- פרטי לקוח -->
            <div class="adpro-detail-card">
                <h3>פרטי לקוח</h3>
                <ul class="adpro-detail-list">
                    <li><span class="adpro-meta-key">שם:</span> <?php echo esc_html($customer_name); ?></li>
                    <li><span class="adpro-meta-key">דוא"ל:</span> <a href="mailto:<?php echo esc_attr($customer_email); ?>"><?php echo esc_html($customer_email); ?></a></li>
                    <li><span class="adpro-meta-key">טלפון:</span> <a href="tel:<?php echo esc_attr($customer_phone); ?>"><?php echo esc_html($customer_phone); ?></a></li>
                </ul>
            </div>
            
            <!-- פרטי חבילת eSIM -->
            <div class="adpro-detail-card">
                <h3>פרטי חבילת eSIM</h3>
                <ul class="adpro-detail-list">
                    <li><span class="adpro-meta-key">מדינה:</span> <?php echo esc_html($esim_details['country']); ?></li>
                    <li><span class="adpro-meta-key">מזהה חבילה:</span> <?php echo esc_html($esim_details['package_id']); ?></li>
                    <?php if (!empty($esim_details['data_limit']) && !empty($esim_details['data_unit'])) : ?>
                        <li><span class="adpro-meta-key">נפח גלישה:</span> <?php echo esc_html($esim_details['data_limit'] . ' ' . $esim_details['data_unit']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($esim_details['validity_days'])) : ?>
                        <li><span class="adpro-meta-key">תקופת תוקף:</span> <?php echo esc_html($esim_details['validity_days'] . ' ימים'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- פרטי ה-eSIM שהתקבלו -->
            <div class="adpro-detail-card">
                <h3>פרטי ה-eSIM שהתקבלו</h3>
                <?php if ($has_esim_details) : ?>
                    <ul class="adpro-detail-list">
                        <?php if (!empty($activation_code)) : ?>
                            <li><span class="adpro-meta-key">קוד הפעלה:</span> <code><?php echo esc_html($activation_code); ?></code></li>
                        <?php endif; ?>
                        <?php if (!empty($qr_code)) : ?>
                            <li>
                                <span class="adpro-meta-key">קוד QR:</span>
                                <div style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($qr_code); ?>" alt="QR Code" class="adpro-qr-code">
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php else : ?>
                    <p>לא נמצאו פרטי eSIM בהזמנה.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- כפתורי פעולה -->
        <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if (!$has_esim_details && !empty($mobimatter_order_id) && $mobimatter_cancelled !== 'yes') : ?>
                <button class="adpro-button adpro-button-warning reprocess-order-btn" data-order-id="<?php echo $order_id; ?>">עבד מחדש את ההזמנה</button>
            <?php endif; ?>
            
            <?php if ($has_esim_details) : ?>
                <button class="adpro-button adpro-button-primary resend-email-btn" data-order-id="<?php echo $order_id; ?>">שלח מייל עם פרטי eSIM</button>
            <?php endif; ?>
            
            <?php if (!empty($mobimatter_order_id) && $mobimatter_cancelled !== 'yes') : ?>
                <button class="adpro-button adpro-button-danger cancel-mobimatter-btn" data-order-id="<?php echo $order_id; ?>">בטל הזמנת מובימטר</button>
            <?php endif; ?>
            
            <a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>" class="adpro-button adpro-button-secondary" target="_blank">צפה בהזמנה בוווקומרס</a>
            
            <?php if (!empty($mobimatter_order_id)) : ?>
                <button class="adpro-button adpro-button-secondary get-mobimatter-details-btn" data-order-id="<?php echo $order_id; ?>">קבל פרטי API עדכניים</button>
            <?php endif; ?>
        </div>
        
        <!-- תוצאות API עדכניות -->
        <div id="mobimatter-api-details" style="margin-top: 20px; display: none;"></div>
        
        <!-- לשוניות נוספות -->
        <div style="margin-top: 30px;">
            <ul class="nav-tab-wrapper">
                <li><a href="#" class="nav-tab nav-tab-active toggle-panel-btn" data-target="order-notes-panel">הערות הזמנה</a></li>
                <li><a href="#" class="nav-tab toggle-panel-btn" data-target="order-meta-panel">מטא-דאטה מלא</a></li>
                <?php if (!empty($esim_details_json)) : ?>
                    <li><a href="#" class="nav-tab toggle-panel-btn" data-target="esim-details-panel">פרטי eSIM מלאים</a></li>
                <?php endif; ?>
            </ul>
            
            <!-- פאנל הערות הזמנה -->
            <div id="order-notes-panel" class="adpro-detail-panel" style="display: block;">
                <h3>הערות הזמנה</h3>
                <?php if (empty($order_notes)) : ?>
                    <p>אין הערות להזמנה זו.</p>
                <?php else : ?>
                    <ul class="adpro-detail-list">
                        <?php foreach ($order_notes as $note) : ?>
                            <li style="margin-bottom: 15px;">
                                <p><strong><?php echo date_i18n('d/m/Y H:i', strtotime($note->comment_date)); ?>:</strong></p>
                                <p><?php echo wp_kses_post($note->comment_content); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- פאנל מטא-דאטה -->
            <div id="order-meta-panel" class="adpro-detail-panel">
                <h3>מטא-דאטה מלא של ההזמנה</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>מפתח</th>
                            <th>ערך</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_meta as $meta_key => $meta_value) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($meta_key); ?></strong></td>
                                <td>
                                    <?php 
                                    if (is_array($meta_value) || is_object($meta_value)) {
                                        echo '<pre>' . esc_html(print_r($meta_value, true)) . '</pre>';
                                    } else {
                                        echo esc_html($meta_value);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- פאנל פרטי eSIM מלאים -->
            <?php if (!empty($esim_details_json)) : ?>
                <div id="esim-details-panel" class="adpro-detail-panel">
                    <h3>פרטי eSIM מלאים</h3>
                    <pre class="adpro-response-log"><?php 
                        $esim_data = json_decode($esim_details_json, true);
                        echo json_encode($esim_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * קבלת הזמנות eSIM אחרונות
 * 
 * @param int $limit מספר ההזמנות לקבלה
 * @return array מערך של נתוני הזמנות
 */
function AdPro_get_recent_esim_orders($limit = 10) {
    $orders = wc_get_orders([
        'limit' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => ['completed', 'processing']
    ]);
    
    $esim_orders = [];
    
    foreach ($orders as $order) {
        $has_esim = false;
        $country = '';
        
        foreach ($order->get_items() as $item) {
            $package_id = $item->get_meta('esim_package_id');
            if (!empty($package_id)) {
                $has_esim = true;
                $country = $item->get_meta('esim_country');
                break;
            }
        }
        
        if (!$has_esim) {
            continue;
        }
        
        $esim_orders[] = [
            'id' => $order->get_id(),
            'date' => $order->get_date_created()->date_i18n('d/m/Y H:i'),
            'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'country' => $country,
            'status' => wc_get_order_status_name($order->get_status()),
            'has_esim_details' => (!empty($order->get_meta('_esim_qr_code')) || !empty($order->get_meta('_esim_activation_code')))
        ];
    }
    
    return $esim_orders;
}

/**
 * קבלת הזמנות eSIM בעייתיות
 * 
 * @return array מערך של נתוני הזמנות בעייתיות
 */
function AdPro_get_problem_esim_orders() {
    $orders = wc_get_orders([
        'limit' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => ['completed', 'processing']
    ]);
    
    $problem_orders = [];
    
    foreach ($orders as $order) {
        $has_esim = false;
        
        foreach ($order->get_items() as $item) {
            $package_id = $item->get_meta('esim_package_id');
            if (!empty($package_id)) {
                $has_esim = true;
                break;
            }
        }
        
        if (!$has_esim) {
            continue;
        }
        
        $mobimatter_order_id = $order->get_meta('_mobimatter_order_id');
        $mobimatter_completed = $order->get_meta('_mobimatter_completed');
        $has_qr_code = !empty($order->get_meta('_esim_qr_code'));
        $has_activation_code = !empty($order->get_meta('_esim_activation_code'));
        
        $problem_type = '';
        $problem = '';
        
        if (empty($mobimatter_order_id)) {
            $problem_type = 'error';
            $problem = 'לא נוצרה הזמנה במובימטר';
        } elseif ($mobimatter_completed !== 'yes' && $order->get_status() === 'completed') {
            $problem_type = 'warning';
            $problem = 'הזמנה הושלמה אך סטטוס מובימטר לא מעודכן';
        } elseif (!$has_qr_code && !$has_activation_code && $order->get_status() === 'completed') {
            $problem_type = 'error';
            $problem = 'אין פרטי eSIM בהזמנה שהושלמה';
        } else {
            continue; // אין בעיה
        }
        
        $problem_orders[] = [
            'id' => $order->get_id(),
            'date' => $order->get_date_created()->date_i18n('d/m/Y H:i'),
            'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'status' => wc_get_order_status_name($order->get_status()),
            'problem_type' => $problem_type,
            'problem' => $problem
        ];
    }
    
    return $problem_orders;
}

/**
 * קבלת הערות הזמנה
 * 
 * @param int $order_id מזהה ההזמנה
 * @return array מערך של הערות הזמנה
 */
function AdPro_get_order_notes($order_id) {
    global $wpdb;
    
    $notes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT comment_ID, comment_content, comment_date
            FROM {$wpdb->comments}
            WHERE comment_post_ID = %d
            AND comment_type = 'order_note'
            ORDER BY comment_date DESC",
            $order_id
        )
    );
    
    return $notes;
}

/**
 * קבלת מטא-דאטה של הזמנה
 * 
 * @param int $order_id מזהה ההזמנה
 * @return array מערך של מטא-דאטה
 */
function AdPro_get_order_meta($order_id) {
    global $wpdb;
    
    $meta_data = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id = %d",
            $order_id
        )
    );
    
    $meta_array = [];
    
    foreach ($meta_data as $meta) {
        $meta_array[$meta->meta_key] = $meta->meta_value;
    }
    
    return $meta_array;
}