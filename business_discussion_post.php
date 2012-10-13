<?php

/* $Id: business_discussion_post.php 36 2009-01-27 02:35:37Z john $ */

$page = "business_discussion_post";
include "header.php";

if(isset($_POST['task'])) { $task = $_POST['task']; } elseif(isset($_GET['task'])) { $task = $_GET['task']; } else { $task = "main"; }
if(isset($_POST['business_id'])) { $business_id = $_POST['business_id']; } elseif(isset($_GET['business_id'])) { $business_id = $_GET['business_id']; } else { $business_id = 0; }

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( (!$user->user_exists && !$setting['setting_permission_business']) || ($user->user_exists && (~(int)$user->level_info['level_business_allow'] & 1)) )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 656);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}

// DISPLAY ERROR PAGE IF NO OWNER
$business = new se_business($user->user_info['user_id'], $business_id);
if( !$business->business_exists )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 2001219);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}

// GET PRIVACY LEVEL
$privacy_max = $business->business_privacy_max($user);
if( !($privacy_max & $business->business_info['business_privacy']) )
{
  header("Location: ".$url->url_create('business', NULL, $business->business_info['business_id']));
  exit();
}

// CHECK IF USER IS ALLOWED TO DISCUSS
if( !($privacy_max & $business->business_info['business_discussion']) )
{
  header("Location: ".$url->url_create('business', NULL, $business->business_info['business_id'])."&v=discussions");
  exit();
}


// SET VARS
$is_error = 0;
$topic_subject = "";
$topic_body = "";


// IF A TOPIC IS BEING POSTED
if($task == "topic_create")
{
  $topic_date = time();
  $topic_subject = censor($_POST['topic_subject']);
  $topic_body = $_POST['topic_body'];

  // ADD BREAKS AND TOPIC BODY
  $topic_body = $business->business_post_bbcode_parse_clean($topic_body);
  $topic_body = addslashes(stripslashes($topic_body));
  
  // RETRIEVE AND CHECK SECURITY CODE IF NECESSARY
  if( $setting['setting_business_discussion_code'] )
  {
    if( !session_id() ) session_start();
    $code = $_SESSION['code'];
    if($code == "") { $code = randomcode(); }
    if($_POST['comment_secure'] != $code) { $is_error = 832; }
  }

  // MAKE SURE TOPIC BODY IS NOT EMPTY
  if(trim($topic_body) == "") { $is_error = 2001298; }

  // CHECK THAT SUBJECT IS NOT EMPTY
  if(trim($topic_subject) == "") { $is_error = 2001299; }

  // ADD TOPIC IF NO ERROR
  if( !$is_error )
  {
    $database->database_query("UPDATE se_allbusiness SET business_totaltopics=business_totaltopics+1 WHERE business_id='{$business->business_info['business_id']}' LIMIT 1");
    $database->database_query("INSERT INTO se_businesstopics (businesstopic_business_id, businesstopic_creatoruser_id, businesstopic_date, businesstopic_subject, businesstopic_totalposts) VALUES ('{$business->business_info['business_id']}', '{$user->user_info['user_id']}', '{$topic_date}', '{$topic_subject}', 1)");
    $topic_id = $database->database_insert_id();
    $database->database_query("INSERT INTO se_businessposts (businesspost_businesstopic_id, businesspost_authoruser_id, businesspost_date, businesspost_body) VALUES ('{$topic_id}', '{$user->user_info['user_id']}', '{$topic_date}', '{$topic_body}')");
    $post_id = $database->database_insert_id();
    
    // INSERT ACTION IF USER EXISTS
    if( $user->user_exists )
    {
      $poster = $user->user_displayname;
      $topic_body_encoded = strip_tags($topic_body, '<br>');
      if( strlen($topic_body_encoded) > 250 )
        $topic_body_encoded = substr($topic_body_encoded, 0, 247)."...";
      $actions->actions_add($user, "businesstopic", Array($user->user_info['user_username'], $user->user_displayname, $business->business_info['business_id'], $business->business_info['business_title'], $topic_id, $topic_subject, $topic_body_encoded), Array(), 0, false, 'business', $business->business_info['business_id'], $business->business_info['business_privacy']);
    }
    else
    {
      SE_Language::_preload(835);
      SE_Language::load();
      $poster = SE_Language::_get(835);
    }

    // SEND business POST NOTIFICATION IF COMMENTER IS NOT OWNER
    if( $business->business_info['business_user_id'] != $user->user_info['user_id'] )
    { 
      $businessowner = new se_user(Array($business->business_info['business_user_id']));
      $notifytype = $notify->notify_add($business->business_info['business_user_id'], 'businesspost', $business->business_info['business_id'], Array($business->business_info['business_id']), Array($business->business_info['business_title']));
      $object_url = $url->url_base.vsprintf($notifytype[notifytype_url], Array($business->business_info['business_id']));
      $businessowner->user_settings();
      if( $businessowner->usersetting_info['usersetting_notify_businesspost'] )
      {
        send_systememail("businesspost", $businessowner->user_info['user_email'], Array($businessowner->user_displayname, $poster, "<a href=\"$object_url\">$object_url</a>"));
      }
    }
    
    $business->business_lastupdate();
    
    header("Location: ".$url->url_create('business_discussion_post', NULL, $business->business_info['business_id'], $topic_id, $post_id));
    exit();
  }
}


// A REPLY IS BEING POSTED
elseif($task == "reply_do")
{
  $businesstopic_id = $_POST['businesstopic_id'];
  $businesspost_body = $_POST['businesspost_body'];
  
  // VALIDATE businessTOPIC ID
  $topic_query = $database->database_query("SELECT businesstopic_id, businesstopic_subject FROM se_businesstopics WHERE businesstopic_id='{$businesstopic_id}' AND businesstopic_business_id='{$business->business_info['business_id']}' LIMIT 1");
  if($database->database_num_rows($topic_query) != 1) { exit(); }
  $businesstopic_info = $database->database_fetch_assoc($topic_query);
  
  // Clean HTML and pre-process bbcode
  $businesspost_body = $business->business_post_bbcode_parse_clean($businesspost_body);
  $businesspost_body = addslashes(stripslashes($businesspost_body));
  
  // RETRIEVE AND CHECK SECURITY CODE IF NECESSARY
  if( $setting['setting_business_discussion_code'] )
  {
    if( !session_id() ) session_start();
    $code = $_SESSION['code'];
    if($code == "") { $code = randomcode(); }
    if($_POST['comment_secure'] != $code) { $is_error = 832; }
  }

  // MAKE SURE TOPIC BODY IS NOT EMPTY
  if( !trim($businesspost_body) ) { $is_error = 2001298; }

  // RUN JAVASCRIPT FUNCTION
  echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'><script type=\"text/javascript\">";

  if( $is_error )
  {
    $error = SE_Language::get($is_error);
    echo "window.parent.document.getElementById('post_error').innerHTML = '{$error}';";
    echo "window.parent.document.getElementById('post_error').style.display = 'block';";
  }
  else
  {
    $database->database_query("UPDATE se_businesstopics SET businesstopic_totalposts=businesstopic_totalposts+1 WHERE businesstopic_id='{$businesstopic_id}' LIMIT 1");
    $database->database_query("INSERT INTO se_businessposts (businesspost_businesstopic_id, businesspost_authoruser_id, businesspost_date, businesspost_body) VALUES ('{$businesstopic_id}', '{$user->user_info['user_id']}', '".time()."', '{$businesspost_body}')");
    $post_id = $database->database_insert_id();
    
    // INSERT ACTION IF USER EXISTS
    if( $user->user_exists )
    {
      $poster = $user->user_displayname;
      
      $businesspost_body_encoded = strip_tags($businesspost_body, '<br>');
      if( strlen($businesspost_body_encoded) > 250 )
        $businesspost_body_encoded = substr($businesspost_body_encoded, 0, 247)."...";
      
      $actions->actions_add($user, "businesspost", Array($user->user_info['user_username'], $user->user_displayname, $business->business_info['business_id'], $businesstopic_info['businesstopic_id'], $businesstopic_info['businesstopic_subject'], $post_id, $businesspost_body_encoded), Array(), 0, false, 'business', $business->business_info['business_id'], $business->business_info['business_privacy']);
    }
    else
    {
      SE_Language::_preload(835);
      SE_Language::load();
      $poster = SE_Language::_get(835);
    }

    // SEND business POST NOTIFICATION IF COMMENTER IS NOT OWNER
    if( $business->business_info['business_user_id'] != $user->user_info['user_id'] )
    { 
      $businessowner = new se_user(Array($business->business_info['business_user_id']));
      $notifytype = $notify->notify_add($business->business_info['business_user_id'], 'businesspost', $business->business_info['business_id'], Array($business->business_info['business_id']), Array($business->business_info['business_title']));
      $object_url = $url->url_base.vsprintf($notifytype[notifytype_url], Array($business->business_info[business_id]));
      $businessowner->user_settings();
      if( $businessowner->usersetting_info['usersetting_notify_businesspost'] )
      {
        send_systememail("businesspost", $businessowner->user_info['user_email'], Array($businessowner->user_displayname, $poster, "<a href=\"$object_url\">$object_url</a>"));
      }
    }
    
    $business->business_lastupdate();
    
    echo "window.parent.location.href = '".$url->url_create('business_discussion_post', NULL, $business->business_info['business_id'], $businesstopic_id, $post_id)."';";
  }
  echo "</script></head><body></body></html>";
  exit();

}

// GET CUSTOM business STYLE IF ALLOWED
if( $business->businessowner_level_info['level_business_style'] )
{ 
  $allbusinesstyle_info = $database->database_fetch_assoc($database->database_query("SELECT allbusinesstyle_css FROM se_allbusinesstyles WHERE allbusinesstyle_business_id='{$business->business_info['business_id']}' LIMIT 1"));
  $global_css = $allbusinesstyle_info['allbusinesstyle_css'];
}

// SET GLOBAL PAGE TITLE
$global_page_title[0] = 2001328;
$global_page_description[0] = 2001329;

// ASSIGN VARIABLES AND INCLUDE FOOTER
$smarty->assign('business', $business);
$smarty->assign('is_error', $is_error);
$smarty->assign('topic_subject', $topic_subject);
$smarty->assign('topic_body', str_replace("<br>", "\r\n", $topic_body));
include "footer.php";
?>