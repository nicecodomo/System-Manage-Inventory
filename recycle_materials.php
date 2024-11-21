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

// จัดการการกู้คืนวัสดุ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    $result = $inventoryManager->restoreMaterial($_POST['mat_id']);
    if ($result) {
        $_SESSION['success'] = "กู้คืนวัสดุสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการกู้คืนวัสดุ";
    }
    header("Location: recycle_materials.php");
    exit();
}

// ดึงข้อมูลวัสดุที่ถูกลบ (เฉพาะสถานะ deleted)
$deletedMaterials = $inventoryManager->getAllMaterials(true); // true เพื่อให้ดึงเฉพาะสถานะ deleted
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">หน้ารีไซเคิลวัสดุ</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- ปุ่มกลับไปยังหน้า manage_materials -->
        <div class="mb-3">
            <a href="manage_materials.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> กลับไป
            </a>
        </div>

        <!-- ตารางแสดงรายการวัสดุที่ถูกลบ -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="deletedMaterialsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวนคงเหลือ</th>
                            <th>หน่วยนับ</th>
                            <th>รายละเอียด</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($deletedMaterials as $item):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['mat_name']); ?></td>
                                <td><?php echo $item['mat_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($item['mat_unit']); ?></td>
                                <td><?php echo htmlspecialchars($item['mat_description']); ?></td>
                                <td>
                                    <form action="recycle_materials.php" method="POST">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="mat_id" value="<?php echo $item['mat_id']; ?>">
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