<?php

/**
 * WebAPI Class
 * ============
 * Bootstrap class — called once in load.php to:
 *   1. Read config.json into $__site_config global
 *   2. Establish the DB connection early
 *   3. Configure secure session cookie params (SameSite=None, Secure)
 *   4. Start the PHP session and attempt to restore a UserSession from token
 *
 * Config path strategy (in priority order):
 *   a) Env variable FRAMEWORK_CONFIG_PATH  (ideal for server/container)
 *   b) Three levels up from htdocs/ (project/config.json)
 *   c) Same directory as htdocs/ → config.json
 */
class WebAPI
{
    public function __construct()
    {
        global $__site_config;

        // --- Locate config.json (never hardcoded) ---
        $configPath = $this->resolveConfigPath();

        if (!$configPath || !file_exists($configPath)) {
            http_response_code(503);
            die('Framework: config.json not found. Run forge.php to create a project.');
        }

        $__site_config = file_get_contents($configPath);

        // Establish DB connection early so all classes can use it
        Database::getConnection();

        // Secure session cookies (required for cross-origin/SameSite=None)
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'],
            $cookieParams['domain'],
            true,   // secure — HTTPS only
            true    // httponly — no JS access
        );
    }

    /**
     * Start the session and restore UserSession from stored token (if present).
     * Called immediately after construction in load.php.
     */
    public function initiateSession(): void
    {
        Session::start();

        if (Session::isset('session_token')) {
            try {
                Session::$usersession = UserSession::authorize(Session::get('session_token'));
            } catch (\Exception $e) {
                // Session invalid/expired — silently clear it
                Session::delete('session_token');
            }
        }
    }

    // ─── Private Helpers ──────────────────────────────────────────────────

    /**
     * Resolve the config.json path using multiple fallback strategies.
     *
     * @return string|null Absolute path or null if not found
     */
    private function resolveConfigPath(): ?string
    {
        // 1. Environment variable (best for production)
        if (!empty($_ENV['FRAMEWORK_CONFIG_PATH']) && file_exists($_ENV['FRAMEWORK_CONFIG_PATH'])) {
            return $_ENV['FRAMEWORK_CONFIG_PATH'];
        }

        // 2. project/config.json — three levels up from libs/
        //    Structure: /project/config.json, /htdocs/libs/WebAPI.class.php
        $candidates = [
            __DIR__ . '/../../../project/config.json',  // htdocs/libs/includes → project/
            __DIR__ . '/../../../../config.json',         // deeper nesting
            $_SERVER['DOCUMENT_ROOT'] . '/../config.json', // server root sibling
            $_SERVER['DOCUMENT_ROOT'] . '/config.json',    // same dir as htdocs
        ];

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real && file_exists($real)) {
                return $real;
            }
        }

        return null;
    }
}
