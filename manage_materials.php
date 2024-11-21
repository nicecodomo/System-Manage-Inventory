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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = $inventoryManager->addMaterial(
                    $_POST['name'],
                    $_POST['quantity'],
                    $_POST['unit'],
                    $_POST['description']
                );
                if ($result) {
                    $_SESSION['success'] = "เพิ่มวัสดุสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มวัสดุ";
                }
                break;

            case 'edit':
                $result = $inventoryManager->updateMaterial(
                    $_POST['mat_id'],
                    $_POST['name'],
                    $_POST['quantity'],
                    $_POST['unit'],
                    $_POST['description']
                );
                if ($result) {
                    $_SESSION['success'] = "อัปเดตข้อมูลวัสดุสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
                }
                break;

            case 'delete':
                $result = $inventoryManager->deleteMaterial($_POST['mat_id']);
                if ($result) {
                    $_SESSION['success'] = "ลบวัสดุสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบวัสดุ";
                }
                break;

            case 'deactivate':
                $result = $inventoryManager->deactivateMaterial($_POST['mat_id']);
                if ($result) {
                    $_SESSION['success'] = "ปิดการใช้งานวัสดุสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการปิดการใช้งาน";
                }
                break;

            case 'update_status':
                $result = $inventoryManager->updateMaterialStatus($_POST['mat_id'], $_POST['status']);
                if ($result) {
                    $_SESSION['success'] = "เปลี่ยนสถานะสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนสถานะ";
                }
                break;

            case 'add_stock':
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
                break;
        }
        header("Location: manage_materials.php");
        exit();
    }
}

// ดึงข้อมูลวัสดุทั้งหมด (ไม่รวมสถานะ deleted)
$materials = $inventoryManager->getAllMaterials(false); // false เพื่อไม่ให้ดึงสถานะ deleted
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">จัดการวัสดุ</h1>
            </div>
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

        <!-- ปุ่มเพิ่มวัสดุใหม่ -->
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addMaterialModal">
                <i class="fas fa-plus"></i> เพิ่มวัสดุใหม่
            </button>
            <a href="add_stock_materials.php" class="btn btn-success">
                <i class="fas fa-plus"></i> เพิ่มจำนวนวัสดุ
            </a>
            <!-- เพิ่มลิงก์ไปยังหน้ารีไซเคิล -->
            <a href="recycle_materials.php" class="btn btn-secondary">
                <i class="fas fa-recycle"></i> Recycle
            </a>
        </div>

        <!-- ตารางแสดงรายการวัสดุ -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="materialsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อวัสดุ</th>
                            <th>จำนวนคงเหลือ</th>
                            <th>หน่วยนับ</th>
                            <th>รายละเอียด</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($materials as $material):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($material['mat_name']); ?></td>
                                <td><?php echo $material['mat_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($material['mat_unit']); ?></td>
                                <td><?php echo htmlspecialchars($material['mat_description']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $material['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($material['status']); ?>
                                    </span>
                                    <?php
                                    // แสดง badge ตามจำนวนคงเหลือ
                                    if ($material['mat_quantity'] <= 10) {
                                        echo '<span class="badge badge-danger">ใกล้หมด</span>';
                                    } elseif ($material['mat_quantity'] <= 30) {
                                        echo '<span class="badge badge-warning">ต่ำ</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button"
                                        class="btn btn-sm btn-info"
                                        data-toggle="modal"
                                        data-target="#editMaterialModal"
                                        onclick="editMaterial(
                                        <?php
                                        echo htmlspecialchars(json_encode([
                                            'mat_id' => $material['mat_id'],
                                            'mat_name' => $material['mat_name'],
                                            'mat_quantity' => $material['mat_quantity'],
                                            'mat_unit' => $material['mat_unit'],
                                            'mat_description' => $material['mat_description']
                                        ]));
                                        ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- ปุ่มลบ -->
                                    <button type="button"
                                        class="btn btn-sm btn-danger"
                                        data-toggle="modal"
                                        data-target="#deleteMaterialModal"
                                        onclick="deleteMaterial(<?php echo $material['mat_id']; ?>, '<?php echo htmlspecialchars($material['mat_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <!-- ปุ่มปิดการใช้งาน -->
                                    <?php if ($material['status'] === 'active'): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-warning"
                                            onclick="updateStatus(<?php echo $material['mat_id']; ?>, 'inactive')">
                                            <i class="fas fa-ban"></i> ปิดการใช้งาน
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                            class="btn btn-sm btn-success"
                                            onclick="updateStatus(<?php echo $material['mat_id']; ?>, 'active')">
                                            <i class="fas fa-check"></i> เปิดใช้งาน
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal เพิ่มวัสดุ -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" role="dialog" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMaterialModalLabel">เพิ่มวัสดุใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_materials.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">ชื่อวัสดุ</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity">จำนวน</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required min="0">
                    </div>
                    <div class="form-group">
                        <label for="unit">หน่วยนับ</label>
                        <input type="text" class="form-control" id="unit" name="unit" required>
                    </div>
                    <div class="form-group">
                        <label for="description">รายละเอียด</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขวัสดุ -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" role="dialog" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMaterialModalLabel">แก้ไขข้อมูลวัสดุ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_materials.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="mat_id" id="edit_mat_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">ชื่อวัสดุ</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_quantity">จำนวน</label>
                        <input type="number" class="form-control" id="edit_quantity" name="quantity" required min="0">
                    </div>
                    <div class="form-group">
                        <label for="edit_unit">หน่วยนับ</label>
                        <input type="text" class="form-control" id="edit_unit" name="unit" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">รายละเอียด</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ลบวัสดุ -->
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" role="dialog" aria-labelledby="deleteMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMaterialModalLabel">ยืนยันการลบวัสดุ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_materials.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="mat_id" id="delete_mat_id">
                <div class="modal-body">
                    <p>คุณต้องการลบวัสดุ "<span id="delete_mat_name"></span>" ใช่หรือไม่?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables & Custom Scripts -->
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#materialsTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });
    });

    // ฟังก์ชันสำหรับแก้ไขวัสดุ
    function editMaterial(material) {
        document.getElementById('edit_mat_id').value = material.mat_id;
        document.getElementById('edit_name').value = material.mat_name;
        document.getElementById('edit_quantity').value = material.mat_quantity;
        document.getElementById('edit_unit').value = material.mat_unit;
        document.getElementById('edit_description').value = material.mat_description;
    }

    // ฟังก์ชันสำหรับลบวัสดุ
    function deleteMaterial(id, name) {
        document.getElementById('delete_mat_id').value = id;
        document.getElementById('delete_mat_name').textContent = name;
    }

    // ฟังก์ชันสำหรับอัปเดตสถานะวัสดุ
    function updateStatus(matId, status) {
        $.post('manage_materials.php', {
            action: 'update_status',
            mat_id: matId,
            status: status
        }, function(response) {
            location.reload(); // โหลดหน้าใหม่เพื่อแสดงผลการเปลี่ยนแปลง
        });
    }
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>