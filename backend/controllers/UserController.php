<?php
// ============================================================
//  Controller — UserController
// ============================================================

namespace Controllers;

use Core\Middleware;
use Core\Response;
use Models\User;
use Services\AuthService;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // GET /user/profile
    public function profile(): void
    {
        $payload = Middleware::auth();
        $user    = $this->userModel->findById((int) $payload['sub']);
        if (!$user) Response::error('Utilizador não encontrado.', 404);
        Response::json($user);
    }

    // PUT /user/profile
    public function updateProfile(): void
    {
        $payload = Middleware::auth();
        $body    = $this->json();

        $allowed = ['name', 'avatar_url', 'preferred_lang', 'theme'];
        $fields  = array_intersect_key($body, array_flip($allowed));

        // Validação do idioma
        if (isset($fields['preferred_lang']) && !in_array($fields['preferred_lang'], ['pt', 'en'])) {
            Response::error('Idioma inválido. Use "pt" ou "en".', 422);
        }

        $this->userModel->updateProfile((int) $payload['sub'], $fields);
        $user = $this->userModel->findById((int) $payload['sub']);
        Response::success($user, 'Perfil actualizado.');
    }

    // PUT /user/password
    public function updatePassword(): void
    {
        $payload = Middleware::auth();
        $body    = $this->json();

        $current  = $body['current_password'] ?? '';
        $new      = $body['new_password']      ?? '';

        $user = $this->userModel->findByEmail(
            $this->userModel->findById((int) $payload['sub'])['email']
        );

        if (!AuthService::verifyPassword($current, $user['password_hash'])) {
            Response::error('Senha actual incorrecta.', 401);
        }

        if (!AuthService::validatePassword($new)) {
            Response::error('Nova senha deve ter mínimo 8 caracteres, 1 maiúscula e 1 número.', 422);
        }

        $this->userModel->updatePassword((int) $payload['sub'], AuthService::hashPassword($new));
        Response::success(null, 'Senha alterada com sucesso!');
    }

    // --- Admin: listar utilizadores ---
    // GET /admin/users
    public function listUsers(): void
    {
        Middleware::admin();
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? DEFAULT_PAGE_SIZE);
        $result  = $this->userModel->getAll($page, $perPage);
        Response::paginated($result['items'], $result['total'], $page, $perPage);
    }

    // DELETE /admin/users/:id
    public function deleteUser(array $params): void
    {
        Middleware::admin();
        $id = (int) $params['id'];
        $this->userModel->deleteById($id);
        Response::success(null, 'Utilizador eliminado.');
    }

    private function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}


// ============================================================
//  Controller — ExportController
// ============================================================

namespace Controllers;

use Core\Middleware;
use Core\Response;
use Services\ExportService;

class ExportController
{
    private ExportService $exportService;

    public function __construct()
    {
        $this->exportService = new ExportService();
    }

    // GET /export/watchlist/csv
    public function watchlistCsv(): void
    {
        $payload = Middleware::auth();
        $this->exportService->exportWatchlistCsv((int) $payload['sub']);
    }

    // GET /export/ratings/csv
    public function ratingsCsv(): void
    {
        $payload = Middleware::auth();
        $this->exportService->exportRatingsCsv((int) $payload['sub']);
    }

    // GET /export/favorites/csv
    public function favoritesCsv(): void
    {
        $payload = Middleware::auth();
        $this->exportService->exportFavoritesCsv((int) $payload['sub']);
    }

    // GET /export/ratings/pdf
    public function ratingsPdf(): void
    {
        $payload = Middleware::auth();
        $user    = (new \Models\User())->findById((int) $payload['sub']);
        $this->exportService->exportRatingsPdf((int) $payload['sub'], $user['name'] ?? 'Utilizador');
    }
}
