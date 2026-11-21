<?php

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        require $viewFile;
    }

    /**
     * Renders a view inside the admin layout (sidebar + top bar).
     * Expected data: pageTitle, pageSubtitle, active (nav key).
     */
    protected function page(string $view, array $data = []): void
    {
        $data['admin'] = AuthController::currentAdmin();
        $data['flashError'] = flash_get('error');
        $data['flashSuccess'] = flash_get('success');
        $data['navCounts'] = [
            'inquiries' => (Inquiry::counts()['new'] ?? 0),
            'appointments' => (Appointment::counts()['pending'] ?? 0),
            'today' => Appointment::todayCount(),
        ];
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        require __DIR__ . '/../views/layout/header.php';
        require $viewFile;
        require __DIR__ . '/../views/layout/footer.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . ADMIN_URL . ltrim($path, '/'));
        exit;
    }

    /** Guards every state-changing admin action: login + POST + CSRF token. */
    protected function guardPost(string $backTo = 'index.php'): void
    {
        AuthController::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($backTo);
        }
        if (!csrf_valid()) {
            flash_set('error', 'Your session expired. Please try again.');
            $this->redirect($backTo);
        }
    }

    protected function intParam(string $key, string $source = 'GET'): int
    {
        $bag = $source === 'POST' ? $_POST : $_GET;
        return max(0, (int) ($bag[$key] ?? 0));
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
