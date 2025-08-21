<?php
get_header();

$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
$country_param = strtolower(get_query_var('AdPro_esim_country'));
$country_data = AdPro_get_country_by_slug($country_param);

// טעינת סגנונות
wp_enqueue_style('AdPro-esim-style', plugins_url('/public/assets/css/style.css', dirname(dirname(__FILE__))));
?>

<div id="AdPro-esim-success">
    <div class="success-icon">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" fill="#4CAF50" />
            <path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </div>
    
    <h1>רכישה בוצעה בהצלחה!</h1>
    
    <p class="success-message">תודה על רכישתך. פרטי ה-eSIM נשלחו לכתובת הדוא"ל שלך.</p>
    
    <?php if (!empty($order_id)): ?>
        <p class="order-id">מספר הזמנה: <?php echo esc_html($order_id); ?></p>
    <?php endif; ?>
    
    <div class="success-actions">
        <?php if (function_exists('wc_get_account_endpoint_url')): ?>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('AdPro-esim')); ?>" class="button primary-button">צפה בחבילות שלי</a>
        <?php else: ?>
            <a href="<?php echo esc_url(home_url('/my-account/AdPro-esim')); ?>" class="button primary-button">צפה בחבילות שלי</a>
        <?php endif; ?>
        
        <?php if ($country_data): ?>
            <a href="<?php echo esc_url(home_url("/esim/{$country_data['slug']}")); ?>" class="button secondary-button">חזרה לחבילות <?php echo esc_html($country_data['hebrew']); ?></a>
        <?php endif; ?>
    </div>
    
    <div class="success-info">
        <h3>הוראות הפעלה</h3>
        <ol>
            <li>סרוק את קוד ה-QR שנשלח אליך במייל</li>
            <li>פעל לפי ההוראות להוספת eSIM למכשיר שלך</li>
            <li>הפעל את ה-eSIM לפני הגעתך ליעד</li>
            <li>ודא שהגדרות הנדידה מופעלות במכשיר שלך</li>
        </ol>
        
        <p class="support-text">לתמיכה טכנית, אנא צור קשר בטלפון: <a href="tel:+97227222222">072-2222222</a></p>
    </div>
</div>

<style>
    #AdPro-esim-success {
        max-width: 600px;
        margin: 50px auto;
        text-align: center;
        padding: 40px 30px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .success-icon {
        margin-bottom: 20px;
    }
    
    #AdPro-esim-success h1 {
        color: #333;
        font-size: 28px;
        margin-bottom: 20px;
    }
    
    .success-message {
        font-size: 18px;
        color: #666;
        margin-bottom: 15px;
    }
    
    .order-id {
        font-family: monospace;
        background-color: #f5f5f5;
        padding: 8px 15px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 25px;
    }
    
    .success-actions {
        margin: 30px 0;
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .button {
        display: inline-block;
        padding: 12px 25px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s;
    }
    
    .primary-button {
        background-color: #4CAF50;
        color: white;
    }
    
    .primary-button:hover {
        background-color: #45a049;
		.primary-button:hover {
        background-color: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .secondary-button {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    .secondary-button:hover {
        background-color: #eaeaea;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .success-info {
        margin-top: 40px;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        text-align: right;
    }
    
    .success-info h3 {
        margin-top: 0;
        color: #333;
        margin-bottom: 15px;
    }
    
    .success-info ol {
        text-align: right;
        padding-right: 20px;
        margin-bottom: 20px;
    }
    
    .success-info li {
        margin-bottom: 10px;
        color: #444;
    }
    
    .support-text {
        color: #666;
        font-size: 14px;
        border-top: 1px solid #eee;
        padding-top: 15px;
        margin-top: 20px;
    }
    
    .support-text a {
        color: #4a90e2;
        text-decoration: none;
    }
    
    .support-text a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        #AdPro-esim-success {
            padding: 30px 20px;
            margin: 30px 15px;
        }
        
        #AdPro-esim-success h1 {
            font-size: 24px;
        }
        
        .success-message {
            font-size: 16px;
        }
        
        .success-actions {
            flex-direction: column;
        }
        
        .button {
            width: 100%;
        }
    }
</style>

<?php
get_footer();