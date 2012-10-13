<?php

/* $Id: classifieds.php 16 2009-01-13 04:01:31Z john $ */

$page = "classifieds";
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

// DISPLAY ERROR PAGE IF NO OWNER
if( !$owner->user_exists )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 828);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}

// ENSURE classifiedS ARE ENABLED FOR THIS USER
if( !$owner->level_info['level_classified_allow'] )
{
  header("Location: ".$url->url_create('profile', $owner->user_info['user_username']));
  exit();
}


// INIT VARS
if(isset($_POST['p'])) { $p = $_POST['p']; } elseif(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }


// SET PRIVACY LEVEL AND WHERE CLAUSE
$privacy_max = $owner->user_privacy_max($user);
$where = "(classified_privacy & $privacy_max)";


// CREATE classified OBJECT
$entries_per_page = (int)$owner->level_info['level_classified_entries'];
if($entries_per_page <= 0) { $entries_per_page = 10; }
$classified = new se_classified($owner->user_info[user_id]);


// GET TOTAL ENTRIES, MAKE ENTRY PAGES, GET ENTRY ARRAY
$total_classifieds = $classified->classified_total($where);
$page_vars = make_page($total_classifieds, $entries_per_page, $p);
$classifieds = $classified->classified_list($page_vars[0], $entries_per_page, "classified_date DESC", $where);


// SET SEO STUFF
$global_page_title = array(4500143, $owner->user_displayname);
$global_page_description = array(4500143, $owner->user_displayname);

//print_r($classifieds);
// ASSIGN VARIABLES AND DISPLAY classified PAGE
$smarty->assign('classifieds', $classifieds);
$smarty->assign('total_classifieds', $total_classifieds);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($classifieds));
include "footer.php";
?>