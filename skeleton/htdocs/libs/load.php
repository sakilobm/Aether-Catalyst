<?php

/**
 * Modern Framework Loader (PSR-4)
 * ===============================
 * Bootstraps the framework using Composer autoloader.
 */

// Load Composer autoloader (PSR-4 + Dependencies)
$loader = require_once __DIR__ . '/../vendor/autoload.php';

// Global config singleton (Legacy support)
global $__site_config;

// Bootstrap: Initialize WebAPI (Loads .env, validates, connects DB, initiates session)
use Aether\WebAPI;

$wapi = new WebAPI();
$wapi->initiateSession();

/**
 * Universal config reader – Checks .env first, then config.json.
 *
 * @param string $key     The key to read (can be ENV_CONSTANT or json_key)
 * @param mixed  $default Default value if not found
 * @return mixed
 */
function get_config(string $key, $default = null)
{
    // 1. Try Environment Variables
    $envValue = $_ENV[strtoupper($key)] ?? $_SERVER[strtoupper($key)] ?? false;
    if ($envValue !== false) return $envValue;

    // 2. Try global config JSON string ($__site_config)
    global $__site_config;
    if (!empty($__site_config)) {
        $array = json_decode($__site_config, true);
        if (isset($array[$key])) return $array[$key];
    }

    return $default;
}

/**
 * Direct include helper for small partials.
 * For full views, use Aether\Session::renderView().
 */
function load_template(string $name): void
{
    include $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . "_templates/{$name}.php";
}
