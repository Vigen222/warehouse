<?php
include "../db/db.php";
requireLogin();

$user_id = (int)$_SESSION['user']['id'];

$search = trim($_GET['search'] ?? '');
$period = $_GET['period'] ?? 'all'; 
$sort   = $_GET['sort'] ?? 'newest';

$query = "SELECT s.*, p.name FROM sales s JOIN products p ON s.product_id = p.id WHERE s.user_id = ?";
$params = [$user_id];

if ($search) { $query .= " AND p.name LIKE ?"; $params[] = "%$search%"; }
if ($period === 'today') { $query .= " AND DATE(s.sold_at) = CURDATE()"; } 
elseif ($period === 'week') { $query .= " AND s.sold_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; } 
elseif ($period === 'month') { $query .= " AND s.sold_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; }

$query .= ($sort === 'oldest') ? " ORDER BY s.sold_at ASC" : " ORDER BY s.sold_at DESC";

$sales_stmt = $conn->prepare($query);
$sales_stmt->execute($params);
$sales_list = $sales_stmt->fetchAll();

$stats_q = $conn->prepare("SELECT COUNT(*) as cnt, SUM(total_price) as sum FROM sales WHERE user_id = ?");
$stats_q->execute([$user_id]);
$stats = $stats_q->fetch();

$chart_q = $conn->prepare("SELECT DATE_FORMAT(sold_at, '%d.%m') as day, SUM(total_price) as revenue FROM sales WHERE user_id = ? GROUP BY day ORDER BY MIN(sold_at) ASC LIMIT 7");
$chart_q->execute([$user_id]);
$chart_data = $chart_q->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <title>Վիճակագրություն</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="nav">
    <a href="../index.php" class="back-link">← ԳԼԽԱՎՈՐ</a>
    <div class="nav-title">ՎԱՃԱՌՔԻ ՊԱՏՄՈՒԹՅՈՒՆ</div>
</div>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">ԸՆԴՀԱՆՈՒՐ ԵԿԱՄՈՒՏ</span>
            <span class="stat-val income">$<?= number_format($stats['sum'] ?? 0, 2) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">ԳՈՐԾԱՐՔՆԵՐ</span>
            <span class="stat-val"><?= $stats['cnt'] ?? 0 ?></span>
        </div>
    </div>

   <div class="filter-box">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>ՈՐՈՆՈՒՄ</label>
            <!-- Добавили класс f-input -->
            <input type="text" name="search" class="f-input" placeholder="Անունը..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div class="filter-group">
            <label>ՇՐՋԱՆ</label>
            <!-- Добавили класс f-input -->
            <select name="period" class="f-input">
                <option value="all" <?= $period == 'all' ? 'selected' : '' ?>>Բոլորը</option>
                <option value="today" <?= $period == 'today' ? 'selected' : '' ?>>Այսօր</option>
                <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Շաբաթ</option>
                <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Ամիս</option>
            </select>
        </div>
        
        <!-- Кнопка теперь с классом btn-search -->
        <button type="submit" class="btn-search">ԳՏՆԵԼ</button>
    </form>
</div>
    <div class="stat-card chart-wrapper">
        <canvas id="myChart"></canvas>
    </div>

    <div class="stat-card table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ԱՄՍԱԹԻՎ</th>
                    <th>ԱՊՐԱՆՔ</th>
                    <th style="text-align:center;">ՔԱՆԱԿ</th>
                    <th style="text-align:right;">ԳՈՒՄԱՐ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales_list)): ?>
                    <tr><td colspan="4" class="empty">Տվյալներ չկան</td></tr>
                <?php else: ?>
                    <?php foreach($sales_list as $s): ?>
                    <tr>
                        <td class="td-date"><?= date('d.m.Y H:i', strtotime($s['sold_at'])) ?></td>
                        <td class="td-name"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="td-qty"><?= $s['quantity_sold'] ?></td>
                        <td class="td-price">$<?= number_format($s['total_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('myChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($chart_data, 'day')) ?>,
        datasets: [{
            label: 'Եկամուտ',
            data: <?= json_encode(array_column($chart_data, 'revenue')) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    }
});
</script>
</body>
</html>