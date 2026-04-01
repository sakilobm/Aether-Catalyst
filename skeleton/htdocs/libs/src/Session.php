<?php

namespace Aether;

use Aether\Traits\SQLGetterSetter;

/**
 * Session Class
 * =============
 * PSR-4 Namespace: Aether\Session
 */
class Session
{
    use SQLGetterSetter;

    public static bool $isError = false;
    public static ?User $user = null;
    public static ?UserSession $usersession = null;

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

    public static function getUser(): ?User          { return self::$user; }
    public static function getUserSession(): ?UserSession { return self::$usersession; }

    public static function isAuthenticated(): bool
    {
        $sess = self::getUserSession();
        return ($sess instanceof UserSession) && $sess->isValid();
    }

    public static function ensureLogin(): void
    {
        if (!self::isAuthenticated()) {
            self::set('_redirect', $_SERVER['REQUEST_URI']);
            header('Location: /login');
            exit;
        }
    }

    /**
     * Advanced View Renderer with Layout Inheritance.
     *
     * @param string $view   The view template inside _templates/
     * @param array  $data   Variables to pass to the view
     * @param string $layout The layout wrapper inside _templates/
     */
    public static function renderView(string $view, array $data = [], string $layout = '_master'): void
    {
        extract($data);
        ob_start();
        $viewPath = $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . "_templates/{$view}.php";

        if (is_file($viewPath)) {
            include $viewPath;
        } else {
            include $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . "_templates/core/_error.php";
        }

        $content = ob_get_clean();

        // Load the layout and inject the view content
        self::loadTemplate($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Legacy template loader (can still be used for small partials).
     */
    public static function loadTemplate(string $name, array $data = []): void
    {
        extract($data);
        $script = $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . "_templates/{$name}.php";

        if (is_file($script)) {
            include $script;
        } else {
            $errorMsg = "Template not found: {$name}";
            include $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . '_templates/core/_error.php';
        }
    }

    public static function renderPage(): void          { self::renderView(self::currentScript()); }
    public static function renderPageOfAdmin(): void   { self::renderView('admin/' . self::getCurrentPageIdentifier(), [], '_masterForAdmin'); }
    public static function renderPageLogin(): void     { self::loadTemplate('login'); }
    public static function renderPageRegister(): void  { self::loadTemplate('signup'); }

    public static function currentScript(): string
    {
        return basename($_SERVER['SCRIPT_NAME'], '.php');
    }

    public static function getCurrentPageIdentifier(): string
    {
        return $_GET['current_page'] ?? 'dashboard';
    }

    public static function countAllUsers(): int
    {
        $db     = Database::getConnection();
        $result = $db->query("SELECT COUNT(*) as count FROM `auth`")->fetch();
        return (int)$result['count'];
    }
}
