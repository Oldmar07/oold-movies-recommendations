<?php
// ============================================================
//  Core — Middleware  (autenticação JWT)
// ============================================================

namespace Core;

class Middleware
{
    /**
     * Valida o token JWT e retorna o payload.
     * Termina a requisição com 401 se inválido.
     */
    public static function auth(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            Response::error('Token de autenticação não fornecido.', 401);
        }

        $token = substr($header, 7);
        $payload = \Services\AuthService::validateToken($token);

        if (!$payload) {
            Response::error('Token inválido ou expirado.', 401);
        }

        return $payload;
    }

    /**
     * Valida JWT e exige que o utilizador seja admin.
     */
    public static function admin(): array
    {
        $payload = self::auth();

        if (($payload['role'] ?? '') !== 'admin') {
            Response::error('Acesso restrito a administradores.', 403);
        }

        return $payload;
    }
}
