/**
 * JavaScript לטיפול במודאלים ופעולות נוספות באזור האישי של eSIM
 */
jQuery(document).ready(function($) {
    // הצגת מודאל הפעלה
    $('.activation-button').on('click', function() {
        const qrCode = $(this).data('qr');
        const activationCode = $(this).data('code');
        const packageName = $(this).data('package');
        
        // יצירת תוכן המודאל
        let modalContent = `
            <div class="modal-qr-image">
                <img src="${qrCode}" alt="QR Code">
            </div>
            
            <div class="modal-activation-code">
                ${activationCode}
            </div>
            
            <div class="activation-steps">
                <h4>הוראות להפעלת ה-eSIM:</h4>
                <ol>
                    <li>ודא שהמכשיר שלך תומך ב-eSIM ומחובר לאינטרנט</li>
                    <li>לך להגדרות > סלולרי (או ניהול כרטיסי SIM)</li>
                    <li>בחר באפשרות "הוסף תוכנית סלולרית" או "הוסף eSIM"</li>
                    <li>סרוק את קוד ה-QR או הזן את קוד ההפעלה ידנית</li>
                    <li>המתן להשלמת ההתקנה ואשר את הפעלת החבילה</li>
                    <li>ודא שנדידה (Roaming) מופעלת בהגדרות</li>
                </ol>
            </div>
        `;
        
        // עדכון כותרת וטעינת תוכן למודאל
        $('#activation-modal h2').text(`הפעלת חבילת ${packageName}`);
        $('#activation-modal-content').html(modalContent);
        
        // הצגת המודאל
        $('#activation-modal').fadeIn(300);
    });
    
    // הצגת מודאל QR
    $('.view-qr-button').on('click', function() {
        const qrCode = $(this).data('qr');
        const activationCode = $(this).data('code');
        
        // יצירת תוכן המודאל
        let modalContent = `
            <div class="modal-qr-image">
                <img src="${qrCode}" alt="QR Code">
            </div>
            
            <div class="modal-activation-code">
                ${activationCode}
            </div>
        `;
        
        // טעינת תוכן למודאל
        $('#qr-modal-content').html(modalContent);
        
        // הצגת המודאל
        $('#qr-modal').fadeIn(300);
    });
    
    // גם תמונת QR בכרטיסייה עצמה יכולה לפתוח את המודאל
    $('.qr-thumbnail img').on('click', function() {
        const qrCode = $(this).attr('src');
        const activationCode = $(this).closest('.esim-package-card').find('.activation-code').text();
        
        // יצירת תוכן המודאל
        let modalContent = `
            <div class="modal-qr-image">
                <img src="${qrCode}" alt="QR Code">
            </div>
            
            <div class="modal-activation-code">
                ${activationCode}
            </div>
        `;
        
        // טעינת תוכן למודאל
        $('#qr-modal-content').html(modalContent);
        
        // הצגת המודאל
        $('#qr-modal').fadeIn(300);
    });
    
    // סגירת מודאלים
    $('.close-modal').on('click', function() {
        $(this).closest('.esim-modal').fadeOut(300);
    });
    
    // סגירת מודאל בקליק מחוץ לתוכן
    $('.esim-modal').on('click', function(e) {
        if ($(e.target).hasClass('esim-modal')) {
            $(this).fadeOut(300);
        }
    });
    
    // סגירת מודאל בלחיצה על אסקייפ
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // Escape key
            $('.esim-modal').fadeOut(300);
        }
    });
});