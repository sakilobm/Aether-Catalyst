<?php

error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/REST.class.php';

/**
 * API Class
 * =========
 * The single entry point for all AJAX/API calls via api.php.
 *
 * Routing strategy (from .htaccess):
 *   /api/{method}            → api.php?rquest={method}
 *   /api/{namespace}/{method} → api.php?rquest={method}&namespace={namespace}
 *
 * Dispatch flow:
 *   1. No namespace + method exists on this class → call it directly
 *   2. With namespace → include libs/api/{namespace}/{method}.php
 *      The file must define a closure: ${basename(__FILE__, '.php')} = function() { ... };
 *      which is then bound to $this and called.
 *
 * Usage in api.php:
 *   $api = new API();
 *   try { $api->processApi(); } catch (Exception $e) { $api->die($e); }
 *
 * Usage in API endpoint files (e.g. libs/api/auth/login.php):
 *   $login = function() {
 *       $token = UserSession::authenticate(...);
 *       $this->response($this->json(['token' => $token]), 200);
 *   };
 */
class API extends REST
{
    /** @var \Closure|null The currently dispatched closure from a namespace file */
    private $current_call;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Primary dispatch method. Call once in api.php.
     *
     * Resolves the ?rquest= parameter and routes to either:
     *   a) A private method in this class, or
     *   b) An external file in libs/api/{namespace}/{method}.php
     */
    public function processApi(): void
    {
        if (!isset($_REQUEST['rquest'])) {
            $this->response($this->json(['error' => 'no_request_specified']), 400);
        }

        $request = strtolower(trim($_REQUEST['rquest']));
        $func    = basename($request); // sanitize path traversal

        if (!isset($_GET['namespace']) && method_exists($this, $func)) {
            // Direct method on this class
            $this->$func();
        } elseif (isset($_GET['namespace'])) {
            $namespace = basename($_GET['namespace']); // sanitize
            $dir       = $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . 'libs/api/' . $namespace;
            $file      = $dir . '/' . $func . '.php';

            if (file_exists($file)) {
                include $file;

                if (!isset($$func) || !($func instanceof \Closure || is_callable($$func))) {
                    $this->response($this->json(['error' => 'handler_not_defined']), 500);
                }

                // Bind the closure to $this so it can call $this->response(), etc.
                $this->current_call = \Closure::bind($$func, $this, get_class($this));
                ($this->current_call)();
            } else {
                $this->response($this->json(['error' => 'method_not_found', 'path' => $file]), 404);
            }
        } else {
            $this->response($this->json(['error' => 'method_not_found']), 404);
        }
    }

    // ─── Auth Helpers (callable from endpoint closures via $this) ─────────

    /**
     * @return bool True if a valid session is active
     */
    public function isAuthenticated(): bool
    {
        return Session::isAuthenticated();
    }

    /**
     * Check that all required parameters exist in the request.
     *
     * @param string|string[] $params  Single key or array of keys
     * @return bool
     */
    public function paramsExists($params): bool
    {
        foreach ((array)$params as $param) {
            if (!array_key_exists($param, $this->_request)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the current session user is the authenticated user for $user.
     *
     * @param User $user
     * @return bool
     */
    public function isAuthenticatedFor(User $user): bool
    {
        $sessionUser = Session::getUser();
        return $sessionUser && $sessionUser->getEmail() === $user->getEmail();
    }

    /**
     * Get the current session user's username.
     *
     * @return string|false
     */
    public function getUsername()
    {
        $user = Session::getUser();
        return $user ? $user->getUsername() : false;
    }

    /**
     * Send a structured error response and exit.
     *
     * @param \Exception $e
     */
    public function die(\Exception $e): void
    {
        $code = 400;
        $msg  = $e->getMessage();

        if (in_array($msg, ['Expired token', 'Unauthorized', 'Forbidden'], true)) {
            $code = 403;
        } elseif ($msg === 'Not found') {
            $code = 404;
        }

        $this->response($this->json(['error' => $msg, 'type' => 'exception']), $code);
    }

    /**
     * Magic method — delegates to bound closure (namespace API calls).
     */
    public function __call(string $method, array $args)
    {
        if (is_callable($this->current_call)) {
            return call_user_func_array($this->current_call, $args);
        }
        $this->response($this->json(['error' => 'method_not_callable', 'method' => $method]), 404);
    }

    // ─── Built-in API methods (no namespace required) ─────────────────────

    /**
     * GET /api/ping → {"status":"ok"}
     * Health-check endpoint.
     */
    private function ping(): void
    {
        $this->response($this->json(['status' => 'ok', 'timestamp' => time()]), 200);
    }

    /**
     * GET /api/test → dump headers
     */
    private function test(): void
    {
        $this->response($this->json(getallheaders()), 200);
    }

    // ─── JSON Helper ──────────────────────────────────────────────────────

    /**
     * Encode an array to JSON. Returns "{}" on failure.
     *
     * @param array $data
     * @return string JSON string
     */
    public function json(array $data): string
    {
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? $encoded : '{}';
    }
}
