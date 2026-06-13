<?php

/**
 * Render a template using the shared layout.
 *
 * @param string $page  Template name (used as $currentPage inside layout)
 * @param array  $data  Variables to extract into the template scope
 */
function renderTemplate(string $page, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $currentPage = $page;
    include BASE_PATH . '/templates/layout.html.php';
}

/**
 * Require the current visitor to be authenticated.
 * Redirects to /login if no session user is present.
 */
function requireAuth(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/login');
    }
}

/**
 * Require the current visitor to be authenticated and to have one of the
 * allowed role IDs. Returns 403 and renders an error page otherwise.
 *
 * @param int[] $allowedRoleIds
 */
function requireRole(array $allowedRoleIds): void
{
    requireAuth();
    $user = getCurrentUser();
    if ($user === null || !in_array((int)$user['role_id'], $allowedRoleIds, true)) {
        http_response_code(403);
        $requiredRoles = implode(', ', $allowedRoleIds);
        renderTemplate('error_403', [
            'message' => 'Доступ запрещён: требуется роль с ID ' . $requiredRoles,
        ]);
        exit;
    }
}

/**
 * HTML-escape a string for safe output.
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Return the currently authenticated user array, or null.
 */
function getCurrentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Return the current user's role_id, or 0 if not authenticated.
 */
function userRole(): int
{
    $user = getCurrentUser();
    return $user !== null ? (int)$user['role_id'] : 0;
}

/**
 * Return true if the current user has an admin or chairman role (role_id 1 or 2).
 * Полные права: пользователи, модерация.
 */
function isAdmin(): bool
{
    return in_array(userRole(), [1, 2], true);
}

/**
 * Член правления: админ, председатель, казначей, секретарь (видят панель /admin).
 */
function isBoard(): bool
{
    return in_array(userRole(), [1, 2, 3, 4], true);
}

/**
 * Может вести финансы и бюджет: админ, председатель, казначей.
 */
function canFinance(): bool
{
    return in_array(userRole(), [1, 2, 3], true);
}

/**
 * Может вести собрания, голосования, протоколы: админ, председатель, секретарь.
 */
function canMeetings(): bool
{
    return in_array(userRole(), [1, 2, 4], true);
}

/**
 * Redirect to a URL and stop execution.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Store a flash message in the session.
 *
 * @param string $msg  Message text
 * @param string $type Bootstrap alert type: 'info', 'success', 'warning', 'danger'
 */
function flash(string $msg, string $type = 'info'): void
{
    $_SESSION['_flash'] = ['msg' => $msg, 'type' => $type];
}

/**
 * Consume and return the current flash message, or null if none.
 */
function getFlash(): ?array
{
    if (isset($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

/**
 * Extract the plot number from an address string.
 *
 * Priority:
 *  1. "Участок [№#]? <digits>" — returns the number after the keyword.
 *  2. First standalone number found in the string.
 *
 * @param string $address  e.g. "Участок №42", "ул. 8 Марта уч. 42"
 * @return int             Plot number, or 0 if not found
 */
function plotFromAddress(string $address): int
{
    if (preg_match('/[Уу]часток[\s\xA0]*[№#]?\s*(\d+)/u', $address, $m)) {
        return (int)$m[1];
    }
    return preg_match('/(\d+)/', $address, $m) ? (int)$m[1] : 0;
}
