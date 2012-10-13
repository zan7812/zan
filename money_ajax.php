<?php
	include "header.php";
	$task = $_POST["task"];
	switch($task)
	{
		case "add_balans":
			$user_name = $_POST["user_name"];
			$balans = $_POST["balans"];
			if ($database->database_affected_rows($database->database_query("SELECT user_id FROM se_users WHERE user_username='$user_name'"))!=0)
			{
				$res = $database->database_query("UPDATE se_users SET user_balans=user_balans+$balans WHERE user_username='$user_name'");
			}
			else
			{
				$is_error = "user_name";
			}
		break;
		case "check_balans":
			$user_name = $_POST["user_name"];
			if ($database->database_affected_rows($database->database_query("SELECT user_id FROM se_users WHERE user_username='$user_name'"))!=0)
			{
				$money = $database->database_fetch_assoc($database->database_query("SELECT user_balans FROM se_users WHERE user_username='$user_name'"));
				$is_error = $money["user_balans"];
			}
			else
			{
				$is_error = "user_name";
			}
		break;
	}
	 echo json_encode(array('is_error'=>$is_error));
?>