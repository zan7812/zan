<?
$page = "masterclass_album_file";
include "header.php";

// MAKE SURE MEDIA VARS ARE SET IN URL
if(isset($_POST['task'])) {
    $task = $_POST['task'];
} elseif(isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = "main";
}
if(isset($_POST['masterclassmedia_id'])) {
    $masterclassmedia_id = $_POST['masterclassmedia_id'];
} elseif(isset($_GET['masterclassmedia_id'])) {
    $masterclassmedia_id = $_GET['masterclassmedia_id'];
} else {
    $masterclassmedia_id = 0;
}
if(isset($_POST['masterclass_id'])) {
    $masterclass_id = $_POST['masterclass_id'];
} elseif(isset($_GET['masterclass_id'])) {
    $masterclass_id = $_GET['masterclass_id'];
} else {
    $masterclass_id = 0;
}

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if($user->user_exists == 0 & $setting[setting_permission_masterclass] == 0) {
    $smarty->assign('error_header', 11153915);
    $smarty->assign('error_message', 11153917);
    $smarty->assign('error_submit', 11153925);
    $smarty->display("error.tpl");
    exit();
}


// INITIALIZE masterclass OBJECT
$masterclass = new rc_masterclass($user->user_info[user_id], $masterclass_id);
if($masterclass->masterclass_exists == 0) {
    header("Location: home.php");
    exit();
}

if(!$masterclass->is_masterclass_active()) {
    // header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]); exit();
}

// GET masterclass ALBUM INFO
$masterclassalbum_info = $database->database_fetch_assoc($database->database_query("SELECT * FROM se_masterclassalbums WHERE masterclassalbum_masterclass_id='".$masterclass->masterclass_info[masterclass_id]."' LIMIT 1"));


// MAKE SURE MEDIA EXISTS
$masterclassmedia_query = $database->database_query("SELECT * FROM se_masterclassmedia WHERE masterclassmedia_id='$masterclassmedia_id' AND masterclassmedia_masterclassalbum_id='$masterclassalbum_info[masterclassalbum_id]' LIMIT 1");
if($database->database_num_rows($masterclassmedia_query) != 1) {
    header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]);
    exit();
}
$masterclassmedia_info = $database->database_fetch_assoc($masterclassmedia_query);

$masterclass->masterclass_owner();
$owner = $masterclass->masterclass_owner;

// CHECK PRIVACY
$privacy_max = $owner->user_privacy_max($user);
if(!($masterclass->masterclass_info[masterclass_privacy] & $privacy_max)) {
    header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]);
    exit();
}


// UPDATE ALBUM VIEWS
$masterclassalbum_views_new = $masterclassalbum_info[masterclassalbum_views] + 1;
$database->database_query("UPDATE se_masterclassalbums SET masterclassalbum_views='$masterclassalbum_views_new' WHERE masterclassalbum_id='$masterclassalbum_info[masterclassalbum_id]' LIMIT 1");

// CHECK IF USER IS ALLOWED TO COMMENT
$allowed_to_comment = 1;
if(!($privacy_max & $masterclass->masterclass_info[masterclass_comments])) {
    $allowed_to_comment = 0;
}


// IF A COMMENT IS BEING POSTED
if($task == "dopost" & $allowed_to_comment != 0) {

    $comment_date = time();
    $comment_body = $_POST['comment_body'];

    // RETRIEVE AND CHECK SECURITY CODE IF NECESSARY
    if($setting[setting_comment_code] != 0) {
        session_start();
        $code = $_SESSION['code'];
        if($code == "") {
            $code = randomcode();
        }
        $comment_secure = $_POST['comment_secure'];

        if($comment_secure != $code) {
            $is_error = 1;
        }
    }

    // MAKE SURE COMMENT BODY IS NOT EMPTY
    $comment_body = censor(str_replace("\r\n", "<br>", $comment_body));
    $comment_body = preg_replace('/(<br>){3,}/is', '<br><br>', $comment_body);
    $comment_body = ChopText($comment_body);
    if(str_replace(" ", "", $comment_body) == "") {
        $is_error = 1;
        $comment_body = "";
    }

    // ADD COMMENT IF NO ERROR
    if($is_error == 0) {
        $database->database_query("INSERT INTO se_masterclassmediacomments (masterclassmediacomment_masterclassmedia_id, masterclassmediacomment_authoruser_id, masterclassmediacomment_date, masterclassmediacomment_body) VALUES ('$masterclassmedia_info[masterclassmedia_id]', '".$user->user_info[user_id]."', '$comment_date', '$comment_body')");

        // INSERT ACTION IF USER EXISTS
        if($user->user_exists != 0) {
            $commenter = $user->user_info[user_username];
            $comment_body_encoded = $comment_body;
            if(strlen($comment_body_encoded) > 250) {
                $comment_body_encoded = substr($comment_body_encoded, 0, 240);
                $comment_body_encoded .= "...";
            }
            $comment_body_encoded = htmlspecialchars(str_replace("<br>", " ", $comment_body_encoded));

            $actions->actions_add($user, "masterclassmediacomment", Array($user->user_info[user_username], $user->user_displayname, $masterclass_id, $masterclass->masterclass_info[masterclass_title], $comment_body_encoded, $masterclassmedia_info[masterclassmedia_id]), Array(), 0, FALSE, "user", $user->user_info[user_id], $masterclass->masterclass_info[masterclass_privacy]);
        } else {
            $commenter = 11153914;
        }

        // SEND COMMENT NOTIFICATION IF NECESSARY
        $masterclassowner_info = $database->database_fetch_assoc($database->database_query("SELECT se_users.user_id, se_users.user_username, se_users.user_email, se_usersettings.usersetting_notify_masterclassmediacomment FROM se_users LEFT JOIN se_usersettings ON se_users.user_id=se_usersettings.usersetting_user_id WHERE se_users.user_id='".$masterclass->masterclass_info[masterclass_user_id]."'"));
        if($masterclassowner_info[usersetting_notify_masterclassmediacomment] == 1 & $masterclassowner_info[user_id] != $user->user_info[user_id]) {
            send_generic($masterclassowner_info[user_email], "$setting[setting_email_fromname] <$setting[setting_email_fromemail]>", $setting[setting_email_masterclassmediacomment_subject], $setting[setting_email_masterclassmediacomment_message], Array('[username]', '[commenter]', '[masterclassname]', '[link]'), Array($masterclassowner_info[user_username], $commenter, $masterclass->masterclass_info[masterclass_title], "<a href=\"".$url->url_base."masterclass_album_file.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."&masterclassmedia_id=$masterclassmedia_info[masterclassmedia_id]\">".$url->url_base."masterclass_album_file.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."&masterclassmedia_id=$masterclassmedia_info[masterclassmedia_id]</a>"));
        }
    }

    echo "<html><head><script type=\"text/javascript\">";
    echo "window.parent.addComment('$is_error', '$comment_body', '$comment_date');";
    echo "</script></head><body></body></html>";
    exit();
}



// GET masterclass MEDIA COMMENTS
$comment = new se_comment('masterclassmedia', 'masterclassmedia_id', $masterclassmedia_info[masterclassmedia_id]);
$total_comments = $comment->comment_total();
$comments = $comment->comment_list(0, $total_comments);




// CREATE BACK MENU LINK
$back = $database->database_query("SELECT masterclassmedia_id FROM se_masterclassmedia WHERE masterclassmedia_masterclassalbum_id='$masterclassmedia_info[masterclassmedia_masterclassalbum_id]' AND masterclassmedia_id<'$masterclassmedia_info[masterclassmedia_id]' ORDER BY masterclassmedia_id DESC LIMIT 1");
if($database->database_num_rows($back) == 1) {
    $back_info = $database->database_fetch_assoc($back);
    $link_back = $url->base."masterclass_album_file.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."&masterclassmedia_id=$back_info[masterclassmedia_id]";
} else {
    $link_back = "#";
}

// CREATE FIRST MENU LINK
$first = $database->database_query("SELECT masterclassmedia_id FROM se_masterclassmedia WHERE masterclassmedia_masterclassalbum_id='$masterclassmedia_info[masterclassmedia_masterclassalbum_id]' ORDER BY masterclassmedia_id ASC LIMIT 1");
if($database->database_num_rows($first) == 1 AND $link_back != "#") {
    $first_info = $database->database_fetch_assoc($first);
    $link_first = $url->base."masterclass_album_file.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."&masterclassmedia_id=$first_info[masterclassmedia_id]";
} else {
    $link_first = "#";
}

// CREATE NEXT MENU LINK
$next = $database->database_query("SELECT masterclassmedia_id FROM se_masterclassmedia WHERE masterclassmedia_masterclassalbum_id='$masterclassmedia_info[masterclassmedia_masterclassalbum_id]' AND masterclassmedia_id>'$masterclassmedia_info[masterclassmedia_id]' ORDER BY masterclassmedia_id ASC LIMIT 1");
if($database->database_num_rows($next) == 1) {
    $next_info = $database->database_fetch_assoc($next);
    $link_next = $url->base."masterclass_album_file.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."&masterclassmedia_id=$next_info[masterclassmedia_id]";
} else {
    $link_next = "#";
}

// CREATE END MENU LINK
$end = $database->database_query("SELECT masterclassmedia_id FROM se_masterclassmedia WHERE masterclassmedia_masterclassalbum_id='$masterclassmedia_info[masterclassmedia_masterclassalbum_id]' ORDER BY masterclassmedia_id DESC LIMIT 1");
if($database->database_num_rows($end) == 1 AND $link_next != "#") {
    $end_info = $database->database_fetch_assoc($end);
    $link_end = $url->base."masterclass_album_file.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."&masterclassmedia_id=$end_info[masterclassmedia_id]";
} else {
    $link_end = "#";
}



// GET CUSTOM masterclass STYLE IF ALLOWED
if($masterclass->masterclassowner_level_info[level_masterclass_style] != 0 & $is_masterclass_private == 0) {
    $masterclassstyle_info = $database->database_fetch_assoc($database->database_query("SELECT masterclassstyle_css FROM se_masterclassstyles WHERE masterclassstyle_masterclass_id='".$masterclass->masterclass_info[masterclass_id]."' LIMIT 1"));
    $global_css = $masterclassstyle_info[masterclassstyle_css];
}

$masterclassmedia_info['masterclassmedia_date'] = date('d.m.Y', $masterclassmedia_info['masterclassmedia_date']);

$user_like = 0;
$count_likes = 0;
$likes = array();

if($masterclassmedia_info['media_likes'])$likes = unserialize($masterclassmedia_info['media_likes']);

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

$media_link = './masterclass_album_file.php?masterclass_id='.$masterclass->masterclass_info['masterclass_id'].'&masterclassmedia_id='.$masterclassmedia_info['masterclassmedia_id'];
$media_thumb = $masterclass->masterclass_dir($masterclass->masterclass_info['masterclass_id']).$masterclassmedia_info['masterclassmedia_id'].'_thumb.'.$masterclassmedia_info['masterclassmedia_ext'];

$smarty->assign('users_like', $users_like_display);
$smarty->assign('media_link', $media_link);
$smarty->assign('media_thumb', $media_thumb);
$smarty->assign('count_likes', $count_likes);
$smarty->assign('user_like', $user_like);

$mediasize = @getimagesize($masterclass->url_masterclassdir($masterclass->masterclass_info['masterclass_id']).$masterclassmedia_info[masterclassmedia_id].'.'.$masterclassmedia_info[masterclassmedia_ext]);
$masterclassmedia_info[media_width] = $mediasize[0];
$masterclassmedia_info[media_height] = $mediasize[1];
if($masterclassmedia_info[media_width] > 528) {
    $masterclassmedia_info[media_width] = '528';
    $masterclassmedia_info[media_realwidth] = $mediasize[0];
}
//print_r($masterclass->url_masterclassdir($masterclass->masterclass_info['masterclass_id']).$masterclassmedia_info[media_id].'.'.$masterclassmedia_info[media_ext]);
// ASSIGN VARIABLES AND DISPLAY ALBUM FILE PAGE
$smarty->assign('masterclass', $masterclass);
$smarty->assign('masterclassmedia_info', $masterclassmedia_info);
$smarty->assign('comments', $comments);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('allowed_to_comment', $allowed_to_comment);
$smarty->assign('link_first', $link_first);
$smarty->assign('link_back', $link_back);
$smarty->assign('link_next', $link_next);
$smarty->assign('link_end', $link_end);
include "footer.php";
?>