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
include_once("class/RequestManagement.php");

$database = new Database();
$db = $database->getConnection();
$inventoryManager = new InventoryManagement($db);
$requestManager = new RequestManagement($db);

// ดึงข้อมูลวัสดุและครุภัณฑ์
$materials = $inventoryManager->getAllMaterials(false);
$equipment = $inventoryManager->getAllEquipment(false);

// คำนวณจำนวนวัสดุที่เบิกได้
$totalMaterials = count($materials);
$totalEquipment = count($equipment);
$totalPendingRequests = $requestManager->getPendingRequestsByUser($_SESSION['userid']);
$totalPendingLoanCount = $inventoryManager->getPendingLoansByUser($_SESSION['userid']);
?>

<div class="container">
    <h1 class="text-center py-2">ยินดีต้อนรับสู่ระบบจัดการวัสดุครุภัณฑ์</h1>

    <div class="row">
        <!-- จำนวนวัสดุที่เบิกได้ -->
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?php echo $totalMaterials; ?></h3>
                    <p>วัสดุที่เบิกได้</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <a href="request_material.php" class="small-box-footer">
                    เบิกวัสดุ <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- จำนวนครุภัณฑ์ -->
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3><?php echo $totalEquipment; ?></h3>
                    <p>ครุภัณฑ์ทั้งหมด</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tools"></i>
                </div>
                <a href="borrow_equipment.php" class="small-box-footer">
                    ยืมครุภัณฑ์ <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- คำขอที่รออนุมัติ -->
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3><?php echo count($totalPendingRequests); ?></h3>
                    <p>คำขอเบิกที่รออนุมัติ</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="my_requests.php" class="small-box-footer">
                    ตรวจสอบ <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- การยืมที่รออนุมัติ -->
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3><?php echo count($totalPendingLoanCount); ?></h3>
                    <p>การยืมที่รออนุมัติ</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <a href="my_loans.php" class="small-box-footer">
                    ตรวจสอบ <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>วัสดุ</h2>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ชื่อวัสดุ</th>
                                <th>จำนวน</th>
                                <th>หน่วย</th>
                                <th>รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['mat_name']); ?></td>
                                    <td><?php echo htmlspecialchars($material['mat_quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($material['mat_unit']); ?></td>
                                    <td><?php echo htmlspecialchars($material['mat_description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>ครุภัณฑ์</h2>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>ประเภท</th>
                                <th>สถาพ</th>
                                <th>สถานะ</th>
                                <th>สถานที่</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['equ_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_condition']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_status']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_location']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>