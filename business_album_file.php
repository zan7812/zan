<?php

/* $Id: business_album_file.php 161 2009-04-28 21:14:59Z john $ */

$page = "business_album_file";
include "header.php";

if(isset($_POST['task'])) {
    $task = $_POST['task'];
} elseif(isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = "";
}
if(isset($_POST['business_id'])) {
    $business_id = $_POST['business_id'];
} elseif(isset($_GET['business_id'])) {
    $business_id = $_GET['business_id'];
} else {
    $business_id = 0;
}
if(isset($_POST['businessmedia_id'])) {
    $businessmedia_id = $_POST['businessmedia_id'];
} elseif(isset($_GET['businessmedia_id'])) {
    $businessmedia_id = $_GET['businessmedia_id'];
} else {
    $businessmedia_id = 0;
}

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( (!$user->user_exists && !$setting['setting_permission_business']) || ($user->user_exists && (~(int)$user->level_info['level_business_allow'] & 1)) ) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 656);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}

// DISPLAY ERROR PAGE IF NO OWNER
$business = new se_business($user->user_info['user_id'], $business_id);
if( !$business->business_exists ) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 2001219);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}



// MAKE SURE MEDIA EXISTS
$media_query = $database->database_query("SELECT se_businessmedia.*, se_businessalbums.*, se_users.user_id, se_users.user_username, se_users.user_fname, se_users.user_lname FROM se_businessmedia LEFT JOIN se_businessalbums ON se_businessmedia.businessmedia_businessalbum_id=se_businessalbums.businessalbum_id LEFT JOIN se_users ON se_businessmedia.businessmedia_user_id WHERE se_businessmedia.businessmedia_id='{$businessmedia_id}' AND se_businessalbums.businessalbum_business_id='{$business->business_info['business_id']}' LIMIT 1");
if( !$database->database_num_rows($media_query) ) {
    header("Location: ".$url->url_create('business', NULL, $business->business_info['business_id']));
    exit();
}

$media_info = $database->database_fetch_assoc($media_query);

$uploader = new se_user();
if( $media_info['businessmedia_user_id'] != $media_info['user_id'] ) {
    $uploader->user_exists = FALSE;
}
else {
    $uploader->user_exists = TRUE;
    $uploader->user_info['user_id'] = $media_info['user_id'];
    $uploader->user_info['user_username'] = $media_info['user_username'];
    $uploader->user_info['user_fname'] = $media_info['user_fname'];
    $uploader->user_info['user_lname'] = $media_info['user_lname'];
    $uploader->user_displayname();
}
$media_info['uploader'] = $uploader;


// GET PRIVACY LEVEL
$privacy_max = $business->business_privacy_max($user);
if( !($privacy_max & $business->business_info['business_privacy']) ) {
    header("Location: ".$url->url_create("business", NULL, $business->business_info['business_id']));
    exit();
}


// GET MEDIA IN ALBUM FOR CAROUSEL
$media_array = Array();
$media_query = $database->database_query("SELECT businessmedia_id, businessmedia_ext, '{$business->business_info['business_id']}' AS businessalbum_business_id FROM se_businessmedia WHERE businessmedia_businessalbum_id='{$media_info['businessalbum_id']}' ORDER BY businessmedia_date DESC");
while($thismedia = $database->database_fetch_assoc($media_query)) {
    $media_array[$thismedia['businessmedia_id']] = $thismedia;
}


// IF USER IS ALLOWED, CHECK TASK
if( $business->user_rank == 2 || $business->user_rank == 1 || ($media_info['uploader']->user_exists && $user->user_info['user_id'] == $media_info['uploader']->user_info['user_id']) ) {
    // DELETE PHOTO
    if($task == "media_delete") {
        $media_path = $business->business_dir($business->business_info['business_id']).$media_info['businessmedia_id'].".".$media_info['businessmedia_ext'];
        if(file_exists($media_path)) {
            @unlink($media_path);
        }
        $thumb_path = $business->business_dir($business->business_info['business_id']).$media_info['businessmedia_id']."_thumb.jpg";
        if(file_exists($thumb_path)) {
            @unlink($thumb_path);
        }
        $action_thumb_path = $url->url_base.substr($business->business_dir($business->business_info['business_id']), 2).$media_info['businessmedia_id']."_thumb.jpg";

        // DELETE ACTION MEDIA IF NECESSARY
        $database->database_query("DELETE FROM se_actionmedia WHERE actionmedia_path = '{$action_thumb_path}'");

        // DELETE MEDIA FROM DATABASE
        $database->database_query("DELETE FROM se_businessmedia, se_businessmediacomments, se_businessmediatags USING se_businessmedia LEFT JOIN se_businessmediacomments ON se_businessmedia.businessmedia_id=se_businessmediacomments.businessmediacomment_businessmedia_id LEFT JOIN se_businessmediatags ON se_businessmedia.businessmedia_id=se_businessmediatags.businessmediatag_businessmedia_id WHERE se_businessmedia.businessmedia_id='{$media_info['businessmedia_id']}'");

        // UPDATE CACHED TOTALS
        $database->database_query("UPDATE se_businessalbums SET businessalbum_totalfiles=businessalbum_totalfiles-1, businessalbum_totalspace=businessalbum_totalspace-'{$media_info['businessmedia_filesize']}' WHERE businessalbum_id='{$media_info['businessmedia_businessalbum_id']}' LIMIT 1");

        // SEND USER TO NEXT PHOTO
        $media_keys = array_keys($media_array);
        $current_index = array_search($media_info[businessmedia_id], $media_keys);
        if($current_index+1 == count($media_array)) {
            $next_index = 0;
        } else {
            $next_index = $current_index+1;
        }
        header("Location: ".$url->url_create('business_media', NULL, $business->business_info['business_id'], $media_keys[$next_index]));
        exit();
    }


    // EDIT PHOTO
    elseif($task == "media_edit") {
        $media_info['businessmedia_title'] = $_POST['businessmedia_title'];
        $media_info['businessmedia_desc'] = $_POST['businessmedia_desc'];
        $database->database_query("UPDATE se_businessmedia SET businessmedia_title='{$media_info['businessmedia_title']}', businessmedia_desc='{$media_info['businessmedia_desc']}' WHERE businessmedia_id='{$media_info['businessmedia_id']}' LIMIT 1");
    }
}



// GET CUSTOM business STYLE IF ALLOWED
if( $business->businessowner_level_info['level_business_style'] ) {
    $allbusinesstyle_info = $database->database_fetch_assoc($database->database_query("SELECT allbusinesstyle_css FROM se_allbusinesstyles WHERE allbusinesstyle_business_id='{$business->business_info['business_id']}' LIMIT 1"));
    $global_css = $allbusinesstyle_info['allbusinesstyle_css'];
}

// GET MEDIA WIDTH/HEIGHT
$mediasize = @getimagesize($business->business_dir($business->business_info['business_id']).$media_info['businessmedia_id'].'.'.$media_info['businessmedia_ext']);
$media_info['businessmedia_width'] = $mediasize[0];
$media_info['businessmedia_height'] = $mediasize[1];
if($media_info[businessmedia_width] > 528) {
    $media_info[businessmedia_width] = '528';
    $media_info[businessmedia_realwidth] = $mediasize[0];
}

// CHECK IF USER IS ALLOWED TO TAG PHOTOS
$allowed_to_tag = ($privacy_max & $media_info['businessalbum_tag']);

// CHECK IF USER IS ALLOWED TO COMMENT
$allowed_to_comment = ($privacy_max & $business->business_info['business_comments']);


// GET MEDIA COMMENTS
$comment = new se_comment('businessmedia', 'businessmedia_id', $media_info['businessmedia_id']);
$total_comments = $comment->comment_total();


// UPDATE ALBUM VIEWS
$album_views_new = ++$media_info['businessalbum_views'];
$database->database_query("UPDATE se_businessalbums SET businessalbum_views=businessalbum_views+1 WHERE businessalbum_id='{$media_info['businessalbum_id']}' LIMIT 1");

// UPDATE NOTIFICATIONS
if( $user->user_info['user_id'] == $business->business_info['business_user_id'] ) {
    $database->database_query("DELETE FROM se_notifys USING se_notifys LEFT JOIN se_notifytypes ON se_notifys.notify_notifytype_id=se_notifytypes.notifytype_id WHERE se_notifys.notify_user_id='{$user->user_info['user_id']}' AND (se_notifytypes.notifytype_name='businessmediacomment' OR se_notifytypes.notifytype_name='businessmediatag' OR se_notifytypes.notifytype_name='newbusinesstag') AND notify_object_id='{$media_info['businessmedia_id']}'");
}



// RETRIEVE TAGS FOR THIS PHOTO
$tag_array = Array();
$tags = $database->database_query("SELECT se_businessmediatags.*, se_users.user_id, se_users.user_username, se_users.user_fname, se_users.user_lname FROM se_businessmediatags LEFT JOIN se_users ON se_businessmediatags.businessmediatag_user_id=se_users.user_id WHERE businessmediatag_businessmedia_id='{$media_info['businessmedia_id']}' ORDER BY businessmediatag_id ASC");
while($tag = $database->database_fetch_assoc($tags)) {
    $taggeduser = new se_user();
    if( $tag['user_id'] ) {
        $taggeduser->user_exists = TRUE;
        $taggeduser->user_info['user_id'] = $tag['user_id'];
        $taggeduser->user_info['user_username'] = $tag['user_username'];
        $taggeduser->user_info['user_fname'] = $tag['user_fname'];
        $taggeduser->user_info['user_lname'] = $tag['user_lname'];
        $taggeduser->user_displayname();
    }
    else {
        $taggeduser->user_exists = FALSE;
    }

    $tag['tagged_user'] = $taggeduser;
    $tag_array[] = $tag;
}


// SET business OWNER (OR EDITOR)
if($business->user_rank == 2 || $business->user_rank == 1) {
    $businessowner = $user;
} else {
    $businessowner = new se_user(Array($business->business_info['business_user_id']));
}


// SET GLOBAL PAGE TITLE
$global_page_title[0] = 2001326;
$global_page_title[1] = $business->business_info['business_title'];
$global_page_description[0] = 2001327;
$global_page_description[1] = $business->business_info['business_title'];

$media_info['businessalbum_datecreated'] = date('d.m.Y', $media_info['businessalbum_datecreated']);

$user_like = 0;
$count_likes = 0;
$likes = array();

if($media_info['media_likes'])$likes = unserialize($media_info['media_likes']);
foreach($likes as $item) {
    $count_likes++;
    $where .= ' user_id = '.$item.' OR';
}
$where =  substr($where, 0, -2);
$result = $database->database_query("SELECT user_id, user_username, user_displayname, user_photo FROM se_users WHERE ".$where);
while($rez = $database->database_fetch_assoc($result)) {
    $users_like[$rez['user_id']] = $rez;
    if($users_like[$rez['user_id']]['user_photo']) {
        $ext = strtolower(str_replace(".", "", strrchr($users_like[$rez['user_id']]['user_photo'], ".")));
        $name = substr($users_like[$rez['user_id']]['user_photo'], 0, (strrpos($users_like[$rez['user_id']]['user_photo'], '.') - strlen($users_like[$rez['user_id']]['user_photo'])));
        $users_like[$rez['user_id']]['user_photo'] = $url->url_userdir($users_like[$rez['user_id']]['user_id']).$name.'_thumb.'.$ext;
    }
    else $users_like[$rez['user_id']]['user_photo'] = './images/nophoto.gif';
}

$i = 0;
$likes = array_reverse($likes);
foreach($likes as $item) {
    $i++;
    $users_like_display[$item] = $users_like[$item];
    if($i == 5) break;
}
if(in_array($user->user_info['user_id'], $likes)) $user_like = 1;

$media_link = './business_album_file.php?business_id='.$business->business_info['business_id'].'&businessmedia_id='.$media_info['businessmedia_id'];
$media_thumb = $business->business_dir($business->business_info['business_id']).$media_info['businessmedia_id'].'_thumb.'.$media_info['businessmedia_ext'];
//print_r($media_thumb);
// ASSIGN VARIABLES AND DISPLAY ALBUM FILE PAGE
$smarty->assign('users_like', $users_like_display);
$smarty->assign('media_link', $media_link);
$smarty->assign('media_thumb', $media_thumb);
$smarty->assign('count_likes', $count_likes);
$smarty->assign('user_like', $user_like);

$smarty->assign('business', $business);
$smarty->assign('businessowner', $businessowner);
$smarty->assign('media_info', $media_info);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('allowed_to_comment', $allowed_to_comment);
$smarty->assign('allowed_to_tag', $allowed_to_tag);
$smarty->assign('media', $media_array);
$smarty->assign('media_keys', array_keys($media_array));
$smarty->assign('tags', $tag_array);
include "footer.php";
?>