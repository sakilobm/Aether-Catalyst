<?php
/**
 * index.php — Public Landing Page
 * Handles logout then renders the main page through the template engine.
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

Session::renderPage();
