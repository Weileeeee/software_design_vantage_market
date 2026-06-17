<?php
// =============================================================
// VantageMarket — UserMailer
// SRP: responsible for transactional email sending only
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Models\User;

final class UserMailer
{
    private string $fromAddress;
    private string $fromName;
    private string $appUrl;

    public function __construct()
    {
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@vantagemarket.com';
        $this->fromName    = $_ENV['MAIL_FROM_NAME']    ?? 'VantageMarket';
        $this->appUrl      = $_ENV['APP_URL']           ?? 'https://vantagemarket.com';
    }

    // ----------------------------------------------------------
    // UC05 — Welcome email after registration
    // ----------------------------------------------------------

    public function sendWelcomeEmail(User $user): void
    {
        $subject = 'Welcome to VantageMarket!';
        $body    = $this->wrapHtml(
            $user->fullName(),
            "Thank you for registering with VantageMarket. Your account is ready.",
            "Start Shopping",
            "{$this->appUrl}/catalogue"
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // UC04 — Password reset email
    // ----------------------------------------------------------

    public function sendPasswordResetEmail(User $user, string $rawToken): void
    {
        $resetUrl = "{$this->appUrl}/reset-password?token={$rawToken}";
        $subject  = 'Reset Your VantageMarket Password';
        $body     = $this->wrapHtml(
            $user->fullName(),
            "We received a request to reset your password. This link expires in 60 minutes.",
            "Reset Password",
            $resetUrl
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // UC04 — Security alert for login from new device/location
    // ----------------------------------------------------------

    public function sendSecurityAlert(User $user, string $ipAddress): void
    {
        $subject = 'New Login Detected — VantageMarket';
        $message = "A login to your account was detected from IP: {$ipAddress}. "
                 . "If this was not you, please reset your password immediately.";
        $body    = $this->wrapHtml(
            $user->fullName(),
            $message,
            "Reset Password",
            "{$this->appUrl}/forgot-password"
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // UC04 — Account locked notification
    // ----------------------------------------------------------

    public function sendAccountLockedEmail(User $user): void
    {
        $subject = 'Your VantageMarket Account Has Been Locked';
        $body    = $this->wrapHtml(
            $user->fullName(),
            "Your account has been temporarily locked due to too many failed login attempts. "
            . "It will unlock automatically in 15 minutes, or you can reset your password now.",
            "Reset Password",
            "{$this->appUrl}/forgot-password"
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------

    private function send(string $to, string $subject, string $htmlBody): void
    {
        // In production: swap mail() for PHPMailer / Symfony Mailer / SES SDK.
        // mail() requires a configured SMTP server in php.ini — on local dev
        // (XAMPP/WAMP) this is usually NOT configured, so we suppress errors
        // and log instead of letting a PHP warning corrupt JSON API responses.
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromAddress}>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        try {
            $sent = @mail($to, $subject, $htmlBody, $headers);
        } catch (\Throwable $e) {
            $sent = false;
            error_log("[Mailer] Exception sending '{$subject}' to {$to}: " . $e->getMessage());
        }

        if (!$sent) {
            error_log("[Mailer] Failed to send '{$subject}' to {$to} — mail() may not be configured (normal on local dev).");
        }
    }

    /** Minimal branded HTML email wrapper */
    private function wrapHtml(
        string $recipientName,
        string $message,
        string $ctaLabel,
        string $ctaUrl
    ): string {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:40px 20px;">
              <table width="600" cellpadding="0" cellspacing="0"
                     style="background:#ffffff;border-radius:8px;overflow:hidden;
                            box-shadow:0 2px 8px rgba(0,0,0,.08);">
                <tr>
                  <td style="background:#0a0a0a;padding:28px 40px;">
                    <span style="font-family:Georgia,serif;font-size:22px;
                                 color:#e8c97e;letter-spacing:2px;">VANTAGEMARKET</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:40px;">
                    <p style="margin:0 0 16px;font-size:16px;color:#333;">
                      Hello, <strong>{$recipientName}</strong>,
                    </p>
                    <p style="margin:0 0 32px;font-size:15px;color:#555;line-height:1.6;">
                      {$message}
                    </p>
                    <a href="{$ctaUrl}"
                       style="display:inline-block;padding:14px 32px;
                              background:#0a0a0a;color:#e8c97e;
                              font-family:Georgia,serif;font-size:14px;
                              letter-spacing:1px;text-decoration:none;
                              border-radius:4px;">
                      {$ctaLabel}
                    </a>
                    <p style="margin:40px 0 0;font-size:12px;color:#aaa;">
                      If you did not request this, please ignore this email.
                      This message was sent by VantageMarket.
                    </p>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }
}
