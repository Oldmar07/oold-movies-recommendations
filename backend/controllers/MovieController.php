<?php
// ============================================================
//  Controller — MovieController
// ============================================================

namespace Controllers;

use Core\Middleware;
use Core\Response;
use Models\Rating;
use Models\Watchlist;
use Models\Favorite;
use Services\TmdbService;

class MovieController
{
    private TmdbService $tmdb;
    private Rating      $ratingModel;
    private Watchlist   $watchlistModel;
    private Favorite    $favoriteModel;

    public function __construct()
    {
        $this->tmdb           = new TmdbService();
        $this->ratingModel    = new Rating();
        $this->watchlistModel = new Watchlist();
        $this->favoriteModel  = new Favorite();
    }

    // ----------------------------------------------------------
    //  Descoberta de filmes
    // ----------------------------------------------------------

    // GET /movies/popular
    public function popular(): void
    {
        $lang = $_GET['lang'] ?? 'pt-BR';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->getPopular($page, $lang));
    }

    // GET /movies/top-rated
    public function topRated(): void
    {
        $lang = $_GET['lang'] ?? 'pt-BR';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->getTopRated($page, $lang));
    }

    // GET /movies/now-playing
    public function nowPlaying(): void
    {
        $lang = $_GET['lang'] ?? 'pt-BR';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->getNowPlaying($page, $lang));
    }

    // GET /movies/upcoming
    public function upcoming(): void
    {
        $lang = $_GET['lang'] ?? 'pt-BR';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->getUpcoming($page, $lang));
    }

    // GET /movies/search?q=...
    public function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        if (empty($query)) Response::error('Parâmetro "q" é obrigatório.', 422);

        $lang = $_GET['lang'] ?? 'pt-BR';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->searchMovies($query, $page, $lang));
    }

    // GET /movies/genres
    public function genres(): void
    {
        $lang = $_GET['lang'] ?? 'pt-BR';
        Response::json($this->tmdb->getGenres($lang));
    }

    // GET /movies/by-genre/:genreId
    public function byGenre(array $params): void
    {
        $genreId = (int) $params['genreId'];
        $lang    = $_GET['lang'] ?? 'pt-BR';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->getByGenre($genreId, $page, $lang));
    }

    // GET /movies/:id
    public function details(array $params): void
    {
        $tmdbId = (int) $params['id'];
        $lang   = $_GET['lang'] ?? 'pt-BR';
        $data   = $this->tmdb->getMovieDetails($tmdbId, $lang);

        // Anexa nota média da comunidade interna
        $data['internal_avg_score'] = $this->ratingModel->getAverageScore($tmdbId);

        Response::json($data);
    }

    // GET /movies/:id/recommendations
    public function recommendations(array $params): void
    {
        $tmdbId = (int) $params['id'];
        $lang   = $_GET['lang'] ?? 'pt-BR';
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        Response::json($this->tmdb->getRecommendations($tmdbId, $page, $lang));
    }

    // ----------------------------------------------------------
    //  Recomendações personalizadas (requer auth)
    // ----------------------------------------------------------

    // GET /movies/for-you
    public function forYou(): void
    {
        $payload = Middleware::auth();
        $lang    = $_GET['lang'] ?? 'pt-BR';
        $movies  = $this->tmdb->getPersonalizedRecommendations((int) $payload['sub'], $lang);
        Response::json(['results' => $movies]);
    }

    // ----------------------------------------------------------
    //  Ratings
    // ----------------------------------------------------------

    // GET /movies/:id/rating
    public function getRating(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $rating  = $this->ratingModel->get((int) $payload['sub'], $tmdbId);
        Response::json($rating ?? (object)[]);
    }

    // POST /movies/:id/rating
    public function upsertRating(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $body    = $this->json();
        $score   = (int) ($body['score'] ?? 0);
        $review  = $body['review'] ?? null;

        if ($score < 1 || $score > 10) {
            Response::error('Nota deve ser entre 1 e 10.', 422);
        }

        $this->ratingModel->upsert((int) $payload['sub'], $tmdbId, $score, $review);
        $this->updateGenreWeights((int) $payload['sub'], $tmdbId, $score);

        Response::success(null, 'Avaliação guardada.');
    }

    // DELETE /movies/:id/rating
    public function deleteRating(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $this->ratingModel->delete((int) $payload['sub'], $tmdbId);
        Response::success(null, 'Avaliação removida.');
    }

    // GET /user/ratings
    public function myRatings(): void
    {
        $payload = Middleware::auth();
        Response::json($this->ratingModel->getByUser((int) $payload['sub']));
    }

    // ----------------------------------------------------------
    //  Watchlist
    // ----------------------------------------------------------

    // GET /user/watchlist
    public function myWatchlist(): void
    {
        $payload = Middleware::auth();
        $watched = isset($_GET['watched']) ? (bool) $_GET['watched'] : null;
        Response::json($this->watchlistModel->getByUser((int) $payload['sub'], $watched));
    }

    // POST /movies/:id/watchlist
    public function addToWatchlist(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $this->watchlistModel->add((int) $payload['sub'], $tmdbId);
        Response::success(null, 'Adicionado à watchlist.');
    }

    // PATCH /movies/:id/watchlist/watched
    public function markWatched(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $this->watchlistModel->markWatched((int) $payload['sub'], $tmdbId);
        Response::success(null, 'Marcado como assistido.');
    }

    // DELETE /movies/:id/watchlist
    public function removeFromWatchlist(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $this->watchlistModel->remove((int) $payload['sub'], $tmdbId);
        Response::success(null, 'Removido da watchlist.');
    }

    // ----------------------------------------------------------
    //  Favoritos
    // ----------------------------------------------------------

    // GET /user/favorites
    public function myFavorites(): void
    {
        $payload = Middleware::auth();
        Response::json($this->favoriteModel->getByUser((int) $payload['sub']));
    }

    // POST /movies/:id/favorite
    public function toggleFavorite(array $params): void
    {
        $payload = Middleware::auth();
        $tmdbId  = (int) $params['id'];
        $action  = $this->favoriteModel->toggle((int) $payload['sub'], $tmdbId);
        Response::success(['action' => $action], $action === 'added' ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.');
    }

    // ----------------------------------------------------------
    //  Helpers privados
    // ----------------------------------------------------------

    /**
     * Aumenta o peso dos géneros do filme avaliado positivamente.
     * Isto alimenta o algoritmo de recomendação personalizada.
     */
    private function updateGenreWeights(int $userId, int $tmdbId, int $score): void
    {
        $db = \Core\Database::getInstance();

        $movie = $db->query(
            'SELECT genres FROM movies_cache WHERE tmdb_id = ?',
            [$tmdbId]
        )->fetch();

        if (!$movie || !$movie['genres']) return;

        $genres = json_decode($movie['genres'], true);
        $delta  = ($score >= 7) ? 0.5 : (($score <= 4) ? -0.3 : 0);

        if ($delta === 0) return;

        foreach ($genres as $genre) {
            $db->query(
                'INSERT INTO genre_preferences (user_id, genre_id, genre_name, weight)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE weight = GREATEST(0, LEAST(10, weight + ?))',
                [$userId, $genre['id'], $genre['name'], max(1, 1 + $delta), $delta]
            );
        }
    }

    private function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
