<?php
// ============================================================
//  Entry Point — index.php
//  Todos os pedidos HTTP passam por aqui via .htaccess
// ============================================================

declare(strict_types=1);

// --- Autoloader simples (sem Composer) ----------------------
spl_autoload_register(function (string $class): void {
    // Converte namespace em caminho de ficheiro
    // Ex: Controllers\AuthController → controllers/AuthController.php
    $file = __DIR__ . '/' . strtolower(str_replace(['\\', '_'], ['/', '/'], $class)) . '.php';

    // Tenta caminho directo
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // Mapa manual para ficheiros que têm múltiplos namespaces num ficheiro
    $map = [
        'models\watchlist' => __DIR__ . '/models/Movie.php',
        'models\favorite'  => __DIR__ . '/models/Movie.php',
        'models\rating'    => __DIR__ . '/models/Movie.php',
        'controllers\exportcontroller' => __DIR__ . '/controllers/UserController.php',
    ];

    $key = strtolower($class);
    if (isset($map[$key])) {
        require_once $map[$key];
    }
});

// --- Config -------------------------------------------------
require_once __DIR__ . '/config/app.php';

// --- CORS ---------------------------------------------------
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS, true) || preg_match('#^http://localhost:\d+$#', $origin)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: http://localhost:4200');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Namespaces necessários ---------------------------------
use Core\Router;
use Core\Response;
use Controllers\AuthController;
use Controllers\MovieController;
use Controllers\UserController;
use Controllers\ExportController;

// --- Instâncias dos controllers -----------------------------
$auth   = new AuthController();
$movies = new MovieController();
$users  = new UserController();
$export = new ExportController();

// --- Router -------------------------------------------------
$router = new Router();

// Auth
$router->post('/auth/register',        fn() => $auth->register());
$router->post('/auth/login',           fn() => $auth->login());
$router->get( '/auth/me',              fn() => $auth->me());
$router->post('/auth/forgot-password', fn() => $auth->forgotPassword());
$router->post('/auth/reset-password',  fn() => $auth->resetPassword());

// Movies — descoberta (públicas)
$router->get('/movies/popular',                fn()     => $movies->popular());
$router->get('/movies/top-rated',              fn()     => $movies->topRated());
$router->get('/movies/now-playing',            fn()     => $movies->nowPlaying());
$router->get('/movies/upcoming',               fn()     => $movies->upcoming());
$router->get('/movies/search',                 fn()     => $movies->search());
$router->get('/movies/genres',                 fn()     => $movies->genres());
$router->get('/movies/for-you',                fn()     => $movies->forYou());
$router->get('/movies/by-genre/:genreId',      fn($p)   => $movies->byGenre($p));
$router->get('/movies/:id',                    fn($p)   => $movies->details($p));
$router->get('/movies/:id/recommendations',    fn($p)   => $movies->recommendations($p));

// Movies — acções do utilizador (requerem auth)
$router->get(   '/movies/:id/rating',          fn($p)   => $movies->getRating($p));
$router->post(  '/movies/:id/rating',          fn($p)   => $movies->upsertRating($p));
$router->delete('/movies/:id/rating',          fn($p)   => $movies->deleteRating($p));
$router->post(  '/movies/:id/watchlist',       fn($p)   => $movies->addToWatchlist($p));
$router->delete('/movies/:id/watchlist',       fn($p)   => $movies->removeFromWatchlist($p));
$router->put(   '/movies/:id/watchlist/watched', fn($p) => $movies->markWatched($p));
$router->post(  '/movies/:id/favorite',        fn($p)   => $movies->toggleFavorite($p));

// User
$router->get('/user/profile',   fn() => $users->profile());
$router->put('/user/profile',   fn() => $users->updateProfile());
$router->put('/user/password',  fn() => $users->updatePassword());
$router->get('/user/ratings',   fn() => $movies->myRatings());
$router->get('/user/watchlist', fn() => $movies->myWatchlist());
$router->get('/user/favorites', fn() => $movies->myFavorites());

// Admin
$router->get(   '/admin/users',     fn()   => $users->listUsers());
$router->delete('/admin/users/:id', fn($p) => $users->deleteUser($p));

// Export
$router->get('/export/watchlist/csv', fn() => $export->watchlistCsv());
$router->get('/export/ratings/csv',   fn() => $export->ratingsCsv());
$router->get('/export/favorites/csv', fn() => $export->favoritesCsv());
$router->get('/export/ratings/pdf',   fn() => $export->ratingsPdf());

// --- Dispatch -----------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove o prefixo /api se existir (ex: Apache com subpasta)
$uri = preg_replace('#^/api#', '', $uri);

$router->dispatch($method, $uri);
