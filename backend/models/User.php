<?php
// ============================================================
//  Model — User
// ============================================================

namespace Models;

use Core\Database;

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT id, name, email, role, avatar_url, preferred_lang, theme, created_at
             FROM users WHERE id = ?',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM users WHERE email = ?',
            [$email]
        )->fetch();

        return $row ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $this->db->query(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)',
            [$name, $email, $passwordHash, $role]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, array $fields): bool
    {
        $allowed = ['name', 'avatar_url', 'preferred_lang', 'theme'];
        $set     = [];
        $values  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $fields)) {
                $set[]    = "$field = ?";
                $values[] = $fields[$field];
            }
        }

        if (empty($set)) return false;

        $values[] = $id;
        $this->db->query(
            'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?',
            $values
        );
        return true;
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->db->query(
            'UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?',
            [$hash, $id]
        );
    }

    public function setResetToken(int $id, string $token): void
    {
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
        $this->db->query(
            'UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?',
            [$token, $expires, $id]
        );
    }

    public function findByResetToken(string $token): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()',
            [$token]
        )->fetch();

        return $row ?: null;
    }

    public function getAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $items  = $this->db->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        )->fetchAll();

        $total = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public function deleteById(int $id): void
    {
        $this->db->query('DELETE FROM users WHERE id = ?', [$id]);
    }
}
