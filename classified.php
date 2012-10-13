<?php

/* $Id: classified.php 16 2009-01-13 04:01:31Z john $ */

$page = "classified";
include "header.php";


// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( !$user->user_exists && !$setting['setting_permission_classified'] )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 656);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}


// PARSE GET/POST
if(isset($_POST['task'])) { $task = $_POST['task']; } elseif(isset($_GET['task'])) { $task = $_GET['task']; } else { $task = "main"; }
if(isset($_GET['classified_id'])) { $classified_id = $_GET['classified_id']; } elseif(isset($_POST['classified_id'])) { $classified_id = $_POST['classified_id']; } else { $classified_id = 0; }


// DISPLAY ERROR PAGE IF NO OWNER
$classified = new se_classified($user->user_info['user_id'], $classified_id);
if( !$classified->classified_exists || !$owner->user_exists )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 828);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}


// GET PRIVACY LEVEL
$privacy_max = $owner->user_privacy_max($user);
$allowed_to_view    = (bool) ($privacy_max & $classified->classified_info['classified_privacy' ]);
$allowed_to_comment = (bool) ($privacy_max & $classified->classified_info['classified_comments']);


// UPDATE CLASSIFIED VIEWS IF GROUP VISIBLE
if( $allowed_to_view )
{
  $classified->classified_info['classified_views']++;
  $sql = "UPDATE se_classifieds SET classified_views='{$classified->classified_info['classified_views']}' WHERE classified_id='{$classified->classified_info['classified_id']}' LIMIT 1";
  $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);
}


// GET CLASSIFIED CATEGORY
/*
$group_category = "";
$group_category_query = $database->database_query("SELECT groupcat_id, groupcat_title FROM se_groupcats WHERE groupcat_id='".$group->group_info[group_groupcat_id]."' LIMIT 1");
if($database->database_num_rows($group_category_query) == 1) {
  $group_category_info = $database->database_fetch_assoc($group_category_query);
  $group_category = $group_category_info[groupcat_title];
}
*/


// GET CLASSIFIED COMMENTS
$comment = new se_comment('classified', 'classified_id', $classified->classified_info['classified_id']);
$total_comments = $comment->comment_total();
$comments = $comment->comment_list(0, 10);


// GET CUSTOM CLASSIFIED STYLE IF ALLOWED
if( $classified->classifiedowner_level_info['level_classified_style'] && $allowed_to_view )
{
  $sql ="SELECT classifiedstyle_css FROM se_classifiedstyles WHERE classifiedstyle_user_id='{$owner->user_info['user_id']}' LIMIT 1";
  $resource = $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);
  
  if( $database->database_num_rows($resource) )
    $classifiedstyle_info = $database->database_fetch_assoc($resource);
  
  if( $classifiedstyle_info )
    $global_css = $classifiedstyle_info['classifiedstyle_css'];
}


// MAKE SURE TITLE IS NOT EMPTY, CONVERT BODY HTML CHARACTERS BACK
if( !$classified->classified_info['classified_title'] )
  $classified->classified_info['classified_title'] = 'Untitled';

$classified->classified_info['classified_body'] = str_replace("\r\n", "", html_entity_decode($classified->classified_info['classified_body']));


// GET CLASSIFIED ALBUM INFO AND MEDIA ARRAY
$sql = "SELECT * FROM se_classifiedalbums WHERE classifiedalbum_classified_id='{$classified->classified_info['classified_id']}' LIMIT 1";
$resource = $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);

if( $database->database_num_rows($resource) )
{
  $classifiedalbum_info = $database->database_fetch_assoc($resource);
  
  $file_array = $classified->classified_media_list(0, 10, "classifiedmedia_id ASC", "(classifiedmedia_classifiedalbum_id='{$classifiedalbum_info['classifiedalbum_id']}')", TRUE);
  $total_files = $classified->classified_media_total($classifiedalbum_info['classifiedalbum_id']);
}


// GET SUBCAT IF NECESSARY
$sql = "SELECT classifiedcat_id, classifiedcat_dependency FROM se_classifiedcats WHERE classifiedcat_id='{$classified->classified_info['classified_classifiedcat_id']}' LIMIT 1";
$resource = $database->database_query($sql) or die("<b>Error: </b>".$database->database_error()."<br /><b>File: </b>".__FILE__."<br /><b>Line: </b>".__LINE__."<br /><b>Query: </b>".$sql);
if( $database->database_num_rows($resource) )
  $thiscat = $database->database_fetch_assoc($resource);

if( !$thiscat || !$thiscat['classifiedcat_dependency'] )
{
  $classified->classified_info['classified_classifiedsubcat_id'] = 0;
}
else
{
  $classified->classified_info['classified_classifiedsubcat_id']  = $classified->classified_info['classified_classifiedcat_id'];
  $classified->classified_info['classified_classifiedcat_id']     = $thiscat['classifiedcat_dependency'];
}


// GET FIELDS
$classifiedcat_info = $database->database_fetch_assoc($database->database_query("SELECT t1.classifiedcat_id AS subcat_id, t1.classifiedcat_title AS subcat_title, t1.classifiedcat_dependency AS subcat_dependency, t2.classifiedcat_id AS cat_id, t2.classifiedcat_title AS cat_title FROM se_classifiedcats AS t1 LEFT JOIN se_classifiedcats AS t2 ON t1.classifiedcat_dependency=t2.classifiedcat_id WHERE t1.classifiedcat_id='{$classified->classified_info['classified_classifiedcat_id']}'"));
if( !$classifiedcat_info['subcat_dependency'] )
{
  $cat_where = "classifiedcat_id='{$classified->classified_info['classified_classifiedcat_id']}'";
}
else
{
  $cat_where = "classifiedcat_id='{$classifiedcat_info['subcat_dependency']}'";
}
$field = new se_field("classified", $classified->classifiedvalue_info);
$field->cat_list(0, 1, 0, $cat_where, "classifiedcat_id='0'", "");


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
      se_notifytypes.notifytype_name='classifiedcomment' AND
      notify_object_id='{$classified->classified_info['classified_id']}'
  ");
}


// SET SEO STUFF
$global_page_content = $classified->classified_info['classified_title'];
$global_page_content = cleanHTML(str_replace('>', '> ', $global_page_content), NULL);
if( strlen($global_page_content)>255 ) $global_page_content = substr($global_page_content, 0, 251).'...';
$global_page_content = addslashes(trim(preg_replace('/\s+/', ' ',$global_page_content)));

$global_page_title = array(
  4500144,
  $owner->user_displayname,
  $global_page_content
);

$global_page_content = $classified->classified_info['classified_body'];
$global_page_content = cleanHTML(str_replace('>', '> ', $global_page_content), NULL);
if( strlen($global_page_content)>255 ) $global_page_content = substr($global_page_content, 0, 251).'...';
$global_page_content = addslashes(trim(preg_replace('/\s+/', ' ',$global_page_content)));

$global_page_description = array(
  4500144,
  $owner->user_displayname,
  $global_page_content
);

$classified->owner_info = new se_user($classified->classified_info['classified_user_id']);
$classified->classified_info['classified_date'] = date('d.m.Y', $classified->classified_info['classified_date']);
// ASSIGN VARIABLES AND DISPLAY CLASSIFIED PAGE
$smarty->assign_by_ref('classified', $classified);
$smarty->assign_by_ref('cats', $field->cats);

$smarty->assign('comments', $comments);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('allowed_to_comment', $allowed_to_comment);

$smarty->assign('files', $file_array);
$smarty->assign('total_files', $total_files);
include "footer.php";
?>