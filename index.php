<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบสิทธิ์การเข้าถึง - เฉพาะ admin เท่านั้น
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'admin') {
    // ถ้าไม่ใช่ admin ให้ redirect ไปตาม role
    if (isset($_SESSION['user_role'])) {
        switch ($_SESSION['user_role']) {
            case 'staff':
                header("Location: index_staff.php");
                break;
            case 'user':
                header("Location: index_user.php");
                break;
        }
    } else {
        header("Location: signin.php");
    }
    exit();
}

include_once("class/Database.php");
include_once("class/UserManagement.php");
include_once("class/InventoryManagement.php");
include_once("class/ReportManagement.php");

$database = new Database();
$db = $database->getConnection();

$userManager = new UserManagement($db);
$inventoryManager = new InventoryManagement($db);
$reportManager = new ReportManagement($db);

$userData = $userManager->getUserById($_SESSION['userid']);
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- สรุปภาพรวม -->
        <div class="row">
            <!-- จำนวนผู้ใช้ทั้งหมด -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($userManager->getAllUsers()); ?></h3>
                        <p>ผู้ใช้ทั้งหมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="manage_users.php" class="small-box-footer">
                        ดูเพิ่มเติม <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- จำนวนวัสดุคงเหลือ -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo count($inventoryManager->getMaterialInventoryReport()); ?></h3>
                        <p>วัสดุคงเหลือ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <a href="report_materials.php" class="small-box-footer">
                        ดูเพิ่มเติม <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- จำนวนครุภัณฑ์ -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count($inventoryManager->getEquipmentStatusReport()); ?></h3>
                        <p>ครุภัณฑ์ทั้งหมด</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <a href="report_equipment.php" class="small-box-footer">
                        ดูเพิ่มเติม <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- การยืมที่รอดำเนินการ -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count($inventoryManager->getActiveLoanRequests()); ?></h3>
                        <p>การยืมที่รอดำเนินการ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="report_loans.php" class="small-box-footer">
                        ดูเพิ่มเติม <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- รายการที่ต้องดำเนินการ -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">การยืมที่เกินกำหนด</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <!-- แสดงรายการยืมที่เกินกำหนด -->
                            <?php
                            $overdueLoans = $reportManager->getOverdueLoanReport();
                            foreach($overdueLoans as $loan) {
                                echo "<tr>";
                                echo "<td>{$loan['equ_name']}</td>";
                                echo "<td>{$loan['user_name']}</td>";
                                echo "<td>{$loan['due_date']}</td>";
                                echo "</tr>";
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">วัสดุที่ใกล้หมด</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover">
                            <!-- แสดงรายการวัสดุที่ใกล้หมด -->
                            <?php
                            $materials = $reportManager->getMaterialInventoryReport();
                            foreach($materials as $material) {
                                if($material['mat_quantity'] < 10) { // ตัวอย่างเงื่อนไข
                                    echo "<tr>";
                                    echo "<td>{$material['mat_name']}</td>";
                                    echo "<td>{$material['mat_quantity']} {$material['mat_unit']}</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include 'layout.php';
?>