<?php
session_start();
include "../db/db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] === 'blocked') {
            $error = "Ձեր հաշիվը արգելափակված է:";
        } else {
            $_SESSION['user'] = $user;
            if ($user['role'] === 'admin') {
                header("Location: ../admin/admin.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
        }
    } else {
        $error = "Սխալ օգտանուն կամ գաղտնաբառ:";
    }
}
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <title>Մուտք - ՊԱՀԵՍՏ</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #0f172a;
            display: flex; justify-content: center; align-items: center;
            height: 100vh; color: #f1f5f9;
        }
        .login-card {
            background: #1e293b;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 1px solid #334155;
            width: 100%; max-width: 400px;
            text-align: center;
        }
        .login-card h1 { margin-bottom: 25px; font-size: 26px; font-weight: 800; color: white; }
        .login-card input {
            width: 100%; padding: 14px 16px; margin-bottom: 15px;
            border-radius: 10px; border: 1px solid #334155;
            background: #0f172a; color: white; font-size: 15px;
            box-sizing: border-box; transition: all 0.2s ease;
        }
        .login-card input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .login-card button {
            width: 100%; padding: 14px; border: none; border-radius: 10px;
            background: #3b82f6; color: white; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: background 0.2s ease;
            margin-top: 10px;
        }
        .login-card button:hover { background: #2563eb; }
        .error {
            background: rgba(239, 68, 68, 0.1); color: #f87171;
            padding: 12px; border-radius: 8px; margin-bottom: 20px;
            font-size: 14px; border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .auth-footer { margin-top: 25px; display: flex; flex-direction: column; gap: 10px; }
        .footer-link { color: #94a3b8; text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .footer-link:hover { color: #3b82f6; }
        .forgot-link { font-size: 13px; text-align: right; display: block; margin-top: -5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="login-card">
    <h1>⬡ ՊԱՀԵՍՏ</h1>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Օգտանուն" required autofocus>
        <input type="password" name="password" placeholder="Գաղտնաբառ" required>
        <a href="forgot-password.php" class="footer-link forgot-link">Մոռացե՞լ եք գաղտնաբառը:</a>
        <button type="submit">Մուտք</button>
    </form>

    <div class="auth-footer">
        <a href="register.php" class="footer-link">Չունե՞ք հաշիվ: Գրանցվել</a>
    </div>
</div>

</body>
</html>