<?php
// ============================================================
//  Controller — AuthController
// ============================================================

namespace Controllers;

use Core\Response;
use Models\User;
use Services\AuthService;
use Services\MailService;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // POST /auth/register
    public function register(): void
    {
        $body = $this->json();

        $name     = trim($body['name']     ?? '');
        $email    = trim($body['email']    ?? '');
        $password = $body['password']      ?? '';

        $errors = [];
        if (strlen($name) < 2)                         $errors['name']     = 'Nome deve ter pelo menos 2 caracteres.';
        if (!AuthService::validateEmail($email))        $errors['email']    = 'Email inválido.';
        if (!AuthService::validatePassword($password))  $errors['password'] = 'Senha deve ter mínimo 8 caracteres, 1 maiúscula e 1 número.';

        if ($errors) Response::error('Dados inválidos.', 422, $errors);

        if ($this->userModel->findByEmail($email)) {
            Response::error('Email já registado.', 409);
        }

        $id   = $this->userModel->create($name, $email, AuthService::hashPassword($password));
        $user = $this->userModel->findById($id);

        Response::success([
            'user'  => $user,
            'token' => AuthService::generateToken(['sub' => $id, 'role' => 'user']),
        ], 'Conta criada com sucesso!', 201);
    }

    // POST /auth/login
    public function login(): void
    {
        $body  = $this->json();
        $email = trim($body['email']    ?? '');
        $pass  = $body['password']      ?? '';

        $user = $this->userModel->findByEmail($email);

        if (!$user || !AuthService::verifyPassword($pass, $user['password_hash'])) {
            Response::error('Credenciais inválidas.', 401);
        }

        $public = $this->userModel->findById((int) $user['id']);

        Response::success([
            'user'  => $public,
            'token' => AuthService::generateToken(['sub' => $user['id'], 'role' => $user['role']]),
        ], 'Login efectuado com sucesso!');
    }

    // POST /auth/forgot-password
    public function forgotPassword(): void
    {
        $body  = $this->json();
        $email = trim($body['email'] ?? '');

        $user = $this->userModel->findByEmail($email);

        // Resposta genérica por segurança (não revela se email existe)
        if (!$user) {
            Response::success(null, 'Se o email existir, receberá instruções em breve.');
        }

        $token = AuthService::generateResetToken();
        $this->userModel->setResetToken((int) $user['id'], $token);
        MailService::sendPasswordReset($user['email'], $user['name'], $token);

        Response::success(null, 'Se o email existir, receberá instruções em breve.');
    }

    // POST /auth/reset-password
    public function resetPassword(): void
    {
        $body     = $this->json();
        $token    = $body['token']    ?? '';
        $password = $body['password'] ?? '';

        if (!AuthService::validatePassword($password)) {
            Response::error('Senha inválida. Mínimo 8 caracteres, 1 maiúscula e 1 número.', 422);
        }

        $user = $this->userModel->findByResetToken($token);
        if (!$user) Response::error('Token inválido ou expirado.', 400);

        $this->userModel->updatePassword((int) $user['id'], AuthService::hashPassword($password));
        Response::success(null, 'Senha alterada com sucesso!');
    }

    // GET /auth/me
    public function me(): void
    {
        $payload = \Core\Middleware::auth();
        $user    = $this->userModel->findById((int) $payload['sub']);

        if (!$user) Response::error('Utilizador não encontrado.', 404);
        Response::success($user);
    }

    // ----------------------------------------------------------
    private function json(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
