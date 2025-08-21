<?php
get_header();

$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
$country_param = strtolower(get_query_var('AdPro_esim_country'));
$country_data = AdPro_get_country_by_slug($country_param);

// טעינת סגנונות
wp_enqueue_style('AdPro-esim-style', plugins_url('/public/assets/css/style.css', dirname(dirname(__FILE__))));
?>

<div id="AdPro-esim-error">
    <div class="error-icon">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" fill="#e74c3c" />
            <path d="M15 9L9 15" stroke="white" stroke-width="2" stroke-linecap="round" />
            <path d="M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" />
        </svg>
    </div>
    
    <h1>שגיאה בתהליך התשלום</h1>
    
    <p class="error-message">התשלום לא בוצע בהצלחה.</p>
    
    <?php if (!empty($order_id)): ?>
        <p class="order-id">מספר הזמנה: <?php echo esc_html($order_id); ?></p>
    <?php endif; ?>
    
    <div class="error-details">
        <p>ייתכן שהייתה בעיה באחד מהפרטים הבאים:</p>
        <ul>
            <li>פרטי כרטיס האשראי שגויים</li>
            <li>אין מספיק מסגרת אשראי</li>
            <li>בעיה זמנית במערכת הסליקה</li>
            <li>כרטיס האשראי חסום לעסקאות מקוונות</li>
        </ul>
    </div>
    
    <div class="error-actions">
        <?php if ($country_data): ?>
            <a href="<?php echo esc_url(home_url("/esim/{$country_data['slug']}")); ?>" class="button primary-button">נסה שוב</a>
        <?php else: ?>
            <a href="<?php echo esc_url(home_url("/esim")); ?>" class="button primary-button">חזרה לחיפוש חבילות</a>
        <?php endif; ?>
    </div>
    
    <div class="support-info">
        <p>אם אתה ממשיך לקבל שגיאה זו, אנא צור קשר עם שירות הלקוחות שלנו:</p>
        <p><a href="tel:+97227222222">072-2222222</a> או <a href="mailto:support@yourdomain.com">support@yourdomain.com</a></p>
    </div>
</div>

<style>
    #AdPro-esim-error {
        max-width: 600px;
        margin: 50px auto;
        text-align: center;
        padding: 40px 30px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .error-icon {
        margin-bottom: 20px;
    }
    
    #AdPro-esim-error h1 {
        color: #e74c3c;
        font-size: 28px;
        margin-bottom: 20px;
    }
    
    .error-message {
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
    
    .error-details {
        margin: 30px 0;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        text-align: right;
    }
    
    .error-details p {
        margin-top: 0;
        color: #333;
        margin-bottom: 15px;
    }
    
    .error-details ul {
        text-align: right;
        padding-right: 20px;
        margin-bottom: 0;
    }
    
    .error-details li {
        margin-bottom: 8px;
        color: #444;
    }
    
    .error-actions {
        margin: 30px 0;
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
        background-color: #4a90e2;
        color: white;
    }
    
    .primary-button:hover {
        background-color: #3a7bc8;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .support-info {
        margin-top: 30px;
        color: #666;
        font-size: 14px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    
    .support-info a {
        color: #4a90e2;
        text-decoration: none;
    }
    
    .support-info a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        #AdPro-esim-error {
            padding: 30px 20px;
            margin: 30px 15px;
        }
        
        #AdPro-esim-error h1 {
            font-size: 24px;
        }
        
        .error-message {
            font-size: 16px;
        }
    }
</style>

<?php
get_footer();