<?php

/* $Id: browse_albums.php 16 2009-01-13 04:01:31Z john $ */

$page = "browse_albums";
include "header.php";


// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( !$user->user_exists && !$setting['setting_permission_album'] ) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 656);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}


// PARSE GET/POST
if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 0;
}
if(isset($_POST['s'])) {
    $s = $_POST['s'];
} elseif(isset($_GET['s'])) {
    $s = $_GET['s'];
} else {
    $s = "album_datecreated DESC";
}

if(isset($_POST['sort'])) {
    $sort = $_POST['sort'];
} elseif(isset($_GET['sort'])) {
    $sort = $_GET['sort'];
} else {
    $sort = "signupdate";
}

if(isset($_POST['show'])) {
    $show = $_POST['show'];
} elseif(isset($_GET['show'])) {
    $show = $_GET['show'];
} else {
    $show = "admins";
}
if(isset($_POST['cmp'])) {
    $cmp_sort = $_POST['cmp'];
} elseif(isset($_GET['cmp'])) {
    $cmp_sort = $_GET['cmp'];
} else {
    $cmp_sort = '';
}

// ENSURE SORT/VIEW ARE VALID
if($s != "album_datecreated DESC" && $s != "album_dateupdated DESC") {
    $s = "album_dateupdated DESC";
}
if(!is_numeric($cmp_sort) && $cmp_sort != "winner") {
    $cmp_sort = '';
}


// SET WHERE CLAUSE
if($show != 'works') {
    $where = "CASE
	    WHEN se_albums.album_user_id='{$user->user_info[user_id]}'
	      THEN TRUE
	    WHEN ((se_albums.album_privacy & @SE_PRIVACY_REGISTERED) AND '{$user->user_exists}'<>0)
	      THEN TRUE
	    WHEN ((se_albums.album_privacy & @SE_PRIVACY_ANONYMOUS) AND '{$user->user_exists}'=0)
	      THEN TRUE
	    WHEN ((se_albums.album_privacy & @SE_PRIVACY_FRIEND) AND (SELECT TRUE FROM se_friends WHERE friend_user_id1=se_albums.album_user_id AND friend_user_id2='{$user->user_info[user_id]}' AND friend_status='1' LIMIT 1))
	      THEN TRUE
	    WHEN ((se_albums.album_privacy & @SE_PRIVACY_SUBNET) AND '{$user->user_exists}'<>0 AND (SELECT TRUE FROM se_users WHERE user_id=se_albums.album_user_id AND user_subnet_id='{$user->user_info[user_subnet_id]}' LIMIT 1))
	      THEN TRUE
	    WHEN ((se_albums.album_privacy & @SE_PRIVACY_FRIEND2) AND (SELECT TRUE FROM se_friends AS friends_primary LEFT JOIN se_users ON friends_primary.friend_user_id1=se_users.user_id LEFT JOIN se_friends AS friends_secondary ON friends_primary.friend_user_id2=friends_secondary.friend_user_id1 WHERE friends_primary.friend_user_id1=se_albums.album_user_id AND friends_secondary.friend_user_id2='{$user->user_info[user_id]}' AND se_users.user_subnet_id='{$user->user_info[user_subnet_id]}' LIMIT 1))
	      THEN TRUE
	    ELSE FALSE
	END";
}
else {
    $where = 'se_businessalbums.businessalbum_totalfiles > 0';
}


// ONLY MY FRIENDS' ALBUMS
if($cmp_sort != "" && is_numeric($cmp_sort)) {

    // SET WHERE CLAUSE
    $where .= " AND se_albums.album_competition = ".$cmp_sort;

}

// CREATE ALBUM OBJECT
$albums_per_page = 20;
$album = new se_album();

if($show == 'works') {
    if($sort == 'views') $orderby = ' business_views DESC ';
    else $orderby = ' businessalbum_dateupdated DESC ';
    $total_albums = $album->works_total($where);
}
else {
    if($show == 'albums') $where .= ' AND se_users.user_level_id != 3';
    else $where .= ' AND se_users.user_level_id = 3';
    if($sort == 'views') $orderby = ' album_views DESC ';
    else $orderby = ' album_dateupdated DESC ';
    $total_albums = $album->album_total($where);
}
$page_vars = make_page($total_albums, $albums_per_page, $p);
// MAKE ENTRY PAGES


if($show == 'albums') $album_array2 = $album->album_list($page_vars[0], $albums_per_page, $orderby, $where);
elseif($show == 'works') $album_array2 = $album->works_list($page_vars[0], $albums_per_page, $orderby, $where);
else $album_array2 = $album->album_list($page_vars[0], $albums_per_page, $orderby, $where);
//print_r($album_array2);
//$i=0;
//$result = $database->database_query("SELECT * FROM se_allalbumslist ORDER BY timestamp DESC LIMIT ".$page_vars[0].",".$albums_per_page);
//while($rez = $database->database_fetch_assoc($result)){
//    if($rez['album_type'] == 'user_album'){
//        $album_array2[$i] = $album->album_list(0, 1, 'album_dateupdated DESC', 'se_albums.album_id = '.$rez['album_id']);
//        $album_array2[$i]['album_type'] = 'user_album';
//        $album_array2[$i]['album_1'] = $rez['album_id'];
//    }
//    elseif($rez['album_type'] == 'business_album'){
//        $album_array2[$i] = $album->works_list(0, 1, 'businessalbum_datecreated DESC', 'se_businessalbums.businessalbum_id = '.$rez['album_id']);
//        $album_array2[$i]['album_type'] = 'business_album';
//        $album_array2[$i]['album_1'] = $rez['album_id'];
//    }
//    $i++;
//}

// ASSIGN SMARTY VARIABLES AND DISPLAY ALBUMS PAGE
$smarty->assign('sort', $sort);
$smarty->assign('albums', $album_array2);
$smarty->assign('show', $show);
$smarty->assign('total_albums', $total_albums);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($album_array2));
$smarty->assign('s', $s);
$smarty->assign('cmp_sort', $cmp_sort);
include "footer.php";
?>
