<?php
include_once("class/Database.php");
include_once("class/InventoryManagement.php");

session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$inventoryManager = new InventoryManagement($db);

// ตรวจสอบว่ามีการส่งข้อมูลจากฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanId = $_POST['loan_id'];
    $condition = $_POST['equ_condition']; // รับค่าจากฟอร์ม

    // คืนครุภัณฑ์
    if ($inventoryManager->returnEquipment($loanId, $condition)) {
        $_SESSION['success'] = "คืนครุภัณฑ์เรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการคืนครุภัณฑ์";
    }

    // เปลี่ยนเส้นทางกลับไปยังหน้า approved_loans.php
    header("Location: approved_loans.php");
    exit();
} else {
    // ถ้าไม่ใช่การส่งข้อมูลจากฟอร์ม ให้เปลี่ยนเส้นทางกลับไปยังหน้า approved_loans.php
    header("Location: approved_loans.php");
    exit();
}
