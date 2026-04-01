<?php

include_once __DIR__ . '/../traits/SQLGetterSetter.trait.php';

/**
 * User Class
 * ==========
 * Handles auth table interactions: signup, login, and per-user data access.
 *
 * Usage:
 *   // Static — create a new user
 *   User::signup('johndoe', 'password123', 'john@example.com', '9876543210');
 *
 *   // Static — validate credentials, returns username on success
 *   $username = User::login('john@example.com', 'password123');
 *
 *   // Instance — access any column from `auth` table via magic getters
 *   $user = new User('johndoe');
 *   $email = $user->getEmail();
 *   $user->setPhone('1234567890');
 */
class User
{
    use SQLGetterSetter;

    /** @var int DB row ID */
    public int $id;

    /** @var string Username resolved at construction */
    public string $username;

    /** @var string Table name for SQLGetterSetter trait */
    public string $table = 'auth';

    /** @var \mysqli DB connection for SQLGetterSetter trait */
    public \mysqli $conn;

    /**
     * Register a new user. Passwords are bcrypt-hashed (cost=10).
     *
     * @param string $username
     * @param string $password  Plain-text password (hashed before insert)
     * @param string $email
     * @param string $phone
     * @return bool True on success
     */
    public static function signup(string $username, string $password, string $email, string $phone): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $conn = Database::getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO `auth` (`username`, `password`, `email`, `phone`, `active`) VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->bind_param('ssss', $username, $hash, $email, $phone);

        try {
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log('User::signup() error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate credentials. Returns the username on success, false on failure.
     *
     * @param string $user     Username OR email
     * @param string $password Plain-text password
     * @return string|false
     */
    public static function login(string $user, string $password)
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare(
            "SELECT `username`, `password`, `active`, `blocked` FROM `auth`
             WHERE `username` = ? OR `email` = ? LIMIT 1"
        );
        $stmt->bind_param('ss', $user, $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ((int)$row['blocked'] === 1) {
                return false; // account blocked
            }
            if ((int)$row['active'] === 0) {
                return false; // account inactive
            }
            if (password_verify($password, $row['password'])) {
                return $row['username'];
            }
        }

        return false;
    }

    /**
     * Construct a User object by username, email, or numeric ID.
     *
     * @param string|int $identifier
     * @throws \Exception If user not found
     */
    public function __construct($identifier)
    {
        $this->table = 'auth';
        $this->conn  = Database::getConnection();

        $stmt = $this->conn->prepare(
            "SELECT `id`, `username` FROM `auth`
             WHERE `username` = ? OR `email` = ? OR `id` = ? LIMIT 1"
        );
        $id = (int)$identifier;
        $stmt->bind_param('ssi', $identifier, $identifier, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new \Exception("User '{$identifier}' does not exist.");
        }

        $row            = $result->fetch_assoc();
        $this->id       = (int)$row['id'];
        $this->username = $row['username'];
    }
}
