<?php

/**
 * SQLGetterSetter Trait
 * =====================
 * Magic getter/setter for any ORM-style class.
 *
 * Requirements — the using class constructor MUST set:
 *   $this->id    — The DB row primary key
 *   $this->conn  — The MySQLi connection
 *   $this->table — The table name (string)
 *
 * Usage:
 *   $post->getTitle();           // SELECT `title` FROM `posts` WHERE `id` = ...
 *   $post->setTitle('New');      // UPDATE `posts` SET `title` = 'New' WHERE `id` = ...
 *   $post->setLikeCount('INCREMENT');  // Atomic increment
 *   $post->setLikeCount('DECREMENT'); // Atomic decrement
 *   $post->delete();             // DELETE FROM `table` WHERE `id` = ...
 */
trait SQLGetterSetter
{
    /**
     * Magic method: automatically maps getXxx() / setXxx() to SQL SELECT / UPDATE.
     * CamelCase method names are converted to snake_case column names.
     *
     * @param string $name      Method name (e.g. getFirstName, setIsActive)
     * @param array  $arguments Method arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // Convert CamelCase to snake_case column name
        $column = preg_replace('/\B([A-Z])/', '_$1', substr($name, 3));
        $column = strtolower(preg_replace('/[^0-9a-zA-Z_]/', '', $column));

        if (str_starts_with($name, 'get')) {
            return $this->_get_data($column);
        } elseif (str_starts_with($name, 'set')) {
            return $this->_set_data($column, $arguments[0]);
        } else {
            throw new \BadMethodCallException(static::class . "::__call() → {$name} is undefined.");
        }
    }

    /**
     * SELECT a single column for this row.
     *
     * @param string $var Column name
     * @return mixed Column value or false
     */
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

        error_log(static::class . "::_get_data() → column '{$var}' not found for id={$this->id}");
        return false;
    }

    /**
     * UPDATE a single column for this row.
     * Pass 'INCREMENT' or 'DECREMENT' for atomic counter operations.
     *
     * @param string $var  Column name
     * @param mixed  $data New value, 'INCREMENT', or 'DECREMENT'
     * @return bool
     */
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
            throw new \RuntimeException(static::class . "::_set_data() → {$var}: " . $e->getMessage());
        }
    }

    /**
     * DELETE this row from the database.
     *
     * @return bool
     */
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
