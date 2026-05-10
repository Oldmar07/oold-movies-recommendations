<?php
// ============================================================
//  Service — AuthService  (JWT, hash, tokens de reset)
// ============================================================

namespace Services;

class AuthService
{
    // ----------------------------------------------------------
    //  JWT  (implementação manual — sem dependências externas)
    // ----------------------------------------------------------

    public static function generateToken(array $payload): string
    {
        $header  = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;
        $body    = self::base64url(json_encode($payload));
        $sig     = self::base64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        return "$header.$body.$sig";
    }

    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $body, $sig] = $parts;
        $expected = self::base64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));

        if (!hash_equals($expected, $sig)) return null;

        $payload = json_decode(self::base64urlDecode($body), true);
        if (!$payload || ($payload['exp'] ?? 0) < time()) return null;

        return $payload;
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // ----------------------------------------------------------
    //  Password
    // ----------------------------------------------------------

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    // ----------------------------------------------------------
    //  Reset token
    // ----------------------------------------------------------

    public static function generateResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    // ----------------------------------------------------------
    //  Validação de input
    // ----------------------------------------------------------

    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePassword(string $password): bool
    {
        // Mínimo 8 caracteres, 1 maiúscula, 1 número
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[0-9]/', $password);
    }
}
