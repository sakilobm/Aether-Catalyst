<?php

/**
 * UserSession Class
 * =================
 * Token-based session authentication system.
 *
 * Flow:
 *   1. User logs in  → UserSession::authenticate($user, $pass, $fp) → returns $token
 *   2. Token stored  → Session::set('session_token', $token)
 *   3. Every request → WebAPI::initiateSession() calls UserSession::authorize($token)
 *   4. Check auth    → Session::isAuthenticated() → bool
 *
 * The `session` table schema:
 *   id, uid, token, login_time, ip, user_agent, active, fingerprint
 */
class UserSession
{
    /** @var array Raw row data from `session` table */
    public array $data = [];

    /** @var int|null User ID from session */
    public ?int $uid = null;

    /** @var string The token this session was constructed with */
    public string $token;

    /** @var \mysqli */
    public \mysqli $conn;

    // ─── Static Methods ────────────────────────────────────────────────────

    /**
     * Authenticate credentials and insert a new session token.
     *
     * @param string      $user        Username or email
     * @param string      $password    Plain-text password
     * @param string|null $fingerprint Browser fingerprint (from FingerprintJS)
     * @return string|false Session token on success, false on failure
     */
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
        } catch (\Exception $e) {
            throw new \RuntimeException("UserSession::authenticate() failed: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Authorize a token with 4-level security checks:
     *   1. IP and User-Agent must exist
     *   2. Session must be found (exists) and active
     *   3. IP must match
     *   4. User-Agent must match
     *   (5. Fingerprint check — currently relaxed/truthy)
     *
     * @param string $token Session token from cookie/session
     * @return UserSession Authorized session object
     * @throws \Exception On any authorization failure
     */
    public static function authorize(string $token): self
    {
        $session = new self($token);

        $ip    = $_SERVER['REMOTE_ADDR']     ?? null;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (!$ip || !$agent) {
            throw new \Exception("IP and User-Agent are required.");
        }
        if (!$session->isValid() || !$session->isActive()) {
            $session->removeSession();
            throw new \Exception("Session expired or inactive.");
        }
        if ($ip !== $session->getIP()) {
            throw new \Exception("IP mismatch.");
        }
        if ($agent !== $session->getUserAgent()) {
            throw new \Exception("User-Agent mismatch.");
        }

        Session::$user = $session->getUser();
        return $session;
    }

    // ─── Constructor ───────────────────────────────────────────────────────

    /**
     * Load a session row by token.
     *
     * @param string $token
     * @throws \Exception If token not found in DB
     */
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
            throw new \Exception("Session token is invalid.");
        }

        $this->data = $result->fetch_assoc();
        $this->uid  = (int)$this->data['uid'];
    }

    // ─── Instance Methods ──────────────────────────────────────────────────

    /**
     * Get the User object linked to this session.
     *
     * @return User
     */
    public function getUser(): User
    {
        return new User($this->uid);
    }

    /**
     * Check if the session is within the 24-hour validity window.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (!isset($this->data['login_time'])) {
            throw new \RuntimeException("Session has no login_time.");
        }
        $loginTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->data['login_time']);
        return (time() - $loginTime->getTimestamp()) < 86400; // 24 hours
    }

    /** @return bool */
    public function isActive(): bool
    {
        return isset($this->data['active']) && (int)$this->data['active'] === 1;
    }

    /** @return string|false */
    public function getIP()
    {
        return $this->data['ip'] ?? false;
    }

    /** @return string|false */
    public function getUserAgent()
    {
        return $this->data['user_agent'] ?? false;
    }

    /** @return mixed */
    public function getFingerprint()
    {
        return $this->data['fingerprint'] ?? false;
    }

    /**
     * Deactivate all sessions for this user (e.g., on logout from all devices).
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE `session` SET `active` = 0 WHERE `uid` = ?"
        );
        $stmt->bind_param('i', $this->uid);
        return $stmt->execute();
    }

    /**
     * Delete this specific session row.
     *
     * @return bool
     */
    public function removeSession(): bool
    {
        if (!isset($this->data['id'])) {
            return false;
        }
        $id   = (int)$this->data['id'];
        $stmt = $this->conn->prepare("DELETE FROM `session` WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
