<?php
include "../db/db.php";
requireLogin();

$user_id = (int)$_SESSION['user']['id'];

// --- ОБРАБОТКА ДЕЙСТВИЙ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $name = trim($_POST['name']);
        $price = (float)$_POST['price'];
        $qty = (int)$_POST['qty'];
        $image_name = null;

        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); 
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = time() . "_" . uniqid() . "." . $file_ext; 
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name);
        }

        if ($name && $price > 0 && $qty >= 0) {
            $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, user_id, image, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $price, $qty, $user_id, $image_name]);
        }
    }

    if ($action === 'edit_product') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $price = (float)$_POST['price'];
        $qty = (int)$_POST['qty'];

        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $price, $qty, $id, $user_id]);
    }

    header("Location: user.php"); exit;
}

$my_products = $conn->prepare("SELECT * FROM products WHERE user_id = ? AND status = 'active' ORDER BY id DESC");
$my_products->execute([$user_id]);
$products = $my_products->fetchAll();
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <title>Իմ Ապրանքները</title>
    <style>
        body { margin: 0; background-color: #0f172a; color: white; font-family: 'Segoe UI', sans-serif; display: flex; flex-direction: column; align-items: center; }
        
        /* Шапка */
        .nav-header { 
            width: 100%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 15px 40px; 
            background: #1e293b; 
            border-bottom: 1px solid #334155; 
            box-sizing: border-box;
        }

        .container { width: 100%; max-width: 1200px; padding: 40px 20px; box-sizing: border-box; }

        /* Форма добавления - Компактный блок слева */
        .add-section { 
            background: #1e293b; 
            border-radius: 16px; 
            padding: 30px; 
            border: 1px solid #334155; 
            width: 100%; 
            max-width: 450px; 
            margin-bottom: 50px;
        }

        /* Улучшенное окно загрузки картинки */
        .file-upload-wrapper {
            position: relative;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            text-align: center;
            transition: 0.3s;
        }
        .file-upload-wrapper:hover { border-color: #3b82f6; }
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0; top: 0; opacity: 0;
            width: 100%; height: 100%;
            cursor: pointer;
        }
        .file-upload-text { font-size: 13px; color: #94a3b8; }

        /* Сетка товаров */
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); 
            gap: 25px; 
            width: 100%;
        }

        .card { 
            background: #1e293b; 
            border-radius: 14px; 
            padding: 18px; 
            border: 1px solid #334155; 
            position: relative; 
            transition: 0.2s;
        }
        .card:hover { border-color: #3b82f6; transform: translateY(-3px); }

        .btn-edit {
            position: absolute; top: 12px; right: 12px;
            background: transparent; color: #94a3b8; border: none;
            cursor: pointer; font-size: 16px; opacity: 0.4; transition: 0.2s;
        }
        .card:hover .btn-edit { opacity: 1; color: #f59e0b; }

        input[type="text"], input[type="number"] { 
            background: #0f172a; border: 1px solid #334155; color: white; 
            padding: 12px; border-radius: 8px; width: 100%; margin-bottom: 12px; 
            box-sizing: border-box; 
        }

        .btn-submit { 
            background: #3b82f6; color: white; border: none; padding: 14px; 
            border-radius: 8px; width: 100%; font-weight: bold; cursor: pointer; font-size: 15px;
        }

        .img-preview { width: 100%; height: 170px; object-fit: cover; border-radius: 10px; margin-bottom: 12px; background: #0f172a; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: #1e293b; padding: 30px; border-radius: 16px; border: 1px solid #334155; width: 90%; max-width: 400px; }
    </style>
</head>
<body>

<div class="nav-header">
    <a href="../index.php" style="color:white; text-decoration:none; font-size:14px;">← Գլխավոր</a>
    <div style="font-weight:bold; font-size:18px;">ԻՄ ՀԱՅՏԱՐԱՐՈՒԹՅՈՒՆՆԵՐԸ</div>
    <div style="width:70px;"></div>
</div>

<div class="container">
    <div class="add-section">
        <h3 style="margin:0 0 20px 0; color:#94a3b8; display:flex; align-items:center; gap:8px;">
            <span style="font-size:24px;">+</span> Ավելացնել ապրանք
        </h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            <input type="text" name="name" placeholder="Անվանում" required>
            <div style="display:flex; gap:10px;">
                <input type="number" name="price" step="0.01" placeholder="Գին $" required>
                <input type="number" name="qty" placeholder="Քանակ" required>
            </div>
            
            <div class="file-upload-wrapper">
                <span class="file-upload-text" id="file-name">📷 Նկար (Ընտրել ֆայլը)</span>
                <input type="file" name="image" accept="image/*" onchange="updateFileName(this)">
            </div>

            <button type="submit" class="btn-submit">Ստեղծել</button>
        </form>
    </div>

    <h3 style="margin-bottom:25px; color:#94a3b8; font-size:16px; display:flex; align-items:center; gap:10px;">
        📦 Իմ ակտիվ ապրանքները
    </h3>

    <div class="grid">
        <?php foreach($products as $p): ?>
        <div class="card">
            <button class="btn-edit" onclick='openEditModal(<?= json_encode($p) ?>)'>✏️</button>
            
            <?php if($p['image']): ?>
                <img src="uploads/<?= $p['image'] ?>" class="img-preview">
            <?php else: ?>
                <div class="img-preview" style="display:flex; align-items:center; justify-content:center; font-size:50px;">📦</div>
            <?php endif; ?>
            
            <h4 style="margin:0; font-size:17px;"><?= htmlspecialchars($p['name']) ?></h4>
            <div style="color:#22c55e; font-size:1.3rem; font-weight:bold; margin:10px 0;">$<?= number_format($p['price'], 2) ?></div>
            <div style="color:#94a3b8; font-size:13px;">Պահեստում: <?= $p['quantity'] ?> հատ</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Խմբագրել ապրանքը</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="id" id="edit_id">
            <input type="text" name="name" id="edit_name" required>
            <input type="number" name="price" id="edit_price" step="0.01" required>
            <input type="number" name="qty" id="edit_qty" required>
            <button type="submit" class="btn-submit" style="background:#f59e0b;">Պահպանել</button>
            <button type="button" onclick="closeModal()" style="background:none; color:#94a3b8; border:none; width:100%; margin-top:15px; cursor:pointer;">Չեղարկել</button>
        </form>
    </div>
</div>
/*Js code*/
<script>
function updateFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : "📷 Նկար (Ընտրել ֆայլը)";
    document.getElementById('file-name').innerText = fileName;
}

function openEditModal(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_qty').value = p.quantity;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
window.onclick = function(e) { if (e.target.id == 'editModal') closeModal(); }
</script>

</body>
</html>