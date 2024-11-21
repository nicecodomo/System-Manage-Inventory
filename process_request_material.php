<?php
session_start();
include_once("class/Database.php");
include_once("class/RequestManagement.php");
include_once("class/UserManagement.php");

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'user') {
    header("Location: signin.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$requestManager = new RequestManagement($db);
$userManager = new UserManagement($db);

$userData = $userManager->getUserById($_SESSION['userid']);

// ตรวจสอบการส่งข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['materials'])) {
    $selectedMaterials = $_POST['materials'];
    $quantities = $_POST['quantity'];
    $_SESSION['cart'] = array();

    foreach ($selectedMaterials as $materialId) {
        if (isset($quantities[$materialId])) {
            $_SESSION['cart'][$materialId] = $quantities[$materialId];
        }
    }

    // ส่งคำขอวัสดุไปยังฐานข้อมูล
    foreach ($_SESSION['cart'] as $materialId => $quantity) {
        $result = $requestManager->requestMaterial($userData['user_id'], $materialId, $quantity);
        if (!$result) {
            $_SESSION['error'] = "ไม่สามารถส่งคำขอได้";
            break;
        }
    }

    $_SESSION['success'] = "ส่งคำขอเบิกวัสดุเรียบร้อยแล้ว รอการอนุมัติจาก staff";
} else {
    $_SESSION['error'] = "ข้อมูลไม่ถูกต้อง";
}

header("Location: request_material.php");
exit();
?> 
