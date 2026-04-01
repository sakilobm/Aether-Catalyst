<?php

namespace Aether;

use Exception;
use Closure;

/**
 * API Class
 * =========
 * PSR-4 Namespace: Aether\API
 */
class API extends REST
{
    private $current_call;

    // Middleware stack
    protected array $middleware = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add middleware to the global stack.
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Execute the middleware pipeline and then the API action.
     */
    public function processApi(): void
    {
        if (!isset($_REQUEST['rquest'])) {
            $this->response($this->json(['error' => 'no_request_specified']), 400);
        }

        $request = strtolower(trim($_REQUEST['rquest']));
        $func    = basename($request);

        // Run middleware pipeline before any action
        $this->runMiddlewarePipeline(function() use ($func) {
            $this->dispatch($func);
        });
    }

    /**
     * Dispatch the actual API logic.
     */
    private function dispatch(string $func): void
    {
        if (!isset($_GET['namespace']) && method_exists($this, $func)) {
            $this->$func();
        } elseif (isset($_GET['namespace'])) {
            $namespace = basename($_GET['namespace']);
            $file      = $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . 'libs/api/' . $namespace . '/' . $func . '.php';

            if (file_exists($file)) {
                include $file;
                if (isset($$func) && ($$func instanceof Closure || is_callable($$func))) {
                    $this->current_call = Closure::bind($$func, $this, get_class($this));
                    ($this->current_call)();
                } else {
                    $this->response($this->json(['error' => 'handler_not_defined']), 500);
                }
            } else {
                $this->response($this->json(['error' => 'method_not_found']), 404);
            }
        } else {
            $this->response($this->json(['error' => 'method_not_found']), 404);
        }
    }

    /**
     * Pipeline handler using a recursive closure.
     */
    private function runMiddlewarePipeline(callable $destination): void
    {
        $pipeline = array_reverse($this->middleware);
        $next = $destination;

        foreach ($pipeline as $middleware) {
            $next = function() use ($middleware, $next) {
                return $middleware($this, $next);
            };
        }

        $next();
    }

    public function isAuthenticated(): bool { return Session::isAuthenticated(); }

    public function paramsExists($params): bool
    {
        foreach ((array)$params as $param) {
            if (!array_key_exists($param, $this->_request)) return false;
        }
        return true;
    }

    public function die(Exception $e): void
    {
        $this->response($this->json(['error' => $e->getMessage()]), 400);
    }

    public function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
