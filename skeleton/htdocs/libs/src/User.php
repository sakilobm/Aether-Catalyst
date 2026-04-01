<?php

namespace Aether;

use Aether\Traits\SQLGetterSetter;
use Exception;

/**
 * User Class
 * ==========
 * PSR-4 Namespace: Aether\User
 */
class User
{
    use SQLGetterSetter;

    public int $id;
    public string $username;
    public string $table = 'auth';
    public $conn;

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
        } catch (Exception $e) {
            error_log('User::signup() error: ' . $e->getMessage());
            return false;
        }
    }

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
                return false;
            }
            if ((int)$row['active'] === 0) {
                return false;
            }
            if (password_verify($password, $row['password'])) {
                return $row['username'];
            }
        }

        return false;
    }

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
            throw new Exception("User '{$identifier}' does not exist.");
        }

        $row            = $result->fetch_assoc();
        $this->id       = (int)$row['id'];
        $this->username = $row['username'];
    }
}
