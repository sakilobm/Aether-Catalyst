<?php
/**
 * error.php — Generic 404/500 Error Page
 */
require_once 'libs/load.php';
http_response_code(404);
Session::$isError = true;
Session::renderPage();
