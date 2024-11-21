<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/InventoryManagement.php");
include_once("class/RequestManagement.php");
include_once("class/UserManagement.php");
include_once("class/ReportManagement.php");

$database = new Database();
$db = $database->getConnection();
$userManager = new UserManagement($db);
$inventoryManager = new InventoryManagement($db);
$requestManager = new RequestManagement($db);
$reportManager = new ReportManagement($db);

$userData = $userManager->getUserById($_SESSION['userid']);

// ดึงข้อมูลวัสดุที่ใกล้หมด
$lowStockMaterials = $inventoryManager->getLowStockMaterials(5);

// ดึงคำขอล่าสุด
$recentRequests = $inventoryManager->getRecentRequests(10);
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">หน้าหลัก - เจ้าหน้าที่</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- สรุปภาพรวม -->
        <div class="row">
            <!-- จำนวนวัสดุคงเหลือ -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($inventoryManager->getMaterialInventoryReport()); ?></h3>
                        <p>วัสดุคงเหลือ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <a href="manage_materials.php" class="small-box-footer">
                        จัดการวัสดุ <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- จำนวนครุภัณฑ์ -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo count($inventoryManager->getEquipmentStatusReport()); ?></h3>
                        <p>ครุภัณฑ์ทั้งหมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <a href="manage_equipment.php" class="small-box-footer">
                        จัดการครุภัณฑ์ <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- คำขอที่รออนุมัติ -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count($requestManager->getPendingRequests()); ?></h3>
                        <p>คำขอที่รออนุมัติ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="approve_requests.php" class="small-box-footer">
                        จัดการคำขอ <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- การยืมที่เกินกำหนด -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count($reportManager->getOverdueLoanReport()); ?></h3>
                        <p>การยืมที่เกินกำหนด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <a href="manage_equipment.php" class="small-box-footer">
                        ตรวจสอบ <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- วัสดุที่ใกล้หมด -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">วัสดุที่ใกล้หมด</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>รหัสวัสดุ</th>
                                    <th>ชื่อวัสดุ</th>
                                    <th>จำนวนคงเหลือ</th>
                                    <th>หน่วยนับ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockMaterials as $material): ?>
                                    <tr>
                                        <td><?php echo $material['mat_id']; ?></td>
                                        <td><?php echo htmlspecialchars($material['mat_name']); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $material['mat_quantity'] <= 5 ? 'danger' : 'warning'; 
                                            ?>">
                                                <?php echo $material['mat_quantity'] <= 5 ? 'ใกล้หมด' : 'ต่ำ'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($material['mat_unit']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- คำขอล่าสุด -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">คำขอล่าสุด</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>ผู้ขอ</th>
                                    <th>รายการ</th>
                                    <th>ประเภท</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentRequests as $request): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟแสดงสถิติ -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สถิติการเบิกวัสดุรายเดือน</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="materialRequestChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สถิติการยืมครุภัณฑ์รายเดือน</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="equipmentLoanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Chart.js Scripts -->
<script>
$(document).ready(function() {
    // สร้างกราฟแสดงสถิติการเบิกวัสดุ
    const materialStats = <?php echo json_encode($reportManager->getMonthlyMaterialRequestStats()); ?>;
    const materialCtx = document.getElementById('materialRequestChart').getContext('2d');
    new Chart(materialCtx, {
        type: 'line',
        data: {
            labels: materialStats.map(stat => stat.month),
            datasets: [{
                label: 'จำนวนการเบิก',
                data: materialStats.map(stat => stat.total_requests),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // สร้างกราฟแสดงสถิติการยืมครุภัณฑ์
    const equipmentStats = <?php echo json_encode($reportManager->getMonthlyLoanStats()); ?>;
    const equipmentCtx = document.getElementById('equipmentLoanChart').getContext('2d');
    new Chart(equipmentCtx, {
        type: 'line',
        data: {
            labels: equipmentStats.map(stat => stat.month),
            datasets: [{
                label: 'จำนวนการยืม',
                data: equipmentStats.map(stat => stat.total_loans),
                borderColor: 'rgb(54, 162, 235)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?> 