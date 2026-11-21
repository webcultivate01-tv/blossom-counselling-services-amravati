<?php

class AccountController extends Controller
{
    public function index(): void
    {
        AuthController::requireLogin();

        $this->page('account/index', [
            'pageTitle' => 'My Account',
            'pageSubtitle' => 'Manage your admin account',
            'active' => 'account',
        ]);
    }

    public function update(): void
    {
        $this->guardPost('index.php?action=account');
        $admin = AuthController::currentAdmin();

        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 100);
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($name === '') {
            $this->fail('Please enter your name.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $this->fail('Please enter a valid email address.');
        }
        if (Admin::emailTaken($email, (int) $admin['id'])) {
            $this->fail('That email is already used by another account.');
        }

        Admin::updateProfile((int) $admin['id'], $name, $email);
        flash_set('success', 'Profile updated.');
        $this->redirect('index.php?action=account');
    }

    public function password(): void
    {
        $this->guardPost('index.php?action=account');
        $admin = AuthController::currentAdmin();

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (!password_verify($current, $admin['password'])) {
            $this->fail('Your current password is not correct.');
        }
        if (strlen($new) < 8) {
            $this->fail('Your new password must be at least 8 characters.');
        }
        if ($new !== $confirm) {
            $this->fail('The two new passwords do not match.');
        }

        Admin::updatePassword((int) $admin['id'], $new);

        // The token is tied to the password, so issue a fresh one to keep this session signed in.
        $fresh = Admin::findById((int) $admin['id']);
        (new AuthController())->issueToken($fresh);

        flash_set('success', 'Password updated.');
        $this->redirect('index.php?action=account');
    }

    public function photo(): void
    {
        $this->guardPost('index.php?action=account');
        $admin = AuthController::currentAdmin();

        if (!empty($_POST['remove'])) {
            Uploader::delete($admin['avatar']);
            Admin::updateAvatar((int) $admin['id'], null);
            flash_set('success', 'Profile photo removed.');
            $this->redirect('index.php?action=account');
        }

        if (!Uploader::hasFile($_FILES['photo'] ?? null)) {
            $this->fail('Please choose a photo first.');
        }

        try {
            $path = Uploader::saveImage($_FILES['photo'], 'profile');
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage());
        }

        Uploader::delete($admin['avatar']);
        Admin::updateAvatar((int) $admin['id'], $path);
        flash_set('success', 'Profile photo updated.');
        $this->redirect('index.php?action=account');
    }

    private function fail(string $message): never
    {
        flash_set('error', $message);
        $this->redirect('index.php?action=account');
    }
}
