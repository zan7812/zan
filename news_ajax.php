<?php

/* $Id: news_ajax.php 16 2009-01-13 04:01:31Z john $ */

ob_start();
$page = "news_ajax";
include "header.php";


// PROCESS INPUT
$task           = ( !empty($_POST['task'])          ? $_POST['task']          : ( !empty($_GET['task'])           ? $_GET['task']           : NULL ) );
$news_id  = ( !empty($_POST['news_id']) ? $_POST['news_id'] : ( !empty($_GET['news_id'])  ? $_GET['news_id']  : NULL ) );



// DELETE
if( $task=="deletenews" )
{
  $news = new se_news($user->user_info['user_id']);
  
  // OUTPUT
  ob_end_clean();
  
  if( $user->user_exists && $news_id && $news->news_delete($news_id) )
    echo '{"result":"success"}';
  else
    echo '{"result":"failure"}';
  
  exit();
}

?>