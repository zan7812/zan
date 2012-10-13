<?php

/* $Id: album.php 16 2009-01-13 04:01:31Z john $ */

$page = "group_album";
include "header.php";

// PARSE GET/POST
if(isset($_POST['p'])) { $p = $_POST['p']; } elseif(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }
if(isset($_GET['album_id'])) { $album_id = $_GET['album_id']; } else { $album_id = 0; }


// SET VARS
$media_per_page = 20;

// GET ALBUM INFO
$album_query = $database->database_query("SELECT se_groupalbums.* FROM se_groupalbums
        WHERE se_groupalbums.groupalbum_id='$album_id' LIMIT 1");
$album_info = $database->database_fetch_assoc($album_query);

if( !$album_info ) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 1630400036);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}

$owner = new se_user($album_info['groupalbum_user_id']);

// SET WHERE/SORTBY
$where = "(groupmedia_groupalbum_id='{$album_info['groupalbum_id']}')";
$sortby = " groupalbum_datecreated ASC";
$select = "";

// UPDATE ALBUM VIEWS
if($user->user_info[user_id] != $owner->user_info['user_id']) {
  $album_views_new = $album_info[groupalbum_views] + 1;
  $database->database_query("UPDATE se_groupalbums SET groupalbum_views='$album_views_new' WHERE groupalbum_id='{$album_info['groupalbum_id']}' LIMIT 1");
}


// GET TOTAL FILES IN ALBUM
$total_files = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) as count FROM se_groupmedia WHERE ".$where));
//print_r($album_info);
// MAKE MEDIA PAGES
$page_vars = make_page($total_files['count'], $media_per_page, $p);

$group = new se_group($user->user_info['user_id'], $album_info['groupalbum_group_id']);
// GET MEDIA ARRAY
$file_array = $group->group_album_media_list($page_vars[0], $media_per_page, $sortby, $where, $album_info['groupalbum_id']);

// SET GLOBAL PAGE TITLE
$global_page_title[0] = 1000155;
$global_page_title[1] = $owner->user_displayname;
$global_page_title[2] = $album_info['groupalbum_title'];
$global_page_description[0] = 1000156;
$global_page_description[1] = $album_info['groupalbum_desc'];

// ASSIGN VARIABLES AND DISPLAY ALBUM PAGE
$smarty->assign('group', $group);
$smarty->assign('album_info', $album_info);
$smarty->assign('files', $file_array);
$smarty->assign('total_files', $total_files['count']);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($file_array));
include "footer.php";
?>