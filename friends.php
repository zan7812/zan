<?php

/* $Id: user_friends.php 42 2009-01-29 04:55:14Z john $ */

$page = "friends";
include "header.php";


if(isset($_POST['user'])) {
    $p = $_POST['user'];
} elseif(isset($_GET['user'])) {
    $p = $_GET['user'];
} else {
    $p = '';
}
if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 1;
}
if(isset($_POST['s'])) {
    $s = $_POST['s'];
} elseif(isset($_GET['s'])) {
    $s = $_GET['s'];
} else {
    $s = "ud";
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
//print_r($owner->user_info['user_id']);exit();
// ENSURE CONECTIONS ARE ALLOWED FOR THIS USER
if( !$owner->user_info['user_id'] ) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 1085);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}


// SET FRIEND SORT-BY VARIABLES FOR HEADING LINKS
$u = "ud";    // LAST UPDATE DATE
$l = "ld";    // LAST LOGIN DATE
$t = "t";     // FRIEND TYPE

// SET SORT VARIABLE FOR DATABASE QUERY
switch($s) {
    case "ud": $sort = "se_users.user_dateupdated DESC";
        $u = "ud";
        break;
    case "ld": $sort = "se_users.user_lastlogindate DESC";
        $l = "ld";
        break;
    case "t": $sort = "se_friends.friend_type";
        $t = "td";
        break;
    default: $sort = "se_users.user_dateupdated DESC";
        $u = "ud";
}

// SET WHERE CLAUSE
$is_where = 0;
$where = "";
if($search != "") {
    $is_where = 1;
    $where = "(se_users.user_username LIKE '%$search%' OR se_users.user_fname LIKE '%$search%' OR se_users.user_lname LIKE '%$search%' OR CONCAT(se_users.user_fname, ' ', se_users.user_lname) LIKE '%$search%' OR se_users.user_email LIKE '%$search%')";
}

// DECIDE WHETHER TO SHOW DETAILS
$connection_types = explode("<!>", trim($setting['setting_connection_types']));
$show_details = ( !empty($connection_types) || $setting['setting_connection_other'] || $setting['setting_connection_explain'] );

// GET TOTAL FRIENDS
$total_friends = $owner->user_friend_total(0, 1, $is_where, $where);

// MAKE FRIEND PAGES
$friends_per_page = 10;
$page_vars = make_page($total_friends, $friends_per_page, $p);

// GET FRIEND ARRAY
$friends = $owner->user_friend_list($page_vars[0], $friends_per_page, 0, 1, $sort, $where, $show_details);


$result = $database->database_query("SELECT sea.action_id, sea.action_actiontype_id, sea.action_text, sea.action_object_owner, sec.actiontype_text, seu.user_photo, seu.user_id, seu.user_username
    FROM se_actions sea
    INNER JOIN se_friends sef ON sef.friend_user_id2 = sea.action_user_id
    INNER JOIN se_actiontypes sec ON sec.actiontype_id = sea.action_actiontype_id
    INNER JOIN se_users seu ON seu.user_id = sea.action_user_id
    WHERE sef.friend_user_id1 = ".$owner->user_info['user_id']." ORDER BY action_date DESC LIMIT 0, 10 ");
$i = 0;
while($rez = $database->database_fetch_assoc($result)) {
    $friend_news[] = $rez;
    $friend_news[$i]['action_text'] = unserialize($rez['action_text']);
    $i++;
}


// ASSIGN VARIABLES AND INCLUDE FOOTER
$smarty->assign('fnews', $friend_news);
$smarty->assign('owner', $owner);
$smarty->assign('s', $s);
$smarty->assign('u', $u);
$smarty->assign('l', $l);
$smarty->assign('t', $t);
$smarty->assign('search', $search);
$smarty->assign('friends', $friends);
$smarty->assign('total_friends', $total_friends);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($friends));
$smarty->assign('show_details', $show_details);
include "footer.php";
?>