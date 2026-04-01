<?php

/**
 * Session Class
 * =============
 * Static facade for PHP sessions + template rendering engine.
 *
 * Key responsibilities:
 *  - Wrap PHP $_SESSION operations
 *  - loadTemplate() — include _templates/ files with data injection
 *  - renderPage*()  — named shortcuts for master layouts
 *  - isAuthenticated() — check if a valid UserSession is active
 *  - ensureLogin()     — redirect to /admin if not logged in
 *
 * Used in every page: Session::renderPage(), Session::isAuthenticated(), etc.
 */

include_once __DIR__ . '/../traits/SQLGetterSetter.trait.php';

class Session
{
    /** @var bool Set to true to force the error template */
    public static bool $isError = false;

    /** @var User|null The currently authenticated User object */
    public static ?User $user = null;

    /** @var UserSession|null The active session object */
    public static ?UserSession $usersession = null;

    // ─── PHP Session Wrappers ─────────────────────────────────────────────

    public static function start(): void         { session_start(); }
    public static function unset(): void         { session_unset(); }
    public static function destroy(): void       { session_destroy(); }

    public static function set(string $key, $value): void   { $_SESSION[$key] = $value; }
    public static function delete(string $key): void        { unset($_SESSION[$key]); }
    public static function isset(string $key): bool         { return isset($_SESSION[$key]); }

    public static function get(string $key, $default = false)
    {
        return self::isset($key) ? $_SESSION[$key] : $default;
    }

    // ─── User Access ──────────────────────────────────────────────────────

    /** @return User|null */
    public static function getUser(): ?User          { return self::$user; }

    /** @return UserSession|null */
    public static function getUserSession(): ?UserSession { return self::$usersession; }

    /**
     * Check if the session user owns a resource (by email).
     *
     * @param string $ownerEmail
     * @return bool
     */
    public static function isOwnerOf(string $ownerEmail): bool
    {
        $user = self::getUser();
        return $user && $user->getEmail() === $ownerEmail;
    }

    // ─── Authentication ───────────────────────────────────────────────────

    /**
     * Returns true only if a valid, active UserSession is loaded.
     *
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        $sess = self::getUserSession();
        return ($sess instanceof UserSession) && $sess->isValid();
    }

    /**
     * Hard-redirect to /admin (login page) if not authenticated.
     * Call at the top of any protected page.
     */
    public static function ensureLogin(): void
    {
        if (!self::isAuthenticated()) {
            self::set('_redirect', $_SERVER['REQUEST_URI']);
            header('Location: /admin');
            exit;
        }
    }

    // ─── Template Engine ──────────────────────────────────────────────────

    /**
     * Include a template file from _templates/ with optional data injection.
     *
     * Variables in $data are extracted into local scope before include.
     * Falls back to core/_error template if the file doesn't exist.
     *
     * @param string $name Template path relative to _templates/ (no .php)
     * @param array  $data Key-value pairs to extract as local variables
     *
     * @example
     *   Session::loadTemplate('core/_nav');
     *   Session::loadTemplate('index/postcard', ['post' => $post]);
     */
    public static function loadTemplate(string $name, array $data = []): void
    {
        extract($data);
        $script = $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . "_templates/{$name}.php";

        if (is_file($script)) {
            include $script;
        } else {
            // Fallback error template — never a blank page
            $errorMsg = "Template not found: {$name}";
            include $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . '_templates/core/_error.php';
        }
    }

    // ─── Named Page Renderers  ────────────────────────────────────────────

    /** Render the public-facing master layout */
    public static function renderPage(): void          { self::loadTemplate('_master'); }

    /** Render the admin dashboard master layout */
    public static function renderPageOfAdmin(): void   { self::loadTemplate('_masterForAdmin'); }

    /** Render the login page */
    public static function renderPageLogin(): void     { self::loadTemplate('login'); }

    /** Render the signup/register page */
    public static function renderPageRegister(): void  { self::loadTemplate('signup'); }

    /** Render the single-post master layout */
    public static function renderPagePost(): void      { self::loadTemplate('_masterForPost'); }

    // ─── Utilities ────────────────────────────────────────────────────────

    /**
     * Returns the current PHP script name without extension.
     * Used by _master.php to auto-load the matching template.
     *
     * @return string e.g. 'index', 'posts', 'admin'
     */
    public static function currentScript(): string
    {
        return basename($_SERVER['SCRIPT_NAME'], '.php');
    }

    /**
     * Returns the URL path segments as an array.
     * Useful for routing / URL inspection.
     *
     * @return string[]
     */
    public static function getRequestPageName(): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return explode('/', trim($path, '/'));
    }

    /**
     * Return the current_page GET param, defaulting to 'dashboard'.
     * Used in the admin sidebar to highlight the active nav item.
     *
     * @return string
     */
    public static function getCurrentPageIdentifier(): string
    {
        return $_GET['current_page'] ?? 'dashboard';
    }

    /**
     * Count all registered users.
     *
     * @return int
     */
    public static function countAllUsers(): int
    {
        $db     = Database::getConnection();
        $result = $db->query("SELECT COUNT(*) as count FROM `auth`");
        return $result ? (int)$result->fetch_assoc()['count'] : 0;
    }
}
