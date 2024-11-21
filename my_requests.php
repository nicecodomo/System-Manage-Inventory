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

// ดึงข้อมูลคำขอวัสดุของผู้ใช้งาน
$allRequests = $requestManager->getAllRequestsByUser($_SESSION['userid']);
$pendingRequests = $requestManager->getPendingRequestsByUser($_SESSION['userid']);
$approvedRequests = $requestManager->getApprovedRequestsByUser($_SESSION['userid']);
$rejectedRequests = $requestManager->getRejectedRequestsByUser($_SESSION['userid']);
?>

<div class="container">
    <h1 class="text-center">ประวัติการเบิกวัสดุ</h1>

    <div class="mb-3 text-center">
        <button class="btn btn-outline-primary" onclick="filterRequests('all')">ทั้งหมด</button>
        <button class="btn btn-outline-warning" onclick="filterRequests('รออนุมัติ')">คำขอที่รออนุมัติ</button>
        <button class="btn btn-outline-success" onclick="filterRequests('อนุมัติ')">คำขอที่อนุมัติแล้ว</button>
        <button class="btn btn-outline-danger" onclick="filterRequests('ปฏิเสธ')">คำขอที่ไม่อนุมัติ</button>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table id="requestTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ประเภท</th>
                        <th>ชื่อวัสดุ</th>
                        <th>จำนวนที่เบิก</th>
                        <th>สถานะ</th>
                        <th>วันที่ขอ</th>
                    </tr>
                </thead>
                <tbody id="requestBody">
                    <?php
                    $i = 1;
                    // รวมคำขอทั้งหมด
                    // $allRequests = array_merge($pendingRequests, $approvedRequests, $rejectedRequests);
                    foreach ($allRequests as $request): ?>
                        <tr class="request-row" data-status="<?php echo strtolower($request['req_status']); ?>">
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($request['type']); ?></td>
                            <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['req_quantity']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($request['req_status']) === 'อนุมัติ' ? 'success' : (strtolower($request['req_status']) === 'ปฏิเสธ' ? 'danger' : 'warning'); ?>">
                                    <?php echo htmlspecialchars($request['req_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($request['req_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#requestTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });
    });

    function filterRequests(status) {
        const rows = document.querySelectorAll('.request-row');
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