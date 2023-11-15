<?php

require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');
date_default_timezone_set("Asia/Kolkata");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMail($email,$token,$type)
{

    if($type == "email_confirmation")
    {
        $page = 'verify.php';
        $subject = "Account Verification Link";
        $content = "Confirm your email";
    }
    else
    {
        $page = 'index.php';
        $subject = "Account Reset Link";
        $content = "Reset your Account";
    }

    require("PHPMailer/PHPMailer.php");
    require("PHPMailer/SMTP.php");
    require("PHPMailer/Exception.php");

    $mail = new PHPMailer(true);

    try {  
      
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'kuldeeprajpurohit012@gmail.com';                     //SMTP username
        $mail->Password   = 'miohuspndrnvtrow';                               //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    
        //Recipients
        $mail->setFrom('kuldeeprajpurohit012@gmail.com', 'KS Hotel');
        $mail->addAddress($email);     //Add a recipient
        
        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = "Thanks for registration!
          Click the link to $content: <br>
          <a href='http://127.0.0.1/HBWebsite/$page?$type&email=$email&token=$token'>Click Me</a>";
        
        $mail->send();
        return true;
    } 
    catch (Exception $e){
        return false;
    }
}

if(isset($_POST['register']))
{
    $data = filteration($_POST);

    // match password and confirm password field

    if($data['pass'] != $data['cpass']){
        echo 'pass_mismatch';
        exit;
    }

    // check user exists or not

    $u_exists = select("SELECT * FROM `user_cred` WHERE `email`=? OR `phonenum` = ?  LIMIT 1", 
    [$data['email'],$data['phonenum']],'ss');
    
    if(mysqli_num_rows($u_exists)!=0){
        $u_exists_fetch = mysqli_fetch_assoc($u_exists);
        echo ($u_exists_fetch['phonenum'] == $data['phonenum']) ? 'phone_already' : 'email_already';
        exit;          
    }
   
    // upload user image to server

   $img = uploadUserImage($_FILES['profile']);
    
    if($img == 'inv_img') {
        echo 'inv_img';
         exit;
    } 
    else if($img == 'upd_failed') {
         echo 'upd_failed';
         exit;
    }
    

    //send confirmation link to user's email

    $token = bin2hex(random_bytes(16));

    if(!sendMail($data['email'],$token,'email_confirmation'))
    {
        echo 'email_failed';
        exit;
    }

    $password=password_hash($_POST['pass'],PASSWORD_BCRYPT);

    $query = "INSERT INTO `user_cred`(`name`, `email`, `address`, `phonenum`, `pincode`, `dob`, `profile`, `password`, `token`) VALUES (?,?,?,?,?,?,?,?,?)";

    $values = [$data['name'],$data['email'],$data['address'],$data['phonenum'],$data['pincode'],$data['dob'],
    $img,$password,$token];

   if(insert($query,$values,'sssssssss')){
    echo 1;
   }
   else {
    echo 'ins_failed';
   }    

}

if(isset($_POST['login']))
{
    $data = filteration($_POST);

    $u_exists = select("SELECT * FROM `user_cred` WHERE `email`=? OR `phonenum`=? LIMIT 1",
    [$data['email_mob'],$data['email_mob']],"ss");

    if(mysqli_num_rows($u_exists)==0)
    {
        echo 'inv_email_mob';
        exit;
    }
    else
    {
        $u_fetch = mysqli_fetch_assoc($u_exists);

        if($u_fetch['is_verified']==0){
            echo 'not_verified';
        }
        else if($u_fetch['status']==0)
        {
            echo 'inactive';
        }
        else
        {
            if(!password_verify($data['pass'],$u_fetch['password']))
            {
              echo 'invalid_pass';
            }
            else
            {
                session_start();
                $_SESSION['login'] = true;
                $_SESSION['uId'] = $u_fetch['id'];
                $_SESSION['uName'] = $u_fetch['name'];
                $_SESSION['uPic'] = $u_fetch['profile'];
                $_SESSION['uPhone'] = $u_fetch['phonenum'];
                $_SESSION['uEmail'] = $u_fetch['email'];
                echo 1;
            }
        }
    }   
}

if(isset($_POST['forgot']))
{
    $data = filteration($_POST);

    $u_exists = select("SELECT * FROM `user_cred` WHERE `email`=? LIMIT 1", [$data['email']],"s");

    if(mysqli_num_rows($u_exists)==0){
        echo 'inv_email';
    }
    else
    {
        $u_fetch = mysqli_fetch_assoc($u_exists);
        if($u_fetch['is_verified']==0){
            echo 'not_verified';
        }
        else if($u_fetch['status']==0)
        {
            echo 'inactive';
        }
        else
        {
            // send reset link to email
            $token = bin2hex(random_bytes(16));

            if(!sendMail($data['email'],$token,'account_recovery'))
            {
               echo 'mail_failed'; 
            }
            else
            {
                $date = date("Y-m-d");

                $query = mysqli_query($con,"UPDATE `user_cred` SET `token`='$token', `t_expire`='$date' 
                WHERE `id`='$u_fetch[id]'");

                if($query)
                {
                    echo 1;
                }
                else
                {
                    echo 'upd_failed';
                }
            }
        }
    }
}

if(isset($_POST['recover_user']))
{
    $data = filteration($_POST);

    $enc_pass = password_hash($data['pass'],PASSWORD_BCRYPT);

    $query = "UPDATE `user_cred` SET `password`=?, `token`=?, `t_expire`=?
    WHERE `email`=? AND `token`=?";

    $values = [$enc_pass,null,null,$data['email'],$data['token']];

    if(update($query,$values,'sssss'))
    {
        echo 1;
    }
    else
    {
        echo 0;
    }
}

?>