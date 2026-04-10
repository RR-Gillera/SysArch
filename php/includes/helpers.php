<?php
/**
 * Shared helpers: CSRF protection, auth guards, HTML escaping.
 */

// ── HTML escaping ────────────────────────────────────────────────────────────

function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── CSRF ─────────────────────────────────────────────────────────────────────

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('CSRF token mismatch.');
    }
}

// ── Auth guards ──────────────────────────────────────────────────────────────

function require_student(): void {
    $id   = $_SESSION['IdNumber'] ?? '';
    $role = $_SESSION['Role']     ?? '';
    if ($id === '') {
        header('Location: /php/login.php');
        exit;
    }
    if (strtolower($role) !== 'student') {
        header('Location: /php/admin/home.php');
        exit;
    }
}

function require_admin(): void {
    $id   = $_SESSION['IdNumber'] ?? '';
    $role = $_SESSION['Role']     ?? '';
    if ($id === '') {
        header('Location: /php/login.php');
        exit;
    }
    if (strtolower($role) !== 'admin') {
        header('Location: /php/home.php');
        exit;
    }
}

function is_admin(): bool {
    return !empty($_SESSION['IdNumber'])
        && strtolower($_SESSION['Role'] ?? '') === 'admin';
}

function is_student(): bool {
    return !empty($_SESSION['IdNumber'])
        && strtolower($_SESSION['Role'] ?? '') === 'student';
}

// ── Flash messages ───────────────────────────────────────────────────────────

function set_flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}
