<?php
include "../db/db.php"; // session_start()-ը արդեն կա db.php-ի մեջ

ini_set('display_errors', 1);
error_reporting(E_ALL);

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $imageName = 'default.png';

    // ===== 1. ՎԱԼԻԴԱՑԻԱ =====
    if (strlen($username) < 3) {
        $error = "Օգտանունը պետք է լինի առնվազն 3 նիշ։";
    } elseif (strlen($password) < 4) {
        $error = "Գաղտնաբառը պետք է լինի առնվազն 4 նիշ։";
    } else {
        // ===== 2. ՍՏՈՒԳՈՒՄ ԵՆՔ ՕԳՏԱՏԻՐՈՋ ԱՌԿԱՅՈՒԹՅՈՒՆԸ =====
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "Այս օգտանունն արդեն զբաղված է։";
        } else {
            // ===== 3. ՆԿԱՐԻ ՎԵՐԲԵՌՆՈՒՄ =====
            if (!empty($_FILES['image']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $fileName = $_FILES['image']['name'];
                $fileSize = $_FILES['image']['size'];
                $tmpName = $_FILES['image']['tmp_name'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Ավելացնում ենք չափի սահմանափակում (օրինակ՝ 2MB)
                if ($fileSize > 2 * 1024 * 1024) {
                    $error = "Նկարի չափը չպետք է գերազանցի 2MB-ը։";
                } elseif (in_array($ext, $allowed)) {
                    $imageName = uniqid() . "." . $ext;
                    
                    // ՈՒՇԱԴՐՈՒԹՅՈՒՆ: Քանի որ մենք auth/ պապկայում ենք, պետք է գնանք ../uploads/
                    $uploadDir = "../uploads/"; 
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    if (!move_uploaded_file($tmpName, $uploadDir . $imageName)) {
                        $imageName = 'default.png'; // Եթե վերբեռնումը ձախողվի
                    }
                } else {
                    $error = "Անթույլատրելի ֆայլի տեսակ։";
                }
            }

            // Եթե նկարի հետ կապված սխալ չկա, շարունակում ենք
            if (empty($error)) {
                // ===== 4. ԳԱՂՏՆԱԲԱՌԻ ՀԱՇԱՎՈՐՈՒՄ =====
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // ===== 5. ԳՐԱՆՑՈՒՄ =====
                $stmt = $conn->prepare("
                    INSERT INTO users (username, password, image, role)
                    VALUES (?, ?, ?, 'user')
                ");

                if ($stmt->execute([$username, $hash, $imageName])) {
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Տեղի է ունեցել սխալ գրանցման ժամանակ։";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Գրանցում — Պահեստ</title>
    <style>
        /* ԸՆԴՀԱՆՈՒՐ ՈՃԵՐ */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #0b1220; 
            color: white; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }

        /* ՔԱՐՏԻ ՈՃԸ */
        .register-card {
            background: #111827;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #1f2937;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 380px;
            text-align: center;
        }

        h2 { 
            margin-bottom: 25px; 
            font-size: 24px; 
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* ՖՈՐՄԱՅԻ ԷԼԵՄԵՆՏՆԵՐ */
        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        input[name="username"] {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            background: #0b1220;
            border: 1px solid #334155;
            color: white;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
            font-size: 15px;
        }

        input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        /* ՖԱՅԼԻ ՎԵՐԲԵՌՆՈՒՄ */
        input[type="file"] {
            width: 100%;
            background: #0b1220;
            color: #94a3b8;
            padding: 8px;
            border-radius: 10px;
            border: 1px dashed #334155;
            cursor: pointer;
        }

        input[type="file"]::file-selector-button {
            background: #1f2937;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            margin-right: 10px;
            cursor: pointer;
        }

        /* ԿՈՃԱԿ */
        button {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
            margin-top: 10px;
        }

        button:hover {
            background: #2563eb;
        }

        /* ՍԽԱԼԻ ՀԱՂՈՐԴԱԳՐՈՒԹՅՈՒՆ */
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* ՀՂՈՒՄՆԵՐ */
        .login-links {
            margin-top: 20px;
            font-size: 14px;
            color: #94a3b8;
        }

        .login-links a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: bold;
        }

        .login-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="register-card">
    <h2>⬡ Գրանցում</h2>

    <?php if ($error): ?>
        <div class="alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Օգտանուն</label>
            <input name="username" type="text" placeholder="Մուտքագրեք օգտանունը"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Գաղտնաբառ</label>
            <input name="password" type="password" placeholder="Մուտքագրեք գաղտնաբառը" required>
        </div>

        <div class="form-group">
            <label>Պրոֆիլի նկար</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit">Գրանցվել</button>
    </form>

    <div class="login-links">
        Արդեն հաշիվ ունե՞ք։ <a href="login.php">Մուտք</a>
    </div>
</div>

</body>
</html>