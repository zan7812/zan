<?php

/* $Id: browse_allbusiness.php 80 2009-03-06 02:46:35Z john $ */

$page = "browse_allbusiness";
include "header.php";

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if( (!$user->user_exists && !$setting['setting_permission_business']) || ($user->user_exists && (~(int)$user->level_info['level_business_allow'] & 1)) )
{
  $page = "error";
  $smarty->assign('error_header', 639);
  $smarty->assign('error_message', 656);
  $smarty->assign('error_submit', 641);
  include "footer.php";
}


// PARSE GET/POST
if(isset($_POST['p'])) { $p = $_POST['p']; } elseif(isset($_GET['p'])) { $p = $_GET['p']; } else { $p = 1; }
if(isset($_POST['s'])) { $s = $_POST['s']; } elseif(isset($_GET['s'])) { $s = $_GET['s']; } else { $s = "business_datecreated DESC"; }
if(isset($_POST['v'])) { $v = $_POST['v']; } elseif(isset($_GET['v'])) { $v = $_GET['v']; } else { $v = 0; }
if(isset($_POST['businesscat_id'])) { $businesscat_id = $_POST['businesscat_id']; } elseif(isset($_GET['businesscat_id'])) { $businesscat_id = $_GET['businesscat_id']; } else { $businesscat_id = 0; }

// ENSURE SORT/VIEW ARE VALID
if($s != "business_datecreated DESC" && $s != "business_totalmembers DESC") { $s = "business_datecreated DESC"; }
if($v != "0" && $v != "1") { $v = 0; }


// SET WHERE CLAUSE
$where = "CASE
	    WHEN se_allbusiness.business_user_id='{$user->user_info['user_id']}'
	      THEN TRUE
	    WHEN ((se_allbusiness.business_privacy & 32) AND '{$user->user_exists}'<>0)
	      THEN TRUE
	    WHEN ((se_allbusiness.business_privacy & 64) AND '{$user->user_exists}'=0)
	      THEN TRUE
	    WHEN ((se_allbusiness.business_privacy & 2) AND (SELECT TRUE FROM se_businessmembers WHERE businessmember_user_id='{$user->user_info['user_id']}' AND businessmember_business_id=se_allbusiness.business_id AND businessmember_status=1 LIMIT 1))
	      THEN TRUE
	    WHEN ((se_allbusiness.business_privacy & 4) AND '{$user->user_exists}'<>0 AND (SELECT TRUE FROM se_friends WHERE friend_user_id1=se_allbusiness.business_user_id AND friend_user_id2='{$user->user_info['user_id']}' AND friend_status=1 LIMIT 1))
	      THEN TRUE
	    WHEN ((se_allbusiness.business_privacy & 8) AND '{$user->user_exists}'<>0 AND (SELECT TRUE FROM se_businessmembers LEFT JOIN se_friends ON se_businessmembers.businessmember_user_id=se_friends.friend_user_id1 WHERE se_businessmembers.businessmember_business_id=se_allbusiness.business_id AND se_friends.friend_user_id2='{$user->user_info['user_id']}' AND se_businessmembers.businessmember_status=1 AND se_friends.friend_status=1 LIMIT 1))
	      THEN TRUE
	    WHEN ((se_allbusiness.business_privacy & 16) AND '{$user->user_exists}'<>0 AND (SELECT TRUE FROM se_businessmembers LEFT JOIN se_friends AS friends_primary ON se_businessmembers.businessmember_user_id=friends_primary.friend_user_id1 LEFT JOIN se_friends AS friends_secondary ON friends_primary.friend_user_id2=friends_secondary.friend_user_id1 WHERE se_businessmembers.businessmember_business_id=se_allbusiness.business_id AND se_businessmembers.businessmember_status=1 AND friends_secondary.friend_user_id2='{$user->user_info['user_id']}' AND friends_primary.friend_status=1 AND friends_secondary.friend_status=1 LIMIT 1))
	      THEN TRUE
	    ELSE FALSE
	END";


// ONLY MY FRIENDS' allbusiness
if($v == "1" && $user->user_exists)
{
  // SET WHERE CLAUSE
  $where .= " AND (SELECT TRUE FROM se_friends LEFT JOIN se_businessmembers ON se_friends.friend_user_id2=se_businessmembers.businessmember_user_id WHERE friend_user_id1='{$user->user_info['user_id']}' AND friend_status=1 AND businessmember_business_id=se_allbusiness.business_id AND businessmember_status=1 LIMIT 1)";
}


// SPECIFIC business CATEGORY
if( is_numeric($businesscat_id) )
{
  $businesscat_query = $database->database_query("SELECT businesscat_id, businesscat_title, businesscat_dependency FROM se_businesscats WHERE businesscat_id='{$businesscat_id}' LIMIT 1");
  if( $database->database_num_rows($businesscat_query) )
  {
    $businesscat = $database->database_fetch_assoc($businesscat_query);
    if( !$businesscat['businesscat_dependency'] )
    {
      $cat_ids[] = $businesscat['businesscat_id'];
      $depcats = $database->database_query("SELECT businesscat_id FROM se_businesscats WHERE businesscat_id='{$businesscat['businesscat_id']}' OR businesscat_dependency='{$businesscat['businesscat_id']}'");
      while($depcat_info = $database->database_fetch_assoc($depcats)) { $cat_ids[] = $depcat_info['businesscat_id']; }
      $where .= " AND se_allbusiness.business_businesscat_id IN('".implode("', '", $cat_ids)."')";
    }
    else
    {
      $where .= " AND se_allbusiness.business_businesscat_id='{$businesscat['businesscat_id']}'";
      $allbusinessubcat = $businesscat;
      $businesscat = $database->database_fetch_assoc($database->database_query("SELECT businesscat_id, businesscat_title FROM se_businesscats WHERE businesscat_id='{$businesscat['businesscat_dependency']}' LIMIT 1"));
    }
  }
}

// CREATE business OBJECT
$business = new se_business();

// GET TOTAL allbusiness
$total_allbusiness = $business->business_total($where);

// MAKE ENTRY PAGES
$allbusiness_per_page = 10;
$page_vars = make_page($total_allbusiness, $allbusiness_per_page, $p);

// GET business ARRAY
$business_array = $business->business_list($page_vars[0], $allbusiness_per_page, $s, $where, TRUE);

// GET CATS
$field = new se_field("business");
$field->cat_list(0, 0, 0, "", "", "businessfield_id=0");
$cat_array = $field->cats;

// SET GLOBAL PAGE TITLE
$global_page_title[0] = 2001324;
$global_page_description[0] = 2001325;

// ASSIGN SMARTY VARIABLES AND DISPLAY allbusiness PAGE
$smarty->assign('businesscat_id', $businesscat_id);
$smarty->assign('businesscat', $businesscat);
$smarty->assign('allbusinessubcat', $allbusinessubcat);
$smarty->assign('cats', $cat_array);
$smarty->assign('allbusiness', $business_array);
$smarty->assign('total_allbusiness', $total_allbusiness);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($business_array));
$smarty->assign('s', $s);
$smarty->assign('v', $v);
include "footer.php";
?>