<?php
include "../db/db.php";

// Проверяем сессию, чтобы не было ошибки "Notice"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $user_id    = (int)$_SESSION['user']['id'];
    $reason     = trim($_POST['reason'] ?? '');

    if ($reason !== '') {
        try {
            // ВОТ ТА САМАЯ СТРОКА:
            // Мы заменили reason на report_text и убрали status
            $stmt = $conn->prepare("INSERT INTO reports (user_id, product_id, report_text) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $reason]);
            
        } catch (PDOException $e) {
            // Если что-то пойдет не так, мы увидим ошибку
            die("Ошибка БД: " . $e->getMessage());
        }
    }
}

// Возвращаем пользователя на главную
header("Location: ../index.php");
exit;