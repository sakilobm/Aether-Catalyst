<?php

/**
 * Signup API Endpoint
 * ===================
 * POST /api/auth/signup
 *
 * Required POST params: username, password, email_address, phone
 *
 * Response 200: { "message": "Successfully registered" }
 * Response 400: Missing params or registration error
 * Response 409: Username/email already taken
 */

$signup = function () {
    if ($this->paramsExists(['username', 'password', 'email_address', 'phone'])) {
        $username = $this->_request['username'];
        $password = $this->_request['password'];
        $email    = $this->_request['email_address'];
        $phone    = $this->_request['phone'];

        try {
            $result = User::signup($username, $password, $email, $phone);

            if ($result) {
                $this->response($this->json(['message' => 'Successfully registered']), 200);
            } else {
                $this->response($this->json(['message' => 'Registration failed — username or email may already exist']), 409);
            }
        } catch (Exception $e) {
            $this->response($this->json(['message' => 'Server error', 'detail' => $e->getMessage()]), 500);
        }
    } else {
        $this->response($this->json(['message' => 'Bad request — missing required fields']), 400);
    }
};
