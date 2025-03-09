jQuery(document).ready(function($) {
    
    // ==== סקריפטים לדף הגדרות ====
    
    // בדיקת חיבור ל-API של מובימטר
    $('#test-mobimatter-api').on('click', function(e) {
        e.preventDefault();
        var $result = $('#api-test-result');
        
        $result.html('<span style="color: #aaa;">בודק חיבור...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'AdPro_test_mobimatter_api'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span style="color: green;">✓ החיבור תקין!</span>');
                } else {
                    $result.html('<span style="color: red;">✗ שגיאה: ' + response.data + '</span>');
                }
            },
            error: function() {
                $result.html('<span style="color: red;">✗ שגיאה בבדיקה</span>');
            }
        });
    });
    
    // בדיקת חיבור ל-API של iCount
    $('#test-icount-api').on('click', function(e) {
        e.preventDefault();
        var $result = $('#icount-test-result');
        
        $result.html('<span style="color: #aaa;">בודק חיבור...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'AdPro_test_icount_api'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span style="color: green;">✓ החיבור תקין!</span>');
                } else {
                    $result.html('<span style="color: red;">✗ שגיאה: ' + response.data + '</span>');
                }
            },
            error: function() {
                $result.html('<span style="color: red;">✗ שגיאה בבדיקה</span>');
            }
        });
    });
    
    // ==== סקריפטים לדף ניהול תוכן מדינות ====
    
    // טאבים של מדינות
    $('.country-tab').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).attr('href');
        
        $('.country-tab').removeClass('active');
        $('.country-tab-content').removeClass('active');
        
        $(this).addClass('active');
        $(tabId).addClass('active');
    });
    
    // העלאת תמונות
    $('.media-upload-button').on('click', function(e) {
        e.preventDefault();
        
        var targetInput = $(this).data('target');
        var $input = $('input[name="' + targetInput + '"]');
        
        var mediaUploader = wp.media({
            title: 'בחר תמונה',
            button: {
                text: 'שימוש בתמונה זו'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // ==== סקריפטים לדף ניהול ספקים ====
    
    // עדכון תווית המתג
    $('input[type="checkbox"]').on('change', function() {
        var $label = $(this).siblings('.switch-label');
        
        if ($(this).is(':checked')) {
            $label.text('מוסתר');
        } else {
            $label.text('מוצג');
        }
    });
});