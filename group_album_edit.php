<?php

/* $Id: user_album_edit.php 2 2009-01-10 20:53:09Z john $ */

$page = "group_album_edit";
include "header.php";

if(isset($_POST['task'])) {
    $task = $_POST['task'];
} elseif(isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = "main";
}
if(isset($_GET['album_id'])) {
    $album_id = $_GET['album_id'];
} elseif(isset($_POST['album_id'])) {
    $album_id = $_POST['album_id'];
} else {
    exit();
}

$album_query = $database->database_query("SELECT se_groupalbums.* FROM se_groupalbums
        WHERE se_groupalbums.groupalbum_id='$album_id' LIMIT 1");
$album_info = $database->database_fetch_assoc($album_query);
// ENSURE ALBUMS ARE ENABLED FOR THIS USER
if($user->user_info['user_id'] != $album_info['groupalbum_user_id']) {
    header("Location: user_home.php");
    exit();
}

$group = new se_group($user->user_info['user_id'], $album_info['groupalbum_group_id']);
// SET VARIABLES
$result = 0;
$is_error = 0;

// SAVE NEW INFO
if($task == "dosave") {
    $album_info[groupalbum_title] = censor($_POST['album_title']);
    $album_info[groupalbum_desc] = censor(str_replace("\r\n", "<br>", $_POST['album_desc']));
//  $album_info[album_search] = $_POST['album_search'];
//  $album_info[album_privacy] = $_POST['album_privacy'];
//  $album_info[album_comments] = $_POST['album_comments'];
//  $album_info[album_tag] = $_POST['album_tag'];
    $album_info[groupalbum_dateupdated] = time();
//  $album_info[album_competition] = $_POST['album_competition'];


    // MAKE SURE SUBMITTED PRIVACY OPTIONS ARE ALLOWED, IF NOT, SET TO EVERYONE
//  if(!in_array($album_info[album_privacy], $level_album_privacy)) { $album_info[album_privacy] = $level_album_privacy[0]; }
//  if(!in_array($album_info[album_comments], $level_album_comments)) { $album_info[album_comments] = $level_album_comments[0]; }
//  if(!in_array($album_info[album_tag], $level_album_tag)) { $album_info[album_tag] = $level_album_tag[0]; }

    // CHECK THAT TITLE IS NOT BLANK
    if(trim($album_info[groupalbum_title]) == "") {
        $is_error = 1000073;
    }

    // IF NO ERROR, CONTINUE
    if($is_error == 0) {

        // EDIT ALBUM IN DATABASE
        $database->database_query("UPDATE se_groupalbums SET groupalbum_title='$album_info[groupalbum_title]',
				    groupalbum_desc='$album_info[groupalbum_desc]',
				    groupalbum_search='$album_info[groupalbum_search]',
				    groupalbum_privacy='$album_info[groupalbum_privacy]',
				    groupalbum_comments='$album_info[groupalbum_comments]',
				    groupalbum_tag='$album_info[groupalbum_tag]',
				    groupalbum_dateupdated='$album_info[groupalbum_dateupdated]' WHERE groupalbum_id='$album_info[groupalbum_id]'");

        // UPDATE LAST UPDATE DATE (SAY THAT 10 TIMES FAST)
        $user->user_lastupdate();

        $result = 1;
    }
}

// RESTORE LINE BREAKS
$album_info[groupalbum_desc] = str_replace("<br>", "\r\n", $album_info[groupalbum_desc]);

// ASSIGN VARIABLES AND SHOW EDIT ALBUMS PAGE
$smarty->assign('group', $group);
$smarty->assign('result', $result);
$smarty->assign('is_error', $is_error);
$smarty->assign('album_info', $album_info);
//$smarty->assign('privacy_options', $privacy_options);
//$smarty->assign('comment_options', $comment_options);
//$smarty->assign('tag_options', $tag_options);
include "footer.php";
?>