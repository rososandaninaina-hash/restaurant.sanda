<?php
declare(strict_types=1);

/** Échappe les textes avant affichage HTML pour limiter les injections XSS. */
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirection puis arrêt du script. */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Jeton CSRF stocké en session. */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Vérifie le jeton CSRF envoyé par un formulaire POST. */
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Formulaire expiré ou invalide. Revenez à la page précédente et réessayez.');
    }
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $message;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        flash('warning', 'Connectez-vous pour continuer.');
        redirect('/restaurant-gestion/login.php');
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Accès refusé : vous ne disposez pas des droits nécessaires.');
    }
}

function money(mixed $amount): string {
    return number_format((float)$amount, 0, ',', ' ') . ' Ar';
}

/** Valide un entier positif venant d'un formulaire. */
function positive_int(mixed $value): ?int {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return ($filtered !== false && $filtered > 0) ? (int)$filtered : null;
}
