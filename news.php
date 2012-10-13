<?php

/* $Id: news.php 16 2009-01-13 04:01:31Z john $ */

$page = "news";
include "header.php";


// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( !$user->user_exists && !$setting['setting_permission_news'] )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 656);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}


// PARSE GET/POST
if(isset($_POST['task'])) { $task = $_POST['task']; } elseif(isset($_GET['task'])) { $task = $_GET['task']; } else { $task = "main"; }
if(isset($_GET['news_id'])) { $news_id = $_GET['news_id']; } elseif(isset($_POST['news_id'])) { $news_id = $_POST['news_id']; } else { $news_id = 0; }


// DISPLAY ERROR PAGE IF NO OWNER
$news = new se_news($user->user_info['user_id'], $news_id);
if( !$news->news_exists || !$owner->user_exists )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 828);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}


// GET PRIVACY LEVEL
$privacy_max = $owner->user_privacy_max($user);
$allowed_to_view    = (bool) ($privacy_max & $news->news_info['news_privacy' ]);
$allowed_to_comment = (bool) ($privacy_max & $news->news_info['news_comments']);
if(!$user->user_exists) $allowed_to_comment = 0;

// UPDATE news VIEWS IF GROUP VISIBLE
if( $allowed_to_view )
{
  $news->news_info['news_views']++;
  $sql = "UPDATE se_allnews SET news_views='{$news->news_info['news_views']}' WHERE news_id='{$news->news_info['news_id']}' LIMIT 1";
  $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);
}


// GET news CATEGORY
/*
$group_category = "";
$group_category_query = $database->database_query("SELECT groupcat_id, groupcat_title FROM se_groupcats WHERE groupcat_id='".$group->group_info[group_groupcat_id]."' LIMIT 1");
if($database->database_num_rows($group_category_query) == 1) {
  $group_category_info = $database->database_fetch_assoc($group_category_query);
  $group_category = $group_category_info[groupcat_title];
}
*/


// GET news COMMENTS
$comment = new se_comment('news', 'news_id', $news->news_info['news_id']);
$total_comments = $comment->comment_total();
$comments = $comment->comment_list(0, 10);


// GET CUSTOM news STYLE IF ALLOWED
if( $news->newsowner_level_info['level_news_style'] && $allowed_to_view )
{
  $sql ="SELECT allnewstyle_css FROM se_allnewstyles WHERE allnewstyle_user_id='{$owner->user_info['user_id']}' LIMIT 1";
  $resource = $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);
  
  if( $database->database_num_rows($resource) )
    $allnewstyle_info = $database->database_fetch_assoc($resource);
  
  if( $allnewstyle_info )
    $global_css = $allnewstyle_info['allnewstyle_css'];
}


// MAKE SURE TITLE IS NOT EMPTY, CONVERT BODY HTML CHARACTERS BACK
if( !$news->news_info['news_title'] )
  $news->news_info['news_title'] = 'Untitled';

$news->news_info['news_body'] = str_replace("\r\n", "", html_entity_decode($news->news_info['news_body']));


// GET news ALBUM INFO AND MEDIA ARRAY
$sql = "SELECT * FROM se_newsalbums WHERE newsalbum_news_id='{$news->news_info['news_id']}' LIMIT 1";
$resource = $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);

if( $database->database_num_rows($resource) )
{
  $newsalbum_info = $database->database_fetch_assoc($resource);
  
  $file_array = $news->news_media_list(0, 10, "newsmedia_id ASC", "(newsmedia_newsalbum_id='{$newsalbum_info['newsalbum_id']}')", TRUE);
  $total_files = $news->news_media_total($newsalbum_info['newsalbum_id']);
}


// GET SUBCAT IF NECESSARY
$sql = "SELECT newscat_id, newscat_dependency FROM se_newscats WHERE newscat_id='{$news->news_info['news_newscat_id']}' LIMIT 1";
$resource = $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);
if( $database->database_num_rows($resource) )
  $thiscat = $database->database_fetch_assoc($resource);

if( !$thiscat || !$thiscat['newscat_dependency'] )
{
  $news->news_info['news_allnewsubcat_id'] = 0;
}
else
{
  $news->news_info['news_allnewsubcat_id']  = $news->news_info['news_newscat_id'];
  $news->news_info['news_newscat_id']     = $thiscat['newscat_dependency'];
}


// GET FIELDS
$newscat_info = $database->database_fetch_assoc($database->database_query("SELECT t1.newscat_id AS subcat_id, t1.newscat_title AS subcat_title, t1.newscat_dependency AS subcat_dependency, t2.newscat_id AS cat_id, t2.newscat_title AS cat_title FROM se_newscats AS t1 LEFT JOIN se_newscats AS t2 ON t1.newscat_dependency=t2.newscat_id WHERE t1.newscat_id='{$news->news_info['news_newscat_id']}'"));
if( !$newscat_info['subcat_dependency'] )
{
  $cat_where = "newscat_id='{$news->news_info['news_newscat_id']}'";
}
else
{
  $cat_where = "newscat_id='{$newscat_info['subcat_dependency']}'";
}
$field = new se_field("news", $news->newsvalue_info);
$field->cat_list(0, 1, 0, $cat_where, "newscat_id='0'", "");


// DELETE NOTIFICATIONS
if( $user->user_info['user_id']==$owner->user_info['user_id'] )
{
  $database->database_query("
    DELETE FROM
      se_notifys
    USING
      se_notifys
    LEFT JOIN
      se_notifytypes
      ON se_notifys.notify_notifytype_id=se_notifytypes.notifytype_id
    WHERE
      se_notifys.notify_user_id='{$owner->user_info[user_id]}' AND
      se_notifytypes.notifytype_name='newscomment' AND
      notify_object_id='{$news->news_info['news_id']}'
  ");
}


// SET SEO STUFF
$global_page_content = $news->news_info['news_title'];
$global_page_content = cleanHTML(str_replace('>', '> ', $global_page_content), NULL);
if( strlen($global_page_content)>255 ) $global_page_content = substr($global_page_content, 0, 251).'...';
$global_page_content = addslashes(trim(preg_replace('/\s+/', ' ',$global_page_content)));

$global_page_title = array(
  4500344,
  $owner->user_displayname,
  $global_page_content
);

$global_page_content = $news->news_info['news_body'];
$global_page_content = cleanHTML(str_replace('>', '> ', $global_page_content), NULL);
if( strlen($global_page_content)>255 ) $global_page_content = substr($global_page_content, 0, 251).'...';
$global_page_content = addslashes(trim(preg_replace('/\s+/', ' ',$global_page_content)));

$global_page_description = array(
  4500344,
  $owner->user_displayname,
  $global_page_content
);
$news->owner_info = new se_user($news->news_info['news_user_id']);
$news->news_info['news_date'] = date('d.m.Y', $news->news_info['news_date']);
//print_r($news);
// ASSIGN VARIABLES AND DISPLAY news PAGE
$smarty->assign_by_ref('news', $news);
$smarty->assign_by_ref('cats', $field->cats);

$smarty->assign('comments', $comments);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('allowed_to_comment', $allowed_to_comment);

$smarty->assign('files', $file_array);
$smarty->assign('total_files', $total_files);
include "footer.php";
?>