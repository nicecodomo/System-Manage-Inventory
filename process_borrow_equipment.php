<?php
require("sys_header.inc.php");
ob_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'user') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/InventoryManagement.php");

$database = new Database();
$db = $database->getConnection();
$inventoryManager = new InventoryManagement($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่ามีการส่งข้อมูล equipment_id และ return_date
    if (isset($_POST['equipment_id']) && isset($_POST['return_date'])) {
        $userId = $_SESSION['userid'];
        $equipmentIds = $_POST['equipment_id'];
        $returnDates = $_POST['return_date'];

        // ทำการยืมครุภัณฑ์
        foreach ($equipmentIds as $index => $equipmentId) {
            $returnDate = $returnDates[$index]; // วันที่คืนที่ตรงกับ ID ของครุภัณฑ์
            $result = $inventoryManager->requestEquipment($userId, $equipmentId, $returnDate);
            if (!$result) {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการยืมครุภัณฑ์";
                break;
            }
        }

        $_SESSION['success'] = "ยืมครุภัณฑ์เรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "ข้อมูลไม่ถูกต้อง";
    }
    header("Location: borrow_equipment.php");
    exit();
}
