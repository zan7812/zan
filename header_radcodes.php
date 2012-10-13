<?php

if(!defined('SE_PAGE')) { exit(); }

include_once "./include/class_radcodes.php";
include_once "./include/class_radcodes_map.php";



SE_Hook::register("se_footer", 'radcodes_hook_se_footer');

