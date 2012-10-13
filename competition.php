<?
$page = "competition";
include "header.php";

if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 0;
}

if(!is_numeric($p) && $p = 0) {
    $page = "error";
    $smarty->assign('error_header', 11010601);
    $smarty->assign('error_message', 11010605);
    $smarty->assign('error_submit', 11010602);
    include "footer.php";
}

$competition = $database->database_fetch_assoc($database->database_query("SELECT * FROM se_competitions WHERE competition_id = ".$p));
$competition['competition_date'] = date('d:m:Y', $competition['competition_date']);
if($competition['competition_winner']) {
    if($competition['competition_category'] == 1) {
        $wnr = $database->database_fetch_assoc($database->database_query("SELECT se_albums.album_id, se_albums.album_title, se_users.user_username, se_users.user_displayname FROM se_albums
            LEFT JOIN se_users ON se_users.user_id = se_albums.album_user_id WHERE se_albums.album_id = ".$competition['competition_winner']));
        $winner['object'] = '<br/> Фотоальбом - <a href="album.php?user='.$wnr['user_username'].'&album_id='.$wnr['album_id'].'">'.$wnr['album_title'].'</a>';
    }
    if($competition['competition_category'] == 2) {
        $wnr = $database->database_fetch_assoc($database->database_query("SELECT se_articles.article_id, se_articles.article_title, se_users.user_username, se_users.user_displayname FROM se_articles
            LEFT JOIN se_users ON se_users.user_id = se_articles.article_user_id WHERE se_articles.article_id = ".$competition['competition_winner']));
        $winner['object'] = '<br/> Статья - <a href="./article.php?article_id='.$wnr['article_id'].'">'.$wnr['article_title'].'</a>';
    }
    $winner['user'] = '<a href="./profile.php?user='.$wnr['user_username'].'">'.$wnr['user_displayname'].'</a>';
    if($competition['competition_category'] == 3) {
        $wnr = $database->database_fetch_assoc($database->database_query("SELECT se_masterclasss.masterclass_id, se_masterclasss.masterclass_title, se_users.user_username, se_users.user_displayname FROM se_masterclasss
            LEFT JOIN se_users ON se_users.user_id = se_masterclasss.masterclass_user_id WHERE se_masterclasss.masterclass_id = ".$competition['competition_winner']));
        $winner['object'] = '<br/> Мастер-класс - <a href="./masterclass.php?masterclass_id='.$wnr['masterclass_id'].'">'.$wnr['masterclass_title'].'</a>';
    }
    $winner['user'] = '<a href="./profile.php?user='.$wnr['user_username'].'">'.$wnr['user_displayname'].'</a>';
}
if(!$competition) {
    $page = "error";
    $smarty->assign('error_header', 11010601);
    $smarty->assign('error_message', 11010605);
    $smarty->assign('error_submit', 11010602);
    include "footer.php";
}
$smarty->assign('cmp', $competition);
$smarty->assign('winner', $winner);
include "footer.php";

