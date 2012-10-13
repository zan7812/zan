<?php

switch($page) {

  // CODE FOR PROFILE PAGE
  case "profile":
	$entries = Array();
	$total_entries = 0;
	if($owner->level_info[level_masterclass_allow] != 0) {
    $current_time = time();
	  // START masterclass
	  $masterclass = new rc_masterclass($owner->user_info[user_id]);
	  $entries_per_page = 5;
	  $sort = "masterclass_date_start DESC";

	  // GET PRIVACY LEVEL AND SET WHERE
	  $privacy_level = $owner->user_privacy_max($user, $owner->level_info[level_masterclass_privacy]);
	  $where = "(masterclass_privacy<='$privacy_level') AND masterclass_approved = '1' AND masterclass_draft = '0'";

	  // GET TOTAL ENTRIES
	  $total_entries = $masterclass->masterclass_total($where);

	  // GET ENTRY ARRAY
	  $entries = $masterclass->masterclass_list(0, $entries_per_page, $sort, $where, 1);

	}

	// ASSIGN ENTRIES SMARY VARIABLE
	$smarty->assign('masterclass_entries', $entries);
	$smarty->assign('total_masterclass_entries', $total_entries);
    break;

}
