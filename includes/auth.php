<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../app/services/AuthService.php';

function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit();
    }
}

function require_role(string $role): void
{
    require_login();
    $user = $_SESSION['user'] ?? null;
    // Using strtolower to ensure role checks are not case-sensitive
    if (!$user || strtolower($user['role'] ?? '') !== strtolower($role)) {
        http_response_code(403);
        die('Access denied.');
    }
}

function auth_user(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

function auth_login(string $username, string $password): bool
{
    $service = new AuthService();
    return $service->login($username, $password);
}

function auth_logout(): void
{
    $service = new AuthService();
    $service->logout();
}
