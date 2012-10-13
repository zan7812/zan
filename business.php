<?php

/* $Id: business.php 69 2009-02-26 01:22:42Z szerrade $ */

$page = "business";
include "header.php";

if($_POST['task'] == 'appointment') {
    $date = explode('.', $_POST['date']);
    $time = mktime($_POST['hours'], $_POST['minutes'], 0, $date[1], $date[0], $date[2]);
    $database->database_query("INSERT INTO se_appoinments (master_id, master_business_id, user_id, app_time, wish) VALUES
        (".$_POST['owner_id'].", ".$_POST['business_id'].", ".$user->user_info['user_id'].", ".$time.", '".$_POST['wish']."')");
    exit();
}
elseif($_POST['task'] == 'get_appoinments' && is_numeric($_POST['business_id']) && $_POST['date']) {
    $date = explode('.', $_POST['date']);
    $time = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
    $out = '';
    $result = $database->database_query("SELECT user_id, app_time, approved, wish FROM se_appoinments WHERE master_business_id = ".$_POST['business_id']." AND (app_time > ".$time." AND app_time < ".($time+86400).") ORDER BY app_time DESC");
    while($rez = $database->database_fetch_assoc($result)) {
        $app_user = new SEUser($rez['user_id']);
        $out .= '<div style="min-height: 115px"><table class="business_appoinment" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td><a href="./profile.php'.$app_user->user_info['user_username'].'">'.$app_user->user_info['user_displayname'].'</a></td>
                        <td style="text-align: right"><b>дата приема</b></td>
                    </tr>
                    <tr>
                        <td><a href="./profile.php'.$app_user->user_info['user_username'].'"><img class="photo2" src="'.$user->user_photo("./images/nophoto.gif", TRUE).'" /></a></td>
                        <td style="text-align: right">'.$app_user->rus_date('d F Y', $rez['app_time']).'<br/>время: '.date('H:i', $rez['app_time']).'</td>
                    </tr>';
        if($rez['wish']) {
            if(strlen($rez['wish']) > 150) $out .= '<tr><td colspan="2">'.substr($rez['wish'], 0, 200).'...</td></tr>';
            else  $out .= '<tr><td style="text-align: center" colspan="2">'.$rez['wish'].'</td></tr>';
        }
        if($rez['approved'] == 1) $out .= '<tr><td style="text-align: center;" colspan="2">записан(а) на прием</td></tr>';
        if($rez['approved'] == 0) $out .= '<tr><td style="text-align: center" colspan="2">запись в рассмотрении</td></tr>';
        $out .='</table></div>';
    }
    if($out == '') $out = '<div style="text-align: center;font-weight: bold;padding-top: 5px;">На данный день еще никто не записан</div>';
    // OUTPUT JSON
    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
    header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header ("Pragma: no-cache"); // HTTP/1.0
    header("Content-Type: application/json");
    echo json_encode($out);
    exit();
}
elseif(($_POST['task'] == 'approve' || $_POST['task'] == 'delete') && is_numeric($_POST['app_id'])) {
    if($_POST['task'] == 'delete') $database->database_query("DELETE FROM se_appoinments WHERE app_id = ".$_POST['app_id']);
    elseif($_POST['task'] == 'approve') $database->database_query("UPDATE se_appoinments SET approved = 1 WHERE app_id = ".$_POST['app_id']);
}

if(isset($_POST['business_id'])) {
    $business_id = $_POST['business_id'];
} elseif(isset($_GET['business_id'])) {
    $business_id = $_GET['business_id'];
} else {
    $business_id = 0;
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


// GET VIEW AND VARS
if(isset($_POST['v'])) {
    $v = $_POST['v'];
} elseif(isset($_GET['v'])) {
    $v = $_GET['v'];
} else {
    $v = "business";
}
if(isset($_POST['search'])) {
    $search = $_POST['search'];
} elseif(isset($_GET['search'])) {
    $search = $_GET['search'];
} else {
    $search = "";
}
if(isset($_POST['task'])) {
    $task = $_POST['task'];
} elseif(isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = "";
}
if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 1;
}

// VALIDATE VIEW VAR
if($v != "business" && $v != "members" && $v != "comments" && $v != "photos" && $v != "discussions") {
    $v = "business";
}


// DELETE DISCUSSION TOPIC
if( $task == "topic_delete" && ($business->user_rank == 2 || $business->user_rank == 1) ) {
    $businesstopic_id = $_GET['businesstopic_id'];
    $resource = $database->database_query("SELECT NULL FROM se_businesstopics WHERE se_businesstopics.businesstopic_id='{$businesstopic_id}' LIMIT 1");

    if( $database->database_num_rows($resource) ) {
        $database->database_query("DELETE FROM se_businesstopics WHERE se_businesstopics.businesstopic_id='{$businesstopic_id}' LIMIT 1");
        $database->database_query("DELETE FROM se_businessposts WHERE se_businessposts.businesspost_businesstopic_id='{$businesstopic_id}' LIMIT 1");
        $database->database_query("UPDATE se_allbusiness SET business_totaltopics=business_totaltopics-1 WHERE business_id='{$business->business_info['business_id']}' LIMIT 1");
        $business->business_info['business_totaltopics']--;
    }
}


// RETRIEVE FILES
elseif($task == "files_get") {
    // GET VARS
    if(isset($_POST['p'])) {
        $p = $_POST['p'];
    } elseif(isset($_GET['p'])) {
        $p = $_GET['p'];
    } else {
        $p = 1;
    }
    if(isset($_POST['cpp'])) {
        $cpp = $_POST['cpp'];
    } elseif(isset($_GET['cpp'])) {
        $cpp = $_GET['cpp'];
    } else {
        $cpp = 1;
    }

    // GET business ALBUM INFO
    $businessalbum_info = $database->database_fetch_assoc($database->database_query("SELECT businessalbum_id FROM se_businessalbums WHERE businessalbum_business_id='{$business->business_info['business_id']}' LIMIT 1"));

    // GET TOTAL FILES
    $total_files = $business->business_media_total($businessalbum_info['businessalbum_id']);

    // MAKE FILE PAGES AND GET FILE ARRAY
    $page_vars = make_page($total_files, $cpp, $p);
    $business_files = $business->business_media_list($page_vars[0], $cpp, $sort_by = "businessmedia_date DESC", $where = "");


    // CONSTRUCT JSON RESPONSE
    $file_output = Array('total_files' => (int) $total_files,
            'maxpage' => (int) $page_vars[2],
            'p_start' => (int) ($page_vars[0]+1),
            'p_end' => (int) ($page_vars[0]+count($business_files)),
            'p' => (int) $page_vars[1],
            'files' => $business_files);
    echo json_encode($file_output);
    exit();

}


// GET PRIVACY LEVEL
$privacy_max = $business->business_privacy_max($user);
$allowed_to_view = (bool) ($privacy_max & $business->business_info['business_privacy']);
$is_business_private = !$allowed_to_view;

// CHECK IF USER IS ALLOWED TO COMMENT
$allowed_to_comment = (bool) ($privacy_max & $business->business_info['business_comments']);

// CHECK IF USER IS ALLOWED TO POST IN DISCUSSION
$allowed_to_discuss = (bool) ($privacy_max & $business->business_info['business_discussion']);

// CHECK IF USER IS ALLOWED TO UPLOAD PHOTOS
$allowed_to_upload = (bool) ($privacy_max & $business->business_info['business_upload']);

// CHECK IF USER IS ALLOWED TO INVITE MEMBERS
$allowed_to_invite = (bool) ( $business->user_rank>=1 || ($business->user_rank>-1 && $business->business_info['business_invite']) );


// UPDATE business VIEWS IF business VISIBLE
if( $allowed_to_view ) {
    $business_views = $business->business_info['business_views'] + 1;
    $database->database_query("UPDATE se_allbusiness SET business_views=business_views+1 WHERE business_id='{$business->business_info['business_id']}' LIMIT 1");
}

// DELETE COMMENT NOTIFICATIONS IF VIEWING COMMENT PAGE
if( /* $v == "discussions" && */ $user->user_info['user_id'] == $business->business_info['business_user_id'] ) {
    $database->database_query("DELETE FROM se_notifys USING se_notifys LEFT JOIN se_notifytypes ON se_notifys.notify_notifytype_id=se_notifytypes.notifytype_id WHERE se_notifys.notify_user_id='{$business->business_info['business_user_id']}' AND se_notifytypes.notifytype_name='businesscomment' AND notify_object_id='{$business->business_info['business_id']}'");
}

// DELETE POST NOTIFICATIONS IF VIEWING DISCUSSION PAGE
if( /* $v == "discussions" && */ $user->user_info['user_id'] == $business->business_info['business_user_id'] ) {
    $database->database_query("DELETE FROM se_notifys USING se_notifys LEFT JOIN se_notifytypes ON se_notifys.notify_notifytype_id=se_notifytypes.notifytype_id WHERE se_notifys.notify_user_id='{$business->business_info['business_user_id']}' AND se_notifytypes.notifytype_name='businesspost' AND notify_object_id='{$business->business_info['business_id']}'");
}

// GET business COMMENTS
$comment = new se_comment('business', 'business_id', $business->business_info['business_id']);
$total_comments = $comment->comment_total();

// GET business MEDIA
$businessalbum_info = $database->database_fetch_assoc($database->database_query("SELECT businessalbum_id FROM se_businessalbums WHERE businessalbum_business_id='{$business->business_info['business_id']}' LIMIT 1"));
$total_files = $business->business_media_total($businessalbum_info[businessalbum_id]);

// GET business FIELDS
$businesscat_info = $database->database_fetch_assoc($database->database_query("SELECT t1.businesscat_id AS subcat_id, t1.businesscat_title AS subcat_title, t1.businesscat_dependency AS subcat_dependency, t2.businesscat_id AS cat_id, t2.businesscat_title AS cat_title FROM se_businesscats AS t1 LEFT JOIN se_businesscats AS t2 ON t1.businesscat_dependency=t2.businesscat_id WHERE t1.businesscat_id='{$business->business_info['business_businesscat_id']}'"));
if($businesscat_info['subcat_dependency'] == 0) {
    $cat_where = "businesscat_id='{$business->business_info['business_businesscat_id']}'";
} else {
    $cat_where = "businesscat_id='{$businesscat_info['subcat_dependency']}'";
}
$field = new se_field("business", $business->businessvalue_info);
$field->cat_list(0, 1, 0, $cat_where, "businesscat_id='0'", "");

// SET WHERE CLAUSE FOR MEMBER LIST
$where[] = "(se_businessmembers.businessmember_status='1')";
if($search != "") {
    $where[] = "(se_users.user_username LIKE '%{$search}%' OR CONCAT(se_users.user_fname, ' ', se_users.user_lname) LIKE '%{$search}%' OR se_users.user_email LIKE '%{$search}%')";
}

// GET TOTAL MEMBERS
$total_members = $business->business_member_total(implode(" AND ", $where), 1);

// MAKE MEMBER PAGES AND GET MEMBER ARRAY
$members_per_page = 10;
if($v == "members") {
    $p_members = $p;
} else {
    $p_members = 1;
}
$page_vars_members = make_page($total_members, $members_per_page, $p_members);
$members = $business->business_member_list($page_vars_members[0], $members_per_page, "is_viewers_friend DESC, se_users.user_username", implode(" AND ", $where));

// GET MASTER TOTAL OF MEMBERS
$total_members_all = $business->business_member_total("(se_businessmembers.businessmember_status='1')");

// GET OFFICERS
$where_officers = "se_businessmembers.businessmember_rank<>'0' AND se_businessmembers.businessmember_status='1' AND se_businessmembers.businessmember_approved='1'";
$total_officers = $business->business_member_total($where_officers, 0);
$officers = $business->business_member_list(0, $total_officers, "se_businessmembers.businessmember_rank DESC, se_users.user_username", $where_officers);

// CHECK TO SEE IF USER IS SUBSCRIBED TO business AND UPDATE VIEW TIME
if($database->database_num_rows($database->database_query("SELECT NULL FROM se_allbusinessubscribes WHERE allbusinessubscribe_business_id='{$business->business_info['business_id']}' AND allbusinessubscribe_user_id='{$user->user_info['user_id']}' LIMIT 1")) == 1) {
    $is_subscribed = 1;
    $database->database_query("UPDATE se_allbusinessubscribes SET allbusinessubscribe_time='".time()."' WHERE allbusinessubscribe_business_id='{$business->business_info['business_id']}' AND allbusinessubscribe_user_id='{$user->user_info['user_id']}'");
} else {
    $is_subscribed = 0;
}

// GET TOTAL DISCUSSION TOPICS
$total_topics = $business->business_topic_total();

// MAKE TOPIC PAGES AND GET TOPIC ARRAY
$topics_per_page = 10;
if($v == "discussions") {
    $p_topics = $p;
} else {
    $p_topics = 1;
}
$page_vars_topics = make_page($total_topics, $topics_per_page, $p_topics);
$topics = $business->business_topic_list($page_vars_topics[0], $topics_per_page, "businesstopic_sticky DESC, businesspost_date DESC");
//$topics = $business->business_topic_list($page_vars_topics[0], $topics_per_page, "businesstopic_sticky DESC, businesstopic_date DESC");


// GET CUSTOM business STYLE IF ALLOWED
if( $business->businessowner_level_info['level_business_style'] && !$is_business_private ) {
    $allbusinesstyle_info = $database->database_fetch_assoc($database->database_query("SELECT allbusinesstyle_css FROM se_allbusinesstyles WHERE allbusinesstyle_business_id='{$business->business_info['business_id']}' LIMIT 1"));
    $global_css = $allbusinesstyle_info['allbusinesstyle_css'];
}

// SET GLOBAL PAGE TITLE
$global_page_title[0] = 2000312; 
$global_page_title[1] = $business->business_info['business_title'];
$global_page_description[0] = 2000313;
$global_page_description[1] = $business->business_info['business_desc'];

// GET ACTIONS
$actions_array = $actions->actions_display(0, $setting['setting_actions_actionsonprofile'], "se_actions.action_object_owner='business' AND se_actions.action_object_owner_id='{$business->business_info['business_id']}'");
//print_r($actions_array);

foreach($actions_array  as $key => $action) {
    if($action['action_text'] == '2001359') {
        if(strlen($action['action_vars'][6])>100) $actions_array[$key]['action_vars'][6] = substr($action['action_vars'][6], 0, 100).'...';
    }
    if($action['action_text'] == '2001351') {
        if(strlen($action['action_vars'][4])>100) $actions_array[$key]['action_vars'][4] = substr($action['action_vars'][4], 0, 100).'...';
    }
}
$smarty->assign_by_ref('actions', $actions_array);

//не подтвержденные записи
$result = $database->database_query("SELECT * FROM se_appoinments WHERE master_business_id = ".$business->business_info['business_id']." ORDER BY app_time DESC");
while($rez = $database->database_fetch_assoc($result)) {
    if($rez['approved'] == 0) {
        if($rez['app_time'] > time()) {
            $app_user = new SEUser($rez['user_id']);
            $rez['user_username'] = $app_user->user_info['user_username'];
            $rez['user_displayname'] = $app_user->user_info['user_displayname'];
            $rez['user_photo'] = $app_user->user_photo("./images/nophoto.gif", TRUE, 'photo2');
            $rez['date'] = $app_user->rus_date('d F Y', $rez['app_time']).'<br/>время: '.date('H:i', $rez['app_time']);
            $new_appoinments[] = $rez;
        }
    }
    else {

        if($rez['app_time'] > time() && $rez['app_time'] < (time() + 2629743)) {
            $app_user = new SEUser($rez['user_id']);
            $rez['user_username'] = $app_user->user_info['user_username'];
            $rez['user_displayname'] = $app_user->user_info['user_displayname'];
            $rez['user_photo'] = $app_user->user_photo("./images/nophoto.gif", TRUE, 'photo2');
            $rez['date'] = $app_user->rus_date('d F Y', $rez['app_time']).'<br/>время: '.date('H:i', $rez['app_time']);
            $appoinments[] = $rez;
        }
    }
}

if($user->user_info['user_id'] == $business->business_info['business_user_id']) $allowed_to_comment = 0;

$smarty->assign('appoinments', $appoinments);
$smarty->assign('new_appoinments', $new_appoinments);
//$smarty->assign('new_appoinments', $new_appoinments);
//print_r($business);
// ASSIGN VARIABLES AND DISPLAY business PAGE
$smarty->assign_by_ref('business', $business);
$smarty->assign_by_ref('cats', $field->cats);
$smarty->assign_by_ref('members', $members);
$smarty->assign_by_ref('officers', $officers);
$smarty->assign_by_ref('topics', $topics);

$smarty->assign('businesscat_info', $businesscat_info);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('total_files', $total_files);
$smarty->assign('is_business_private', $is_business_private);
$smarty->assign('allowed_to_view', $allowed_to_view);
$smarty->assign('allowed_to_comment', $allowed_to_comment);
$smarty->assign('allowed_to_discuss', $allowed_to_discuss);
$smarty->assign('allowed_to_upload', $allowed_to_upload);
$smarty->assign('allowed_to_invite', $allowed_to_invite);
$smarty->assign('is_subscribed', $is_subscribed);
$smarty->assign('v', $v);
$smarty->assign('search', $search);
$smarty->assign('total_members', $total_members);
$smarty->assign('total_members_all', $total_members_all);
$smarty->assign('maxpage_members', $page_vars_members[2]);
$smarty->assign('p_start_members', $page_vars_members[0]+1);
$smarty->assign('p_end_members', $page_vars_members[0]+count($members));
$smarty->assign('p_members', $page_vars_members[1]);
$smarty->assign('total_topics', $total_topics);
$smarty->assign('maxpage_topics', $page_vars_topics[2]);
$smarty->assign('p_start_topics', $page_vars_topics[0]+1);
$smarty->assign('p_end_topics', $page_vars_topics[0]+count($topics));
$smarty->assign('p_topics', $page_vars_topics[1]);
include "footer.php";
?>