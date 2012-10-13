<?php
include("header.php");
$purse_info = $database->database_fetch_assoc($database->database_query("SELECT*FROM se_wm"));
$admin_purse = $setting["setting_admin_purse"];
$admin_purse_eur = $setting["setting_admin_purse_eur"];
$admin_purse_usd = $setting["setting_admin_purse_usd"];

if (in_array($_POST["LMI_PAYEE_PURSE"],$purse_info))
{
	user_transaction($_POST["payment_id"],$_POST["LMI_PAYER_WM"]);
	print "yes";
}

?>