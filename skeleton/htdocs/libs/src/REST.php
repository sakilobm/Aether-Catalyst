<?php

namespace Aether;

/**
 * REST Class
 * ==========
 * PSR-4 Namespace: Aether\REST
 */
class REST
{
    public array $_request = [];
    public string $_content_type = 'application/json';
    private int $_code = 200;

    public function __construct() { $this->inputs(); }

    public function response(string $data, int $status): void
    {
        $this->_code = $status;
        $this->setHeaders();
        echo $data;
        exit;
    }

    public function get_request_method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }

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

    private function cleanInputs($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanInputs'], $data);
        }
        return trim(strip_tags($data));
    }

    private function setHeaders(): void
    {
        $messages = [
            200 => 'OK', 201 => 'Created', 204 => 'No Content',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found', 405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];

        $msg = $messages[$this->_code] ?? 'Unknown';
        header("HTTP/1.1 {$this->_code} {$msg}");
        header("Content-Type: {$this->_content_type}");
    }
}
