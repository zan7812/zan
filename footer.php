<?php

/* $Id: footer.php 273 2009-12-11 02:09:22Z john $ */

// ENSURE THIS IS BEING INCLUDED IN AN SE SCRIPT
defined('SE_PAGE') or exit();

SE_DEBUG ? $_benchmark->end('page') : NULL;
SE_DEBUG ? $_benchmark->start('shutdown') : NULL;

// GET LANGUAGES AVAILABLE IF NECESSARY
if($setting['setting_lang_anonymous'] == 1 || ($setting['setting_lang_allow'] == 1 && $user->user_exists != 0))
{
  $lang_packlist_raw = SECore::getLanguages();
  //$lang_packlist = SELanguage::list_packs();
  ksort($lang_packlist_raw);
  $lang_packlist = array_values($lang_packlist_raw);
}


// ASSIGN LOGGED-IN USER VARS
if( $user->user_exists )
{ 
  $smarty->assign('user_unread_pms', $user->user_message_total(0, 1));
}

// Token
if( !defined('SE_PAGE_AJAX') )
{
  $token = md5(uniqid(mt_rand(), true));
  $session->set('token', $token);
  $smarty->assign('token', $token);
}

// CALL SPECIFIC PAGE HOOK
($hook = SE_Hook::exists('se_'.$page)) ? SE_Hook::call($hook, array()) : NULL;

// CALL FOOTER HOOK
($hook = SE_Hook::exists('se_footer')) ? SE_Hook::call($hook, array()) : NULL;

// CHECK IF IN SMOOTHBOX
$global_smoothbox = false;
if(isset($_GET['in_smoothbox'])) { if($_GET['in_smoothbox'] == true) { $global_smoothbox = true; }}

//Articles block
$count_acrticles = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) AS count FROM se_articles WHERE article_approved = 1"));
$result = $database->database_query("SELECT article_title, article_body, article_id FROM se_articles WHERE article_approved = 1 ORDER BY article_id DESC LIMIT 7");
while($rez = $database->database_fetch_assoc($result)){
    $rez['article_body'] = str_replace("\r\n", "",html_entity_decode($rez['article_body']));
    $articles[] = $rez;
}

//Masterclass block
$count_masterclasses = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) AS count FROM se_masterclasss WHERE masterclass_approved = 1"));
$result = $database->database_query("SELECT masterclass_title, masterclass_body, masterclass_id, masterclass_photo FROM se_masterclasss WHERE masterclass_approved = 1 ORDER BY masterclass_id DESC LIMIT 3");
while($rez = $database->database_fetch_assoc($result)){
    $rez['masterclass_body'] = str_replace("\r\n", "",html_entity_decode($rez['masterclass_body']));
    $masterclasses[] = $rez;
}

//Classifieds block
$count_classified = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) AS count FROM se_classifieds"));
$result = $database->database_query("SELECT sec.classified_title, sec.classified_id, sec.classified_city, sec.classified_services, seu.user_username 
    FROM se_classifieds sec INNER JOIN se_users seu ON sec.classified_user_id = seu.user_id ORDER BY sec.classified_id DESC LIMIT 7");
while($rez = $database->database_fetch_assoc($result)){
//    $rez['article_body'] = str_replace("\r\n", "",html_entity_decode($rez['article_body']));
    $fclassifieds[] = $rez;
}
//USER FRIENDS
$result = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) as count FROM se_friends WHERE friend_user_id2 = ".$user->user_info['user_id']." AND friend_status = 0"));
$friends_count = $result['count'];

//USER NEW MEAAGES
$result = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) AS count FROM se_pmconvoops WHERE pmconvoop_user_id = ".$user->user_info['user_id']." AND pmconvoop_read = 0 AND pmconvoop_deleted_inbox = 0"));
$messages_count = $result['count'];

//ваше дело
if($user->profile_info['profilevalue_20'] != 1){
    $user_business = $database->database_fetch_assoc($database->database_query("SELECT business_id FROM se_allbusiness WHERE business_user_id = ".$user->user_info['user_id']." LIMIT 1"));
    if($user_business['business_id']){
        $appoinment_count['count'] = 0;
        $appoinment_count = $database->database_fetch_assoc($database->database_query("SELECT COUNT(*) as count FROM se_appoinments WHERE master_business_id = ".$user_business['business_id']." AND approved = 0 AND app_time > ".time()));
        $smarty->assign('business_appoinments', $appoinment_count['count']);
        $smarty->assign('my_business', $user_business['business_id']);
    }
}

if(strstr($page, 'user_') || strstr($page, 'forum') || strstr($page, 'content') || $page == 'album' || $page == 'albums' || $page == 'error' || $page == 'friends' || $page == 'help_tos'){
    $smarty->assign('google_advertisment', 0);
}
else{
    $smarty->assign('google_advertisment', 0);
}

// ASSIGN GLOBAL SMARTY OBJECTS/VARIABLES
$smarty->assign('messages_count', $messages_count);
$smarty->assign('friends_count', $friends_count);
$smarty->assign('fcount_classified', $count_classified);
$smarty->assign('fclassifieds', $fclassifieds);
$smarty->assign('count_masterclasses', $count_masterclasses);
$smarty->assign('masterclasses', $masterclasses);
$smarty->assign('count_acrticles', $count_acrticles);
$smarty->assign('articles', $articles);
$smarty->assign_by_ref('url', $url);
$smarty->assign_by_ref('misc', $misc);
$smarty->assign_by_ref('datetime', $datetime);
$smarty->assign_by_ref('database', $database);
$smarty->assign_by_ref('admin', $admin);
$smarty->assign_by_ref('user', $user);
$smarty->assign_by_ref('owner', $owner);
$smarty->assign_by_ref('ads', $ads);
$smarty->assign_by_ref('setting', $setting);
$smarty->assign_by_ref('se_javascript', $se_javascript);
$smarty->assign('lang_packlist', $lang_packlist);
$smarty->assign('notifys', $notify->notify_summary());
$smarty->assign('global_plugins', $global_plugins);
$smarty->assign('global_smoothbox', $global_smoothbox);
$smarty->assign('global_page', $page);
$smarty->assign('global_page_title', ( !empty($global_page_title) ? $global_page_title : NULL ));
$smarty->assign('global_page_description', ( !empty($global_page_description) ? str_replace("\"", "'", $global_page_description) : NULL ));
$smarty->assign('global_css', $global_css);
$smarty->assign('global_timezone', $global_timezone);
$smarty->assign('global_language', SELanguage::info('language_id'));

if( SE_DEBUG )
{
  $_benchmark->end('shutdown');
  
  $smarty->assign('debug_uid', $_benchmark->getUid());
  $smarty->assign_by_ref('debug_benchmark_object', $_benchmark);
  
  $_benchmark->start('output');
}


// DISPLAY PAGE
$smarty->display("$page.tpl");


if( SE_DEBUG )
{
  $_benchmark->end('output');
  $_benchmark->end('total');
  
  $smarty->assign('debug_benchmark', $_benchmark->getLog());
  $smarty->assign('debug_benchmark_total', $_benchmark->getTotalTime());
  
  // Save logging info
  file_put_contents('./log/'.$_benchmark->getUid().'.html', $smarty->fetch('debug.tpl'));
  //file_put_contents(SE_ROOT.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.$_benchmark->getUid(), $smarty->fetch('debug.tpl'));
}

exit();
?>