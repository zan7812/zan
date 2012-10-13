<?
$page = "masterclass_comments";
include "header.php";

if(isset($_POST['task'])) { $task = $_POST['task']; } elseif(isset($_GET['task'])) { $task = $_GET['task']; } else { $task = "main"; }
if(isset($_POST['p'])) { $p = $_POST['p']; } elseif(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }
if(isset($_POST['masterclass_id'])) { $masterclass_id = $_POST['masterclass_id']; } elseif(isset($_GET['masterclass_id'])) { $masterclass_id = $_GET['masterclass_id']; } else { $masterclass_id = 0; }

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if($user->user_exists == 0 & $setting[setting_permission_masterclass] == 0) {
  $page = "error";
  $smarty->assign('error_header', 11154020);
  $smarty->assign('error_message', 11154022);
  $smarty->assign('error_submit', 11154023);
  include "footer.php";
}


// INITIALIZE masterclass OBJECT
$masterclass = new rc_masterclass($user->user_info[user_id], $masterclass_id);
if($masterclass->masterclass_exists == 0) { header("Location: home.php"); exit(); }

if(!$masterclass->is_masterclass_active()) {
  header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]); exit();
}


$masterclass->masterclass_owner();
$owner = $masterclass->masterclass_owner;

// CHECK PRIVACY
$privacy_max = $owner->user_privacy_max($user);
if(!($masterclass->masterclass_info[masterclass_privacy] & $privacy_max)) {
  header("Location: masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]); exit();
}

// SET VARS
$is_error = 0;
$refresh = 0;
$allowed_to_comment = 1;


// CHECK IF USER IS ALLOWED TO COMMENT
$allowed_to_comment = 1;
if(!($privacy_max & $masterclass->masterclass_info[masterclass_comments])) { $allowed_to_comment = 0; }


// IF A COMMENT IS BEING POSTED
if($task == "dopost" & $allowed_to_comment != 0) {
  $comment_date = time();
  $comment_body = $_POST['comment_body'];

  // RETRIEVE AND CHECK SECURITY CODE IF NECESSARY
  if($setting[setting_comment_code] != 0) {
    session_start();
    $code = $_SESSION['code'];
    if($code == "") { $code = randomcode(); }
    $comment_secure = $_POST['comment_secure'];

    if($comment_secure != $code) { $is_error = 1; }
  }

  // MAKE SURE COMMENT BODY IS NOT EMPTY
  $comment_body = censor(str_replace("\r\n", "<br>", $comment_body));
  $comment_body = preg_replace('/(<br>){3,}/is', '<br><br>', $comment_body);
  $comment_body = ChopText($comment_body);
  if(str_replace(" ", "", $comment_body) == "") { $is_error = 1; $comment_body = ""; }

  // ADD COMMENT IF NO ERROR
  if($is_error == 0) {
    $database->database_query("INSERT INTO se_masterclasscomments (masterclasscomment_masterclass_id, masterclasscomment_authoruser_id, masterclasscomment_date, masterclasscomment_body) VALUES ('".$masterclass->masterclass_info[masterclass_id]."', '".$user->user_info[user_id]."', '$comment_date', '$comment_body')");

    // INSERT ACTION IF USER EXISTS
    if($user->user_exists != 0) {
      $commenter = $user->user_info[user_username];
      $comment_body_encoded = $comment_body;
      if(strlen($comment_body_encoded) > 250) { 
        $comment_body_encoded = substr($comment_body_encoded, 0, 240);
        $comment_body_encoded .= "...";
      }
      $comment_body_encoded = htmlspecialchars(str_replace("<br>", " ", $comment_body_encoded));
      $actions->actions_add($user, "masterclasscomment", Array($user->user_info[user_username], $user->user_displayname, $masterclass_id, $masterclass->masterclass_info[masterclass_title], $comment_body_encoded), Array(), 0, FALSE, "user", $user->user_info[user_id], $masterclass->masterclass_info[masterclass_privacy]);
      
    } else { 
      $commenter = 11154012;
    }

    // GET masterclass CREATOR INFO AND SEND NOTIFICATION IF COMMENTER IS NOT LEADER
    $masterclassowner_info = $database->database_fetch_assoc($database->database_query("SELECT se_users.user_id, se_users.user_username, se_users.user_email, se_usersettings.usersetting_notify_masterclasscomment FROM se_users LEFT JOIN se_usersettings ON se_users.user_id=se_usersettings.usersetting_user_id WHERE se_users.user_id='".$masterclass->masterclass_info[masterclass_user_id]."'"));
    if($masterclassowner_info[usersetting_notify_masterclasscomment] == 1 & $masterclassowner_info[user_id] != $user->user_info[user_id]) {
      send_generic($masterclassowner_info[user_email], "$setting[setting_email_fromname] <$setting[setting_email_fromemail]>", $setting[setting_email_masterclasscomment_subject], $setting[setting_email_masterclasscomment_message], Array('[username]', '[commenter]', '[masterclassname]', '[link]'), Array($masterclassowner_info[user_username], $commenter, $masterclass->masterclass_info[masterclass_title], "<a href=\"".$url->url_base."masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."\">".$url->url_base."masterclass.php?masterclass_id=".$masterclass->masterclass_info[masterclass_id]."</a>"));
    }
  }

  echo "<html><head><script type=\"text/javascript\">";
  echo "window.parent.addComment('$is_error', '$comment_body', '$comment_date');";
  echo "</script></head><body></body></html>";
  exit();
}



// START COMMENT OBJECT
$comment = new se_comment('masterclass', 'masterclass_id', $masterclass->masterclass_info[masterclass_id]);

// GET TOTAL COMMENTS
$total_comments = $comment->comment_total();

// MAKE COMMENT PAGES
$comments_per_page = 10;
$page_vars = make_page($total_comments, $comments_per_page, $p);

// GET masterclass COMMENTS
$comments = $comment->comment_list($page_vars[0], $comments_per_page);


// GET CUSTOM masterclass STYLE IF ALLOWED
if($masterclass->masterclassowner_level_info[level_masterclass_style] != 0) {
  $masterclassstyle_info = $database->database_fetch_assoc($database->database_query("SELECT masterclassstyle_css FROM se_masterclassstyles WHERE masterclassstyle_masterclass_id='".$masterclass->masterclass_info[masterclass_id]."' LIMIT 1"));
  $global_css = $masterclassstyle_info[masterclassstyle_css];
}


// ASSIGN VARIABLES AND INCLUDE FOOTER
$smarty->assign('masterclass', $masterclass);
$smarty->assign('comments', $comments);
$smarty->assign('allowed_to_comment', $allowed_to_comment);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('total_comments', $total_comments);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($comments));
include "footer.php";
?>