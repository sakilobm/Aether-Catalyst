<?php

namespace Aether;

use Exception;
use DateTime;
use RuntimeException;

/**
 * UserSession Class
 * =================
 * PSR-4 Namespace: Aether\UserSession
 */
class UserSession
{
    public array $data = [];
    public ?int $uid = null;
    public string $token;
    public $conn;

    public static function authenticate(string $user, string $password, ?string $fingerprint = null)
    {
        $username = User::login($user, $password);
        if (!$username) {
            return false;
        }

        $userObj = new User($username);
        $conn    = Database::getConnection();
        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $agent   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $token   = md5(random_int(0, 9_999_999) . $ip . $agent . time());

        $stmt = $conn->prepare(
            "INSERT INTO `session` (`uid`, `token`, `login_time`, `ip`, `user_agent`, `active`, `fingerprint`)
             VALUES (?, ?, NOW(), ?, ?, 1, ?)"
        );
        $stmt->bind_param('issss', $userObj->id, $token, $ip, $agent, $fingerprint);

        try {
            if ($stmt->execute()) {
                Session::set('session_token', $token);
                Session::set('fingerprint', $fingerprint);
                return $token;
            }
        } catch (Exception $e) {
            throw new RuntimeException("UserSession::authenticate() failed: " . $e->getMessage());
        }

        return false;
    }

    public static function authorize(string $token): self
    {
        $session = new self($token);

        $ip    = $_SERVER['REMOTE_ADDR']     ?? null;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (!$ip || !$agent) {
            throw new Exception("IP and User-Agent are required.");
        }
        if (!$session->isValid() || !$session->isActive()) {
            $session->removeSession();
            throw new Exception("Session expired or inactive.");
        }
        if ($ip !== $session->getIP()) {
            throw new Exception("IP mismatch.");
        }
        if ($agent !== $session->getUserAgent()) {
            throw new Exception("User-Agent mismatch.");
        }

        Session::$user = $session->getUser();
        return $session;
    }

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->conn  = Database::getConnection();

        $stmt = $this->conn->prepare(
            "SELECT * FROM `session` WHERE `token` = ? LIMIT 1"
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Session token is invalid.");
        }

        $this->data = $result->fetch_assoc();
        $this->uid  = (int)$this->data['uid'];
    }

    public function getUser(): User
    {
        return new User($this->uid);
    }

    public function isValid(): bool
    {
        if (!isset($this->data['login_time'])) {
            throw new RuntimeException("Session has no login_time.");
        }
        $loginTime = DateTime::createFromFormat('Y-m-d H:i:s', $this->data['login_time']);
        return (time() - $loginTime->getTimestamp()) < 86400;
    }

    public function isActive(): bool
    {
        return isset($this->data['active']) && (int)$this->data['active'] === 1;
    }

    public function getIP()             { return $this->data['ip'] ?? false; }
    public function getUserAgent()      { return $this->data['user_agent'] ?? false; }
    public function getFingerprint()    { return $this->data['fingerprint'] ?? false; }

    public function deactivate(): bool
    {
        $stmt = $this->conn->prepare("UPDATE `session` SET `active` = 0 WHERE `uid` = ?");
        $stmt->bind_param('i', $this->uid);
        return $stmt->execute();
    }

    public function removeSession(): bool
    {
        if (!isset($this->data['id'])) { return false; }
        $id   = (int)$this->data['id'];
        $stmt = $this->conn->prepare("DELETE FROM `session` WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
