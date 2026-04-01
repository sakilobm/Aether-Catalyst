<?php

/**
 * Modern Public Landing Page (Aether Catalyst)
 */

require_once 'libs/load.php';

use Aether\Session;
use Aether\UserSession;

// Handle logout directly in index
if (isset($_GET['logout']) && Session::isset('session_token')) {
    try {
        $sess = new UserSession(Session::get('session_token'));
        $sess->removeSession();
    } catch (Exception $e) {}
    Session::unset();
    Session::destroy();
    header('Location: /');
    exit;
}

// Render the homepage view using the _master layout (Template Inheritance)
Session::renderPage();
