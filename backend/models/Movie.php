<?php
// ============================================================
//  Model — Rating
// ============================================================

namespace Models;

use Core\Database;

class Rating
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByUser(int $userId): array
    {
        return $this->db->query(
            'SELECT r.id, r.user_id, r.tmdb_id, r.score, r.review, r.created_at, r.updated_at,
                    COALESCE(NULLIF(mc.title, \'\'), CONCAT(\'Filme #\', r.tmdb_id)) AS title,
                    mc.poster_path, mc.release_date
             FROM ratings r
             LEFT JOIN movies_cache mc ON mc.tmdb_id = r.tmdb_id
             WHERE r.user_id = ?
             ORDER BY r.updated_at DESC',
            [$userId]
        )->fetchAll();
    }

    public function get(int $userId, int $tmdbId): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM ratings WHERE user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId]
        )->fetch();
        return $row ?: null;
    }

    public function upsert(int $userId, int $tmdbId, int $score, ?string $review): void
    {
        $this->db->query(
            'INSERT INTO ratings (user_id, tmdb_id, score, review)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE score=VALUES(score), review=VALUES(review), updated_at=NOW()',
            [$userId, $tmdbId, $score, $review]
        );
    }

    public function delete(int $userId, int $tmdbId): void
    {
        $this->db->query(
            'DELETE FROM ratings WHERE user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId]
        );
    }

    public function getAverageScore(int $tmdbId): float
    {
        $avg = $this->db->query(
            'SELECT AVG(score) FROM ratings WHERE tmdb_id = ?',
            [$tmdbId]
        )->fetchColumn();
        return round((float) $avg, 1);
    }
}


// ============================================================
//  Model — Watchlist
// ============================================================

namespace Models;

class Watchlist
{
    private \Core\Database $db;

    public function __construct()
    {
        $this->db = \Core\Database::getInstance();
    }

    public function getByUser(int $userId, ?bool $watched = null): array
    {
        $sql    = 'SELECT w.id, w.user_id, w.tmdb_id, w.watched, w.added_at, w.watched_at,
                          COALESCE(NULLIF(mc.title, \'\'), CONCAT(\'Filme #\', w.tmdb_id)) AS title,
                          mc.poster_path, mc.release_date, mc.vote_average
                   FROM watchlist w
                   LEFT JOIN movies_cache mc ON mc.tmdb_id = w.tmdb_id
                   WHERE w.user_id = ?';
        $params = [$userId];

        if ($watched !== null) {
            $sql     .= ' AND w.watched = ?';
            $params[] = (int) $watched;
        }

        return $this->db->query($sql . ' ORDER BY w.added_at DESC', $params)->fetchAll();
    }

    public function add(int $userId, int $tmdbId): void
    {
        $this->db->query(
            'INSERT IGNORE INTO watchlist (user_id, tmdb_id) VALUES (?,?)',
            [$userId, $tmdbId]
        );
    }

    public function markWatched(int $userId, int $tmdbId): void
    {
        $this->db->query(
            'UPDATE watchlist SET watched = 1, watched_at = NOW()
             WHERE user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId]
        );
    }

    public function remove(int $userId, int $tmdbId): void
    {
        $this->db->query(
            'DELETE FROM watchlist WHERE user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId]
        );
    }
}


// ============================================================
//  Model — Favorite
// ============================================================

namespace Models;

class Favorite
{
    private \Core\Database $db;

    public function __construct()
    {
        $this->db = \Core\Database::getInstance();
    }

    public function getByUser(int $userId): array
    {
        return $this->db->query(
            'SELECT f.id, f.user_id, f.tmdb_id, f.added_at,
                    COALESCE(NULLIF(mc.title, \'\'), CONCAT(\'Filme #\', f.tmdb_id)) AS title,
                    mc.poster_path, mc.release_date, mc.vote_average
             FROM favorites f
             LEFT JOIN movies_cache mc ON mc.tmdb_id = f.tmdb_id
             WHERE f.user_id = ?
             ORDER BY f.added_at DESC',
            [$userId]
        )->fetchAll();
    }

    public function toggle(int $userId, int $tmdbId): string
    {
        $exists = $this->db->query(
            'SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId]
        )->fetch();

        if ($exists) {
            $this->db->query(
                'DELETE FROM favorites WHERE user_id = ? AND tmdb_id = ?',
                [$userId, $tmdbId]
            );
            return 'removed';
        }

        $this->db->query(
            'INSERT INTO favorites (user_id, tmdb_id) VALUES (?,?)',
            [$userId, $tmdbId]
        );
        return 'added';
    }

    public function remove(int $userId, int $tmdbId): void
    {
        $this->db->query(
            'DELETE FROM favorites WHERE user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId]
        );
    }
}
