<?php

/* $Id: header_news.php 7 2009-01-11 06:01:49Z john $ */

// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT
defined('SE_PAGE') or exit();

// INCLUDE allnews CLASS FILE
include "./include/class_news.php";

// INCLUDE allnews FUNCTION FILE
include "./include/functions_news.php";

// PRELOAD LANGUAGE
SE_Language::_preload(4500207);

// SET MAIN MENU VARS
if( ($user->user_exists && $user->level_info['level_news_allow']) || (!$user->user_exists && $setting['setting_permission_news']) )
{
  $plugin_vars['menu_main'] = array('file' => 'browse_allnews.php', 'title' => 4500207);
}

// SET USER MENU VARS
if( ($user->user_exists && $user->level_info['level_news_allow']) )
{
  $plugin_vars['menu_user'] = array('file' => 'user_news.php', 'icon' => 'news_news16.gif', 'title' => 4500207);
}

// SET PROFILE MENU VARS
if( $owner->level_info['level_news_allow'] && $page=="profile" )
{
  // START news
  $news = new se_news($owner->user_info['user_id']);
  $listings_per_page = 5;
  $sort = "news_date DESC";

  // GET PRIVACY LEVEL AND SET WHERE
  $privacy_max = $owner->user_privacy_max($user);
  $where = "(news_privacy & $privacy_max)";

  // GET TOTAL LISTINGS
  $total_allnews = $news->news_total($where);

  // GET LISTING ARRAY
  $allnews = $news->news_list(0, $listings_per_page, $sort, $where);

  // ASSIGN ENTRIES SMARY VARIABLE
  $smarty->assign_by_ref('allnews', $allnews);
  $smarty->assign('total_allnews', $total_allnews);
  
  //print_r($allnews);
  
  // SET PROFILE MENU VARS
  if( $total_allnews )
  {
    $plugin_vars['menu_profile_tab'] = array('file'=> 'profile_news.tpl', 'title' => 4500207);
    $plugin_vars['menu_profile_side'] = "";
  }
}



// SET HOOKS
SE_Hook::register("se_search_do", 'search_news');

SE_Hook::register("se_user_delete", 'deleteuser_news');

SE_Hook::register("se_site_statistics", 'site_statistics_news');

?>