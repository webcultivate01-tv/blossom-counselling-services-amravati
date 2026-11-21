<?php

class AuthController extends Controller
{
    private static ?array $currentAdmin = null;
    private static bool $resolved = false;

    /* ---------------- Login / logout ---------------- */

    public function showLogin(): void
    {
        if (self::isLoggedIn()) {
            $this->redirect('index.php');
        }

        $this->render('auth/login', [
            'error' => $this->flash('error'),
            'success' => $this->flash('success'),
            'old_email' => $this->flash('old_email') ?? '',
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->fail('Please enter both email and password.', 'index.php?action=login', $email);
        }

        $admin = Admin::findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            $this->fail('Invalid email or password.', 'index.php?action=login', $email);
        }

        $this->issueToken($admin);
        Admin::touchLastLogin((int) $admin['id']);

        $this->redirect('index.php');
    }

    public function logout(): void
    {
        $this->clearToken();
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['success'] = 'You have been signed out.';
        $this->redirect('index.php?action=login');
    }

    /* ---------------- Forgot password flow ---------------- */

    public function showForgot(): void
    {
        $this->render('auth/forgot', [
            'error' => $this->flash('error'),
            'old_email' => $this->flash('old_email') ?? '',
        ]);
    }

    public function sendCode(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Please enter a valid email address.', 'index.php?action=forgot', $email);
        }

        $admin = Admin::findByEmail($email);

        if ($admin) {
            $wait = PasswordReset::cooldownRemaining((int) $admin['id']);
            if ($wait > 0) {
                $this->fail("Please wait {$wait} seconds before requesting another code.", 'index.php?action=forgot', $email);
            }

            try {
                $code = PasswordReset::create((int) $admin['id']);
                $this->emailCode($admin, $code);
            } catch (Throwable $e) {
                error_log('[Admin Panel] Reset email failed: ' . $e->getMessage());
                $this->fail('We could not send the email right now. Please check the mail settings and try again.', 'index.php?action=forgot', $email);
            }
        }

        // Same response whether or not the email exists, so the form can't be used to discover admin emails.
        $_SESSION['reset_email'] = $email;
        $_SESSION['success'] = 'If this email is registered, a 6-digit code has been sent to it.';
        $this->redirect('index.php?action=reset');
    }

    public function showReset(): void
    {
        if (empty($_SESSION['reset_email'])) {
            $this->redirect('index.php?action=forgot');
        }

        $this->render('auth/reset', [
            'error' => $this->flash('error'),
            'success' => $this->flash('success'),
            'email' => $_SESSION['reset_email'],
        ]);
    }

    public function resetPassword(): void
    {
        $email = $_SESSION['reset_email'] ?? '';
        if ($email === '') {
            $this->redirect('index.php?action=forgot');
        }

        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        $back = 'index.php?action=reset';

        if (strlen($code) !== 6) {
            $this->fail('Enter the 6-digit code from your email.', $back);
        }
        if (strlen($password) < 8) {
            $this->fail('Your new password must be at least 8 characters.', $back);
        }
        if ($password !== $confirm) {
            $this->fail('The two passwords do not match.', $back);
        }

        $admin = Admin::findByEmail($email);
        $result = $admin ? PasswordReset::verify((int) $admin['id'], $code) : 'invalid';

        if ($result !== 'ok') {
            $messages = [
                'invalid' => 'That code is not correct. Please try again.',
                'expired' => 'That code has expired. Please request a new one.',
                'locked' => 'Too many wrong attempts. Please request a new code.',
            ];
            $this->fail($messages[$result], $result === 'invalid' ? $back : 'index.php?action=forgot');
        }

        Admin::updatePassword((int) $admin['id'], $password);
        PasswordReset::clear((int) $admin['id']);

        unset($_SESSION['reset_email']);
        $this->clearToken();
        $_SESSION['success'] = 'Password updated. You can now sign in with your new password.';
        $this->redirect('index.php?action=login');
    }

    /* ---------------- Token authentication ---------------- */

    /** The signed-in admin (validated from the token cookie), or null. */
    public static function currentAdmin(): ?array
    {
        if (self::$resolved) {
            return self::$currentAdmin;
        }
        self::$resolved = true;

        $token = $_COOKIE[TOKEN_COOKIE] ?? '';
        $claims = $token !== '' ? Jwt::decode($token) : null;
        if (!$claims || empty($claims['sub'])) {
            return null;
        }

        $admin = Admin::findById((int) $claims['sub']);
        // Tokens issued before a password change stop working.
        if (!$admin || !hash_equals(Admin::passwordStamp($admin['password']), (string) ($claims['pwd'] ?? ''))) {
            return null;
        }

        return self::$currentAdmin = $admin;
    }

    public static function isLoggedIn(): bool
    {
        return self::currentAdmin() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . ADMIN_URL . 'index.php?action=login');
            exit;
        }
    }

    public function issueToken(array $admin): void
    {
        $token = Jwt::encode([
            'sub' => (int) $admin['id'],
            'pwd' => Admin::passwordStamp($admin['password']),
        ], TOKEN_TTL);

        setcookie(TOKEN_COOKIE, $token, $this->cookieOptions(time() + TOKEN_TTL));
    }

    private function clearToken(): void
    {
        setcookie(TOKEN_COOKIE, '', $this->cookieOptions(time() - 3600));
        unset($_COOKIE[TOKEN_COOKIE]);
    }

    private function cookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => ADMIN_URL,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ];
    }

    /* ---------------- Helpers ---------------- */

    private function emailCode(array $admin, string $code): void
    {
        $name = htmlspecialchars($admin['name']);
        $ttl = PasswordReset::CODE_TTL_MINUTES;
        $app = htmlspecialchars(APP_NAME);

        $html = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;color:#1f2937">
  <h2 style="margin:0 0 12px">Reset your admin password</h2>
  <p>Hi {$name}, use this code to reset your {$app} admin password:</p>
  <p style="font-size:32px;font-weight:700;letter-spacing:8px;background:#f3f4f8;padding:16px;text-align:center;border-radius:8px">{$code}</p>
  <p>This code expires in {$ttl} minutes. If you didn't request it, you can ignore this email.</p>
</div>
HTML;
        $text = "Your " . APP_NAME . " admin password reset code is {$code}. It expires in {$ttl} minutes.";

        Mailer::send($admin['email'], APP_NAME . ' password reset code', $html, $text);
    }

    private function flash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $value;
    }

    private function fail(string $message, string $redirectTo, ?string $email = null): void
    {
        $_SESSION['error'] = $message;
        if ($email !== null) {
            $_SESSION['old_email'] = $email;
        }
        $this->redirect($redirectTo);
    }
}
