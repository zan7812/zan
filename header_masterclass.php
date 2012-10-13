<?php
// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT
if(!defined('SE_PAGE')) {
    exit();
}

//include_once "./lang/lang_".$global_lang."_masterclass.php";
include_once "./include/class_radcodes.php";
include_once "./include/class_masterclass.php";
include_once "./include/functions_masterclass.php";

SE_Language::_preload_multi(11153101, 11153102);


// SET MAIN MENU VARS
if($user->user_exists != 0 || $setting[setting_permission_masterclass] != 0) {
    $plugin_vars[menu_main] = Array('file' => 'masterclasss.php', 'title' => 11153101);
}
// SET USER MENU VARS
if($user->level_info[level_masterclass_allow] == 1) {
    $plugin_vars[menu_user] = Array('file' => 'user_masterclass_settings.php', 'icon' => 'masterclass16.gif', 'title' => 11153102);
}

// SET PROFILE MENU VARS
if($owner->level_info[level_masterclass_allow] == 1 && $page == "profile") {

    $rc_masterclass = new rc_masterclass($owner->user_info[user_id]);
    $masterclass_entries_per_page = 5;
    $sort = "masterclass_date_start DESC";

    // GET PRIVACY LEVEL AND SET WHERE
    $masterclass_privacy_max = $owner->user_privacy_max($user);
    $where = "(masterclass_privacy & $masterclass_privacy_max) AND masterclass_approved = '1' AND masterclass_draft = '0'";

    // GET TOTAL ENTRIES
    $total_masterclass_entries = $rc_masterclass->masterclass_total($where);

    // GET ENTRY ARRAY
    $masterclass_entries = $rc_masterclass->masterclass_list(0, $masterclass_entries_per_page, $sort, $where, 1);

    $smarty->assign('masterclass_entries', $masterclass_entries);
    $smarty->assign('total_masterclass_entries', $total_masterclass_entries);

    // SET PROFILE MENU VARS
    if(count($masterclass_entries) > 0) {

        // DETERMINE WHERE TO SHOW ALBUMS
        $level_masterclass_profile = explode(",", $owner->level_info[level_masterclass_profile]);
        if(!in_array($owner->user_info[user_profile_masterclass], $level_masterclass_profile)) {
            $user_profile_masterclass = $level_masterclass_profile[0];
        } else {
            $user_profile_masterclass = $owner->user_info[user_profile_masterclass];
        }

        $user_profile_masterclass = "side";

        // SHOW ALBUM IN APPROPRIATE LOCATION
        if($user_profile_masterclass == "tab") {
            $plugin_vars[menu_profile_tab] = Array('file'=> 'profile_masterclass_tab.tpl', 'title' => 11153101);
        } else {
            $plugin_vars[menu_profile_side] = Array('file'=> 'profile_masterclass_side.tpl', 'title' => 11153101);
        }
    }
}
    $result = $database->database_query("SELECT competition_id, competition_title FROM se_competitions WHERE competition_status = 0 AND competition_category = 3");
while($rez = $database->database_fetch_assoc($result)) {
    $masterclass_competitions[] = $rez;
//print_r($rez);
}
if(count($masterclass_competitions)> 0) $smarty->assign('masterclasscompetitions', $masterclass_competitions);

