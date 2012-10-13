<?php
$page = 'custom_upload';
include 'header.php';

if($user->user_exists && !$_FILES['upload']['error'] && in_array(strtolower(str_replace(".", "", strrchr($_FILES['upload']['name'], "."))), array('jpg', 'jpeg', 'png', 'bmp', 'gif')))
{
    $upload_folder = $url->url_userdir($user->user_info['user_id']);
    $path = $upload_folder.time().'_'.$_FILES['upload']['name'];
    move_uploaded_file($_FILES['upload']['tmp_name'], $path);
    echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(4, '$path', '');</script>";
}
else
    exit();

?>