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
$equipmentLoans = $reportManager->getEquipmentLoanReport($startDate, $endDate);
$equipmentStatus = $reportManager->getEquipmentStatusReport();
$overdueLoans = $reportManager->getOverdueLoanReport();
$equipmentUsageStats = $reportManager->getEquipmentUsageStats($startDate, $endDate);
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">รายงานครุภัณฑ์</h1>
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
                        <h3><?php echo count($equipmentStatus); ?></h3>
                        <p>ครุภัณฑ์ทั้งหมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <?php
                        $availableCount = 0;
                        foreach ($equipmentStatus as $status) {
                            if ($status['equ_status'] === 'ว่าง') {
                                $availableCount = $status['count'];
                                break;
                            }
                        }
                        ?>
                        <h3><?php echo $availableCount; ?></h3>
                        <p>ครุภัณฑ์ที่ว่าง</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count($equipmentLoans); ?></h3>
                        <p>การยืมในช่วงเวลา</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count($overdueLoans); ?></h3>
                        <p>การยืมที่เกินกำหนด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
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

        <!-- รายงานสถานะครุภัณฑ์ -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">รายงานสถานะครุภัณฑ์</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="equipmentTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>ประเภท</th>
                            <th>สถานที่</th>
                            <th>สภาพ</th>
                            <th>สถานะ</th>
                            <th>ผู้ยืม</th>
                            <th>กำหนดคืน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($equipmentStatus as $equipment):
                        ?>
                            <tr>
                                <!-- <td><?php echo $equipment['equ_id']; ?></td> -->
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($equipment['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['equ_type']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['equ_location']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['equ_condition']); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                                                echo $equipment['equ_status'] === 'ว่าง' ? 'success' : ($equipment['equ_status'] === 'ถูกยืม' ? 'warning' : 'danger');
                                                                ?>">
                                        <?php echo $equipment['equ_status']; ?>
                                    </span>
                                </td>
                                <?php if ($equipment['equ_status'] === 'ถูกยืม'): ?>
                                    <td><?php echo $equipment['borrowed_by'] ?? '-'; ?></td>
                                    <td><?php echo $equipment['loan_return_date'] ? date('d/m/Y', strtotime($equipment['loan_return_date'])) : '-'; ?></td>
                                <?php else: ?>
                                    <td>-</td>
                                    <td>-</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- รายงานการยืมที่เกินกำหนด -->
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title">รายการยืมที่เกินกำหนด</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="overdueTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ครุภัณฑ์</th>
                            <th>ผู้ยืม</th>
                            <th>แผนก</th>
                            <th>วันที่ยืม</th>
                            <th>กำหนดคืน</th>
                            <th>เกินกำหนด (วัน)</th>
                            <th>เบอร์ติดต่อ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueLoans as $loan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($loan['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['department_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($loan['borrow_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($loan['due_date'])); ?></td>
                                <td><?php echo $loan['days_overdue']; ?></td>
                                <td><?php echo htmlspecialchars($loan['user_contact']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- กราฟแสดงสถิติการยืม -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สถิติการยืมครุภัณฑ์รายเดือน</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyLoanChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">ครุภัณฑ์ที่ถูกยืมบ่อย</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="popularEquipmentChart"></canvas>
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
        $('#equipmentTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        $('#overdueTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        // สร้างกราฟแสดงสถิติรายเดือน
        const monthlyStats = <?php echo json_encode($reportManager->getMonthlyLoanStats()); ?>;
        const monthlyCtx = document.getElementById('monthlyLoanChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyStats.map(stat => stat.month),
                datasets: [{
                    label: 'จำนวนการยืม',
                    data: monthlyStats.map(stat => stat.total_loans),
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

        // สร้างกราฟแสดงครุภัณฑ์ที่ถูกยืมบ่อย
        const usageStats = <?php echo json_encode($equipmentUsageStats); ?>;
        const popularCtx = document.getElementById('popularEquipmentChart').getContext('2d');
        new Chart(popularCtx, {
            type: 'bar',
            data: {
                labels: usageStats.slice(0, 10).map(stat => stat.equ_name),
                datasets: [{
                    label: 'จำนวนครั้งที่ถูกยืม',
                    data: usageStats.slice(0, 10).map(stat => stat.usage_count),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
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