<?php

require('config.php');

require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');


session_start();

function regenerate_session($uId)
{
    $user_q = select("SELECT * FROM `user_cred` WHERE `id`=? LIMIT 1",[$uId],'i');
    $user_fetch = mysqli_fetch_assoc($user_q);

    $_SESSION['login'] = true;
    $_SESSION['uId'] = $user_fetch['id'];
    $_SESSION['uName'] = $user_fetch['name'];
    $_SESSION['uPic'] = $user_fetch['profile'];
    $_SESSION['uPhone'] = $user_fetch['phonenum'];
    $_SESSION['uEmail'] = $user_fetch['email'];

}

require('razorpay-php/Razorpay.php');
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$success = true;

$error = "Payment Failed";

if (empty($_POST['razorpay_payment_id']) === false)
{
    $api = new Api($keyId, $keySecret);

    try
    {
        // Please note that the razorpay order ID must
        // come from a trusted source (session here, but
        // could be database or something else)
        $attributes = array(
            'razorpay_order_id' => $_SESSION['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature'],
            'transid' => $_POST['trans_id'],
            'transamt' => $_POST['trans_amt'],
            'transstatus' => $_POST['trans_status'],
            'transrspmsg' => $_POST['trans_rsp_msg'],

        );

        $api->utility->verifyPaymentSignature($attributes);
    }
    catch(SignatureVerificationError $e)
    {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
}

if ($success === true)
{
    $orderid = $_SESSION['razorpay_order_id'];
    $paymentid = $_POST['razorpay_payment_id'];
    $email = $_SESSION['email'];
    $name = $_SESSION['name'];
    $phonenum = $_SESSION['phonenum'];
    $address = $_SESSION['address'];
    $orderid1 =  $_SESSION['orderid1'];
    $custid =  $_SESSION['custid'];
    $amount = $_SESSION['txnamount'];
    $checkin = $_SESSION['checkin'];
    $checkout = $_SESSION['checkout'];
    $transid = $_POST['trans_id'];
    $transamt = $_POST['trans_amt'];
    $transtatus = $_POST['trans_status'];
    $transrspmsg = $_POST['trans_rsp_msg'];
    date_default_timezone_set("Asia/Kolkata");
    $ordate = date('d-m-y h-i-s');

    $query1 = "INSERT INTO `booking_order`( `user_id`, `room_id`, `check_in`, `check_out`, `order_id`,`trans_id`,`trans_amt`) VALUES (?,?,?,?,?,?,?)";
    insert($query1,[$custid,$_SESSION['room']['id'],$checkin,$checkout,$orderid1,$paymentid,$amount],'isssssi');

    $booking_id = mysqli_insert_id($con);

    $query2 = "INSERT INTO `order_details`(`booking_id`,`room_name`,`price`,`total_pay`,`user_name`,`email`, `phonenum`,`address`) VALUES (?,?,?,?,?,?,?,?)";
    insert($query2,[$booking_id,$_SESSION['room']['name'],$_SESSION['room']['price'],
    $amount,$name,$email,$phonenum,$address],'isiissss');

    $slct_query = "SELECT `booking_id`,`user_id` FROM `booking_order`
     WHERE `order_id`= '$orderid1'";

    $slct_res = mysqli_query($con,$slct_query);

    if(mysqli_num_rows($slct_res)==0)
    {
        redirect('../index.php');
    }

    $slct_fetch = mysqli_fetch_assoc($slct_res);

    if(!(isset($_SESSION['login']) && $_SESSION['login'] == true)){
        regenerate_session($slct_fetch['user_id']);
    }

    $upd_query = "UPDATE `booking_order` SET `booking_status`='booked',
    `trans_id`= '$paymentid',`trans_amt`='$amount',
    `trans_status`='successful',`trans_resp_msg`='booked successfully'
    WHERE `booking_id` ='$slct_fetch[booking_id]'";

    mysqli_query($con,$upd_query);
    
}
else
{
    $upd_query = "UPDATE  `booking_order` SET `booking_status`='payment failed',
    `trans_id`= '$paymentid',`trans_amt`='$amount',
    `trans_status`='failed',`trans_resp_msg`='booking Failure'
    WHERE `booking_id` ='$slct_fetch[booking_id]'";

    mysqli_query($con,$upd_query);    
}

redirect('../success.php?order='.$orderid1);    

?>
