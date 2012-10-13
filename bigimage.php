<?
$page = "bigimage";
include "header.php";

// MAKE SURE MEDIA VARS ARE SET IN URL
if(isset($_POST['image'])) {
    $image = $_POST['image'];
} elseif(isset($_GET['image'])) {
    $image = $_GET['image'];
} else {
    $image = "0";
}
if(isset($_POST['type'])) {
    $type = $_POST['type'];
} elseif(isset($_GET['type'])) {
    $type= $_GET['type'];
} else {
    $type = "0";
}
if(isset($_POST['type_id'])) {
    $type_id = $_POST['type_id'];
} elseif(isset($_GET['type_id'])) {
    $type_id= $_GET['type_id'];
} else {
    $type_id = "0";
}
if(isset($_POST['owner'])) {
    $owner = $_POST['owner'];
} elseif(isset($_GET['owner'])) {
    $owner = $_GET['owner'];
} else {
    $owner = "0";
}

if($type == 'album') {
    $image_path = $url->url_userdir($owner).$image;
}

if($type == 'business') {
    $business = new se_business();
    $image_path = $business->business_dir($type_id).$image;
}

if($type == 'group') {
    $group = new se_group();
    $image_path = $group->group_dir($type_id).$image;
}

if($type == 'masterclass') {
    $masterclass = new rc_masterclass();
    $image_path = $masterclass->url_masterclassdir($type_id).$image;
}

if($type == 'article') {
    $article = new rc_article();
    $image_path = $article->url_articledir($type_id).$image;
}

//print_r($image_path.'1');
$smarty->assign('image_path', $image_path);
include "footer.php";
?>