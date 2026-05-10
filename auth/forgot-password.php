<?php
include "../db/db.php";

$message = "";
$error = "";
$step = 1; // Шаг 1: ввод никнейма, Шаг 2: ввод нового пароля
$reset_user_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ШАГ 1: Проверка никнейма
    if (isset($_POST['check_username'])) {
        $username = trim($_POST['username']);
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $step = 2;
            $reset_user_id = $user['id'];
        } else {
            $error = "Օգտատերը չի գտնվել:"; // Пользователь не найден
        }
    }

    // ШАГ 2: Сохранение нового пароля
    if (isset($_POST['reset_password'])) {
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'];

        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            
            $message = "Գաղտնաբառը հաջողությամբ փոխվեց:"; // Пароль успешно изменен
            $step = 3; // Конец процесса
        } else {
            $error = "Մուտքագրեք նոր գաղտնաբառը:";
            $step = 2;
            $reset_user_id = $user_id;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <title>Վերականգնել գաղտնաբառը</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #0f172a;
            display: flex; justify-content: center; align-items: center;
            height: 100vh; color: #f1f5f9;
        }

        .card {
            background: #1e293b;
            padding: 40px;
            border-radius: 16px;
            border: 1px solid #334155;
            width: 100%; max-width: 380px;
            text-align: center;
        }

        h2 { margin-bottom: 10px; font-size: 24px; color: white; }
        p { color: #94a3b8; font-size: 14px; margin-bottom: 25px; line-height: 1.4; }

        input {
            width: 100%; padding: 14px; margin-bottom: 15px;
            border-radius: 10px; border: 1px solid #334155;
            background: #0f172a; color: white; box-sizing: border-box;
            font-size: 15px;
        }

        button {
            width: 100%; padding: 14px; border: none; border-radius: 10px;
            background: #3b82f6; color: white; font-weight: 600; cursor: pointer;
            font-size: 16px; transition: background 0.2s;
        }
        button:hover { background: #2563eb; }

        .error { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; border: 1px solid rgba(239, 68, 68, 0.2); }
        .success { background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; border: 1px solid rgba(34, 197, 94, 0.2); }

        .back { display: block; margin-top: 20px; color: #94a3b8; text-decoration: none; font-size: 13px; }
        .back:hover { color: white; }
    </style>
</head>
<body>

<div class="card">
    <h2>Վերականգնում</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <p>Մուտքագրեք ձեր <b>օգտանունը (username)</b> գաղտնաբառը փոխելու համար:</p>
        <form method="POST">
            <input type="text" name="username" placeholder="Օգտանուն" required autofocus>
            <button type="submit" name="check_username">Շարունակել</button>
        </form>
    <?php endif; ?>

    <?php if ($step === 2): ?>
        <p>Մուտքագրեք ձեր <b>նոր գաղտնաբառը</b>:</p>
        <form method="POST">
            <input type="hidden" name="user_id" value="<?= $reset_user_id ?>">
            <input type="password" name="new_password" placeholder="Նոր գաղտնաբառ" required autofocus>
            <button type="submit" name="reset_password">Պահպանել</button>
        </form>
    <?php endif; ?>

    <?php if ($step === 3): ?>
        <p>Ձեր գաղտնաբառը հաջողությամբ փոխվել է: Այժմ կարող եք մուտք գործել:</p>
        <a href="login.php" style="display:block; background:#22c55e; color:white; padding:12px; border-radius:10px; text-decoration:none; font-weight:bold;">Մուտք գործել</a>
    <?php endif; ?>
    
    <?php if ($step !== 3): ?>
        <a href="login.php" class="back">← Վերադառնալ մուտքի էջ</a>
    <?php endif; ?>
</div>

</body>
</html>