<?php

/* $Id: search_advanced.php 42 2009-01-29 04:55:14Z john $ */

$page = "search_advanced";
include "header.php";

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if($user->user_exists == 0 && $setting['setting_permission_search'] == 0) {
    $page = "error";
    $smarty->assign('error_header', 639);
    $smarty->assign('error_message', 656);
    $smarty->assign('error_submit', 641);
    include "footer.php";
}

if(isset($_POST['task'])) {
    $task = $_POST['task'];
} elseif(isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = "main";
}
if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 1;
}
if(isset($_POST['sort'])) {
    $sort = $_POST['sort'];
} elseif(isset($_GET['sort'])) {
    $sort = $_GET['sort'];
} else {
    $sort = 'rating';
}


// SET VARS
$showfields = 1;
$linked_field_title = "";
$linked_field_value = "";
//$sort = $sort;
$users_per_page = 24;
//print $sort;

// BROWSE USERS WITH A VALUE IN A SPECIFIC FIELD
// LINKED FROM PROFILE
if($task == "browse") {
    // GET BASIC VARIABLES
    $field_id = $_GET['field_id'];
    $field_value = $_GET['field_value'];
    $linked_field_value = $field_value;
    $url_string = "field_id=".$field_id."&field_value=".urlencode($field_value)."&";
    $showfields = 0;

    // BEGIN CONSTRUCTING BROWSE QUERY
    $browse_query = "SELECT se_users.user_id, se_users.user_username, se_users.user_fname, se_users.user_lname, se_users.user_photo FROM se_profilevalues LEFT JOIN se_users ON se_profilevalues.profilevalue_user_id=se_users.user_id LEFT JOIN se_levels ON se_levels.level_id=se_users.user_level_id WHERE se_users.user_verified='1' AND se_users.user_enabled='1' AND (se_users.user_search='1' OR se_levels.level_profile_search='0')";

    // GET FIELD INFO
    $field_info = $database->database_fetch_assoc($database->database_query("SELECT profilefield_id AS field_id, profilefield_title AS field_title, profilefield_type AS field_type, profilefield_options AS field_options, profilefield_dependency AS field_dependency FROM se_profilefields WHERE profilefield_id='$field_id'"));

    // GET PARENT FIELD INFO
    $parent_field_title = 0;
    $parent_field_option = 0;
    if($field_info['field_dependency'] != 0) {
        $parent_field_info = $database->database_fetch_assoc($database->database_query("SELECT profilefield_title AS field_title, profilefield_type AS field_type, profilefield_options AS field_options FROM se_profilefields WHERE profilefield_id='{$field_info['field_dependency']}'"));
        $parent_field_title = $parent_field_info['field_title'];
        if($parent_field_info['field_type'] == 3 || $parent_field_info['field_type'] == 4 || $parent_field_info['field_type'] == 6) {
            $options = unserialize($parent_field_info['field_options']);
            for($i=0,$max=count($options);$i<$max;$i++) {
                if($field_info['field_id'] == $options[$i]['dependent_id']) {
                    $parent_field_option = $options[$i]['label'];
                }
            }
        }
    }

    SE_Language::_preload_multi($field_info['field_title'], $parent_field_title, $parent_field_option);
    SE_Language::load();
    if(SE_Language::_get($parent_field_title) != "") {
        $linked_field_title = SE_Language::_get($parent_field_title).": ";
    }
    if(SE_Language::_get($parent_field_option) != "") {
        $linked_field_title .= SE_Language::_get($parent_field_option);
    }
    if($linked_field_title != "") {
        $linked_field_title .= " ";
    }
    $linked_field_title .= SE_Language::_get($field_info['field_title']);

    // GET FIELD VALUE
    switch($field_info['field_type']) {
        case 1:
        case 2:
            $browse_query .= " AND profilevalue_{$field_info['field_id']} LIKE '%{$field_value}%'";
            break;
        case 3:
        case 4:
            $browse_query .= " AND profilevalue_{$field_info['field_id']}='{$field_value}'";
            $options = unserialize($field_info['field_options']);
            for($i=0,$max=count($options);$i<$max;$i++) {
                if($field_value == $options[$i]['value']) {
                    SE_Language::_preload($options[$i]['label']);
                    SE_Language::load();
                    $linked_field_value = SE_Language::_get($options[$i]['label']);
                }
            }
            break;
        case 5:
            $browse_query .= " AND (MONTH(profilevalue_{$field_info['field_id']})=MONTH('{$field_value}') OR MONTH('{$field_value}')=0) AND (DAY(profilevalue_{$field_info['field_id']})=DAY('{$field_value}') OR DAY('{$field_value}')=0) AND (YEAR(profilevalue_{$field_info['field_id']})=YEAR('{$field_value}') OR YEAR('{$field_value}')=0)";
            // SET MONTH, DAY, AND YEAR FORMAT FROM SETTINGS
            switch($setting['setting_dateformat']) {
                case "n/j/Y": case "n.j.Y": case "n-j-Y": case "M. j, Y": case "F j, Y": case "l, F j, Y": $date_order = "mdy";
                    break;
                case "Y/n/j": case "Ynj": case "Y-n-d": case "Y-m-d": case "Y-M-j": $date_order = "ymd";
                    break;
                case "j/n/Y": case "j.n.Y": case "j F Y": case "D j F Y": case "l j F Y": case "D-j-M-Y": case "D j M Y": case "j-M-Y": $date_order = "dmy";
                    break;
            }

            $year = substr($field_value, 0, 4);
            $month = substr($field_value, 5, 2);
            $day = substr($field_value, 8, 2);
            if($field_value != "0000-00-00") {
                if($year == "0000") {
                    $year = "";
                }
                if($month == "00") {
                    $month = "";
                } else {
                    $month = $datetime->cdate("F", mktime(0, 0, 0, $month, 1, 1990));
                }
                if($day == "00") {
                    $day = "";
                } else {
                    $day = $datetime->cdate("j", mktime(0, 0, 0, 1, $day, 1990));
                }
                switch($date_order) {
                    case "mdy": $linked_field_value = "$month $day $year";
                        break;
                    case "ymd": $linked_field_value = "$year $month $day";
                        break;
                    case "dmy": $linked_field_value = "$day $month $year";
                        break;
                }
            }
            break;
        case 6:
            $browse_query .= " AND FIND_IN_SET('{$field_value}', profilevalue_{$field_info['field_id']})";
            $options = unserialize($field_info['field_options']);
            for($i=0,$max=count($options);$i<$max;$i++) {
                if($field_value == $options[$i]['value']) {
                    SE_Language::_preload($options[$i]['label']);
                    SE_Language::load();
                    $linked_field_value = SE_Language::_get($options[$i]['label']);
                }
            }
            break;
    }

    // GET TOTAL USERS
    $total_users = $database->database_num_rows($database->database_query($browse_query));

    // MAKE BROWSE PAGES
    $page_vars = make_page($total_users, $users_per_page, $p);

    // ADD LIMIT TO QUERY
    $browse_query .= " ORDER BY user_$sort DESC LIMIT $page_vars[0], $users_per_page";

    // GET USERS
    $online_users_array = online_users();
    $users = $database->database_query($browse_query);
    while($user_info = $database->database_fetch_assoc($users)) {
        $browse_user = new se_user();
        $browse_user->user_info['user_id'] = $user_info['user_id'];
        $browse_user->user_info['user_username'] = $user_info['user_username'];
        $browse_user->user_info['user_fname'] = $user_info['user_fname'];
        $browse_user->user_info['user_lname'] = $user_info['user_lname'];
        $browse_user->user_info['user_photo'] = $user_info['user_photo'];
        $browse_user->user_displayname();

        // DETERMINE IF USER IS ONLINE
        if(in_array($browse_user->user_info['user_username'], $online_users_array[2])) {
            $browse_user->is_online = 1;
        } else {
            $browse_user->is_online = 0;
        }

        $user_array[] = $browse_user;
    }


    // SET GLOBAL PAGE TITLE/DESCRIPTION
    $global_page_title[0] = 1083;
    $global_page_title[1] = "$linked_field_title: $linked_field_value";
    $global_page_description[0] = 1084;
    $global_page_description[1] = $total_users;
    $global_page_description[2] = "$linked_field_title: $linked_field_value";
}


// SEARCH THROUGH USERS BASED ON NUMEROUS PROFILE CRITERIA
else {
    // START FIELD OBJECT
    $field = new se_field("profile");

    // GET CATS TO DISPLAY ACROSS TOP
    $field->cat_list(0, 0, 0, "(SELECT TRUE FROM se_profilecats AS t2 LEFT JOIN se_profilefields ON t2.profilecat_id=se_profilefields.profilefield_profilecat_id WHERE t2.profilecat_dependency=se_profilecats.profilecat_id AND profilefield_search<>0 LIMIT 1)", "profilecat_id=0");
    $cat_menu_array = $field->cats;

    if(isset($_POST['cat_selected'])) {
        $cat_selected = $_POST['cat_selected'];
    } elseif(isset($_GET['cat_selected'])) {
        $cat_selected = $_GET['cat_selected'];
    } else {
        $cat_selected = $cat_menu_array[0]['cat_id'];
    }

    // GET LIST OF FIELDS
    $field->cat_list(0, 0, 1, "profilecat_id='{$cat_selected}'", "", "profilefield_search<>'0'");
    $cat_array = $field->cats;
    $url_string = $field->url_string;

    // PERFORM SEARCH
    if(isset($_POST['sort'])) {
        $sort = $_POST['sort'];
    } elseif(isset($_GET['sort'])) {
        $sort = $_GET['sort'];
    } else {
        $sort = 'rating';
    }
    if(isset($_POST['user_online'])) {
        $user_online = $_POST['user_online'];
    } elseif(isset($_GET['user_online'])) {
        $user_online = $_GET['user_online'];
    } else {
        $user_online = 0;
    }
    if(isset($_POST['user_withphoto'])) {
        $user_withphoto = $_POST['user_withphoto'];
    } elseif(isset($_GET['user_withphoto'])) {
        $user_withphoto = $_GET['user_withphoto'];
    } else {
        $user_withphoto = 0;
    }

    $user_types = $database->database_fetch_assoc($database->database_query("SELECT profilefield_options FROM se_profilefields WHERE profilefield_id = 20"));
    $user_types = unserialize($user_types['profilefield_options']);

    //    $countries = $database->database_fetch_assoc($database->database_query("SELECT profilefield_options FROM se_profilefields WHERE profilefield_id = 7"));
    $cities = $database->database_fetch_assoc($database->database_query("SELECT profilefield_options FROM se_profilefields WHERE profilefield_id = 8"));
//    $countries = unserialize($countries['profilefield_options']);
    $cities = unserialize($cities['profilefield_options']);
    //most popular users
    $users = $database->database_query("SELECT se_users.user_id, se_users.user_username, se_users.user_fname, se_users.user_lname, se_users.user_photo, se_users.user_rating,
                    se_users.user_signupdate, se_profilevalues.profilevalue_7, se_profilevalues.profilevalue_8, se_profilevalues.profilevalue_20 FROM se_profilevalues
                    LEFT JOIN se_users ON se_profilevalues.profilevalue_user_id=se_users.user_id
                    LEFT JOIN se_profileviews ON se_profileviews.profileview_user_id = se_users.user_id
                    LEFT JOIN se_levels ON se_levels.level_id=se_users.user_level_id
                    WHERE se_users.user_verified='1' AND se_users.user_enabled='1' AND
                    (se_users.user_search='1' OR se_levels.level_profile_search='0') ORDER BY se_profileviews.profileview_views DESC LIMIT 5");
    while($user_info = $database->database_fetch_assoc($users)) {
        $popular_user = new se_user();
        $popular_user->user_info['user_id'] = $user_info['user_id'];
        $popular_user->user_info['user_username'] = $user_info['user_username'];
        $popular_user->user_info['user_fname'] = $user_info['user_fname'];
        $popular_user->user_info['user_lname'] = $user_info['user_lname'];
        $popular_user->user_info['user_photo'] = $user_info['user_photo'];
        $popular_user->user_info['user_rating'] = $user_info['user_rating'];
        $popular_user->user_info['profilevalue_8'] = $user_info['profilevalue_8'];
        $popular_user->user_info['profilevalue_20'] = $user_info['profilevalue_20'];
        $popular_user->user_info['user_signupdate'] = date('d.m.Y',$user_info['user_signupdate']);
        foreach($cities as $key => $item) {
            if($popular_user->user_info['profilevalue_8'] == $item['value']) {
                $popular_user->user_info['profilevalue_8'] = $item['label'];
                break;
            }
        }
        foreach ($user_types as $user_type) {
            if($popular_user->user_info['profilevalue_20'] == $user_type['value']) {
                $popular_user->user_info['profilevalue_20'] = $user_type['label'];
            }
        }
        $popular_user->user_displayname();
        $popular_user_array[] = $popular_user;
    }
//    print_r($popular_user_array);
    // BEGIN CONSTRUCTING SEARCH QUERY
//    print_r($cities);
    $search_query = "SELECT se_users.user_id, se_users.user_username, se_users.user_fname, se_users.user_lname, se_users.user_photo, se_users.user_rating,
                    se_users.user_signupdate, se_profilevalues.profilevalue_7, se_profilevalues.profilevalue_8, se_profilevalues.profilevalue_20,
                    se_users.negative_feedback, se_users.positive_feedback, se_users.neutral_feedback FROM se_profilevalues
                    LEFT JOIN se_users ON se_profilevalues.profilevalue_user_id=se_users.user_id
                    LEFT JOIN se_levels ON se_levels.level_id=se_users.user_level_id
                    WHERE se_users.user_profilecat_id='{$cat_selected}' AND se_users.user_verified='1' AND se_users.user_enabled='1' AND
                    (se_users.user_search='1' OR se_levels.level_profile_search='0')";
    if($user_online == 1) {
        $search_query .= " AND user_lastactive>'".(time()-10*60)."' AND user_invisible=0";
    }
    if($user_withphoto == 1) {
        $search_query .= " AND user_photo <> ''";
    }
    if($field->field_query != "") {
        $search_query .= " AND ".$field->field_query;
    }

    // GET TOTAL USERS
    $total_users = $database->database_num_rows($database->database_query($search_query));

    // MAKE SEARCH PAGES
    $page_vars = make_page($total_users, $users_per_page, $p);

    // ADD LIMIT TO QUERY
    $search_query .= " ORDER BY user_$sort DESC LIMIT $page_vars[0], $users_per_page";

    // GET USERS
    $online_users_array = online_users();
    $users = $database->database_query($search_query);
    while($user_info = $database->database_fetch_assoc($users)) {
        $search_user = new se_user();
        $search_user->user_info['user_id'] = $user_info['user_id'];
        $search_user->user_info['user_username'] = $user_info['user_username'];
        $search_user->user_info['user_fname'] = $user_info['user_fname'];
        $search_user->user_info['user_lname'] = $user_info['user_lname'];
        $search_user->user_info['user_photo'] = $user_info['user_photo'];
        $search_user->user_info['user_rating'] = $user_info['user_rating'];
        $search_user->user_info['negative_feedback'] = $user_info['negative_feedback'];
        $search_user->user_info['positive_feedback'] = $user_info['positive_feedback'];
        $search_user->user_info['neutral_feedback'] = $user_info['neutral_feedback'];
        $search_user->user_info['profilevalue_8'] = $user_info['profilevalue_8'];
        $search_user->user_info['profilevalue_20'] = $user_info['profilevalue_20'];
        $search_user->user_info['profilevalue_20_id'] = $user_info['profilevalue_20'];
        $search_user->user_info['user_signupdate'] = date('d.m.Y',$user_info['user_signupdate']);
        foreach($cities as $key => $item) {
            if($search_user->user_info['profilevalue_8'] == $item['value']) {
                $search_user->user_info['profilevalue_8'] = $item['label'];
                break;
            }
        }
        foreach ($user_types as $user_type) {
            if($search_user->user_info['profilevalue_20'] == $user_type['value']) {
                $search_user->user_info['profilevalue_20'] = $user_type['label'];
            }
        }

        $search_user->user_displayname();

        // DETERMINE IF USER IS ONLINE
        if(in_array($search_user->user_info['user_username'], $online_users_array[2])) {
            $search_user->is_online = 1;
        } else {
            $search_user->is_online = 0;
        }

        $user_array[] = $search_user;
    }

    // SET GLOBAL PAGE TITLE
    $global_page_title[0] = 926;
    $global_page_description[0] = 1088;
}



//print_r($user_array);
// ASSIGN VARIABLES AND INCLUDE FOOTER
$smarty->assign('users', $user_array);
$smarty->assign('popular_user', $popular_user_array);
//$smarty->assign('users', $user_array);
//$smarty->assign('countries', $countries);
$smarty->assign('total_users', $total_users);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($user_array));
$smarty->assign('showfields', $showfields);
$smarty->assign('url_string', $url_string);
$smarty->assign('linked_field_title', $linked_field_title);
$smarty->assign('linked_field_value', $linked_field_value);
$smarty->assign('cats_menu', $cat_menu_array);
$smarty->assign('cat_selected', $cat_selected);
$smarty->assign('cats', $cat_array);
$smarty->assign('sort', $sort);
$smarty->assign('task', $task);
$smarty->assign('user_online', $user_online);
$smarty->assign('user_withphoto', $user_withphoto);
include "footer.php";
?>