<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<h1 id="box">Please do not refresh this page....</h1>

<form name='razorpayform' action="verify.php" method="POST">
    <input type="hidden" name="trans_id" id="transid">
    <input type="hidden" name="trans_amt" id="transamt">
    <input type="hidden" name="trans_status" id="transstatus">
    <input type="hidden" name="trans_rsp_msg" id="transrspmsg">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
</form>
<script>
// Checkout details as a json
var options = <?php echo $json?>;

/**
 * The entire list of Checkout fields is available at
 * https://docs.razorpay.com/docs/checkout-form#checkout-fields
 */
options.handler = function(response) {
    document.getElementById('transid').value = response.transid;
    document.getElementById('transamt').value = response.transamt;
    document.getElementById('transstatus').value = response.transstatus;
    document.getElementById('transrspmsg').value = response.transrspmsg;
    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
    document.getElementById('razorpay_signature').value = response.razorpay_signature;
    document.razorpayform.submit();
};

// Boolean whether to show image inside a white frame. (default: true)
options.theme.image_padding = false;

options.modal = {
    ondismiss: function() {
        console.log("This code runs when the popup is closed");
    },
    // Boolean indicating whether pressing escape key 
    // should close the checkout form. (default: true)
    escape: true,
    // Boolean indicating whether clicking translucent blank
    // space outside checkout form should close the form. (default: false)
    backdropclose: false
};

setTimeout(() => {
    const box = document.getElementById('box');

    box.style.display = 'none';

}, 2000);

var rzp = new Razorpay(options);
rzp.on('payment.failed', function (response){

        alert(response.error.metadata.order_id);
        alert(response.error.metadata.payment_id);
});

rzp.open();
</script>