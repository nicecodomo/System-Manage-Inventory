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

// ดึงข้อมูลการยืมที่อนุมัติแล้ว
$approvedLoans = $inventoryManager->getApprovedLoans();

// ดึงจำนวนคำขอที่รออนุมัติ
$pendingLoanCount = count($inventoryManager->getPendingLoans());
$approvedLoanCount = count($inventoryManager->getApprovedLoans());
$returnedLoanCount = count($inventoryManager->getReturnedLoans());
$rejectedLoanCount = count($inventoryManager->getRejectedLoans());
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">รายการยืมครุภัณฑ์ (อนุมัติแล้ว/รอคืน)</h1>
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
                <table id="approvedLoanTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ยืม</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>วันที่ยืม</th>
                            <th>วันที่คืน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($approvedLoans as $loan):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($loan['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['loan_date']); ?></td>
                                <td><?php echo date('Y/m/d', strtotime($loan['loan_return_date'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#returnModal<?php echo $loan['loan_id']; ?>">
                                        คืน
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal สำหรับคืนครุภัณฑ์ -->
                            <div class="modal fade" id="returnModal<?php echo $loan['loan_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="returnModalLabel">คืนครุภัณฑ์</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form action="process_return_loan.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                                <div class="form-group">
                                                    <label for="equ_condition">สภาพของครุภัณฑ์</label>
                                                    <select class="form-control" id="equ_condition" name="equ_condition" required>
                                                        <option value="ดี">ดี</option>
                                                        <option value="พอใช้">พอใช้</option>
                                                        <option value="ต้องซ่อม">ต้องซ่อม</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                                                <button type="submit" class="btn btn-primary">คืน</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

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
    $('#approvedLoanTable').DataTable({
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