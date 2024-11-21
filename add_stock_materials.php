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

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_stock') {
    $success = true;
    $error = false;

    if (isset($_POST['materials']) && is_array($_POST['materials'])) {
        foreach ($_POST['materials'] as $item) {
            if (!empty($item['mat_id']) && !empty($item['quantity'])) {
                $result = $inventoryManager->addMaterialStock(
                    $item['mat_id'],
                    $item['quantity']
                );
                if (!$result) {
                    $error = true;
                    break;
                }
            }
        }
    }

    if ($error) {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มจำนวนวัสดุ";
    } else {
        $_SESSION['success'] = "เพิ่มจำนวนวัสดุสำเร็จ";
    }
    header("Location: manage_materials.php");
    exit();
}

// ดึงข้อมูลวัสดุทั้งหมด
$materials = $inventoryManager->getAllMaterials(false); // false เพื่อไม่ให้ดึงสถานะ deleted
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">เพิ่มจำนวนวัสดุ</h1>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form action="add_stock_materials.php" method="POST">
            <input type="hidden" name="action" value="add_stock">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th width="40%">วัสดุ</th>
                            <th width="20%">จำนวนคงเหลือ</th>
                            <th width="20%">จำนวนที่ต้องการเพิ่ม</th>
                            <th width="20%">หน่วยนับ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $index => $material): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox"
                                            class="form-check-input"
                                            id="mat_<?php echo $material['mat_id']; ?>"
                                            name="materials[<?php echo $index; ?>][mat_id]"
                                            value="<?php echo $material['mat_id']; ?>">
                                        <label class="form-check-label"
                                            for="mat_<?php echo $material['mat_id']; ?>">
                                            <?php echo htmlspecialchars($material['mat_name']); ?>
                                        </label>
                                    </div>
                                </td>
                                <td><?php echo $material['mat_quantity']; ?></td>
                                <td>
                                    <input type="number"
                                        class="form-control"
                                        name="materials[<?php echo $index; ?>][quantity]"
                                        min="1"
                                        disabled
                                        data-mat-id="<?php echo $material['mat_id']; ?>">
                                </td>
                                <td><?php echo htmlspecialchars($material['mat_unit']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.history.back();">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</section>

<script>
    $(document).ready(function() {
        // เปิด/ปิดช่องกรอกจำนวนตาม checkbox
        $('.form-check-input').change(function() {
            const matId = $(this).val();
            const quantityInput = document.querySelector(`input[data-mat-id="${matId}"]`);
            quantityInput.disabled = !this.checked; // เปิด/ปิดช่องกรอกจำนวน
            quantityInput.required = this.checked; // กำหนดให้เป็นฟิลด์ที่จำเป็นเมื่อเช็คบล็อก
            if (!this.checked) quantityInput.value = ''; // ล้างค่าถ้าไม่เช็ค
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?> 