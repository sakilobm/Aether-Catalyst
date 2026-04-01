<?php

/**
 * Login API Endpoint
 * ==================
 * POST /api/auth/login
 *
 * Required POST params: user, password
 * Optional COOKIE:      fingerprint (from FingerprintJS)
 *
 * Response 200: { "message": "Authenticated", "token": "..." }
 * Response 400: Bad request
 * Response 401: Wrong credentials
 */

$login = function () {
    if ($this->paramsExists(['user', 'password'])) {
        $user        = $this->_request['user'];
        $password    = $this->_request['password'];
        $fingerprint = $_COOKIE['fingerprint'] ?? null;

        $token = UserSession::authenticate($user, $password, $fingerprint);

        if ($token) {
            $this->response($this->json([
                'message' => 'Authenticated',
                'token'   => $token,
            ]), 200);
        } else {
            $this->response($this->json([
                'message' => 'Invalid credentials',
            ]), 401);
        }
    } else {
        $this->response($this->json(['message' => 'Bad request — missing user or password']), 400);
    }
};
