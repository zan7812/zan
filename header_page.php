<?

// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT11
if(!defined('SE_PAGE')) { exit(); }

//include_once "./lang/lang_".$global_lang."_page.php";
include_once "./include/class_radcodes.php";
include_once "./include/class_page.php";
include_once "./include/functions_page.php";

SE_Language::_preload_multi(11010104);

if($setting['setting_page_main_page_id'] != 0) {
  $plugin_vars[menu_user] = Array('file' => $url->url_create('page', $setting['setting_page_main_page_id']), 'icon' => 'page_page16.gif', 'title' => 11010104);
}

$rc_page = new rc_page();

$page_usermenu_items = $rc_page->get_usermenu_items();
$smarty->assign('page_usermenu_items', $page_usermenu_items);

$page_topmenu_items = $rc_page->get_topmenu_items();
$smarty->assign('page_topmenu_items', $page_topmenu_items);