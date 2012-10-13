<?php

/* $Id: browse_allnews.php 20 2009-01-15 04:04:40Z john $ */

$page = "browse_allnews";
include "header.php";

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( !$user->user_exists && !$setting['setting_permission_news'] ) {
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
    $p = 1;
}
//if(isset($_POST['s'])) { $s = $_POST['s']; } elseif(isset($_GET['s'])) { $s = $_GET['s']; } else { $s = "news_datecreated DESC"; }
if(isset($_POST['v'])) {
    $v = $_POST['v'];
} elseif(isset($_GET['v'])) {
    $v = $_GET['v'];
} else {
    $v = 0;
}

if(isset($_POST['news_search'])) {
    $news_search = $_POST['news_search'];
} elseif(isset($_GET['news_search'])) {
    $news_search = $_GET['news_search'];
} else {
    $news_search = NULL;
}

$s = rc_toolkit::get_request('s','date');
if ($s == 'view') {
    $sort = "news_views DESC";
}
elseif ($s == 'title') {
    $sort = "article_title ASC";
}
else {
    $sort = "news_date DESC";
    $s = 'date';
}

if($v != "0" && $v != "1" && $v != '2' && strpos($v, 'p')) {
    $v = 0;
}


// SET WHERE CLAUSE
$where = " se_allnews.news_title != '' ";


// ONLY MY FRIENDS' allnews
if( $v=="1" && $user->user_exists ) {
    // SET WHERE CLAUSE
    $where .= " AND (SELECT TRUE FROM se_friends WHERE friend_user_id1='{$user->user_info['user_id']}' AND friend_user_id2=se_allnews.news_user_id AND friend_status=1)";
}
elseif($v == '2') {
    $newscat_id = 2;
}
elseif(strstr($v, 'p')) {
    $where .= " AND (SELECT true FROM se_profilevalues WHERE se_profilevalues.profilevalue_id = se_allnews.news_user_id AND se_profilevalues.profilevalue_20 = ".str_replace('p', '', $v).") ";
}
//print_r($v);


// SPECIFIC news CATEGORY
if( $newscat_id ) {
    $sql = "SELECT newscat_id, newscat_title, newscat_dependency FROM se_newscats WHERE newscat_id='{$newscat_id}' LIMIT 1";
    $resource = $database->database_query($sql);

    if( $database->database_num_rows($resource) ) {
        $newscat = $database->database_fetch_assoc($resource);

        if( !$newscat['newscat_dependency'] ) {
            $cat_ids[] = $newscat['newscat_id'];
            $depcats = $database->database_query("SELECT newscat_id FROM se_newscats WHERE newscat_id='{$newscat['newscat_id']}' OR newscat_dependency='{$newscat['newscat_id']}'");
            while($depcat_info = $database->database_fetch_assoc($depcats)) {
                $cat_ids[] = $depcat_info['newscat_id'];
            }
            $where .= " AND se_allnews.news_newscat_id IN('".implode("', '", $cat_ids)."')";
        }
        else {
            $where .= " AND se_allnews.news_newscat_id='{$newscat['newscat_id']}'";
            $allnewsubcat = $newscat;
            $newscat = $database->database_fetch_assoc($database->database_query("SELECT newscat_id, newscat_title FROM se_newscats WHERE newscat_id='{$newscat['newscat_dependency']}'"));
        }
    }
}


// GET CATS
$field = new se_field("news");
$field->cat_list(0, 0, 0, "", "", "");
$cat_menu_array = $field->cats;

//$field->cat_list(0, 0, 1, "newscat_id='{$newscat['newscat_id']}'", "", "");
$field->field_list(0, 0, 1, "newsfield_newscat_id='{$newscat['newscat_id']}' && newsfield_search<>'0'");


// BEGIN CONSTRUCTING SEARCH QUERY
//echo $field->field_query;
if( $field->field_query )
    $where .= " AND ".$field->field_query;

if( !empty($news_search) ) {

    $where .= " AND (news_title LIKE '%$news_search%' OR news_title LIKE '%$news_search%')";
}
//print_r($where);
// CREATE news OBJECT, GET TOTAL allnews, MAKE ENTRY PAGES, GET news ARRAY
$news = new se_news();

$total_allnews = $news->news_total($where, TRUE);
$allnews_per_page = 10;
$page_vars = make_page($total_allnews, $allnews_per_page, $p);

$news_array = $news->news_list($page_vars[0], $allnews_per_page, $sort, $where, TRUE);

$i = 0;
$result = $database->database_query("SELECT sec.news_title, sec.news_id, sec.news_body, seu.user_username
    FROM se_allnews sec INNER JOIN se_users seu ON sec.news_user_id = seu.user_id ORDER BY sec.news_views DESC LIMIT 5");
while($rez = $database->database_fetch_assoc($result)) {
    $popular_news[] = $rez;
    $popular_news[$i]['news_body'] = strip_tags(htmlspecialchars_decode($popular_news[$i]['news_body']));
    $i++;
}
$smarty->assign('popularnews', $popular_news);

//категории пользователей для сортировки
$profilefield_20 = $database->database_fetch_assoc($database->database_query("SELECT profilefield_options FROM se_profilefields WHERE profilefield_id = 20"));
$profilefield_20 = unserialize($profilefield_20['profilefield_options']);
foreach($profilefield_20 as &$value) {
    $value['value'] = 'p'.$value['value'];
}
$smarty->assign('profilefield_20', $profilefield_20);

// ASSIGN SMARTY VARIABLES AND DISPLAY allnews PAGE
$smarty->assign('newscat_id', $newscat_id);
$smarty->assign('newscat', $newscat);
$smarty->assign('allnewsubcat', $allnewsubcat);
$smarty->assign('news_search', $news_search);

$smarty->assign_by_ref('cats_menu', $cat_menu_array);
$smarty->assign_by_ref('cats', $field->cats);
$smarty->assign_by_ref('fields', $field->fields);
$smarty->assign_by_ref('url_string', $field->url_string);

$smarty->assign('allnews', $news_array);
$smarty->assign('total_allnews', $total_allnews);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($news_array));
$smarty->assign('s', $s);
$smarty->assign('v', $v);
include "footer.php";
?>