<?php

namespace Aether\Traits;

use Aether\Database;

/**
 * SQLGetterSetter Trait
 * =====================
 * PSR-4 Namespace: Aether\Traits\SQLGetterSetter
 */
trait SQLGetterSetter
{
    /**
     * Magic method: automatically maps getXxx() / setXxx() to SQL SELECT / UPDATE.
     */
    public function __call(string $name, array $arguments)
    {
        $column = preg_replace('/\B([A-Z])/', '_$1', substr($name, 3));
        $column = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '', $column));

        if (str_starts_with($name, 'get')) {
            return $this->_get_data($column);
        } elseif (str_starts_with($name, 'set')) {
            return $this->_set_data($column, $arguments[0]);
        } else {
            throw new \BadMethodCallException(static::class . "::__call() -> {$name} is undefined.");
        }
    }

    private function _get_data(string $var)
    {
        if (!$this->conn) {
            $this->conn = Database::getConnection();
        }

        $stmt = $this->conn->prepare("SELECT `{$var}` FROM `{$this->table}` WHERE `id` = ? LIMIT 1");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            return $result->fetch_assoc()[$var];
        }

        return false;
    }

    private function _set_data(string $var, $data): bool
    {
        if (!$this->conn) {
            $this->conn = Database::getConnection();
        }

        try {
            if ($data === 'INCREMENT') {
                $sql = "UPDATE `{$this->table}` SET `{$var}` = `{$var}` + 1 WHERE `id` = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $this->id);
            } elseif ($data === 'DECREMENT') {
                $sql = "UPDATE `{$this->table}` SET `{$var}` = `{$var}` - 1 WHERE `id` = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $this->id);
            } else {
                $sql = "UPDATE `{$this->table}` SET `{$var}` = ? WHERE `id` = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('si', $data, $this->id);
            }

            return $stmt->execute();
        } catch (\Exception $e) {
            throw new \RuntimeException(static::class . "::_set_data() -> {$var}: " . $e->getMessage());
        }
    }

    public function delete(): bool
    {
        if (!$this->conn) {
            $this->conn = Database::getConnection();
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM `{$this->table}` WHERE `id` = ?");
            $stmt->bind_param('i', $this->id);
            return $stmt->execute();
        } catch (\Exception $e) {
            throw new \RuntimeException(static::class . "::delete() failed: " . $e->getMessage());
        }
    }
}
