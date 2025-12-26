<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";


require_once __DIR__ . '/functions.php';


if (isset($_SESSION['user_id'])) {
    if (is_admin()) {
        require_once __DIR__ . '/header_admin.php';
    } else {
        require_once __DIR__ . '/header_member.php';
    }
} else {
    require_once __DIR__ . '/header_public.php';
}