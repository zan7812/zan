<?
$page = "masterclass";
include "header.php";

if(isset($_GET['masterclass_id'])) { $masterclass_id = $_GET['masterclass_id']; } else { $masterclass_id = 0; }

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if($user->user_exists == 0 & $setting[setting_permission_masterclass] == 0) {
  $page = "error";
  $smarty->assign('error_header', 11153528);
  $smarty->assign('error_message', 11153530);
  $smarty->assign('error_submit', 11153539);
  include "footer.php";
}


// INITIALIZE masterclass OBJECT
$rc_masterclass = new rc_masterclass($user->user_info[user_id], $masterclass_id);

//rc_toolkit::debug($rc_masterclass);

if($rc_masterclass->masterclass_exists == 0) {
  $page = "error";
  $smarty->assign('error_header', 11153528);
  $smarty->assign('error_message', 11153541);
  $smarty->assign('error_submit', 11153539);
  include "footer.php";
}
elseif ($rc_masterclass->masterclass_info[masterclass_approved] == 0) {
  $page = "error";
  $smarty->assign('error_header', 11153528);
  $smarty->assign('error_message', 11153502);
  $smarty->assign('error_submit', 11153539);
  include "footer.php";
}
elseif ($rc_masterclass->masterclass_info[masterclass_draft] == 1) {
  $page = "error";
  $smarty->assign('error_header', 11153528);
  $smarty->assign('error_message', 11153503);
  $smarty->assign('error_submit', 11153539);
  include "footer.php";
}

$rc_masterclass->masterclass_owner();
$owner = $rc_masterclass->masterclass_owner;

// CHECK PRIVACY
$privacy_max = $owner->user_privacy_max($user);

//rc_toolkit::debug($privacy_max,'$privacy_max');
//rc_toolkit::debug($rc_masterclass->masterclass_info[masterclass_privacy],'$rc_masterclass->masterclass_info[masterclass_privacy]');
//rc_toolkit::debug(!($rc_masterclass->masterclass_info[masterclass_privacy] & $privacy_max),'$($rc_masterclass->masterclass_info[masterclass_privacy] & $privacy_max)');

if(!($rc_masterclass->masterclass_info[masterclass_privacy] & $privacy_max)) {
  $page = "error";
  $smarty->assign('error_header', 11153528);
  $smarty->assign('error_message', 11153501);
  $smarty->assign('error_submit', 11153539);
  include "footer.php";
}
  
  
// UPDATE masterclass VIEWS IF masterclass VISIBLE
$masterclass_views = $rc_masterclass->masterclass_info[masterclass_views]+1;
$database->database_query("UPDATE se_masterclasss SET masterclass_views='$masterclass_views' WHERE masterclass_id='".$rc_masterclass->masterclass_info[masterclass_id]."'");


// GET masterclass LEADER INFO
$masterclassowner_info = $database->database_fetch_assoc($database->database_query("SELECT user_id, user_username FROM se_users WHERE user_id='".$rc_masterclass->masterclass_info[masterclass_user_id]."'"));

// GET masterclass CATEGORY
$masterclass_category = "";
$parent_category = "";
$masterclass_category_query = $database->database_query("SELECT masterclasscat_id, masterclasscat_title, masterclasscat_dependency FROM se_masterclasscats WHERE masterclasscat_id='".$rc_masterclass->masterclass_info[masterclass_masterclasscat_id]."' LIMIT 1");
if($database->database_num_rows($masterclass_category_query) == 1) {
  $masterclass_category_info = $database->database_fetch_assoc($masterclass_category_query);
  $masterclass_category = $masterclass_category_info[masterclasscat_title];
  if($masterclass_category_info[masterclasscat_dependency] != 0) {
    $parent_category = $database->database_fetch_assoc($database->database_query("SELECT masterclasscat_id, masterclasscat_title FROM se_masterclasscats WHERE masterclasscat_id='".$masterclass_category_info[masterclasscat_dependency]."' LIMIT 1"));
  }
}


// GET masterclass COMMENTS
$comment = new se_comment('masterclass', 'masterclass_id', $rc_masterclass->masterclass_info[masterclass_id]);
$total_comments = $comment->comment_total();
$comments = $comment->comment_list(0, 10);



// CHECK IF USER IS ALLOWED TO COMMENT
$allowed_to_comment = 1;
if(!($privacy_max & $rc_masterclass->masterclass_info[masterclass_comments])) { $allowed_to_comment = 0; }

// SHOW FILES IN THIS ALBUM
$masterclassalbum_info = $database->database_fetch_assoc($database->database_query("SELECT masterclassalbum_id FROM se_masterclassalbums WHERE masterclassalbum_masterclass_id='".$rc_masterclass->masterclass_info[masterclass_id]."' LIMIT 1"));
$total_files = $rc_masterclass->masterclass_media_total($masterclassalbum_info[masterclassalbum_id]);
$file_array = $rc_masterclass->masterclass_media_list(0, 5, "RAND()", "(masterclassmedia_masterclassalbum_id='$masterclassalbum_info[masterclassalbum_id]')");

$rc_masterclass->masterclass_info[masterclass_body] = str_replace("\r\n", "", html_entity_decode($rc_masterclass->masterclass_info[masterclass_body]));

$rc_tag = new rc_masterclasstag();
$masterclass_tags = $rc_tag->get_object_tags($masterclass_id);

// ASSIGN VARIABLES AND DISPLAY masterclass PAGE
$smarty->assign('masterclass', $rc_masterclass);
$smarty->assign('masterclassowner_info', $masterclassowner_info);
$smarty->assign('masterclass_category', $masterclass_category);
$smarty->assign('parent_category', $parent_category);
$smarty->assign('comments', $comments);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('masterclass_tags', $masterclass_tags);
$smarty->assign('is_masterclass_private', $is_masterclass_private);
$smarty->assign('allowed_to_comment', $allowed_to_comment);
$smarty->assign('files', $file_array);
$smarty->assign('total_files', $total_files);
include "footer.php";
?>