<?php
include "header.php";

function add_sms($sms_code,$msg,$sms_summ)
{
	global $database,$user;
	$sms_code = intval($sms_code);
	$sms_summ = intval($sms_summ);
	$res = $database->database_query("INSERT INTO se_sms_payment SET sms_code = $sms_code, sms_msg ='$msg',sms_summ = $sms_summ");
	if ($res)
	{
		return true;
	}
}

$smsid = $_GET['smsid'];
$skey = $_GET['skey'];
$summ = $_GET["cost"];
$msg = $_GET["msg"];
$secret_info = $database->database_fetch_assoc($database->database_query("SELECT wm_secret FROM se_wm"));
$secretkey = $secret_info["wm_secret"];

if (md5($secretkey) != $skey) header ("HTTP/1.0 404 Not Found");

add_sms($smsid,$msg,$summ);

echo "smsid:$smsid\n";	
echo "Ваш платеж принят.\n";
?>