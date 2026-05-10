<?php
include "../db/db.php";
requireLogin();

$user_id = (int)$_SESSION['user']['id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username)) {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$username, $user_id]);
        $_SESSION['user']['username'] = $username;
        $message = "Պրոֆիլը թարմացվեց:";
    }

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $message = "Գաղտնաբառը փոխվեց:";
    }

    if (!empty($_FILES['avatar']['name'])) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = "user_" . $user_id . "_" . time() . "." . $ext;
        $target = "../uploads/" . $filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            $stmt = $conn->prepare("UPDATE users SET image = ? WHERE id = ?");
            $stmt->execute([$filename, $user_id]);
            $_SESSION['user']['image'] = $filename;
            $message = "Նկարը թարմացվեց:";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <title>Կարգավորումներ</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { 
            background-color: #0f172a; 
            color: white; 
            font-family: sans-serif; 
            margin: 0; 
            padding: 0;
        }

        /* 1. Header-ի ուղղում (Full width) */
        .nav { 
            width: 100%; 
            background: #161d2f; 
            padding: 15px 30px; 
            display: flex; 
            align-items: center; 
            box-sizing: border-box;
            border-bottom: 1px solid #334155;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .back-link { color: #3b82f6; text-decoration: none; font-weight: bold; margin-right: 20px; }
        .nav-title { font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }

        .container { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding-top: 80px; /* Տեղ header-ի համար */
        }

        .settings-card { 
            background: #1e293b; 
            padding: 40px; 
            border-radius: 20px; 
            border: 1px solid #334155; 
            width: 100%;
            max-width: 450px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.4); 
            text-align: center;
        }

        /* 2. Ավատարի և ֆայլի ընտրության հատվածը */
        .avatar-upload-section { margin-bottom: 30px; }
        .settings-avatar { 
            width: 130px; 
            height: 130px; 
            border-radius: 50%; 
            border: 4px solid #3b82f6; 
            object-fit: cover; 
            margin-bottom: 15px;
        }
        .file-input-container {
            background: #0f172a;
            border: 1px dashed #334155;
            padding: 10px;
            border-radius: 10px;
            cursor: pointer;
        }
        .file-input { color: #94a3b8; font-size: 13px; width: 100%; }

        /* 3. Ինփութների ոճը */
        .form-group { text-align: left; margin-bottom: 20px; }
        .stat-label { 
            display: block; 
            color: #3b82f6; 
            font-size: 11px; 
            margin-bottom: 8px; 
            font-weight: bold;
            text-transform: uppercase; 
        }
        .f-input { 
            width: 100%; 
            background: #0f172a; 
            border: 1px solid #334155; 
            color: white; 
            padding: 14px; 
            border-radius: 10px; 
            box-sizing: border-box;
            font-size: 15px;
        }
        .f-input:focus { border-color: #3b82f6; outline: none; }

        .btn-save { 
            width: 100%; 
            background: #3b82f6; 
            color: white; 
            border: none; 
            padding: 15px; 
            border-radius: 10px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 14px;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-save:hover { background: #2563eb; transform: translateY(-2px); }
        
        .alert { 
            background: #059669; 
            color: white; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 14px; 
        }
    </style>
</head>
<body>

<div class="nav">
    <a href="../index.php" class="back-link">← ԳԼԽԱՎՈՐ</a>
    <div class="nav-title">ԿԱՐԳԱՎՈՐՈՒՄՆԵՐ</div>
</div>

<div class="container">
    <div class="settings-card">
        <?php if($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <form action="settings.php" method="POST" enctype="multipart/form-data">
            
            <div class="avatar-upload-section">
                <img src="../uploads/<?= htmlspecialchars($user['image'] ?? 'default.png') ?>" class="settings-avatar">
                <div class="form-group">
                    <label class="stat-label" style="text-align:center;">ՓՈԽԵԼ ՆԿԱՐԸ</label>
                    <div class="file-input-container">
                        <input type="file" name="avatar" class="file-input">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="stat-label">ՕԳՏԱՆՈՒՆ</label>
                <input type="text" name="username" class="f-input" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label class="stat-label">ՆՈՐ ԳԱՂՏՆԱԲԱՌ</label>
                <input type="password" name="password" class="f-input" placeholder="********">
                <small style="color: #64748b; font-size: 11px;">Թողնել դատարկ, եթե չեք ուզում փոխել</small>
            </div>

            <button type="submit" class="btn-save">ՊԱՀՊԱՆԵԼ ՓՈՓՈԽՈՒԹՅՈՒՆՆԵՐԸ</button>
        </form>
    </div>
</div>

</body>
</html>