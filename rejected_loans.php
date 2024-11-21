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

$database = new Database();
$db = $database->getConnection();
$inventoryManager = new InventoryManagement($db);

// ดึงข้อมูลการยืมที่ไม่อนุมัติ
$rejectedLoans = $inventoryManager->getRejectedLoans();

// ดึงจำนวนคำขอที่รออนุมัติ
$pendingLoanCount = count($inventoryManager->getPendingLoans());
// ดึงจำนวนคำขอที่อนุมัติแล้ว
$approvedLoanCount = count($inventoryManager->getApprovedLoans());
// ดึงจำนวนคำขอที่คืนแล้ว
$returnedLoanCount = count($inventoryManager->getReturnedLoans());
// ดึงจำนวนคำขอที่ไม่อนุมัติ
$rejectedLoanCount = count($inventoryManager->getRejectedLoans());
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">รายการยืมครุภัณฑ์ (ไม่อนุมัติ/ยกเลิก)</h1>
            </div>
        </div>
        <div class="mb-3">
            <a href="pending_loans.php" class="btn btn-warning">รออนุมัติ <?php echo $pendingLoanCount > 0 ? "($pendingLoanCount)" : '(0)'; ?></a>
            <a href="approved_loans.php" class="btn btn-success">อนุมัติแล้ว/รอคืน <?php echo $approvedLoanCount > 0 ? "($approvedLoanCount)" : '(0)'; ?></a>
            <a href="returned_loans.php" class="btn btn-primary">คืนแล้ว <?php echo $returnedLoanCount > 0 ? "($returnedLoanCount)" : '(0)'; ?></a>
            <a href="rejected_loans.php" class="btn btn-danger">ไม่อนุมัติ/ยกเลิก <?php echo $rejectedLoanCount > 0 ? "($rejectedLoanCount)" : '(0)'; ?></a>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body table-responsive">
                <table id="rejectedLoanTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ยืม</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>วันที่ยืม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($rejectedLoans as $loan):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($loan['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['loan_date']); ?></td>
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
    $('#rejectedLoanTable').DataTable({
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