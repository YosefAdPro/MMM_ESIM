<?php
/**
 * פונקציה ליצירת טבלאות SQL לאחסון נתוני חבילות eSIM
 */
function AdPro_create_database_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // טבלה לאחסון חבילות eSIM
    $table_packages = $wpdb->prefix . 'adpro_esim_packages';
    
    $sql_packages = "CREATE TABLE $table_packages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id varchar(128) NOT NULL,
        country_iso varchar(10) NOT NULL,
        provider_id varchar(128) NOT NULL,
        provider_name varchar(128) DEFAULT NULL,
        title varchar(255) DEFAULT NULL,
        retail_price decimal(10,2) DEFAULT NULL,
        currency_code varchar(10) DEFAULT NULL,
        data_limit varchar(50) DEFAULT NULL,
        data_unit varchar(20) DEFAULT NULL,
        validity_days int(11) DEFAULT NULL,
        product_details longtext DEFAULT NULL,
        countries longtext DEFAULT NULL,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY product_id (product_id),
        KEY country_iso (country_iso),
        KEY provider_id (provider_id)
    ) $charset_collate;";
    
    // טבלה לאחסון מדינות נתמכות
    $table_countries = $wpdb->prefix . 'adpro_esim_countries';
    
    $sql_countries = "CREATE TABLE $table_countries (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        iso varchar(10) NOT NULL,
        english_name varchar(128) NOT NULL,
        hebrew_name varchar(128) NOT NULL,
        slug varchar(128) DEFAULT NULL,
        has_packages tinyint(1) DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY iso (iso)
    ) $charset_collate;";
    
    // טבלה לאחסון רשתות סלולריות
    $table_networks = $wpdb->prefix . 'adpro_esim_networks';
    
    $sql_networks = "CREATE TABLE $table_networks (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id varchar(128) NOT NULL,
        country_iso varchar(10) NOT NULL,
        network_brand varchar(128) DEFAULT NULL,
        network_id varchar(128) DEFAULT NULL,
        is_4g tinyint(1) DEFAULT 0,
        is_5g tinyint(1) DEFAULT 0,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY country_iso (country_iso)
    ) $charset_collate;";
    
    // שימוש בקובץ ה-upgrade של וורדפרס להרצת השאילתות
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_packages);
    dbDelta($sql_countries);
    dbDelta($sql_networks);
    
    // שמירת גרסת המסד נתונים
    update_option('adpro_esim_db_version', '1.0');
}

// הוסף את יצירת הטבלאות בהפעלת התוסף
register_activation_hook(__FILE__, 'AdPro_create_database_tables');