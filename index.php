<?php
include "db/db.php";
requireLogin();

$check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$check->execute([$_SESSION['user']['id']]);
$check_user = $check->fetch();
if (!$check_user || $check_user['role'] === 'blocked') {
    session_destroy();
    header("Location: /warehouse_project3/auth/login.php");
    exit;
}

if (isAdmin()) {
    header("Location: admin/admin.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$total_q = $conn->prepare("SELECT COUNT(*) as cnt, SUM(quantity_sold) as qty, SUM(total_price) as sum FROM sales WHERE user_id = ?");
$total_q->execute([$user_id]);
$total = $total_q->fetch();

$best_q = $conn->prepare("
    SELECT p.name, SUM(s.quantity_sold) as total_qty
    FROM sales s JOIN products p ON s.product_id = p.id
    WHERE s.user_id = ? GROUP BY s.product_id ORDER BY total_qty DESC LIMIT 1
");
$best_q->execute([$user_id]);
$best_item = $best_q->fetch();

$today_q = $conn->prepare("SELECT SUM(total_price) as sum, COUNT(*) as cnt FROM sales WHERE user_id = ? AND DATE(sold_at) = CURDATE()");
$today_q->execute([$user_id]);
$today = $today_q->fetch();

$yesterday_q = $conn->prepare("SELECT SUM(total_price) as sum, COUNT(*) as cnt FROM sales WHERE user_id = ? AND DATE(sold_at) = SUBDATE(CURDATE(), 1)");
$yesterday_q->execute([$user_id]);
$yesterday = $yesterday_q->fetch();

$search = $_GET['search'] ?? '';
$conditions = ["p.status = 'active'"];
$params = [];

if ($search !== '') {
    $conditions[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

$where = implode(" AND ", $conditions);
$query = "SELECT p.*, COALESCE(u.username, (SELECT username FROM users WHERE role='admin' LIMIT 1)) as owner_name FROM products p LEFT JOIN users u ON p.user_id = u.id WHERE $where ORDER BY p.id DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ՊԱՀԵՍՏ - Գլխավոր</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .nav { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; background: #161d2f; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #3b82f6; }
        .nav-link { color: #94a3b8; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px; transition: 0.3s; }
        .nav-link:hover { color: #3b82f6; }
        .nav-link.exit { color: #ef4444; font-weight: bold; }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 25px 0; }
        .stat-card { background: #161d2f; padding: 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); }
        .stat-label { display: block; color: #64748b; font-size: 10px; letter-spacing: 1px; margin-bottom: 8px; text-transform: uppercase; }
        .stat-val { font-size: 20px; font-weight: bold; }
        .stat-sub { font-size: 12px; color: #475569; margin-top: 5px; }
        .search-section { background: #161d2f; padding: 25px; border-radius: 20px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.05); }
        .filter-input { background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 10px 12px; color: #fff; outline: none; width: 100%; box-sizing: border-box; font-size: 14px; transition: 0.2s; }
        .filter-input:focus { border-color: #3b82f6; }
        .filter-actions { display: flex; gap: 10px; margin-top: 10px; }
        .filter-btn { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; padding: 11px 28px; border-radius: 10px; border: none; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .filter-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59,130,246,0.4); }
        .reset-btn { background: rgba(255,255,255,0.05); color: #94a3b8; padding: 11px 20px; border-radius: 10px; border: 1px solid #334155; text-decoration: none; font-size: 14px; transition: 0.2s; display: inline-flex; align-items: center; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: #161d2f; padding: 20px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.05); position: relative; }
        .product-img { width: 100%; height: 180px; background: #0f172a; border-radius: 12px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .product-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-desc { color: #64748b; font-size: 12px; margin-bottom: 10px; line-height: 1.5; border-left: 2px solid #334155; padding-left: 8px; }
        .sell-btn { flex: 1; background: #22c55e; color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .sell-btn:hover { background: #16a34a; }
        .report-container { margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05); }
        .report-form { display: flex; gap: 5px; }
        .report-input { flex: 1; background: #0f172a; border: 1px solid #1e293b; border-radius: 6px; padding: 5px 10px; color: #94a3b8; font-size: 12px; outline: none; }
        .report-btn { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); border-radius: 6px; cursor: pointer; padding: 5px 10px; }
        .results-count { color: #64748b; font-size: 13px; margin-bottom: 20px; }
        .results-count span { color: #3b82f6; font-weight: bold; }
    </style>
</head>
<body>

<div class="nav">
    <b class="logo">⬡ ՊԱՀԵՍՏ</b>
    <div class="nav-right">
        <div class="user-box" style="display:flex; align-items:center; gap:10px;">
            <img src="uploads/<?= htmlspecialchars($user['image'] ?? 'default.png') ?>" class="avatar">
            <span class="username"><?= htmlspecialchars($user['username']) ?></span>
        </div>
        <a href="functions/sell.php" class="nav-link"><span>📦</span> Իմ հայտարարությունները</a>
        <a href="admin/history.php" class="nav-link"><span>📈</span> Վիճակագրություն</a>
        <a href="functions/settings.php" class="nav-link"><span>⚙</span> Կարգավորումներ</a>
        <a href="auth/logout.php" class="nav-link exit"><span>🚪</span> Ելք</a>
    </div>
</div>

<div class="container">

    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">ԸՆԴՀԱՆՈՒՐ ՎԱՃԱՌՔ</span>
            <span class="stat-val"><?= $total['cnt'] ?? 0 ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">ՎԱՃԱՌՎԱԾ ՔԱՆԱԿ</span>
            <span class="stat-val"><?= $total['qty'] ?? 0 ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">ԵԿԱՄՈՒՏ</span>
            <span class="stat-val" style="color:#22c55e;">$<?= number_format($total['sum'] ?? 0, 2) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">🏆 ԼԱՎАГՈՒՅՆ ԱՊՐԱՆՔ</span>
            <span class="stat-val" style="font-size:16px;"><?= htmlspecialchars($best_item['name'] ?? '—') ?></span>
        </div>
    </div>

    <div style="display:flex; gap:20px; margin-bottom:30px;">
        <div class="stat-card" style="flex:1;">
            <span class="stat-label">ԱՅՍՕՐ</span>
            <span class="stat-val" style="color:#22c55e;">$<?= number_format($today['sum'] ?? 0, 2) ?></span>
            <div class="stat-sub"><?= $today['cnt'] ?? 0 ?> վաճառք</div>
        </div>
        <div class="stat-card" style="flex:1;">
            <span class="stat-label">ԵՐԵԿ</span>
            <span class="stat-val" style="color:#94a3b8;">$<?= number_format($yesterday['sum'] ?? 0, 2) ?></span>
            <div class="stat-sub"><?= $yesterday['cnt'] ?? 0 ?> վաճառք</div>
        </div>
    </div>

    <div class="search-section">
        <form method="GET">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Փնտրել ըստ անվանման..." class="filter-input">
            <div class="filter-actions">
                <button type="submit" class="filter-btn">🔍 Կիրառել</button>
                <a href="index.php" class="reset-btn">✕ Չեղարկել</a>
            </div>
        </form>
    </div>

    <div class="results-count">
        Գտնված ապրանքներ: <span><?= count($products) ?></span>
    </div>

    <div class="grid">
        <?php foreach($products as $p): ?>
        <div class="card">
            <div class="product-img">
                <?php if(!empty($p['image']) && $p['image'] !== 'default.png'): ?>
                    <img src="uploads/<?= htmlspecialchars($p['image']) ?>">
                <?php else: ?>
                    <span style="font-size:40px; opacity:0.3;">📦</span>
                <?php endif; ?>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <h3 style="margin:0; font-size:1.1rem;"><?= htmlspecialchars($p['name']) ?></h3>
                <span style="font-size:10px; background:rgba(255,255,255,0.05); padding:2px 6px; border-radius:4px; color:#94a3b8;">
                    👤 <?= htmlspecialchars($p['owner_name']) ?>
                </span>
            </div>

            <?php if (!empty($p['description'])): ?>
                <p class="product-desc"><?= htmlspecialchars($p['description']) ?></p>
            <?php endif; ?>

            <div style="color:#22c55e; font-size:1.3rem; font-weight:bold; margin-bottom:5px;">
                $<?= number_format($p['price'], 2) ?>
            </div>
            <p style="color:#94a3b8; font-size:13px; margin-bottom:15px;">Առկա է: <b style="color:#fff;"><?= $p['quantity'] ?></b> հատ</p>

            <form action="functions/do_sell.php" method="POST" style="display:flex; gap:10px; margin-bottom:15px;">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <input type="number" name="qty" value="1" min="1" max="<?= $p['quantity'] ?>"
                       style="width:60px; background:#0f172a; border:1px solid #334155; color:#fff; padding:5px; border-radius:6px; text-align:center;">
                <button type="submit" class="sell-btn">Վաճառել</button>
            </form>

            <div class="report-container">
                <form action="admin/report_handler.php" method="POST" class="report-form">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="text" name="reason" class="report-input" placeholder="Խնդիր..." required>
                    <button type="submit" class="report-btn" title="Բողոքել">🔔</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</body>
</html>