<?
$page = "content";
include "header.php";
$page_id = rc_toolkit::get_request('page');

$rc_page = new rc_page();
if (is_numeric($page_id)) {
    $page_info = $rc_page->get_record($page_id);
}
else {
    $page_info = $rc_page->get_record_by_criteria("page_key = '$page_id'");
}

if (!$page_info) {
    $error_message = 11010605;
}
else {
    if ($user->user_exists == 0 & $page_info['page_type'] == 0) {
        $error_message = 11010603;
    }
    elseif ($page_info['page_type'] == 2 && !in_array($user->user_info['user_level_id'], explode(',',$page_info['page_levels']))) {
        $error_message = 11010606;
    }
    elseif ($page_info['page_status'] == 0 || $setting['setting_permission_page'] == 0) {
        $error_message = 11010604;
    }
}

if (isset($error_message)) {
    $page = "error";
    $smarty->assign('error_header', 11010601);
    $smarty->assign('error_message', $error_message);
    $smarty->assign('error_submit', 11010602);
    include "footer.php";
}
else {
    $page = "content";
    if ($page_info['page_script']) {
        include_once ($page_info['page_script']);
    }
    $page_info['page_content'] = str_replace("{include file='header.tpl'}", "", $page_info['page_content']);
    if($page_id == 'contacts') {
        include "help_contact.php";
    }
    $page_info['page_content'] = str_replace("{include file='footer.tpl'}", "",$page_info['page_content']);

    $smarty->assign('page_id', $page_info['page_id']);
    $smarty->assign('page_text_id', $page_id);
    $smarty->assign('page_info', $page_info);
    include "footer.php";
}
