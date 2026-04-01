<?php

/**
 * REST Class
 * ==========
 * Base HTTP handler. All request input is sanitized here.
 * Handles GET, POST, PUT, DELETE methods.
 * API extends this class.
 *
 * Available to subclasses:
 *   $this->_request  — sanitized input array (merged GET+POST for POST requests)
 *   $this->response($data, $statusCode) — send JSON response and exit
 *   $this->get_request_method() — 'GET', 'POST', 'PUT', 'DELETE'
 */
class REST
{
    /** @var array Sanitized request parameters */
    public array $_request = [];

    /** @var string Response content type */
    public string $_content_type = 'application/json';

    /** @var int HTTP response code */
    private int $_code = 200;

    public function __construct()
    {
        $this->inputs();
    }

    /**
     * Send HTTP response and exit.
     *
     * @param string $data   Already-encoded JSON string
     * @param int    $status HTTP status code
     */
    public function response(string $data, int $status): void
    {
        $this->_code = $status;
        $this->setHeaders();
        echo $data;
        exit;
    }

    /** @return string HTTP method in uppercase */
    public function get_request_method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** @return string|null HTTP Referer header */
    public function get_referer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /** @param string $type MIME type string */
    public function setContentType(string $type): void
    {
        $this->_content_type = $type;
    }

    // ─── Private ──────────────────────────────────────────────────────────

    /**
     * Parse and sanitize inputs based on HTTP method.
     */
    private function inputs(): void
    {
        switch ($this->get_request_method()) {
            case 'POST':
                $this->_request = $this->cleanInputs(array_merge($_GET, $_POST));
                break;
            case 'GET':
            case 'DELETE':
                $this->_request = $this->cleanInputs($_GET);
                break;
            case 'PUT':
            case 'PATCH':
                parse_str(file_get_contents('php://input'), $input);
                $this->_request = $this->cleanInputs($input);
                break;
            default:
                $this->response('', 406);
        }
    }

    /**
     * Recursively strip HTML tags and trim whitespace from input.
     *
     * @param mixed $data
     * @return mixed
     */
    private function cleanInputs($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanInputs'], $data);
        }
        return trim(strip_tags($data));
    }

    /**
     * Set HTTP response headers.
     */
    private function setHeaders(): void
    {
        $messages = [
            200 => 'OK', 201 => 'Created', 204 => 'No Content',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable',
            409 => 'Conflict', 422 => 'Unprocessable Entity',
            500 => 'Internal Server Error', 503 => 'Service Unavailable',
        ];

        $msg = $messages[$this->_code] ?? 'Unknown';
        header("HTTP/1.1 {$this->_code} {$msg}");
        header("Content-Type: {$this->_content_type}");
        header('X-Content-Type-Options: nosniff');
    }
}
