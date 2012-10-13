<?
$page = "message_file";
include "header.php";

// MAKE SURE MEDIA VARS ARE SET IN URL
if(isset($_POST['image_id'])) {
    $image_id = $_POST['image_id'];
} elseif(isset($_GET['image_id'])) {
    $image_id = $_GET['image_id'];
} else {
    $image_id = "0";
}

if(is_numeric($image_id)) {
    $result = $database->database_fetch_assoc($database->database_query("SELECT * FROM se_messages_media WHERE messagemedia_id = ".$image_id));
    $image_path = './uploads_messages/'.$result['messagemedia_id'].'.'.$result['messagemedia_ext'];
}
$smarty->assign('image_path', $image_path);
include "footer.php";
?>