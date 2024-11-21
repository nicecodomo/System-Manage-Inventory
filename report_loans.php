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
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// ดึงข้อมูลรายงาน
$loanHistory = $reportManager->getLoanHistory($startDate, $endDate, $departmentId, $status);
$monthlyStats = $reportManager->getMonthlyLoanStats();
$approvalSummary = $reportManager->getApprovalSummaryReport($startDate, $endDate);
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">รายงานการยืม-คืนครุภัณฑ์</h1>
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
                        <h3><?php echo $monthlyStats[0]['total_loans'] ?? 0; ?></h3>
                        <p>การยืมทั้งหมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $monthlyStats[0]['returned'] ?? 0; ?></h3>
                        <p>คืนแล้ว</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $monthlyStats[0]['active'] ?? 0; ?></h3>
                        <p>กำลังยืม</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php
                            $overdue = array_filter($loanHistory, function ($loan) {
                                return $loan['days_overdue'] > 0 && $loan['loan_status'] === 'อนุมัติแล้ว';
                            });
                            echo count($overdue);
                            ?></h3>
                        <p>เกินกำหนด</p>
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
                    <div class="form-group mx-sm-3">
                        <label for="status" class="mr-2">สถานะ</label>
                        <select class="form-control" id="status" name="status">
                            <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                            <option value="ถกยืม" <?php echo $status == 'ถูกยืม' ? 'selected' : ''; ?>>กำลังยืม</option>
                            <option value="คืนแล้ว" <?php echo $status == 'คืนแล้ว' ? 'selected' : ''; ?>>คืนแล้ว</option>
                            <option value="เกินกำหนด" <?php echo $status == 'เกินกำหนด' ? 'selected' : ''; ?>>เกินกำหนด</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                </form>
            </div>
        </div>

        <!-- ประวัติการยืม-คืน -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ประวัติการยืม-คืนครุภัณฑ์</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table id="loanTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>วันที่ยืม</th>
                            <th>ครุภัณฑ์</th>
                            <th>ผู้ยืม</th>
                            <th>แผนก</th>
                            <th>กำหนดคืน</th>
                            <th>วันที่คืน</th>
                            <th>สถานะ</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loanHistory as $loan): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($loan['loan_date'])); ?></td>
                                <td><?php echo htmlspecialchars($loan['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['department_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($loan['loan_return_date'])); ?></td>
                                <td><?php echo $loan['loan_return_date'] ? date('d/m/Y', strtotime($loan['loan_return_date'])) : '-'; ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                                                echo $loan['loan_status'] === 'คืนแล้ว' ? 'success' : 
                                                                     ($loan['loan_status'] === 'รออนุมัติ' ? 'info' : 
                                                                     ($loan['loan_status'] === 'ไม่อนุมัติ' ? 'danger' : 
                                                                     ($loan['days_overdue'] > 0 ? 'danger' : 'warning')));
                                                                ?>">
                                        <?php
                                        echo $loan['loan_status'] === 'คืนแล้ว' ? 'คืนแล้ว' : 
                                                                 ($loan['loan_status'] === 'รออนุมัติ' ? 'รออนุมัติ' : 
                                                                 ($loan['loan_status'] === 'ไม่อนุมัติ' ? 'ไม่อนุมัติ' : 
                                                                 ($loan['days_overdue'] > 0 ? 'เกินกำหนด' : 'กำลังยืม')));
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    if ($loan['days_overdue'] > 0 && $loan['loan_status'] !== 'คืนแล้ว') {
                                        echo "เกินกำหนด {$loan['days_overdue']} วัน";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- กราฟแสดงสถิติ -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สถิติการยืม-คืนรายเดือน</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyLoanChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">สัดส่วนการยืมตามแผนก</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="departmentLoanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DataTables & Chart.js Scripts -->
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#loanTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        // สร้างกราฟแสดงสถิติรายเดือน
        const monthlyStats = <?php echo json_encode($monthlyStats); ?>;
        const monthlyCtx = document.getElementById('monthlyLoanChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyStats.map(stat => stat.month),
                datasets: [{
                    label: 'จำนวนการยืมทั้งหมด',
                    data: monthlyStats.map(stat => stat.total_loans),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'คืนแล้ว',
                    data: monthlyStats.map(stat => stat.returned),
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

        // สร้างกราฟแสดงสัดส่วนตามแผนก
        const departmentStats = <?php echo json_encode($reportManager->getDepartmentLoanStats()); ?>;
        const deptCtx = document.getElementById('departmentLoanChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'pie',
            data: {
                labels: departmentStats.map(stat => stat.department_name),
                datasets: [{
                    data: departmentStats.map(stat => stat.total_loans),
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