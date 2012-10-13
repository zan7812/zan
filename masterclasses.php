<?
$page = "masterclasses";
include "header.php";

// DISPLAY ERROR PAGE IF USER IS NOT LOGGED IN AND ADMIN SETTING REQUIRES REGISTRATION
if($user->user_exists == 0 & $setting[setting_permission_masterclass] == 0) {
    $page = "error";
    $smarty->assign('error_header', 11153636);
    $smarty->assign('error_message', 11153637);
    $smarty->assign('error_submit', 11153638);
    include "footer.php";
}

if(isset($_POST['di-mcategory'])) {
    $di_mcategory = $_POST['di-mcategory'];
} elseif(isset($_GET['di-mcategory'])) {
    $di_mcategory = $_GET['di-mcategory'];
} else {
    $di_mcategory = "1";
}
if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 1;
}
if(isset($_POST['s'])) {
    $s = $_POST['s'];
} elseif(isset($_GET['s'])) {
    $s = $_GET['s'];
} else {
    $s = 'date';
}

$keyword = rc_toolkit::get_request('keyword');
$f = rc_toolkit::get_request('f');
$tag = rc_toolkit::get_request('tag');

// CREATE masterclass OBJECT
$now = time();
$current_time = time();
$masterclass = new rc_masterclass();
$rc_tag = new rc_masterclasstag();


$criterias = array(
        "masterclass_approved = '1'",
        "masterclass_draft = '0'",
        "masterclass_search= '1'"
);
if ($owner->user_exists) {
    $criterias[] = "masterclass_user_id = '{$owner->user_info['user_id']}'";
}
if (strlen($keyword)) {
    $criterias[] = "(masterclass_title LIKE '%$keyword%' OR masterclass_body LIKE '%$keyword%')";
}
if ($f == 1) {
    $criterias[] = "masterclass_featured = '1'";
}

if (strlen($tag)) {
    $ids = $rc_tag->get_object_ids_tagged_with($tag);
    $criterias[] = "masterclass_id IN ('" . join("','",$ids) . "')";
}
$masterclass_menu_filter = $criterias;
$rc_masterclasscats = new rc_masterclasscats();
$menu_options = array(
        'expanded_category_id' => $masterclasscat_id,
        'count_criteria' => join(" AND ", $masterclass_menu_filter)
);
$categories = $rc_masterclasscats->get_category_menu($menu_options);

if ($masterclasscat_id != "") {
    if ($masterclasscat_id > 0) {
        $criterias[] = "(masterclass_masterclasscat_id='$masterclasscat_id' OR masterclasscat_dependency='$masterclasscat_id')";
    }
    else {
        $criterias[] = "masterclass_masterclasscat_id='0'";
    }
}
else {
    $nocat = 1;
}

if($di_mcategory == 2) {
    $where .= ' se_users.user_level_id != 3';
}
else {
    $where .= ' se_users.user_level_id = 3';
}

$where .= ' AND masterclass_approved = 1';

if (strlen($keyword)) {
    $where .= " AND (masterclass_title LIKE '%$keyword%' OR masterclass_body LIKE '%$keyword%')";
}
// GET TOTAL masterclassS
$total_masterclasss = $masterclass->masterclass_total($where);
$masterclasss_totalnocat = $masterclass->masterclass_total(join(' AND ', array_merge($masterclass_menu_filter,array('no'=>"masterclass_masterclasscat_id='0'"))));

// MAKE masterclass PAGES
$masterclasss_per_page = 20;
$page_vars = make_page($total_masterclasss, $masterclasss_per_page, $p);


$s = rc_toolkit::get_request('s','date');
if ($s == 'view') {
    $sort = "masterclass_views DESC";
}
elseif ($s == 'title') {
    $sort = "masterclass_title ASC";
}
else {
    $sort = "masterclass_date_start DESC";
    $s = 'date';
}

$category_info = $rc_masterclasscats->get_record($masterclasscat_id);

/*
rc_toolkit::debug($categories, "CATEGORIES MENU");
rc_toolkit::debug($total_masterclasss, "total_masterclasss");
rc_toolkit::debug($where, "where");
rc_toolkit::debug($sort, "sort");
*/

// GET masterclassS
$masterclass_array = $masterclass->masterclass_list($page_vars[0], $masterclasss_per_page, $sort, $where, 1);

foreach ($masterclass_array as $k => $masterclass_entry) {
    $masterclass_array[$k]['masterclass']->masterclass_info['masterclass_body'] = str_replace("\r\n", "",html_entity_decode($masterclass_entry['masterclass']->masterclass_info['masterclass_body']));
}

//rc_toolkit::debug($masterclass_array, "masterclass_array");
$result = $database->database_query("SELECT masterclass_title, masterclass_body, masterclass_id, masterclass_photo FROM se_masterclasss WHERE masterclass_approved = 1 ORDER BY masterclass_views DESC LIMIT 5");
while($rez = $database->database_fetch_assoc($result)) {
    $rez['masterclass_body'] = str_replace("\r\n", "",html_entity_decode($rez['masterclass_body']));
    $popular_masterclasses[] = $rez;
}

// POPULAR TAGS
$popular_max_tags = 50;
$popular_order_tag_by = 'name'; // use 'count' or 'name'
$popular_distribution_classes=array(1,3,7,10,16,25,40,50);
$popular_tags = $rc_tag->get_popular_tags($popular_max_tags, $popular_order_tag_by, null, $popular_distribution_classes);
$smarty->assign('popular_tags', $popular_tags);
//rc_toolkit::debug($popular_tags, "popular_tags");
// -----------------
//print_r($masterclass_array);
$smarty->assign('tag', $tag);
$smarty->assign('keyword', $keyword);
$smarty->assign('s', $s);
$smarty->assign('f', $f);

// ASSIGN SMARTY VARIABLES AND DISPLAY BROWSE masterclassS PAGE
$smarty->assign('popularmasterclasses', $popular_masterclasses);
$smarty->assign('di_mcategory', $di_mcategory);
$smarty->assign('masterclasss_totalnocat', $masterclasss_totalnocat);
$smarty->assign('total_masterclasss', $total_masterclasss);
$smarty->assign('categories', $categories);
$smarty->assign('masterclasscat_id', $masterclasscat_id);
$smarty->assign('masterclasscat_title', $category_info[masterclasscat_title]);
$smarty->assign('masterclass_array', $masterclass_array);
$smarty->assign('nocat', $nocat);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($masterclass_array));
include "footer.php";
?>