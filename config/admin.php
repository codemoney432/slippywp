<?php
// Admin configuration
// Change this password to a secure value
define('ADMIN_PASSWORD', 'change_this_password'); // TODO: Change this to a secure password

// Session management for admin
session_start();

/**
 * Check if user is authenticated as admin
 */
function isAdmin() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

/**
 * Authenticate admin with password
 */
function authenticateAdmin($password) {
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
        return true;
    }
    return false;
}

/**
 * Logout admin
 */
function logoutAdmin() {
    $_SESSION['admin_authenticated'] = false;
    unset($_SESSION['admin_authenticated']);
}



