<?php
// ============================================================
//  Service — TmdbService  (integração com TMDB API)
// ============================================================

namespace Services;

use Core\Database;

class TmdbService
{
    private ?Database $db = null;

    // ----------------------------------------------------------
    //  Métodos públicos
    // ----------------------------------------------------------

    public function searchMovies(string $query, int $page = 1, string $lang = 'pt-BR'): array
    {
        $data = $this->request('/search/movie', [
            'query'    => $query,
            'page'     => $page,
            'language' => $lang,
        ]);
        $this->cacheResults($data);
        return $data;
    }

    public function getPopular(int $page = 1, string $lang = 'pt-BR'): array
    {
        $data = $this->request('/movie/popular', ['page' => $page, 'language' => $lang]);
        $this->cacheResults($data);
        return $data;
    }

    public function getTopRated(int $page = 1, string $lang = 'pt-BR'): array
    {
        $data = $this->request('/movie/top_rated', ['page' => $page, 'language' => $lang]);
        $this->cacheResults($data);
        return $data;
    }

    public function getNowPlaying(int $page = 1, string $lang = 'pt-BR'): array
    {
        $data = $this->request('/movie/now_playing', ['page' => $page, 'language' => $lang]);
        $this->cacheResults($data);
        return $data;
    }

    public function getUpcoming(int $page = 1, string $lang = 'pt-BR'): array
    {
        $data = $this->request('/movie/upcoming', ['page' => $page, 'language' => $lang]);
        $this->cacheResults($data);
        return $data;
    }

    public function getMovieDetails(int $tmdbId, string $lang = 'pt-BR'): array
    {
        $data = $this->request("/movie/{$tmdbId}", [
            'language'          => $lang,
            'append_to_response'=> 'credits,videos,similar,recommendations',
        ]);

        // Guarda no cache
        if (!empty($data)) {
            $this->cacheMovie($data);
        }

        return $data;
    }

    public function getRecommendations(int $tmdbId, int $page = 1, string $lang = 'pt-BR'): array
    {
        return $this->request("/movie/{$tmdbId}/recommendations", [
            'page'     => $page,
            'language' => $lang,
        ]);
    }

    public function getSimilar(int $tmdbId, int $page = 1, string $lang = 'pt-BR'): array
    {
        return $this->request("/movie/{$tmdbId}/similar", [
            'page'     => $page,
            'language' => $lang,
        ]);
    }

    public function getByGenre(int $genreId, int $page = 1, string $lang = 'pt-BR'): array
    {
        $data = $this->request('/discover/movie', [
            'with_genres' => $genreId,
            'page'        => $page,
            'language'    => $lang,
            'sort_by'     => 'popularity.desc',
        ]);
        $this->cacheResults($data);
        return $data;
    }

    public function getGenres(string $lang = 'pt-BR'): array
    {
        return $this->request('/genre/movie/list', ['language' => $lang]);
    }

    /**
     * Recomendações personalizadas baseadas nos géneros preferidos do utilizador
     * e nos filmes melhor avaliados por ele.
     */
    public function getPersonalizedRecommendations(int $userId, string $lang = 'pt-BR'): array
    {
        // 1. Géneros preferidos do utilizador
        $genres = $this->db()->query(
            'SELECT genre_id, weight FROM genre_preferences WHERE user_id = ? ORDER BY weight DESC LIMIT 3',
            [$userId]
        )->fetchAll();

        // 2. Filmes mais bem avaliados pelo utilizador
        $topRated = $this->db()->query(
            'SELECT tmdb_id FROM ratings WHERE user_id = ? ORDER BY score DESC LIMIT 3',
            [$userId]
        )->fetchAll();

        $results = [];

        // Recomendações baseadas em géneros
        foreach ($genres as $genre) {
            $data = $this->getByGenre((int) $genre['genre_id'], 1, $lang);
            if (!empty($data['results'])) {
                $results = array_merge($results, array_slice($data['results'], 0, 5));
            }
        }

        // Recomendações baseadas em filmes similares
        foreach ($topRated as $movie) {
            $data = $this->getRecommendations((int) $movie['tmdb_id'], 1, $lang);
            if (!empty($data['results'])) {
                $results = array_merge($results, array_slice($data['results'], 0, 5));
            }
        }

        // Remove duplicados e filmes já avaliados
        $ratedIds = array_column($topRated, 'tmdb_id');
        $seen     = [];
        $unique   = [];

        foreach ($results as $movie) {
            $id = $movie['id'];
            if (!in_array($id, $ratedIds) && !isset($seen[$id])) {
                $seen[$id] = true;
                $unique[]  = $movie;
            }
        }

        // Fallback: se não há dados suficientes, retorna populares
        if (count($unique) < 5) {
            $popular = $this->getPopular(1, $lang);
            $unique  = $popular['results'] ?? [];
        }

        return array_slice($unique, 0, 20);
    }

    // ----------------------------------------------------------
    //  HTTP
    // ----------------------------------------------------------

    private function request(string $endpoint, array $params = []): array
    {
        ksort($params);
        $cacheFile = $this->requestCacheFile($endpoint, $params);
        $cached = $this->readRequestCache($cacheFile, false);
        if ($cached !== null) {
            return $cached;
        }

        $params['api_key'] = TMDB_API_KEY;
        $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header'  => 'Accept: application/json',
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) {
            error_log("[TMDB] Request failed: $url");
            return $this->readRequestCache($cacheFile, true) ?? [];
        }

        $data = json_decode($raw, true) ?? [];
        if (!empty($data)) {
            $this->writeRequestCache($cacheFile, $data);
        }

        return $data;
    }

    private function requestCacheFile(string $endpoint, array $params): string
    {
        $key = hash('sha256', $endpoint . '?' . http_build_query($params));
        return dirname(__DIR__) . "/cache/tmdb/{$key}.json";
    }

    private function readRequestCache(string $file, bool $allowExpired): ?array
    {
        if (!is_file($file)) {
            return null;
        }

        if (!$allowExpired && filemtime($file) + TMDB_CACHE_TTL < time()) {
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function writeRequestCache(string $file, array $data): void
    {
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    // ----------------------------------------------------------
    //  Cache
    // ----------------------------------------------------------

    private function cacheMovie(array $movie): void
    {
        try {
            $db = $this->db();
        } catch (\Throwable $e) {
            error_log('[TMDB] Cache skipped: ' . $e->getMessage());
            return;
        }

        // Garante que temos um ID válido e evita sobrescrever cache completo com dados parciais
        $tmdbId = $movie['id'] ?? $movie['tmdb_id'] ?? null;
        if (!$tmdbId) return;

        $genres = isset($movie['genres']) ? json_encode($movie['genres']) : null;

        $db->query(
            'INSERT INTO movies_cache
                (tmdb_id, title, original_title, overview, poster_path, backdrop_path,
                 release_date, genres, vote_average, vote_count, popularity, cached_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW())
             ON DUPLICATE KEY UPDATE
                title=VALUES(title),
                original_title=VALUES(original_title),
                overview=VALUES(overview),
                poster_path=VALUES(poster_path),
                backdrop_path=VALUES(backdrop_path),
                release_date=VALUES(release_date),
                genres=VALUES(genres),
                vote_average=VALUES(vote_average),
                vote_count=VALUES(vote_count),
                popularity=VALUES(popularity),
                cached_at=NOW()',
            [
                $tmdbId,
                $movie['title'] ?? '',
                $movie['original_title'] ?? null,
                $movie['overview'] ?? null,
                $movie['poster_path'] ?? null,
                $movie['backdrop_path'] ?? null,
                $movie['release_date'] ?? null,
                $genres,
                $movie['vote_average'] ?? null,
                $movie['vote_count'] ?? null,
                $movie['popularity'] ?? null,
            ]
        );
    }

    private function cacheResults(array $data): void
    {
        if (!empty($data['results'])) {
            foreach ($data['results'] as $movie) {
                $this->cacheMovie($movie);
            }
        }
    }

    public static function posterUrl(string $path, string $size = 'w500'): string
    {
        return TMDB_IMAGE_URL . "/{$size}{$path}";
    }

    private function db(): Database
    {
        if (!$this->db) {
            $this->db = Database::getInstance();
        }

        return $this->db;
    }
}
