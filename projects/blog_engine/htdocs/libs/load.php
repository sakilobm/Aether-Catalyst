<?php

/**
 * Framework Core Loader
 * =====================
 * Every PHP page starts with: require_once 'libs/load.php';
 * This file bootstraps all classes, reads config.json, and initiates the session.
 */

// Load Composer autoloader (Carbon, php-amqplib, etc.)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

// Load all framework classes in dependency order
$_fwIncludes = [
    'includes/Database.class.php',
    'includes/Session.class.php',
    'includes/User.class.php',
    'includes/UserSession.class.php',
    'includes/REST.class.php',
    'includes/API.class.php',
    'includes/WebAPI.class.php',
];

foreach ($_fwIncludes as $_fwFile) {
    include_once __DIR__ . '/' . $_fwFile;
}

// Auto-load all app-level classes from libs/app/
foreach (glob(__DIR__ . '/app/*.php') as $_appFile) {
    include_once $_appFile;
}

global $__site_config;

// Bootstrap: read config, connect DB, initiate session
$wapi = new WebAPI();
$wapi->initiateSession();

/**
 * Reads a value from config.json (credentials are NEVER hardcoded).
 *
 * @param string $key     The key to read from config.json
 * @param mixed  $default Default value if key not found
 * @return mixed
 */
function get_config(string $key, $default = null)
{
    global $__site_config;
    $array = json_decode($__site_config, true);
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Direct include helper (use Session::loadTemplate() for data injection).
 *
 * @param string $name Template path relative to _templates/ (no .php)
 */
function load_template(string $name): void
{
    include $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . "_templates/{$name}.php";
}
