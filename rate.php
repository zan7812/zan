<?
$page = "rate";
include "header.php";

// SET PARAMETER
$max_rating = 5;

// RETRIEVE REQUIRED VARIABLES
if(isset($_GET['object_table'])) {
    $object_table = $_GET['object_table'];
} else {
    $object_table = "se_ratings_an";
}
if(isset($_GET['object_primary'])) {
    $object_primary = $_GET['object_primary'];
} else {
    $object_primary = "rating_id";
}
if(isset($_GET['object_id'])) {
    $object_id = $_GET['object_id'];
} else {
    $object_id = 0;
}
if(isset($_GET['rating'])) {
    $rating = (int)$_GET['rating'];
} else {
    $rating = 0;
}
if(isset($_GET['task'])) {
    $task = $_GET['task'];
} else {
    $task = "main";
}
//if(isset($_GET['owner_id'])) { $owner_id = $_GET['owner_id']; } else { $owner_id = "0"; }

// EXIT IF USER IS NOT LOGGED IN
$rating_allowed = 1;
if($user->user_exists == 0) {
    $rating_allowed = 0;
}

// EXIT IF VARIABLES AREN'T VALID
$object = $database->database_query("SELECT ".$object_primary." FROM ".$object_table." WHERE ".$object_primary."='".$object_id."'");
if($database->database_num_rows($object) != 1) {
    echo "Incorrect Parameters Specified";
    exit();
}

$rate_points = $database->database_fetch_assoc($database->database_query("SELECT image_vote FROM se_ratings LIMIT 1"));
$owner_id = $database->database_fetch_assoc($database->database_query("SELECT album_user_id FROM se_albums
            INNER JOIN se_media ON se_media.media_album_id = se_albums.album_id WHERE se_media.media_id = ".$object_id));

// RETRIEVE RATING ROW
$rating_query = $database->database_query("SELECT * FROM se_ratings_an WHERE rating_object_table='".$object_table."' AND rating_object_primary='".$object_primary."' AND rating_object_id='".$object_id."'");
if($database->database_num_rows($rating_query) != 1) {
    $rating_info['rating_id'] = 0;
    $rating_info['rating_value'] = 0;
    $rating_info['rating_raters'] = "";
    $rating_info['rating_raters_num'] = 0;
} else {
    $rating_info = $database->database_fetch_assoc($rating_query);
}

// GET NUMBER OF FULL, PARTIAL, EMPTY STARS
$rating_full = floor($rating_info[rating_value]);
if($rating_full != $rating_info[rating_value]) {
    $rating_partial = 1;
} else {
    $rating_partial = 0;
}
$rating_empty = $max_rating-($rating_full+$rating_partial);

// RETRIEVE RATERS ARRAY
$raters = explode(",", trim($rating_info[rating_raters]));
if(in_array($user->user_info[user_id], $raters)) {
    $rating_allowed = 0;
}

// IF RATING IS ALLOWED AND RATING IS WITHIN THE CORRECT PARAMETERS
if($task == "rate" && $rating_allowed != 0 && $rating <= $max_rating && $rating >= 0) {

    // CREATE RATING ROW
    if($rating_info[rating_id] == 0) {
        $new_total_ratings = 1;
        $new_rating = $rating;
        $new_raters = ",".$user->user_info[user_id];
        $database->database_query("INSERT INTO se_ratings_an (rating_object_table, rating_object_primary, rating_object_id, rating_value, rating_raters, rating_raters_num) VALUES ('".$object_table."', '".$object_primary."', '".$object_id."', '$new_rating', '$new_raters', '$new_total_ratings')");


        if($owner_id['album_user_id'] && $rate_points['image_vote'] && $rating) {
            $image_rate_point = $rating*$rate_points['image_vote'];
            $database->database_fetch_assoc($database->database_query("UPDATE se_users SET user_rating = user_rating + ".$image_rate_point." WHERE user_id = ".$owner_id['album_user_id']));
        }
        // UPDATE RATING ROW
    } else {
        $new_total_ratings = $rating_info[rating_raters_num]+1;
        $new_rating = round(($rating_info[rating_value]*$rating_info[rating_raters_num]+$rating)/$new_total_ratings, 2);
        $new_raters = $rating_info[rating_raters].",".$user->user_info[user_id];
        $database->database_query("UPDATE se_ratings_an SET rating_value='$new_rating', rating_raters_num='$new_total_ratings', rating_raters='$new_raters' WHERE rating_id='$rating_info[rating_id]'");
    }

    // CLEAN UP RATING TABLE (CURRENT OBJECT TYPE ONLY)
    $database->database_query("DELETE FROM r USING se_ratings_an r LEFT JOIN ".$object_table." t ON t.".$object_primary."=r.rating_object_id WHERE t.".$object_primary." IS NULL AND r.rating_object_table='".$object_table."' AND r.rating_object_primary='".$object_primary."'");

    // REFRESH
    header("Location: rate.php?object_table=$object_table&object_primary=$object_primary&object_id=$object_id");
    exit();
}



// ASSIGN VARIABLES AND INCLUDE FOOTER
$smarty->assign('rating_value', $rating_info[rating_value]);
$smarty->assign('max_rating', $max_rating);
$smarty->assign('rating_full', $rating_full);
$smarty->assign('rating_partial', $rating_partial);
$smarty->assign('rating_empty', $rating_empty);
$smarty->assign('rating_total', $rating_info[rating_raters_num]);
$smarty->assign('rating_allowed', $rating_allowed);
$smarty->assign('object_table', $object_table);
$smarty->assign('object_primary', $object_primary);
$smarty->assign('object_id', $object_id);
include "footer.php";
?>