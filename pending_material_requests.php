<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'staff') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/RequestManagement.php");

$database = new Database();
$db = $database->getConnection();
$requestManager = new RequestManagement($db);

// ดึงข้อมูลคำขอวัสดุที่รออนุมัติ
$pendingRequests = $requestManager->getPendingRequests();

// ดึงจำนวนคำขอที่รออนุมัติ
$pendingMaterialCount = count($requestManager->getPendingRequests());
$approvedMaterialCount = count($requestManager->getApprovedRequests());
$rejectedMaterialCount = count($requestManager->getRejectedRequests());
?>

<!-- Content Header -->
<div class="content-header">
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

        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">รายการเบิกวัสดุ (รออนุมัติ)</h1>
            </div>
        </div>
        <div class="mb-3">
            <a href="pending_material_requests.php" class="btn btn-warning">รออนุมัติ <?php echo $pendingMaterialCount > 0 ? "($pendingMaterialCount)" : '(0)'; ?></a>
            <a href="approved_material_requests.php" class="btn btn-success">อนุมัติแล้ว <?php echo $approvedMaterialCount > 0 ? "($approvedMaterialCount)" : '(0)'; ?></a>
            <a href="rejected_material_requests.php" class="btn btn-danger">ไม่อนุมัติ <?php echo $rejectedMaterialCount > 0 ? "($rejectedMaterialCount)" : '(0)'; ?></a>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body table-responsive">
                <table id="pendingMaterialTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ขอ</th>
                            <th>ชื่อวัสดุ</th>
                            <th>รายละเอียดวัสดุ</th>
                            <th>วันที่ขอเบิก</th>
                            <th>จำนวนที่ขอ</th>
                            <th>ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($pendingRequests as $request):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['mat_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['mat_description']); ?></td>
                                <td><?php echo htmlspecialchars($request['req_date']); ?></td>
                                <td><?php echo htmlspecialchars($request['req_quantity']); ?></td>
                                <td>
                                    <form action="process_approve_request.php" method="POST">
                                        <input type="hidden" name="request_id" value="<?php echo $request['req_id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-success">อนุมัติ</button>
                                        <button type="submit" name="reject" class="btn btn-danger">ไม่อนุมัติ</button>
                                    </form>
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
        $('#pendingMaterialTable').DataTable({
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