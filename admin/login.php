<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: ' . ($_SESSION['staff_role'] === 'admin' ? 'dashboard.php' : 'orders.php'));
    exit;
}

$error = null;
$MAX_ATTEMPTS = 5;
$LOCKOUT_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM staff_users WHERE email = ?');
    $stmt->execute([$email]);
    $staff = $stmt->fetch();

    $isLocked = $staff && $staff['locked_until'] && strtotime($staff['locked_until']) > time();

    if ($isLocked) {
        $error = 'Akun ini terkunci sementara karena terlalu banyak percobaan gagal. Coba lagi dalam beberapa menit.';
    } elseif ($staff && password_verify($password, $staff['password_hash'])) {
        $pdo->prepare('UPDATE staff_users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')->execute([$staff['id']]);
        session_regenerate_id(true);
        $_SESSION['staff_id'] = $staff['id'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['staff_role'] = $staff['role'];
        header('Location: ' . ($staff['role'] === 'admin' ? 'dashboard.php' : 'orders.php'));
        exit;
    } else {
        if ($staff) {
            $attempts = (int) $staff['failed_attempts'] + 1;
            if ($attempts >= $MAX_ATTEMPTS) {
                $pdo->prepare('UPDATE staff_users SET failed_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?')
                    ->execute([$LOCKOUT_MINUTES, $staff['id']]);
            } else {
                $pdo->prepare('UPDATE staff_users SET failed_attempts = ? WHERE id = ?')->execute([$attempts, $staff['id']]);
            }
        }
        // Delay tetap dijalankan walau email tidak ditemukan, supaya waktu respons
        // tidak membocorkan apakah email itu terdaftar atau tidak.
        usleep(400000);
        $error = 'Email atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Owner — Masakan Nusantara</title>
<link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../assets/css/pages/owner.css">
</head>
<body>
<div class="owner-login-wrap">
    <div class="owner-login-box">
        <h1><i class="fa-solid fa-store"></i> Masakan Nusantara</h1>
        <p class="sub">Owner Panel</p>

        <?php if ($error): ?>
        <div class="owner-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="owner-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="owner-field">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="owner-btn owner-btn--primary owner-btn--block">Masuk</button>
        </form>
    </div>
</div>
</body>
</html>
