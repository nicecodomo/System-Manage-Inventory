<?php
require("sys_header.inc.php");
ob_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'user') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/RequestManagement.php");

$database = new Database();
$db = $database->getConnection();
$requestManager = new RequestManagement($db);

// ดึงข้อมูลการยืมของผู้ใช้งาน
$loanRequests = $requestManager->getLoanRequestsByUser($_SESSION['userid']);
?>

<div class="container">
    <h1 class="text-center">ประวัติการยืมครุภัณฑ์</h1>

    <div class="mb-3 text-center">
        <button class="btn btn-outline-primary" onclick="filterLoans('all')">ทั้งหมด</button>
        <button class="btn btn-outline-warning" onclick="filterLoans('รออนุมัติ')">รออนุมัติ</button>
        <button class="btn btn-outline-success" onclick="filterLoans('อนุมัติแล้ว')">อนุมัติแล้ว</button>
        <button class="btn btn-outline-secondary" onclick="filterLoans('คืนแล้ว')">คืนแล้ว</button>
        <button class="btn btn-outline-danger" onclick="filterLoans('ไม่อนุมัติ')">ไม่อนุมัติ</button>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table id="loanRequestsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ชื่อครุภัณฑ์</th>
                        <th>สถานะ</th>
                        <th>วันที่ยืม</th>
                        <th>วันที่คืน</th>
                    </tr>
                </thead>
                <tbody id="loanBody">
                    <?php
                    $i = 1;
                    foreach ($loanRequests as $request): ?>
                        <tr class="loan-row" data-status="<?php echo strtolower($request['loan_status']); ?>">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo
                                                            htmlspecialchars($request['loan_status']) === 'อนุมัติแล้ว' ? 'success' : (
                                                                htmlspecialchars($request['loan_status']) === 'คืนแล้ว' ? 'secondary' : (
                                                                    htmlspecialchars($request['loan_status']) === 'ไม่อนุมัติ' ? 'danger' : 'warning')); ?>">
                                    <?php echo htmlspecialchars($request['loan_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($request['loan_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['loan_return_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#loanRequestsTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });
    });

    function filterLoans(status) {
        const rows = document.querySelectorAll('.loan-row');
        rows.forEach(row => {
            if (status === 'all' || row.getAttribute('data-status') === status.toLowerCase()) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>