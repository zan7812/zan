<?php

/* $Id: classified_ajax.php 16 2009-01-13 04:01:31Z john $ */

ob_start();
$page = "classified_ajax";
include "header.php";


// PROCESS INPUT
$task           = ( !empty($_POST['task'])          ? $_POST['task']          : ( !empty($_GET['task'])           ? $_GET['task']           : NULL ) );
$classified_id  = ( !empty($_POST['classified_id']) ? $_POST['classified_id'] : ( !empty($_GET['classified_id'])  ? $_GET['classified_id']  : NULL ) );



// DELETE
if( $task=="deleteclassified" )
{
  $classified = new se_classified($user->user_info['user_id']);
  
  // OUTPUT
  ob_end_clean();
  
  if( $user->user_exists && $classified_id && $classified->classified_delete($classified_id) )
    echo '{"result":"success"}';
  else
    echo '{"result":"failure"}';
  
  exit();
}

?>