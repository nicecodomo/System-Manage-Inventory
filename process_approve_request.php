<?php
include_once("class/Database.php");
include_once("class/UserManagement.php");
include_once("class/RequestManagement.php");
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
$requestManager = new RequestManagement($db);
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
    $requestId = $_POST['request_id'];

    // ดึงข้อมูลคำขอ
    $request = $requestManager->getRequestById($requestId);

    if ($request) {
        if (isset($_POST['approve'])) {
            // อนุมัติคำขอวัสดุ
            $requestManager->approveRequest($_SESSION['userid'], $requestId);
            $inventoryManager->reduceMaterialQuantity($request['mat_id'], $request['req_quantity']);
            $_SESSION['success'] = "อนุมัติคำขอวัสดุเรียบร้อยแล้ว";
        } elseif (isset($_POST['reject'])) {
            // ไม่อนุมัติคำขอวัสดุ
            $requestManager->rejectRequest($requestId);
            $_SESSION['error'] = "ไม่อนุมัติคำขอวัสดุเรียบร้อยแล้ว";
        }
    } else {
        $_SESSION['error'] = "ไม่พบคำขอที่ต้องการอนุมัติหรือปฏิเสธ";
    }

    // เปลี่ยนเส้นทางกลับไปยังหน้า pending_material_requests.php
    header("Location: pending_material_requests.php");
    exit();
} else {
    // ถ้าไม่ใช่การส่งข้อมูลจากฟอร์ม ให้เปลี่ยนเส้นทางกลับไปยังหน้า pending_material_requests.php
    header("Location: pending_material_requests.php");
    exit();
}
?> 