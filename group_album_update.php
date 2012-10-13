<?php

/* $Id: user_album_update.php 2 2009-01-10 20:53:09Z john $ */

$page = "group_album_update";
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
    $album_id = 0;
}

// GET ALBUM INFO
$album_query = $database->database_query("SELECT se_groupalbums.* FROM se_groupalbums
        WHERE se_groupalbums.groupalbum_id='$album_id' LIMIT 1");
$album_info = $database->database_fetch_assoc($album_query);

// ENSURE ALBUMS ARE ENABLED FOR THIS USER
if($user->user_info['user_id'] != $album_info['groupalbum_user_id']) {
    header("Location: user_home.php");
    exit();
}

$group = new se_group($user->user_info['user_id'], $album_info['groupalbum_group_id']);
$result = 0;
// ROTATE

if($task == "rotate") {
    $media_id = $_GET['media_id'];
    $dir = $_GET['dir'];

    if($dir == "cc") {
        $dir = 90;
    } else {
        $dir = 270;
    }

    // ROTATE IMAGE
    $media = $database->database_query("SELECT se_groupmedia.*, se_groupalbums.* FROM se_groupmedia
                LEFT JOIN se_groupalbums ON se_groupmedia.groupmedia_groupalbum_id=se_groupalbums.groupalbum_id
                WHERE se_groupmedia.groupmedia_id='$media_id' LIMIT 1");

    $media_info = $database->database_fetch_assoc($media);
//    $group = new se_group($this->user_id, $media_info['groupalbum_id']);
//        print_r($group.'1');
    // GET IMAGE INFORMATION
    $media_path = $group->group_dir($group->group_info['group_id']).$media_info[groupmedia_id].".".$media_info[groupmedia_ext];
    $media_dimensions = @getimagesize($media_path);
    $media_width = $media_dimensions[0];
    $media_height = $media_dimensions[1];

    // ROTATE IMAGE
    switch($media_info[groupmedia_ext]) {
        case "gif":
            $old = imagecreatefromgif($media_path);
            $rotate = imagerotate($old, $dir, 0);
            imagejpeg($rotate, $media_path, 100);
            ImageDestroy($old);
            ImageDestroy($rotate);
            break;
        case "bmp":
            $old = imagecreatefrombmp($media_path);
            $rotate = imagerotate($old, $dir, 0);
            imagejpeg($rotate, $media_path, 100);
            ImageDestroy($old);
            ImageDestroy($rotate);
            break;
        case "jpeg":
        case "jpg":
            $old = imagecreatefromjpeg($media_path);
            $rotate = imagerotate($old, $dir, 0);
            imagejpeg($rotate, $media_path, 100);
            ImageDestroy($old);
            ImageDestroy($rotate);
            break;
        case "png":
            $old = imagecreatefrompng($media_path);
            $rotate = imagerotate($old, $dir, 0);
            imagejpeg($rotate, $media_path, 100);
            ImageDestroy($old);
            ImageDestroy($rotate);
            break;
    }

    // GET THUMB INFO
    $thumb_path = $group->group_dir($group->group_info['group_id']).$media_info[groupmedia_id]."_thumb.jpg";
    $thumb_dimensions = @getimagesize($thumb_path);
    $thumb_width = $thumb_dimensions[0];
    $thumb_height = $thumb_dimensions[1];

    // ROTATE THUMB
    $old = imagecreatefromjpeg($thumb_path);
    $rotate = imagerotate($old, $dir, 0);
    imagejpeg($rotate, $thumb_path, 100);
    ImageDestroy($old);
    ImageDestroy($rotate);
}
//
//
//
// UPDATE FILES IN THIS ALBUM
if($task == "doupdate") {
    $album = new se_album($user->user_info['user_id']);
//if($_POST) exit();

    // GET TOTAL FILES
//    print_r(123);
    $where = "(groupmedia_groupalbum_id='{$album_info['groupalbum_id']}')";
    $total_files = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) as count FROM se_groupmedia WHERE ".$where));

    // DELETE NECESSARY FILES
    $album->group_album_media_delete(0, $total_files['count'], "groupmedia_id ASC", "(groupmedia_groupalbum_id='$album_info[groupalbum_id]')", $album_info[groupalbum_id]);

    // UPDATE NECESSARY FILES
    $media_array = $album->group_album_media_update(0, $total_files['count'], "groupmedia_id ASC", "(groupmedia_groupalbum_id='$album_info[groupalbum_id]')", $album_info[groupalbum_id]);

    // SET ALBUM COVER AND UPDATE DATE
    $newdate = time();
    $album_info[groupalbum_cover] = $_POST['album_cover'];
    if(!in_array($album_info[groupalbum_cover], $media_array)) {
        $album_info[groupalbum_cover] = $media_array[0];
    }
    $database->database_query("UPDATE se_groupalbums SET groupalbum_cover='$album_info[groupalbum_cover]', groupalbum_dateupdated='$newdate' WHERE groupalbum_id='$album_info[groupalbum_id]'");

    // UPDATE LAST UPDATE DATE (SAY THAT 10 TIMES FAST)
    $user->user_lastupdate();

    // SHOW SUCCESS MESSAGE
    $result = 1;
} 
//elseif($task == "moveup") {
//
//    $media_id = $_GET['media_id'];
//
//    $media_query = $database->database_query("SELECT media_id, media_order, media_album_id FROM se_media LEFT JOIN se_albums ON se_media.media_album_id=se_albums.album_id WHERE media_id='$media_id' AND se_albums.album_user_id='".$user->user_info[user_id]."'");
//    if($database->database_num_rows($media_query) == 1) {
//
//        $media_info = $database->database_fetch_assoc($media_query);
//
//        $prev_query = $database->database_query("SELECT media_id, media_order FROM se_media LEFT JOIN se_albums ON se_media.media_album_id=se_albums.album_id WHERE se_media.media_album_id='$media_info[media_album_id]' AND se_albums.album_user_id='".$user->user_info[user_id]."' AND media_order<$media_info[media_order] ORDER BY media_order DESC LIMIT 1");
//        if($database->database_num_rows($prev_query) == 1) {
//
//            $prev_info = $database->database_fetch_assoc($prev_query);
//
//            // SWITCH ORDER
//            $database->database_query("UPDATE se_media SET media_order=$prev_info[media_order] WHERE media_id=$media_info[media_id]");
//            $database->database_query("UPDATE se_media SET media_order=$media_info[media_order] WHERE media_id=$prev_info[media_id]");
//
//            // SEND AJAX CONFIRMATION
//            echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'><script type='text/javascript'>";
//            echo "window.parent.reorderMedia('$media_info[media_id]', '$prev_info[media_id]');";
//            echo "</script></head><body></body></html>";
//            exit();
//
//        }
//    }
//}
$where = "(groupmedia_groupalbum_id='{$album_info['groupalbum_id']}')";
$sortby = " groupmedia_date ASC";
$total_files = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) as count FROM se_groupmedia WHERE ".$where));
// SHOW FILES IN THIS ALBUM

// GET MEDIA ARRAY
$file_array = $group->group_album_media_list(0, 200, $sortby, $where, $album_info['groupalbum_id']);
//print_r($total_files);
//if(isset($_POST['album_id'])) header('Location: user_album.php');

// ASSIGN VARIABLES AND SHOW UDPATE ALBUMS PAGE
$smarty->assign('result', $result);
$smarty->assign('group', $group);
$smarty->assign('files', $file_array);
$smarty->assign('files_total', $total_files['count']);
$smarty->assign('album_info', $album_info);
//$smarty->assign('albums', $album_array);
//$smarty->assign('albums_total', $total_albums);
include "footer.php";
?>