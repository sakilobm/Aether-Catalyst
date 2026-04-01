<?php
/**
 * api.php — API Entry Point
 * All AJAX requests are routed here via .htaccess:
 *   RewriteRule ^/?api/([^/]+)?$ api.php?rquest=$1 [L,QSA,NC]
 *   RewriteRule ^/?api/([^/]+)/(.+)?$ api.php?rquest=$2&namespace=$1 [L,QSA,NC]
 */
require_once 'libs/load.php';

$api = new API();
try {
    $api->processApi();
} catch (Exception $e) {
    $api->die($e);
}
