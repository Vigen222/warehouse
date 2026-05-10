<?php
include "../db/db.php";
requireAdmin();

/* ================= ԳՈՐԾՈՂՈՒԹՅՈՒՆՆԵՐ (ACTIONS) ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Դերի փոփոխություն (ներառյալ Block)
    if ($action === 'change_role' && !empty($_POST['user_id'])) {
        $u_id = (int)$_POST['user_id'];
        $new_role = $_POST['role'];
        if ($u_id !== (int)$_SESSION['user']['id']) {
            $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
            $stmt->execute([$new_role, $u_id]);
        }
    }

    // 2. Օգտատիրոջ ջնջում
    if ($action === 'delete_user' && !empty($_POST['user_id'])) {
        $u_id = (int)$_POST['user_id'];
        if ($u_id !== (int)$_SESSION['user']['id']) {
            $conn->prepare("DELETE FROM sales WHERE user_id=?")->execute([$u_id]);
            $conn->prepare("DELETE FROM reports WHERE user_id=?")->execute([$u_id]);
            $conn->prepare("DELETE FROM users WHERE id=?")->execute([$u_id]);
        }
    }

    // 3. Ապրանքի ավելացում
    if ($action === 'add_product') {
        $image_name = "default_product.png";
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $image_name = time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name);
        }
        $admin_id = (int)$_SESSION['user']['id'];
        $conn->prepare("INSERT INTO products (name, description, price, quantity, image, user_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')")
             ->execute([$_POST['name'], $_POST['description'], (float)$_POST['price'], (int)$_POST['quantity'], $image_name, $admin_id]);
    }

    // 4. Ապրանքի խմբագրում (Edit)
    if ($action === 'edit_product' && !empty($_POST['product_id'])) {
        $p_id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
        $stmt->execute([$p_id]);
        $prod = $stmt->fetch();
        $image_name = $prod['image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $image_name = time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name);
        }

        $conn->prepare("UPDATE products SET name=?, description=?, price=?, quantity=?, image=? WHERE id=?")
             ->execute([$_POST['name'], $_POST['description'], (float)$_POST['price'], (int)$_POST['quantity'], $image_name, $p_id]);
    }

    // 5. Ապրանքի ջնջում
    if ($action === 'delete_product' && !empty($_POST['product_id'])) {
        $p_id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
        $stmt->execute([$p_id]);
        $prod = $stmt->fetch();
        if ($prod && !empty($prod['image']) && $prod['image'] !== 'default_product.png') {
            @unlink("../uploads/" . $prod['image']);
        }
        $conn->prepare("DELETE FROM sales WHERE product_id=?")->execute([$p_id]);
        $conn->prepare("DELETE FROM reports WHERE product_id=?")->execute([$p_id]);
        $conn->prepare("DELETE FROM products WHERE id=?")->execute([$p_id]);
    }

    // 6. Բողոքի ջնջում
    if ($action === 'delete_report' && !empty($_POST['report_id'])) {
        $conn->prepare("DELETE FROM reports WHERE id=?")->execute([(int)$_POST['report_id']]);
    }

    header("Location: admin.php");
    exit;
}

/* ================= ՏՎՅԱԼՆԵՐԻ ՀԱՎԱՔԱԳՐՈՒՄ (DATA) ================= */

$admin_id = (int)$_SESSION['user']['id'];
$admin_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$admin_stmt->execute([$admin_id]);
$admin_user = $admin_stmt->fetch();

$users         = $conn->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$products      = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$sales_history = $conn->query("SELECT s.*, u.username, p.name AS product_name FROM sales s JOIN users u ON s.user_id = u.id JOIN products p ON s.product_id = p.id ORDER BY s.sold_at DESC LIMIT 50")->fetchAll();
$reports       = $conn->query("SELECT r.*, r.report_text AS reason, u.username, p.name AS product_name FROM reports r JOIN users u ON r.user_id = u.id LEFT JOIN products p ON p.id = r.product_id ORDER BY r.id DESC")->fetchAll();

$totalUsers    = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalReports  = $conn->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM sales")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <title>Ադմին Վահանակ 🛡️</title>
    <style>
        body { margin:0; font-family: 'Segoe UI', sans-serif; background:#0b1220; color:white; }
        .topbar { display:flex; justify-content:space-between; padding:10px 30px; background:#111827; border-bottom:1px solid #1f2937; align-items:center; }
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .nav-link { color: #94a3b8; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .nav-link:hover { color: white; }
        .user-box { display: flex; align-items: center; gap: 12px; background: rgba(239, 68, 68, 0.1); padding: 5px 15px; border-radius: 50px; border: 1px solid rgba(239, 68, 68, 0.2); }
        .avatar { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; border: 2px solid #ef4444; }
        .admin-name { font-weight: 600; color: #f8fafc; font-size: 14px; }
        .container { padding: 20px; max-width: 1300px; margin: 0 auto; }
        .grid-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:25px; }
        .card { background:#111827; padding:20px; border-radius:12px; border:1px solid #1f2937; margin-bottom: 20px; }
        table { width:100%; border-collapse:collapse; margin-top: 10px; }
        th, td { padding:12px; text-align:left; border-bottom:1px solid #1f2937; }
        th { color: #94a3b8; font-weight: 500; }
        tr.is-blocked { background: rgba(255, 0, 0, 0.05); }
        .btn { padding:7px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; color:white; transition: 0.2s; }
        .btn:hover { opacity: 0.8; }
        .btn-red { background: #ef4444; }
        .btn-blue { background: #3b82f6; }
        .btn-green { background: #22c55e; }
        .btn-yellow { background: #f59e0b; }
        .badge-blocked { background: #7f1d1d; color: #f87171; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .prod-img { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #334155; }
        input, select { padding:8px; border-radius:6px; background:#1f2937; color:white; border:1px solid #334155; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:#111827; border:1px solid #1f2937; border-radius:16px; padding:30px; width:100%; max-width:480px; }
        .modal-box h3 { margin:0 0 20px 0; font-size:18px; }
        .modal-box label { display:block; color:#94a3b8; font-size:12px; margin-bottom:5px; margin-top:12px; }
        .modal-box input { width:100%; box-sizing:border-box; }
        .modal-actions { display:flex; gap:10px; margin-top:20px; }
    </style>
</head>
<body>

<div class="topbar">
    <b style="font-size: 20px;">🛡️ ADMIN PANEL</b>
    <div class="nav-right">
        <a href="../functions/settings.php" class="nav-link">⚙ Կարգավորումներ</a>
        <div class="user-box">
            <img src="../uploads/<?= htmlspecialchars($admin_user['image'] ?: 'default.png') ?>" class="avatar">
            <span class="admin-name"><?= htmlspecialchars($admin_user['username']) ?></span>
        </div>
        <a href="../auth/logout.php" class="nav-link" style="color:#ef4444; font-weight:bold;">Ելք</a>
    </div>
</div>

<div class="container">

    <!-- Статистика -->
    <div class="grid-stats">
        <div class="card">👥 Օգտատերեր <h2><?= $totalUsers ?></h2></div>
        <div class="card">📦 Ապրանքներ <h2><?= $totalProducts ?></h2></div>
        <div class="card">🚨 Բողոքներ <h2><?= $totalReports ?></h2></div>
        <div class="card">💵 Եկամուտ <h2>$<?= number_format($totalRevenue, 2) ?></h2></div>
    </div>

    <!-- Управление пользователями -->
    <div class="card">
        <h3>👥 Օգտատերերի կառավարում</h3>
        <table>
            <thead>
                <tr>
                    <th>Նկար</th><th>Անուն</th><th>Դերը</th><th>Գործողություն</th><th>Ջնջել</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr class="<?= $u['role'] === 'blocked' ? 'is-blocked' : '' ?>">
                    <td>
                        <img src="../uploads/<?= htmlspecialchars($u['image'] ?: 'default.png') ?>"
                             style="width:35px; height:35px; border-radius:50%; object-fit:cover; border: 1px solid #334155;">
                    </td>
                    <td>
                        <?= htmlspecialchars($u['username']) ?>
                        <?php if($u['role'] === 'blocked'): ?>
                            <span class="badge-blocked">BLOCKED</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="color: <?= $u['role'] === 'admin' ? '#ef4444' : '#94a3b8' ?>;">
                            <?= strtoupper(htmlspecialchars($u['role'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['id'] != $_SESSION['user']['id']): ?>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role">
                                <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
                                <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                <option value="blocked" <?= $u['role']=='blocked'?'selected':'' ?>>❌ Block</option>
                            </select>
                            <button class="btn btn-blue">OK</button>
                        </form>
                        <?php else: ?>
                            <small style="color: #4b5563;">(Դուք եք)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($u['id'] != $_SESSION['user']['id']): ?>
                        <form method="POST" onsubmit="return confirm('Ջնջե՞լ այս օգտատիրոջը:');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-red">🗑</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Товары -->
    <div class="card">
        <h3>📦 Ապրանքներ</h3>
        <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="action" value="add_product">
            <div><label>Նկար</label><br><input type="file" name="image" accept="image/*" style="width:180px;"></div>
            <div><label>Անուն</label><br><input name="name" required></div>
            <div><label>Գին</label><br><input name="price" style="width:80px;" required></div>
            <div><label>Քանակ</label><br><input name="quantity" style="width:70px;" required></div>
            <div style="flex-grow:1;"><label>Նկարագրություն</label><br><input name="description" style="width:100%;"></div>
            <button class="btn btn-green">+ Ավելացնել</button>
        </form>
        <table>
            <thead>
                <tr><th>Նկար</th><th>Անվանում</th><th>Նկարագրություն</th><th>Գին</th><th>Քանակ</th><th>Խմբագրել</th><th>Ջնջել</th></tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td><img src="../uploads/<?= htmlspecialchars($p['image'] ?: 'default_product.png') ?>" class="prod-img"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td style="color:#94a3b8; font-size:13px;"><?= htmlspecialchars($p['description'] ?: '—') ?></td>
                    <td>$<?= number_format($p['price'], 2) ?></td>
                    <td><?= $p['quantity'] ?> հատ</td>
                    <td>
                        <button class="btn btn-yellow" onclick='openEditModal(<?= json_encode([
                            "id"          => $p["id"],
                            "name"        => $p["name"],
                            "description" => $p["description"],
                            "price"       => $p["price"],
                            "quantity"    => $p["quantity"],
                        ]) ?>)'>✏️ Edit</button>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button class="btn btn-red">Ջնջել</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- История продаж -->
    <div class="card">
        <h3>💰 Վաճառքների պատմություն</h3>
        <div style="max-height: 250px; overflow-y: auto;">
            <table>
                <thead>
                    <tr><th>Վաճառող</th><th>Ապրանք</th><th>Գումար</th><th>Ամսաթիվ</th></tr>
                </thead>
                <tbody>
                    <?php foreach($sales_history as $sale): ?>
                    <tr>
                        <td>👤 <?= htmlspecialchars($sale['username']) ?></td>
                        <td><?= htmlspecialchars($sale['product_name']) ?></td>
                        <td style="color:#10b981;">$<?= number_format($sale['total_price'], 2) ?></td>
                        <td><?= date('d.m H:i', strtotime($sale['sold_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Репорты -->
    <div class="card">
        <h3>🚨 Բողոքներ (Reports)</h3>
        <table>
            <thead>
                <tr><th>Ուղարկող</th><th>Ապրանք</th><th>Պատճառ</th><th>Ջնջել</th></tr>
            </thead>
            <tbody>
                <?php foreach($reports as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td><?= htmlspecialchars($r['product_name'] ?? '---') ?></td>
                    <td style="color:#facc15;"><?= htmlspecialchars($r['reason']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete_report">
                            <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                            <button class="btn btn-red">X</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3>✏️ Խմբագրել ապրանքը</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit_id">

            <label>Անուն</label>
            <input type="text" name="name" id="edit_name" required>

            <label>Նկարագրություն</label>
            <input type="text" name="description" id="edit_description">

            <label>Գին</label>
            <input type="number" name="price" id="edit_price" step="0.01" required>

            <label>Քանակ</label>
            <input type="number" name="quantity" id="edit_quantity" required>

            <label>Նկար (թողնել դատարկ՝ չփոխելու համար)</label>
            <input type="file" name="image" accept="image/*">

            <div class="modal-actions">
                <button type="submit" class="btn btn-yellow" style="flex:1;">💾 Պահպանել</button>
                <button type="button" class="btn" style="background:#374151; flex:1;" onclick="closeEditModal()">Չեղարկել</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(p) {
    document.getElementById('edit_id').value          = p.id;
    document.getElementById('edit_name').value        = p.name;
    document.getElementById('edit_description').value = p.description || '';
    document.getElementById('edit_price').value       = p.price;
    document.getElementById('edit_quantity').value    = p.quantity;
    document.getElementById('editModal').classList.add('active');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

</body>
</html>