<?php

/* $Id: header_album.php 16 2009-01-13 04:01:31Z john $ */

// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT
defined('SE_PAGE') or exit();

// INCLUDE ALBUM CLASS FILE
include "./include/class_album.php";

// INCLUDE ALBUM FUNCTION FILE
include "./include/functions_album.php";


// PRELOAD LANGUAGE
SE_Language::_preload_multi(1000007, 1000123, 1000137);

// SET MAIN MENU VARS
if( ($user->user_exists && $user->level_info['level_album_allow']) || (!$user->user_exists && $setting['setting_permission_album']) )
{
  $plugin_vars['menu_main'] = Array('file' => 'browse_albums.php', 'title' => 1000123);
}

// SET USER MENU VARS
if( $user->user_exists && $user->level_info['level_album_allow'] )
{
  $plugin_vars['menu_user'] = Array('file' => 'user_album.php', 'icon' => 'album_album16.gif', 'title' => 1000007);
}

// SET PROFILE MENU VARS
if($owner->level_info['level_album_allow'] == 1 && $page == "profile") {

  // START ALBUM
  $album = new se_album($owner->user_info['user_id']);
  $sort = "album_id DESC";

  // GET PRIVACY LEVEL AND SET WHERE
  $album_privacy_max = $owner->user_privacy_max($user);
  $where = "(album_privacy & $album_privacy_max)";

  // GET TOTAL ALBUMS
  $total_albums = $album->album_total($where);

  // GET ALBUM ARRAY
  $albums = $album->album_list(0, $total_albums, $sort, $where);

  // ASSIGN ALBUMS SMARY VARIABLE
  $smarty->assign('albums', $albums);
  $smarty->assign('total_albums', $total_albums);

  // SET PROFILE MENU VARS
  if($total_albums != 0) {

    // DETERMINE WHERE TO SHOW ALBUMS
    $level_album_profile = explode(",", $owner->level_info['level_album_profile']);
    if(!in_array($owner->user_info['user_profile_album'], $level_album_profile)) { $user_profile_album = $level_album_profile[0]; } else { $user_profile_album = $owner->user_info['user_profile_album']; }

    // SHOW ALBUM IN APPROPRIATE LOCATION
    if($user_profile_album == "tab") {
      $plugin_vars['menu_profile_tab'] = Array('file'=> 'profile_album_tab.tpl', 'title' => 1000007);
    } else {
      $plugin_vars['menu_profile_side'] = Array('file'=> 'profile_album_side.tpl', 'title' => 1000007);
    }
  }
}


// SET SEARCH HOOK
if($page == "search")
  SE_Hook::register("se_search_do", 'search_album');

// SET USER DELETION HOOK
SE_Hook::register("se_user_delete", 'deleteuser_album');

// SET MEDIA TAG HOOK
SE_Hook::register("se_mediatag", 'mediatag_album');

// SET SITE STATISTICS HOOK
SE_Hook::register("se_site_statistics", 'site_statistics_album');

$result = $database->database_query("SELECT competition_id, competition_title FROM se_competitions WHERE competition_status = 0 AND competition_category = 1");
while($rez = $database->database_fetch_assoc($result)){
    $album_competitions[] = $rez;
}
if($album_competitions) $smarty->assign('albumcompetitions', $album_competitions);

?>