<?php
include "../db/db.php"; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $description = $_POST['description'] ?? '';
    $image_db_name = "default.png";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_file_name = time() . "_" . uniqid() . "." . $extension; 
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_db_name = $new_file_name;
        }
    }

    try {
        $query = "INSERT INTO products (user_id, name, price, quantity, description, image, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute([$user_id, $name, $price, $quantity, $description, $image_db_name])) {
            header("Location: ../index.php");
            exit; 
        }
    } catch (PDOException $e) {
        die("Սխալ բазայի հետ: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ավելացնել Ապրանք</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #0f172a; color: #fff; font-family: sans-serif; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .add-card { background: #1e293b; padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .form-grid { display: flex; flex-direction: column; gap: 15px; }
        .form-row { display: flex; gap: 15px; }
        input, textarea { background: #0f172a; border: 1px solid #334155; padding: 12px; border-radius: 10px; color: #fff; outline: none; width: 100%; box-sizing: border-box; font-family: sans-serif; font-size: 14px; }
        input:focus, textarea:focus { border-color: #3b82f6; }
        textarea { resize: vertical; min-height: 100px; }
        .file-label { background: #0f172a; border: 1px dashed #334155; padding: 15px; border-radius: 10px; color: #94a3b8; text-align: center; cursor: pointer; display: block; }
        .file-label:hover { border-color: #3b82f6; color: #fff; }
        .submit-btn { background: #3b82f6; color: #fff; border: none; padding: 15px; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px; }
        .submit-btn:hover { background: #2563eb; transform: translateY(-2px); }
        .back-link { display: inline-block; margin-bottom: 20px; color: #94a3b8; text-decoration: none; }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">
   <a href="/warehouse_project3/index.php" class="back-link">← Վերադառնալ գլխավոր էջ</a>

    <div class="add-card">
        <h2 style="margin-top: 0;">📦 Ավելացնել նոր ապրանք</h2>
        <form action="sell.php" method="POST" enctype="multipart/form-data" class="form-grid">
            <input type="text" name="name" placeholder="Ապրանքի անվանում" required>
            
            <div class="form-row">
                <input type="number" step="0.01" name="price" placeholder="Գին $" required>
                <input type="number" name="quantity" placeholder="Քանակ" required>
            </div>

            <textarea name="description" placeholder="Ապրանքի նկարագրություն (необязательно)"></textarea>
            
            <div class="file-label" onclick="document.getElementById('fileInput').click()">
                <span id="file-label-text">📷 Ընտրել ապրանքի նկարը</span>
                <input type="file" id="fileInput" name="image" accept="image/*" style="display: none;" 
                       onchange="document.getElementById('file-label-text').innerText = '✅ Նկարն ընտրված է'">
            </div>
            
            <button type="submit" class="submit-btn">Հաստատել և Ավելացնել</button>
        </form>
    </div>
</div>

</body>
</html>