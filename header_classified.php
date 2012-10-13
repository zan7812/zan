<?php

/* $Id: header_classified.php 7 2009-01-11 06:01:49Z john $ */

// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT
defined('SE_PAGE') or exit();

// INCLUDE CLASSIFIEDS CLASS FILE
include "./include/class_classified.php";

// INCLUDE CLASSIFIEDS FUNCTION FILE
include "./include/functions_classified.php";

// PRELOAD LANGUAGE
SE_Language::_preload(4500007);

// SET MAIN MENU VARS
if( ($user->user_exists && $user->level_info['level_classified_allow']) || (!$user->user_exists && $setting['setting_permission_classified']) )
{
  $plugin_vars['menu_main'] = array('file' => 'browse_classifieds.php', 'title' => 4500007);
}

// SET USER MENU VARS
if( ($user->user_exists && $user->level_info['level_classified_allow']) )
{
  $plugin_vars['menu_user'] = array('file' => 'user_classified.php', 'icon' => 'classified_classified16.gif', 'title' => 4500007);
}

// SET PROFILE MENU VARS
if( $owner->level_info['level_classified_allow'] && $page=="profile" )
{
  // START CLASSIFIED
  $classified = new se_classified($owner->user_info['user_id']);
  $listings_per_page = 5;
  $sort = "classified_date DESC";

  // GET PRIVACY LEVEL AND SET WHERE
  $privacy_max = $owner->user_privacy_max($user);
  $where = "(classified_privacy & $privacy_max)";

  // GET TOTAL LISTINGS
  $total_classifieds = $classified->classified_total($where);

  // GET LISTING ARRAY
  $classifieds = $classified->classified_list(0, $listings_per_page, $sort, $where);

  // ASSIGN ENTRIES SMARY VARIABLE
  $smarty->assign_by_ref('classifieds', $classifieds);
  $smarty->assign('total_classifieds', $total_classifieds);
  
  //print_r($classifieds);
  
  // SET PROFILE MENU VARS
  if( $total_classifieds )
  {
    $plugin_vars['menu_profile_tab'] = array('file'=> 'profile_classified.tpl', 'title' => 4500007);
    $plugin_vars['menu_profile_side'] = "";
  }
}



// SET HOOKS
SE_Hook::register("se_search_do", 'search_classified');

SE_Hook::register("se_user_delete", 'deleteuser_classified');

SE_Hook::register("se_site_statistics", 'site_statistics_classified');

?>