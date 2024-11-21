<?php
include_once("class/Database.php");
include_once("class/UserManagement.php");
include_once("class/InventoryManagement.php");

session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$userManager = new UserManagement($db);
$inventoryManager = new InventoryManagement($db);

// ตรวจสอบสิทธิ์ผู้ใช้
$userData = $userManager->getUserById($_SESSION['userid']);
$userRole = $userData['user_role'];

if ($userRole !== 'staff' && $userRole !== 'admin') {
    header("Location: index.php");
    exit();
}

// ตรวจสอบว่ามีการส่งข้อมูลจากฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanId = $_POST['loan_id'];

    if (isset($_POST['approve'])) {
        // อนุมัติคำขอยืม
        $inventoryManager->approveLoan($_SESSION['userid'], $loanId);
        $_SESSION['success'] = "อนุมัติคำขอยืมเรียบร้อยแล้ว";
    } elseif (isset($_POST['reject'])) {
        // ไม่อนุมัติคำขอยืม
        $inventoryManager->rejectLoan($loanId);
        $_SESSION['error'] = "ไม่อนุมัติคำขอยืมเรียบร้อยแล้ว";
    }

    // เปลี่ยนเส้นทางกลับไปยังหน้า pending_loans.php
    header("Location: pending_loans.php");
    exit();
} else {
    // ถ้าไม่ใช่การส่งข้อมูลจากฟอร์ม ให้เปลี่ยนเส้นทางกลับไปยังหน้า pending_loans.php
    header("Location: pending_loans.php");
    exit();
}
