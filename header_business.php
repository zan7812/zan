<?php

/* $Id: header_business.php 58 2009-02-12 02:10:33Z john $ */

// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT
defined('SE_PAGE') or exit();

// INCLUDE business FILES
include "./include/class_business.php";
include "./include/functions_business.php";


// PRELOAD LANGUAGE
SE_Language::_preload(2001007);


// SET MENU VARS
if( ($user->user_exists && (int)$user->level_info['level_business_allow'] & 1) || (!$user->user_exists && $setting['setting_permission_business']) )
  $plugin_vars['menu_main'] = Array('file' => 'browse_allbusiness.php', 'title' => 2001007);

if( (int)$user->level_info['level_business_allow'] & 2 )
  $plugin_vars['menu_user'] = Array('file' => 'user_business.php', 'icon' => 'business_business16.gif', 'title' => 2001007);


// SET WHAT'S NEW PAGE UPDATES
if( ($user->level_info['level_business_allow'] & 1) && $page == "user_home" )
{
  // GET business SUBSCRIPTIONS
  $business_subscribes = Array();
  $business_subscribe_query = $database->database_query("SELECT se_allbusinessubscribes.allbusinessubscribe_time, se_allbusiness.business_id, se_allbusiness.business_title, count(se_businesscomments.businesscomment_id) AS total_comments FROM se_allbusinessubscribes LEFT JOIN se_allbusiness ON se_allbusinessubscribes.allbusinessubscribe_business_id=se_allbusiness.business_id LEFT JOIN se_businesscomments ON se_allbusiness.business_id=se_businesscomments.businesscomment_business_id AND se_businesscomments.businesscomment_date>se_allbusinessubscribes.allbusinessubscribe_time WHERE se_allbusinessubscribes.allbusinessubscribe_user_id='{$user->user_info['user_id']}' business BY se_allbusiness.business_id ORDER BY se_allbusiness.business_title");
  $total_business_subscribes = $database->database_num_rows($business_subscribe_query);
  while($subscribe_info = $database->database_fetch_assoc($business_subscribe_query))
  {
    $subscribe_info['total_photos'] = $database->database_num_rows($database->database_query("SELECT NULL FROM se_businessmedia INNER JOIN se_businessalbums ON se_businessmedia.businessmedia_businessalbum_id=se_businessalbums.businessalbum_id AND se_businessalbums.businessalbum_business_id='{$subscribe_info['business_id']}' WHERE se_businessmedia.businessmedia_date>'{$subscribe_info['allbusinessubscribe_time']}'"));
    $subscribe_info['total_posts'] = $database->database_num_rows($database->database_query("SELECT NULL FROM se_businessposts INNER JOIN se_businesstopics ON se_businessposts.businesspost_businesstopic_id=se_businesstopics.businesstopic_id AND se_businesstopics.businesstopic_business_id='{$subscribe_info['business_id']}' WHERE se_businessposts.businesspost_date>'{$subscribe_info['allbusinessubscribe_time']}'"));
    $business_subscribes[] = $subscribe_info;
  }

  // ASSIGN business SUBSCRIPTION SMARY VARIABLE
  $smarty->assign('business_subscribes', $business_subscribes);
  $smarty->assign('total_business_subscribes', $total_business_subscribes);

  // SET PROFILE MENU VARS
  if( $total_business_subscribes )
  {
    $plugin_vars['menu_userhome'] = Array('file'=> 'user_home_business.tpl');
  }
}


// SET PROFILE MENU VARS
if( ($owner->level_info['level_business_allow'] & 2) && $page == "profile")
{
  $business = new se_business($owner->user_info['user_id']);
  $sort_by = "se_businessmembers.businessmember_rank DESC, se_allbusiness.business_title";
  $where = "(se_businessmembers.businessmember_status='1')";

  // GET TOTAL allbusiness
  $total_allbusiness = $business->business_total($where);

  // GET allbusiness ARRAY
  $allbusiness = $business->business_list(0, $total_allbusiness, $sort_by, $where);

  // ASSIGN allbusiness SMARY VARIABLE
  $smarty->assign('allbusiness', $allbusiness);
  $smarty->assign('total_allbusiness', $total_allbusiness);


  // SET PROFILE MENU VARS
  if( $total_allbusiness )
  {
    $plugin_vars['menu_profile_tab'] = "";
    $plugin_vars['menu_profile_side'] = Array('file'=> 'profile_business.tpl', 'title' => 2001007, 'name' => 'business');
  }
}


// Use new template hooks
if( is_a($smarty, 'SESmarty') )
{
  $plugin_vars['uses_tpl_hooks'] = TRUE;
  
  if( !empty($plugin_vars['menu_main']) )
    $smarty->assign_hook('menu_main', $plugin_vars['menu_main']);
  
  if( !empty($plugin_vars['menu_user']) )
    $smarty->assign_hook('menu_user_apps', $plugin_vars['menu_user']);
  
  if( !empty($plugin_vars['menu_profile_side']) )
    $smarty->assign_hook('profile_side', $plugin_vars['menu_profile_side']);
  
  if( !empty($plugin_vars['menu_profile_tab']) )
    $smarty->assign_hook('profile_tab', $plugin_vars['menu_profile_tab']);
  
  if( !empty($plugin_vars['menu_userhome']) )
    $smarty->assign_hook('user_home', $plugin_vars['menu_userhome']);

  if( strpos($page, 'business')!==FALSE || $page=="profile" )
    $smarty->assign_hook('styles', './templates/styles_business.css');
}



// SET HOOKS
SE_Hook::register("se_search_do", 'search_business');
SE_Hook::register("se_user_delete", 'deleteuser_business');
SE_Hook::register("se_mediatag", 'mediatag_business');
SE_Hook::register("se_action_privacy", 'action_privacy_business');
SE_Hook::register("se_site_statistics", 'site_statistics_business');

?>