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
        return $this->request('/search/movie', [
            'query'    => $query,
            'page'     => $page,
            'language' => $lang,
        ]);
    }

    public function getPopular(int $page = 1, string $lang = 'pt-BR'): array
    {
        return $this->request('/movie/popular', ['page' => $page, 'language' => $lang]);
    }

    public function getTopRated(int $page = 1, string $lang = 'pt-BR'): array
    {
        return $this->request('/movie/top_rated', ['page' => $page, 'language' => $lang]);
    }

    public function getNowPlaying(int $page = 1, string $lang = 'pt-BR'): array
    {
        return $this->request('/movie/now_playing', ['page' => $page, 'language' => $lang]);
    }

    public function getUpcoming(int $page = 1, string $lang = 'pt-BR'): array
    {
        return $this->request('/movie/upcoming', ['page' => $page, 'language' => $lang]);
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
        return $this->request('/discover/movie', [
            'with_genres' => $genreId,
            'page'        => $page,
            'language'    => $lang,
            'sort_by'     => 'popularity.desc',
        ]);
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
            return [];
        }

        return json_decode($raw, true) ?? [];
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

        $genres = isset($movie['genres']) ? json_encode($movie['genres']) : null;

        $db->query(
            'INSERT INTO movies_cache
                (tmdb_id, title, original_title, overview, poster_path, backdrop_path,
                 release_date, genres, vote_average, vote_count, popularity, cached_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?, NOW())
             ON DUPLICATE KEY UPDATE
                title=VALUES(title), overview=VALUES(overview),
                vote_average=VALUES(vote_average), vote_count=VALUES(vote_count),
                cached_at=NOW()',
            [
                $movie['id'],
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
