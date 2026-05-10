<?php
include "../db/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$product_id = (int)$_POST['product_id'];
$qty = (int)$_POST['qty'];

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if ($product && $product['quantity'] >= $qty && $qty > 0) {
    $total_price = $product['price'] * $qty;
    $conn->prepare("INSERT INTO sales (user_id, product_id, quantity_sold, total_price) VALUES (?, ?, ?, ?)")
         ->execute([$user_id, $product_id, $qty, $total_price]);
    $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")
         ->execute([$qty, $product_id]);
}

header("Location: ../index.php");
exit;