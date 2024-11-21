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

// จัดการการกู้คืนครุภัณฑ์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    $result = $inventoryManager->restoreEquipment($_POST['equ_id']);
    if ($result) {
        $_SESSION['success'] = "กู้คืนครุภัณฑ์สำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการกู้คืนครุภัณฑ์";
    }
    header("Location: recycle.php");
    exit();
}

// ดึงข้อมูลครุภัณฑ์ที่ถูกลบ (เฉพาะสถานะ deleted)
$deletedEquipment = $inventoryManager->getAllEquipment(true); // true เพื่อให้ดึงเฉพาะสถานะ deleted
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">หน้ารีไซเคิล</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- ปุ่มกลับไปยังหน้า manage_equipment -->
        <div class="mb-3">
            <a href="manage_equipment.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> กลับไป
            </a>
        </div>

        <!-- ตารางแสดงรายการครุภัณฑ์ที่ถูกลบ -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="deletedEquipmentTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>ประเภท</th>
                            <th>สถานที่</th>
                            <th>สภาพ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($deletedEquipment as $item):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['equ_type']); ?></td>
                                <td><?php echo htmlspecialchars($item['equ_location']); ?></td>
                                <td><?php echo htmlspecialchars($item['equ_condition']); ?></td>
                                <td>
                                    <form action="recycle.php" method="POST">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="equ_id" value="<?php echo $item['equ_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">กู้คืน</button>
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

<?php
$content = ob_get_clean();
include 'layout.php';
?>