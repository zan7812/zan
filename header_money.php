<?php
	include ("./include/functions_payment.php");
	$plugin_vars['menu_user'] = Array('file' => 'user_payment.php', 'icon' => 'money16.gif', 'title' => 1630400021);
	if ($page=='profile')
	{
		$user_balans = get_user_balans($user->user_info["user_id"]);
		$plugin_vars['menu_profile_side'] = Array('file'=> 'profile_money.tpl', 'title' => 1630400022, 'name' => 'group');
		$smarty->assign("user_balans",$user_balans);
	}
?>