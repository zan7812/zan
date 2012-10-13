<?
$page = "masterclass_album";
include "header.php";

if(isset($_POST['p'])) { $p = $_POST['p']; } elseif(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }
if(isset($_GET['masterclass_id'])) { $masterclass_id = $_GET['masterclass_id']; } else { $masterclass_id = 0; }

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if($user->user_exists == 0 & $setting[setting_permission_masterclass] == 0) {
  $smarty->assign('error_header', 11153803);
  $smarty->assign('error_message', 11153804);
  $smarty->assign('error_submit', 11153810);
  $smarty->display("error.tpl");
  exit();
}

// INITIALIZE masterclass OBJECT
$masterclass = new rc_masterclass($user->user_info[user_id], $masterclass_id);
if($masterclass->masterclass_exists == 0) { header("Location: home.php"); exit(); }


if(!$masterclass->is_masterclass_active()) {
  header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]); exit();
}

// GET masterclass ALBUM INFO
$masterclassalbum_info = $database->database_fetch_assoc($database->database_query("SELECT * FROM se_masterclassalbums WHERE masterclassalbum_masterclass_id='".$masterclass->masterclass_info[masterclass_id]."' LIMIT 1"));


// GET PRIVACY LEVEL
$masterclass->masterclass_owner();
$owner = $masterclass->masterclass_owner;

// CHECK PRIVACY
$privacy_max = $owner->user_privacy_max($user);
if(!($masterclass->masterclass_info[masterclass_privacy] & $privacy_max)) {
  header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]); exit();
}


// UPDATE ALBUM VIEWS
$masterclassalbum_views_new = $masterclassalbum_info[masterclassalbum_views] + 1;
$database->database_query("UPDATE se_masterclassalbums SET masterclassalbum_views='$masterclassalbum_views_new' WHERE masterclassalbum_id='$masterclassalbum_info[masterclassalbum_id]' LIMIT 1");



// GET TOTAL FILES IN masterclass ALBUM
$total_files = $masterclass->masterclass_media_total($masterclassalbum_info[masterclassalbum_id]);

// MAKE MEDIA PAGES
$files_per_page = 16;
$page_vars = make_page($total_files, $files_per_page, $p);

// GET MEDIA ARRAY
$file_array = $masterclass->masterclass_media_list($page_vars[0], $files_per_page, "masterclassmedia_id ASC", "(masterclassmedia_masterclassalbum_id='$masterclassalbum_info[masterclassalbum_id]')");


// GET CUSTOM masterclass STYLE IF ALLOWED
if($masterclass->masterclassowner_level_info[level_masterclass_style] != 0 & $is_masterclass_private == 0) {
  $masterclassstyle_info = $database->database_fetch_assoc($database->database_query("SELECT masterclassstyle_css FROM se_masterclassstyles WHERE masterclassstyle_masterclass_id='".$masterclass->masterclass_info[masterclass_id]."' LIMIT 1"));
  $global_css = $masterclassstyle_info[masterclassstyle_css];
}




// ASSIGN VARIABLES AND DISPLAY masterclass ALBUM PAGE
$smarty->assign('masterclass', $masterclass);
$smarty->assign('files', $file_array);
$smarty->assign('total_files', $total_files);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($file_array));
include "footer.php";
?>