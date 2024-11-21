<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}
include_once 'sys_footer.inc.php';
include_once("class/Database.php");
include_once("class/UserManagement.php");
include_once("class/RequestManagement.php"); // เพิ่มการจัดการคำขอ

$database = new Database();
$db = $database->getConnection();
$userManager = new UserManagement($db);
$requestManager = new RequestManagement($db); // สร้างอ็อบเจ็กต์การจัดการคำขอ

$userData = $userManager->getUserById($_SESSION['userid']);
$userRole = $userData['user_role'];

// ตรวจสอบสิทธิ์ผู้ใช้
if ($userRole !== 'staff' && $userRole !== 'admin') {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลคำขอที่รออนุมัติ
$requests = $requestManager->getPendingRequests();

// ดึงจำนวนคำขอที่รออนุมัติ
$pendingLoanCount = count($requestManager->getPendingRequests());
$approvedLoanCount = count($requestManager->getApprovedRequests());
$rejectedLoanCount = count($requestManager->getRejectedRequests());
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">อนุมัติคำขอวัสดุ</h1>
            </div>
        </div>
        <div class="mb-3">
            <a href="pending_material_requests.php" class="btn btn-warning">รออนุมัติ <?php echo $pendingLoanCount > 0 ? "($pendingLoanCount)" : '(0)'; ?></a>
            <a href="approved_material_requests.php" class="btn btn-success">อนุมัติแล้ว <?php echo $approvedLoanCount > 0 ? "($approvedLoanCount)" : '(0)'; ?></a>
            <a href="rejected_material_requests.php" class="btn btn-danger">ไม่อนุมัติ <?php echo $rejectedLoanCount > 0 ? "($rejectedLoanCount)" : '(0)'; ?></a>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- แสดงข้อความแจ้งเตือน -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- ตารางแสดงรายการคำขอ -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="requestsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ขอ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>รายละเอียดวัสดุ</th>
                            <th>วันที่ขอเบิก</th>
                            <th>จำนวนที่ขอ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($requests as $request):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['mat_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['mat_description']); ?></td>
                                <td><?php echo htmlspecialchars($request['req_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['req_quantity']); ?></td>
                                <td>
                                    <span class="badge badge-<?php
                                        echo htmlspecialchars($request['req_status']) === 'อนุมัติ' ? 'success' : 
                                             (htmlspecialchars($request['req_status']) === 'ปฏิเสธ' ? 'danger' : 'warning');
                                    ?>">
                                        <?php echo htmlspecialchars($request['req_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- DataTables & Custom Scripts -->
<script>
$(document).ready(function() {
    $('#requestsTable').DataTable({
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>