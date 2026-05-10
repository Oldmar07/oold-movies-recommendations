<?php
// ============================================================
//  Service — MailService  (envio de email com sockets SMTP)
// ============================================================

namespace Services;

class MailService
{
    /**
     * Envia email de recuperação de senha.
     * Usa mail() do PHP — em produção recomenda-se PHPMailer/SMTP real.
     */
    public static function sendPasswordReset(string $toEmail, string $toName, string $token): bool
    {
        $resetUrl = 'http://localhost:4200/auth/reset-password?token=' . urlencode($token);

        $subject = '[OOLD] Recuperação de Senha';

        $body = self::resetTemplate($toName, $resetUrl);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM . "\r\n";
        $headers .= "Reply-To: " . MAIL_USER . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Em ambiente de desenvolvimento, apenas loga o link
        if (APP_ENV === 'development') {
            error_log("[MAIL] Reset link for {$toEmail}: {$resetUrl}");
            return true;
        }

        return mail($toEmail, $subject, $body, $headers);
    }

    private static function resetTemplate(string $name, string $resetUrl): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt">
        <head><meta charset="UTF-8"><title>Recuperação de Senha</title></head>
        <body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px">
          <div style="max-width:500px;margin:auto;background:#fff;border-radius:8px;padding:30px">
            <h2 style="color:#e50914">OOLD</h2>
            <p>Olá, <strong>{$name}</strong>!</p>
            <p>Recebemos um pedido de recuperação de senha para a sua conta.</p>
            <p>Clique no botão abaixo para definir uma nova senha. O link expira em <strong>1 hora</strong>.</p>
            <a href="{$resetUrl}"
               style="display:inline-block;background:#e50914;color:#fff;padding:12px 24px;border-radius:4px;text-decoration:none;margin:16px 0">
              Redefinir Senha
            </a>
            <p style="color:#888;font-size:12px">Se não solicitou a recuperação, ignore este email.</p>
          </div>
        </body>
        </html>
        HTML;
    }
}
