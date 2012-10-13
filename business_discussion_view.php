<?php

/* $Id: business_discussion_view.php 92 2009-03-11 00:17:00Z john $ */

$page = "business_discussion_view";
include "header.php";

if(isset($_POST['p'])) { $p = $_POST['p']; } elseif(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }
if(isset($_POST['task'])) { $task = $_POST['task']; } elseif(isset($_GET['task'])) { $task = $_GET['task']; } else { $task = ""; }
if(isset($_POST['business_id'])) { $business_id = $_POST['business_id']; } elseif(isset($_GET['business_id'])) { $business_id = $_GET['business_id']; } else { $business_id = 0; }
if(isset($_POST['businesstopic_id'])) { $businesstopic_id = $_POST['businesstopic_id']; } elseif(isset($_GET['businesstopic_id'])) { $businesstopic_id = $_GET['businesstopic_id']; } else { $businesstopic_id = 0; }
if(isset($_POST['businesspost_id'])) { $businesspost_id = $_POST['businesspost_id']; } elseif(isset($_GET['businesspost_id'])) { $businesspost_id = $_GET['businesspost_id']; } else { $businesspost_id = 0; }

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



// CHECK THAT TOPIC EXISTS AND GET TOPIC INFO
$topic_query = $database->database_query("SELECT * FROM se_businesstopics WHERE businesstopic_id='{$businesstopic_id}' AND businesstopic_business_id='{$business->business_info['business_id']}' LIMIT 1");
if( !$database->database_num_rows($topic_query) )
{
  header("Location: ".$url->url_create("business", NULL, $business->business_info['business_id'])."&v=discussions");
  exit();
}

$topic_info = $database->database_fetch_assoc($topic_query);


// CHECK IF USER IS ADMIN OR OFFICER
if($business->user_rank == 2 || $business->user_rank == 1)
{
  // STICKY TOPIC
  if($task == "sticky")
  {
    $database->database_query("UPDATE se_businesstopics SET businesstopic_sticky=1 WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
    $topic_info['businesstopic_sticky'] = 1;
  }
  
  // UNSTICKY TOPIC
  elseif($task == "unsticky")
  {
    $database->database_query("UPDATE se_businesstopics SET businesstopic_sticky=0 WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
    $topic_info['businesstopic_sticky'] = 0;
  }
  
  // CLOSE TOPIC
  elseif($task == "close")
  {
    $database->database_query("UPDATE se_businesstopics SET businesstopic_closed=1 WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
    $topic_info['businesstopic_closed'] = 1;
  }
  
  // OPEN TOPIC
  elseif($task == "open")
  {
    $database->database_query("UPDATE se_businesstopics SET businesstopic_closed=0 WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
    $topic_info['businesstopic_closed'] = 0;
  }
  
  // EDIT TOPIC
  elseif($task == "topic_edit")
  {
    $topic_subject = $_POST['topic_subject'];
    
    if( trim($topic_subject) )
    {
      $database->database_query("UPDATE se_businesstopics SET businesstopic_subject='{$topic_subject}' WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
      $topic_info['businesstopic_subject'] = $topic_subject;
    }
  }
}


// EDIT POST
if($task == "post_edit")
{
  $post_query = $database->database_query("SELECT businesspost_id, businesspost_authoruser_id FROM se_businessposts WHERE businesspost_id='{$businesspost_id}' AND businesspost_businesstopic_id='{$topic_info['businesstopic_id']}'");
  if( $database->database_num_rows($post_query) )
  {
    $post_info = $database->database_fetch_assoc($post_query);
    
    // ADD BREAKS AND business POST BODY
    $businesspost_body = $_POST['businesspost_body'];
    $businesspost_body = $business->business_post_bbcode_parse_clean($businesspost_body);
    $businesspost_body = addslashes(stripslashes($businesspost_body));
    $businesspost_date = time();
    
    if( $user->user_exists && $post_info['businesspost_authoruser_id'] == $user->user_info['user_id'] && trim($businesspost_body) )
    {
      $database->database_query("UPDATE se_businessposts SET businesspost_lastedit_date='{$businesspost_date}', businesspost_lastedit_user_id='{$user->user_info['user_id']}', businesspost_body='{$businesspost_body}' WHERE businesspost_id='{$businesspost_id}' LIMIT 1");
      $post_info['businesspost_body'] = $businesspost_body;
      $post_info['businesspost_body_formatted'] = $business->business_post_bbcode_parse_view($post_info['businesspost_body']);
    }
    
    // RUN JAVASCRIPT FUNCTION
    $post_info['businesspost_body'] = addslashes(stripslashes($post_info['businesspost_body']));
    $post_info['businesspost_body_formatted'] = addslashes(stripslashes($post_info['businesspost_body_formatted']));
    echo "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>\n<script type=\"text/javascript\">\n";
    echo "window.parent.document.getElementById('post_div_{$post_info['businesspost_id']}').innerHTML = '{$post_info['businesspost_body_formatted']}';\n";
    echo "window.parent.document.getElementById('post_body_{$post_info['businesspost_id']}').innerHTML = '{$post_info['businesspost_body']}';\n";
    echo "</script>\n</head>\n<body>\n</body>\n</html>";
    exit();
  }
}


// DELETE POST
elseif($task == "post_delete")
{
  $post_query = $database->database_query("SELECT businesspost_id, businesspost_authoruser_id FROM se_businessposts WHERE businesspost_id='{$businesspost_id}' AND businesspost_businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
  if( $database->database_num_rows($post_query) )
  {
    $post_info = $database->database_fetch_assoc($post_query);
    
    if( ($user->user_exists && $post_info['businesspost_authoruser_id'] == $user->user_info['user_id']) || $business->user_rank == 2 || $business->user_rank == 1 )
    {
      $database->database_query("UPDATE se_businessposts SET businesspost_deleted=1 WHERE businesspost_id='{$businesspost_id}' LIMIT 1");
      // Whoops we're not supposed to permanently delete them
      //$database->database_query("UPDATE se_businesstopics SET businesstopic_totalposts=businesstopic_totalposts-1 WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");
    }
  }
}



// GET PRIVACY LEVEL
$privacy_max = $business->business_privacy_max($user);
if( !($privacy_max & $business->business_info['business_privacy']) )
{
  header("Location: ".$url->url_create("business", NULL, $business->business_info['business_id']));
  exit();
}

// CHECK IF USER IS ALLOWED TO POST IN DISCUSSION
$allowed_to_discuss = ( ($privacy_max & $business->business_info['business_discussion']) && !$topic_info['businesstopic_closed'] );


// INCREMENT VIEWS FOR THIS TOPIC
$database->database_query("UPDATE se_businesstopics SET businesstopic_views=businesstopic_views+1 WHERE businesstopic_id='{$topic_info['businesstopic_id']}' LIMIT 1");


// SET POSTS PER PAGE
$posts_per_page = 10;


// IF businessPOST ID IS SET, RESET PAGE
if( $businesspost_id )
{
  $previous_posts = $database->database_num_rows($database->database_query("SELECT NULL FROM se_businessposts WHERE businesspost_id<='{$businesspost_id}' AND businesspost_businesstopic_id='{$topic_info['businesstopic_id']}'"));
  if( $previous_posts ) { $p = ceil($previous_posts/$posts_per_page); }
}


// GET TOTAL POSTS
$total_posts = $business->business_post_total(NULL, $topic_info['businesstopic_id']);

// MAKE POST PAGES
$page_vars = make_page($total_posts, $posts_per_page, $p);

// GET business POSTS
$posts = $business->business_post_list($page_vars[0], $posts_per_page, "se_businessposts.businesspost_date ASC", "(se_businessposts.businesspost_businesstopic_id='{$topic_info['businesstopic_id']}')");

// GET CUSTOM business STYLE IF ALLOWED
if( $business->businessowner_level_info['level_business_style'] )
{ 
  $allbusinesstyle_info = $database->database_fetch_assoc($database->database_query("SELECT allbusinesstyle_css FROM se_allbusinesstyles WHERE allbusinesstyle_business_id='{$business->business_info['business_id']}' LIMIT 1"));
  $global_css = $allbusinesstyle_info['allbusinesstyle_css'];
}

// SET GLOBAL PAGE TITLE
$global_page_title[0] = 2001314;
$global_page_title[1] = $business->business_info['business_title'];
$global_page_title[2] = $topic_info['businesstopic_subject'];
$global_page_description[0] = 2001313;
$global_page_description[1] = $business->business_info['business_desc'];


// ASSIGN VARIABLES AND INCLUDE FOOTER
$smarty->assign('businesspost_id', $businesspost_id);
$smarty->assign('business', $business);
$smarty->assign('posts', $posts);
$smarty->assign('topic_info', $topic_info);
$smarty->assign('allowed_to_discuss', $allowed_to_discuss);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('total_posts', $total_posts);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($posts));
include "footer.php";
?>