<?php

require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');

require('config.php');
require('razorpay-php/Razorpay.php');

session_start();

// Create the Razorpay Order

use Razorpay\Api\Api;

if(!isset($_SESSION['login']) && $_SESSION['login']==true){
    redirect('../index.php');
}

if(isset($_POST['pay_now']))
{

    $EMAIL = $_POST['email'];
    $NAME = $_POST['name'];
    $PHONE = $_POST['phonenum'];
    $ADDRESS = $_POST['address'];
    $ORDER_ID = 'ORD_'.$_SESSION['uId'].random_int(11111,999999);
    $CUST_ID = $_SESSION['uId'];
    $TXN_AMOUNT = $_SESSION['room']['payment'];
    $CHECKIN = $_POST['checkin'];
    $CHECKOUT = $_POST['checkout'];

    $_SESSION['email'] = $EMAIL;
    $_SESSION['name'] = $NAME;
    $_SESSION['phonenum'] = $PHONE;
    $_SESSION['address'] = $ADDRESS;
    $_SESSION['orderid1'] = $ORDER_ID;
    $_SESSION['custid'] = $CUST_ID;
    $_SESSION['txnamount'] = $TXN_AMOUNT;
    $_SESSION['checkin'] = $CHECKIN;
    $_SESSION['checkout'] = $CHECKOUT;

    $api = new Api($keyId, $keySecret);

    //
    // We create an razorpay order using orders api
    // Docs: https://docs.razorpay.com/docs/orders
    //

    $orderData = [
    'receipt'         => 3456,
    'amount'          => $TXN_AMOUNT * 100, // 2000 rupees in paise
    'currency'        => 'INR',
    'payment_capture' => 1 // auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);

    $razorpayOrderId = $razorpayOrder['id'];

    $_SESSION['razorpay_order_id'] = $razorpayOrderId;

    $displayAmount = $amount = $orderData['amount'];

    if ($displayCurrency !== 'INR')
    {
    $url = "https://api.fixer.io/latest?symbols=$displayCurrency&base=INR";
    $exchange = json_decode(file_get_contents($url), true);

    $displayAmount = $exchange['rates'][$displayCurrency] * $amount / 100;
    }

    $checkout = 'manual';

    if (isset($_GET['checkout']) and in_array($_GET['checkout'], ['automatic', 'manual'], true))
    {
    $checkout = $_GET['checkout'];
    }

    $data = [
    "key"               => $keyId,
    "amount"            => $amount,
    "name"              => $_POST['name'],
    "description"       => "Providing Room Services",
    "image"             => "http://127.0.0.1/HBWebsite/images/users/razorpay.svg",
    "prefill"           => [
    "name"              => "Daft Punk",
    "email"             => $EMAIL,
    "contact"           => $PHONE,
    ],
    "notes"             => [
    "address"           => "Hello World",
    "merchant_order_id" => "12312321",
    ],
    "theme"             => [
    "color"             => "#F37254"
    ],
    "order_id"          => $razorpayOrderId,
    ];

    if ($displayCurrency !== 'INR')
    {
    $data['display_currency']  = $displayCurrency;
    $data['display_amount']    = $displayAmount;
    }

    $json = json_encode($data);

    require("checkout/{$checkout}.php");

}


