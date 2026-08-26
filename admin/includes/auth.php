<?php
/**
 * Owner/staff session auth. Include at the top of every protected
 * /admin/ page (after session_start() + db.php).
 */

function requireStaffLogin(): array
{
    if (empty($_SESSION['staff_id'])) {
        header('Location: login.php');
        exit;
    }

    return [
        'id'   => (int) $_SESSION['staff_id'],
        'name' => $_SESSION['staff_name'],
        'role' => $_SESSION['staff_role'],
    ];
}

/**
 * Like requireStaffLogin(), but also restricts to specific roles
 * (e.g. 'admin' for meja/menu/laporan/staf — 'staff'/kasir only
 * gets the order queue). Redirects non-matching roles to orders.php.
 */
function requireRole(array $allowedRoles): array
{
    $staff = requireStaffLogin();
    if (!in_array($staff['role'], $allowedRoles, true)) {
        header('Location: orders.php');
        exit;
    }

    return $staff;
}
