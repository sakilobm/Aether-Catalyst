<?php
/**
 * admin.php — Admin Dashboard Entry Point
 * Handles logout then gates access behind Session::isAuthenticated().
 * Unauthenticated users are redirected to /login.
 */
require_once 'libs/load.php';

// Logout flow
if (isset($_GET['logout']) && Session::isset('session_token')) {
    try {
        $sess = new UserSession(Session::get('session_token'));
        $sess->removeSession();
    } catch (Exception $e) {
        // session already gone — ignore
    }
    Session::unset();
    Session::destroy();
    header('Location: /');
    exit;
}

if (Session::isAuthenticated()) {
    Session::renderPageOfAdmin();
} else {
    header('Location: /login');
    exit;
}
