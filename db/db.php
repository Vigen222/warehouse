<?php
/**
 * Տվյալների բազայի միացում և օգնող ֆունկցիաներ
 * Պրոյեկտ: Warehouse Management System
 */

// 1. Սեսիայի ավտոմատ մեկնարկ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Միացում տվյալների բազային
$host   = "localhost";
$dbname = "a";
$user   = "root";
$pass   = "";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}

/* ================= ՕԳՆՈՂ ՖՈՒՆԿՑԻԱՆԵՐ ================= */

/**
 * Ստուգում է՝ արդյոք օգտատերը ադմին է
 */
function isAdmin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Ստուգում է լոգինը և բլոկավորումը
 * Այս ֆունկցիան ստուգում է բազան ամեն անգամ, որպեսզի բլոկը աշխատի վայրկենական
 */
function requireLogin() {
    global $conn;
    if (!isset($_SESSION['user'])) {
        header("Location: /warehouse_project3/auth/login.php");
        exit;
    }
    // Проверяем роль прямо из базы каждый раз
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] === 'blocked') {
        session_destroy();
        header("Location: /warehouse_project3/auth/login.php");
        exit;
    }
}

/**
 * Պարտադիր ստուգում ադմինիստրատորի էջերի համար
 */
function requireAdmin() {
    requireLogin(); // Նախ ստուգում ենք լոգինը և բլոկը
    if (!isAdmin()) {
        header("Location: /warehouse_project3/index.php");
        exit;
    }
}
?>