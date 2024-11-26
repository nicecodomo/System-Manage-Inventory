<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/ReportManagement.php");
include_once("class/UserManagement.php");

$database = new Database();
$db = $database->getConnection();
$reportManager = new ReportManagement($db);
$userManager = new UserManagement($db);

// ดึงข้อมูลแผนกทั้งหมด
$departments = $userManager->getAllDepartments();

// กำหนดค่าเริ่มต้นสำหรับการกรองข้อมูล
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$departmentId = isset($_GET['department']) ? $_GET['department'] : 'all';

// ดึงข้อมูลรายงาน
$materialRequests = $reportManager->getMaterialRequestReport($startDate, $endDate);
$materialInventory = $reportManager->getMaterialInventoryReport();
$lowStockMaterials = array_filter($materialInventory, function ($item) {
    return $item['mat_quantity'] <= 10; // กำหนดเกณฑ์วัสดุใกล้หมด
});
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">รายงานวัสดุ</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- การ์ดสรุปภาพรวม -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($materialInventory); ?></h3>
                        <p>รายการวัสดุทั้งหมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo array_sum(array_column($materialInventory, 'mat_quantity')); ?></h3>
                        <p>จำนวนวัสดุคงเหลือรวม</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count($materialRequests); ?></h3>
                        <p>รายการเบิกในช่วงเวลา</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count($lowStockMaterials); ?></h3>
                        <p>วัสดุใกล้หมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ฟิลเตอร์ -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ตัวกรองข้อมูล</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="form-inline">
                    <div class="form-group mx-sm-3">
                        <label for="start_date" class="mr-2">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="start_date" name="start_date"
                            value="<?php echo $startDate; ?>">
                    </div>
                    <div class="form-group mx-sm-3">
                        <label for="end_date" class="mr-2">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                            value="<?php echo $endDate; ?>">
                    </div>
                    <div class="form-group mx-sm-3">
                        <label for="department" class="mr-2">แผนก</label>
                        <select class="form-control" id="department" name="department">
                            <option value="all">ทั้งหมด</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dep_id']; ?>"
                                    <?php echo $departmentId == $dept['dep_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['dep_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                </form>
            </div>
        </div>

        <!-- รายงานวัสดุคงเหลือ -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">รายงานวัสดุคงเหลือ</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="inventoryTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>รหัสวัสดุ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวนคงเหลือ</th>
                            <th>หน่วยนับ</th>
                            <th>สถานะ</th>
                            <th>วันที่อัพเดทล่าสุด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($materialInventory as $material):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($material['mat_name']); ?></td>
                                <td><?php echo $material['mat_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($material['mat_unit']); ?></td>
                                <td>
                                    <?php if ($material['mat_quantity'] <= 10): ?>
                                        <span class="badge badge-danger">ใกล้หมด</span>
                                    <?php elseif ($material['mat_quantity'] <= 30): ?>
                                        <span class="badge badge-warning">ต่ำ</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($material['updated_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- รายงานการเบิกวัสดุ -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">รายงานการเบิกวัสดุ</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="requestTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>วันที่เบิก</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวน</th>
                            <th>ผู้เบิก</th>
                            <th>แผนก</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        foreach ($materialRequests as $request): ?>
                            <?php if ($departmentId == 'all' || $request['dep_id'] == $departmentId): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($request['req_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['mat_name']); ?></td>
                                    <td><?php echo $request['total_quantity'] . ' ' . $request['mat_unit']; ?></td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                                                    echo $request['req_status'] == 'อนุมัติ' ? 'success' : ($request['req_status'] == 'รออนุมัติ' ? 'warning' : 'danger');
                                                                    ?>">
                                            <?php echo $request['req_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- กราฟแสดงสถิติการเบิกวัสดุ -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สถิติการเบิกวัสดุรายเดือน</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyRequestChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สัดส่วนการเบิกวัสดุตามแผนก</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="departmentRequestChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DataTables & Chart.js Scripts -->
<script>
    $(document).ready(function() {
        // Initialize DataTables
        $('#inventoryTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        $('#requestTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        // สร้างกราฟแสดงสถิติรายเดือน
        const monthlyStats = <?php echo json_encode($reportManager->getMonthlyMaterialRequestStats()); ?>;
        const monthlyCtx = document.getElementById('monthlyRequestChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyStats.map(stat => stat.month),
                datasets: [{
                    label: 'จำนวนการเบิก',
                    data: monthlyStats.map(stat => stat.total_requests),
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

        // สร้างกราฟแสดงสัดส่วนตามแผนก
        const departmentStats = <?php echo json_encode($reportManager->getDepartmentMaterialStats()); ?>;
        const deptCtx = document.getElementById('departmentRequestChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'pie',
            data: {
                labels: departmentStats.map(stat => stat.department_name),
                datasets: [{
                    data: departmentStats.map(stat => stat.total_requests),
                    backgroundColor: [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 206, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>