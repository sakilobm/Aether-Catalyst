<?php

namespace Aether;

use Exception;
use Dotenv\Dotenv;

/**
 * WebAPI Bootstrap Class
 * =====================
 * PSR-4 Namespace: Aether\WebAPI
 */
class WebAPI
{
    public function __construct()
    {
        global $__site_config;

        $configPath = $this->resolveConfigPath();

        // 1. Load config.json file (Legacy support)
        if ($configPath && file_exists($configPath)) {
            $__site_config = file_get_contents($configPath);
        }

        // 2. Initialize Dotenv (.env validation layer)
        $dotenvRoot = realpath(__DIR__ . '/../../../'); // Root project dir (where .env lives)
        if (file_exists($dotenvRoot . '/.env')) {
            $dotenv = Dotenv::createImmutable($dotenvRoot);
            $dotenv->load();

            // Strict Validation Layer: Ensure critical DB credentials exist
            $dotenv->required(['DB_HOST', 'DB_USER', 'DB_NAME'])->notEmpty();
        }

        // Establish DB connection early
        Database::getConnection();

        // Secure sessions
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path'     => $cookieParams['path'],
            'domain'   => $cookieParams['domain'],
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public function initiateSession(): void
    {
        Session::start();

        if (Session::isset('session_token')) {
            try {
                Session::$usersession = UserSession::authorize(Session::get('session_token'));
            } catch (Exception $e) {
                Session::delete('session_token');
            }
        }
    }

    private function resolveConfigPath(): ?string
    {
        $candidates = [
            __DIR__ . '/../../../project/config.json',
            $_SERVER['DOCUMENT_ROOT'] . '/../config.json',
            $_SERVER['DOCUMENT_ROOT'] . '/config.json',
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
