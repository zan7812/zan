<?
$page = "competitions";
include "header.php";

if(isset($_POST['p'])) {
    $p = $_POST['p'];
} elseif(isset($_GET['p'])) {
    $p = $_GET['p'];
} else {
    $p = 0;
}
$cmp_per_page = 10;
$total_cmp = $database->database_num_rows($database->database_query("SELECT competition_id FROM se_competitions"));
$page_vars = make_page($total_cmp, $cmp_per_page, $p);
$result_query = "SELECT * FROM se_competitions ORDER BY competition_date DESC LIMIT ".$page_vars[0].", ".$cmp_per_page;

$result = $database->database_query($result_query);
while($rez = $database->database_fetch_assoc($result)) {
    if($rez['competition_winner']) {
        //Фотоальбомы
        if($rez['competition_category'] == 1) {
            $wnr = $database->database_fetch_assoc($database->database_query("SELECT se_albums.album_id, se_albums.album_title, se_users.user_username, se_users.user_displayname FROM se_albums
            LEFT JOIN se_users ON se_users.user_id = se_albums.album_user_id WHERE se_albums.album_id = ".$rez['competition_winner']));
            $rez['winner_object'] = '<br/> Фотоальбом - <a href="album.php?user='.$wnr['user_username'].'&album_id='.$wnr['album_id'].'">'.$wnr['album_title'].'</a>';
        }
        //Статьи
        if($rez['competition_category'] == 2) {
            $wnr = $database->database_fetch_assoc($database->database_query("SELECT se_articles.article_id, se_articles.article_title, se_users.user_username, se_users.user_displayname FROM se_articles
            LEFT JOIN se_users ON se_users.user_id = se_articles.article_user_id WHERE se_articles.article_id = ".$rez['competition_winner']));
            $rez['winner_object'] = '<br/> Статья - <a href="./article.php?article_id='.$wnr['article_id'].'">'.$wnr['article_title'].'</a>';
        }
        if($rez['competition_category'] == 3) {
            $wnr = $database->database_fetch_assoc($database->database_query("SELECT se_masterclasss.masterclass_id, se_masterclasss.masterclass_title, se_users.user_username, se_users.user_displayname FROM se_masterclasss
            LEFT JOIN se_users ON se_users.user_id = se_masterclasss.masterclass_user_id WHERE se_masterclasss.masterclass_id = ".$rez['competition_winner']));
            $rez['winner_object'] = '<br/> Мастер-класс - <a href="./masterclass.php?masterclass_id='.$wnr['masterclass_id'].'">'.$wnr['masterclass_title'].'</a>';
        }
        if($rez['competition_category'] == 4) {
            $wnr = $database->database_fetch_assoc($database->database_query("SELECT user_username, user_displayname FROM se_users WHERE user_id = ".$rez['competition_winner']));
            $rez['winner_object'] = '';
        }
        $rez['winner_user'] = '<a href="./profile.php?user='.$wnr['user_username'].'">'.$wnr['user_displayname'].'</a>';
    }
    $rez['competition_body'] = strip_tags($rez['competition_body']);
    $cmps[] = $rez;
}
//print_r($cmps);
$smarty->assign_by_ref('count_cmps', $total_cmp);
$smarty->assign_by_ref('cmps', $cmps);

$smarty->assign('p_start', $page_vars[0]+1);
$smarty->assign('p_end', $page_vars[0]+count($cmps));
$smarty->assign('p_members', $page_vars_members[1]);
$smarty->assign('p', $page_vars[1]);
$smarty->assign('maxpage', $page_vars[2]);
include "footer.php";

