jQuery(document).ready(function($) {
    // בדיקה אם יש פרמטר ic_checkout בכתובת
    const urlParams = new URLSearchParams(window.location.search);
    const isIcountCheckout = urlParams.has('ic_checkout');
    const orderId = urlParams.get('ic_order_id');
    const paymentUrl = urlParams.get('ic_payment_url');
    const returnUrl = urlParams.get('ic_return_url');
    
    if (isIcountCheckout && paymentUrl) {
        // יצירת מודאל התשלום
        if (!$('#payment-modal').length) {
            $('body').append(`
                <div id="payment-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; justify-content: center; align-items: center;">
                    <div style="position: relative; width: 90%; max-width: 800px; height: 80%; background: white; border-radius: 10px; overflow: hidden;">
                        <button id="close-modal" style="position: absolute; top: 10px; right: 10px; background: #f0f0f0; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; z-index: 10;">&times;</button>
                        <iframe id="payment-iframe" src="${decodeURIComponent(paymentUrl)}" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
            `);
            
            // טיפול בסגירת המודאל
            $('#close-modal').on('click', function() {
                $('#payment-modal').hide();
                if (confirm('האם אתה בטוח שברצונך לבטל את התשלום?')) {
                    window.location.href = wc_checkout_params.checkout_url;
                } else {
                    $('#payment-modal').show();
                }
            });
            
            // האזנה להודעות מ-iframe
            window.addEventListener('message', function(event) {
                try {
                    const data = event.data;
                    if (data && data.type === 'icount_payment_success') {
                        // תשלום הצליח
                        window.location.href = decodeURIComponent(returnUrl);
                    } else if (data && data.type === 'icount_payment_failure') {
                        // תשלום נכשל
                        $('#payment-modal').hide();
                        alert('תשלום נכשל. אנא נסה שנית.');
                        window.location.href = wc_checkout_params.checkout_url;
                    }
                } catch (error) {
                    console.error('Error processing message:', error);
                }
            });
        }
    }
});