<?php

/* $Id: user_album_add.php 2 2009-01-10 20:53:09Z john $ */

$page = "group_album_add";
include "header.php";

if(isset($_POST['task'])) {
    $task = $_POST['task'];
} else {
    $task = "main";
}
if(isset($_POST['group_id'])) {
    $group_id = $_POST['group_id'];
}elseif(isset($_GET['group_id'])) {
    $group_id = $_GET['group_id'];
} else {
    $group_id = 0;
}

// DISPLAY ERROR PAGE IF NO OWNER
$group = new se_group($user->user_info['user_id'], $group_id);
if( !$group->group_exists ) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 2000219);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}

$result = $database->database_query("SELECT groupalbum_id, groupalbum_user_id, groupalbum_datecreated, groupalbum_dateupdated, groupalbum_title, groupalbum_desc,
    groupalbum_views, groupalbum_totalfiles, groupalbum_totalspace FROM se_groupalbums WHERE groupalbum_group_id = ".$group->group_info['group_id']);
while($rez = $database->database_fetch_assoc($result)) {
    $group_albums[] = $rez;
}

$create_album = 0;
if($group->user_rank > -1) $create_album = 1;
else {
    header("Location: group.php?group_id=".$group->group_info['group_id']);
    exit();
}

foreach($group_albums as $item) {
    if($item['groupalbum_user_id'] == $user->user_info['user_id']) header("Location: group.php?group_id=".$group->group_info['group_id']);
}

// SET VARS
$is_error = 0;
$album_title = "";
$album_desc = "";
$album_search = 1;
$album_privacy = 255;
$album_comments = 127;
$album_tag = 7;


if($task == "doadd") {

    $album_title = censor($_POST['album_title']);
    $album_desc = censor(str_replace("\r\n", "<br>", $_POST['album_desc']));
//    $album_search = $_POST['album_search'];
//    $album_privacy = $_POST['album_privacy'];
//    $album_comments = $_POST['album_comments'];
//    $album_tag = $_POST['album_tag'];
    $album_datecreated = time();
//    $album_competition = $_POST['album_competition'];

    // MAKE SURE SUBMITTED PRIVACY OPTIONS ARE ALLOWED, IF NOT, SET TO EVERYONE
//    if(!in_array($album_privacy, $level_album_privacy)) {
//        $album_privacy = $level_album_privacy[0];
//    }
//    if(!in_array($album_comments, $level_album_comments)) {
//        $album_comments = $level_album_comments[0];
//    }
//    if(!in_array($album_tag, $level_album_tag)) {
//        $album_tag = $level_album_tag[0];
//    }

    // CHECK THAT TITLE IS NOT BLANK
    if(trim($album_title) == "") {
        $is_error = 1000073;
    }

    // IF NO ERROR, CONTINUE
    if($is_error == 0) {

        // INSERT NEW ALBUM INTO DATABASE
        $database->database_query("INSERT INTO se_groupalbums (
                                groupalbum_group_id,
				groupalbum_user_id,
				groupalbum_datecreated,
				groupalbum_dateupdated,
				groupalbum_title,
				groupalbum_desc,
				groupalbum_search,
				groupalbum_privacy,
				groupalbum_comments,
				groupalbum_tag
				) VALUES (
                                '".$group->group_info[group_id]."',
				'".$user->user_info[user_id]."',
				'$album_datecreated',
				'$album_datecreated',
				'$album_title',
				'$album_desc',
				'$album_search',
				'$album_privacy',
				'$album_comments',
				'$album_tag')
                ");

        // GET ALBUM ID
        $album_id = $database->database_insert_id();

//    $database->database_query("INSERT INTO se_allalbumslist (album_id, album_type, timestamp) VALUES (".$album_id.", 'user_album', ".time().")");
        // UPDATE LAST UPDATE DATE (SAY THAT 10 TIMES FAST)
        $user->user_lastupdate();

        // INSERT ACTION
        if(strlen($album_title) > 100) {
            $album_title = substr($album_title, 0, 97);
            $album_title .= "...";
        }
        $actions->actions_add($user, "newgroupalbum", Array($user->user_info[user_username], $user->user_displayname, $album_id, $album_title, $group->group_info['group_title'], $group->group_info['group_id']), Array(), 0, FALSE, "user", $user->user_info[user_id], $album_privacy);

        // CALL ALBUM CREATION HOOK
        ($hook = SE_Hook::exists('se_album_create')) ? SE_Hook::call($hook, array()) : NULL;

        // SEND TO UPLOAD PAGE
        header("Location: user_group_upload.php?album_id=$album_id&new_album=1&group_id=".$group->group_info['group_id']);
        exit();
    }
}



// ASSIGN VARIABLES AND SHOW ADD ALBUM PAGE
$smarty->assign('group', $group);
$smarty->assign('is_error', $is_error);
$smarty->assign('total_albums', $total_albums);
$smarty->assign('album_title', $album_title);
$smarty->assign('album_desc', str_replace("<br>", "\r\n", $album_desc));
$smarty->assign('album_search', $album_search);
$smarty->assign('album_privacy', $album_privacy);
$smarty->assign('album_comments', $album_comments);
$smarty->assign('album_tag', $album_tag);
$smarty->assign('album_competition', $album_competition);
$smarty->assign('privacy_options', $privacy_options);
$smarty->assign('comment_options', $comment_options);
$smarty->assign('tag_options', $tag_options);
include "footer.php";
?>