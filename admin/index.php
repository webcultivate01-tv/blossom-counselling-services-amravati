<?php
/**
 * Front controller for the admin panel.
 * All requests to /admin go through this file.
 */

declare(strict_types=1);

require __DIR__ . '/config/config.php';
require __DIR__ . '/core/Database.php';
require __DIR__ . '/core/Helpers.php';
require __DIR__ . '/core/Schema.php';
require __DIR__ . '/core/Uploader.php';
require __DIR__ . '/core/Controller.php';
require __DIR__ . '/core/Jwt.php';
require __DIR__ . '/core/Mailer.php';
require __DIR__ . '/models/Admin.php';
require __DIR__ . '/models/PasswordReset.php';
require __DIR__ . '/models/LeadModel.php';
require __DIR__ . '/models/Inquiry.php';
require __DIR__ . '/models/Appointment.php';
require __DIR__ . '/models/Payment.php';
require __DIR__ . '/models/BlogPost.php';
require __DIR__ . '/models/GalleryItem.php';
require __DIR__ . '/models/Category.php';
require __DIR__ . '/models/Report.php';
require __DIR__ . '/models/Analytics.php';
require __DIR__ . '/core/Xlsx.php';
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/LeadController.php';
require __DIR__ . '/controllers/InquiryController.php';
require __DIR__ . '/controllers/AppointmentController.php';
require __DIR__ . '/controllers/PaymentController.php';
require __DIR__ . '/controllers/BlogController.php';
require __DIR__ . '/controllers/GalleryController.php';
require __DIR__ . '/controllers/CategoryController.php';
require __DIR__ . '/controllers/AccountController.php';
require __DIR__ . '/controllers/ReportController.php';
require __DIR__ . '/controllers/PublicController.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$auth = new AuthController();

/** Admin screens: action => [controller, method]. Each method enforces login itself. */
$routes = [
    'inquiries' => [InquiryController::class, 'index'],
    'inquiry' => [InquiryController::class, 'show'],
    'inquiry_status' => [InquiryController::class, 'status'],
    'inquiry_notes' => [InquiryController::class, 'notes'],
    'inquiry_delete' => [InquiryController::class, 'delete'],

    'appointments' => [AppointmentController::class, 'index'],
    'appointment' => [AppointmentController::class, 'show'],
    'appointment_status' => [AppointmentController::class, 'status'],
    'appointment_notes' => [AppointmentController::class, 'notes'],
    'appointments_today' => [AppointmentController::class, 'today'],
    'appointment_complete' => [AppointmentController::class, 'complete'],
    'appointment_schedule' => [AppointmentController::class, 'schedule'],
    'appointment_delete' => [AppointmentController::class, 'delete'],
    'appointment_edit' => [AppointmentController::class, 'edit'],
    'appointment_cancel' => [AppointmentController::class, 'cancel'],

    'payments' => [PaymentController::class, 'index'],
    'payment_add' => [PaymentController::class, 'add'],
    'payment_update' => [PaymentController::class, 'update'],
    'payment_delete' => [PaymentController::class, 'delete'],

    'blogs' => [BlogController::class, 'index'],
    'blog_form' => [BlogController::class, 'form'],
    'blog_save' => [BlogController::class, 'save'],
    'blog_delete' => [BlogController::class, 'delete'],

    'gallery' => [GalleryController::class, 'index'],
    'gallery_upload' => [GalleryController::class, 'upload'],
    'gallery_edit' => [GalleryController::class, 'edit'],
    'gallery_update' => [GalleryController::class, 'update'],
    'gallery_delete' => [GalleryController::class, 'delete'],

    'categories' => [CategoryController::class, 'index'],
    'category_create' => [CategoryController::class, 'create'],
    'category_update' => [CategoryController::class, 'update'],
    'category_delete' => [CategoryController::class, 'delete'],

    'reports' => [ReportController::class, 'index'],
    'report_export' => [ReportController::class, 'export'],

    'account' => [AccountController::class, 'index'],
    'account_update' => [AccountController::class, 'update'],
    'account_password' => [AccountController::class, 'password'],
    'account_photo' => [AccountController::class, 'photo'],

    // Public website endpoints (no login)
    'submit' => [PublicController::class, 'submit'],
    'api' => [PublicController::class, 'api'],
];

try {
    Schema::ensure();

    $route = [$action, $method];

    match (true) {
        $route === ['login', 'GET'] => $auth->showLogin(),
        $route === ['login', 'POST'] => $auth->login(),
        $route === ['logout', 'GET'], $route === ['logout', 'POST'] => $auth->logout(),
        $route === ['forgot', 'GET'] => $auth->showForgot(),
        $route === ['forgot', 'POST'] => $auth->sendCode(),
        $route === ['reset', 'GET'] => $auth->showReset(),
        $route === ['reset', 'POST'] => $auth->resetPassword(),
        $action === null => AuthController::isLoggedIn()
            ? (new DashboardController())->index()
            : $auth->showLogin(),
        is_string($action) && isset($routes[$action]) => (function () use ($routes, $action) {
            [$class, $fn] = $routes[$action];
            (new $class())->$fn();
        })(),
        default => (function () {
            http_response_code(404);
            echo '404 Not Found';
        })(),
    };
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Something went wrong. Please try again later.';
    error_log('[Admin Panel] ' . $e->getMessage());
}
