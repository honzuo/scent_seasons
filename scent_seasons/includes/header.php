<?php
// includes/header.php - Router that includes the appropriate header based on user role
// This ensures members cannot use admin header and vice versa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";

// Load functions to determine user role
require_once __DIR__ . '/functions.php';

// Route to the appropriate header based on user role
if (isset($_SESSION['user_id'])) {
    if (is_admin()) {
        // Admin users get admin header
        require_once __DIR__ . '/header_admin.php';
    } else {
        // Member users get member header
        require_once __DIR__ . '/header_member.php';
    }
} else {
    // Non-logged-in users get public header
    require_once __DIR__ . '/header_public.php';
}