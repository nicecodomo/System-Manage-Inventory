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
                $result = $inventoryManager->addEquipment(
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['condition'],
                    $_POST['location']
                );
                if ($result) {
                    $_SESSION['success'] = "เพิ่มครุภัณฑ์สำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มครุภัณฑ์";
                }
                break;

            case 'edit':
                $result = $inventoryManager->updateEquipment(
                    $_POST['equ_id'],
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['condition'],
                    $_POST['location']
                );
                if ($result) {
                    $_SESSION['success'] = "อัปเดตข้อมูลครุภัณฑ์สำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
                }
                break;

            case 'deactivate':
                $result = $inventoryManager->deactivateEquipment($_POST['equ_id']);
                if ($result) {
                    $_SESSION['success'] = "ปิดการใช้งานครุภัณฑ์สำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการปิดการใช้งาน";
                }
                break;

            case 'delete':
                $result = $inventoryManager->deleteEquipment($_POST['equ_id']);
                if ($result) {
                    $_SESSION['success'] = "ลบครุภัณฑ์สำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบครุภัณฑ์";
                }
                break;

            case 'update_status':
                $result = $inventoryManager->updateStatus($_POST['equ_id'], $_POST['status']);
                if ($result) {
                    $_SESSION['success'] = "เปลี่ยนสถานะสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนสถานะ";
                }
                break;
        }
        header("Location: manage_equipment.php");
        exit();
    }
}

// ดึงข้อมูลครุภัณฑ์ทั้งหมด (ไม่รวมสถานะ deleted)
$equipment = $inventoryManager->getAllEquipment(false); // false เพื่อไม่ให้ดึงสถานะ deleted
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">จัดการครุภัณฑ์</h1>
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

        <!-- ปุ่มเพิ่มครุภัณฑ์ใหม่ -->
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEquipmentModal">
                <i class="fas fa-plus"></i> เพิ่มครุภัณฑ์ใหม่
            </button>
            <!-- เพิ่มลิงก์ไปยังหน้ารีไซเคิล -->
            <a href="recycle_equipment.php" class="btn btn-secondary">
                <i class="fas fa-recycle"></i> Recycle
            </a>
        </div>

        <!-- ตารางแสดงรายการครุภัณฑ์ -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="equipmentTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>ประเภท</th>
                            <th>สถานที่</th>
                            <th>สภาพ</th>
                            <th>สถานะครุภัณฑ์</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($equipment as $item):
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['equ_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['equ_type']); ?></td>
                                <td><?php echo htmlspecialchars($item['equ_location']); ?></td>
                                <td><?php echo htmlspecialchars($item['equ_condition']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $item['equ_status'] === 'ว่าง' ? 'success' : ($item['equ_status'] === 'ถูกยืม' ? 'warning' : 'danger'); ?>">
                                        <?php echo $item['equ_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $item['status'] === 'active' ? 'success' : ($item['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info edit-equipment"
                                        data-toggle="modal"
                                        data-target="#editEquipmentModal"
                                        data-equipment='<?php echo json_encode($item); ?>'>
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>

                                    <?php if ($item['status'] === 'active'): ?>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            data-id="<?php echo $item['equ_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['equ_name']); ?>"
                                            onclick="updateStatus('<?php echo $item['equ_id']; ?>', 'inactive')">
                                            <i class="fas fa-ban"></i> ปิดการใช้งาน
                                        </button>
                                    <?php elseif ($item['status'] === 'inactive'): ?>
                                        <button type="button" class="btn btn-sm btn-success"
                                            data-id="<?php echo $item['equ_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['equ_name']); ?>"
                                            onclick="updateStatus('<?php echo $item['equ_id']; ?>', 'active')">
                                            <i class="fas fa-check"></i> เปิดใช้งาน
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($item['status'] !== 'deleted'): // ตรวจสอบสถานะก่อนแสดงปุ่มลบ 
                                    ?>
                                        <?php if ($item['equ_status'] !== 'ถูกยืม'): ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-equipment"
                                                data-toggle="modal"
                                                data-target="#deleteEquipmentModal"
                                                data-id="<?php echo $item['equ_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['equ_name']); ?>">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-danger" disabled>
                                                <i class="fas fa-trash"></i> ไม่สามารถลบได้
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal เพิ่มครุภัณฑ์ -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEquipmentModalLabel">เพิ่มครุภัณฑ์ใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_equipment.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">ชื่อครุภัณฑ์</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="type">ประเภท</label>
                        <input type="text" class="form-control" id="type" name="type" required>
                    </div>
                    <div class="form-group">
                        <label for="condition">สภาพ</label>
                        <select class="form-control" id="condition" name="condition" required>
                            <option value="ดี">ดี</option>
                            <option value="พอใช้">พอใช้</option>
                            <option value="ต้องซ่อม">ต้องซ่อม</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location">สถานที่</label>
                        <input type="text" class="form-control" id="location" name="location" required>
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

<!-- Modal แก้ไขครุภัณฑ์ -->
<div class="modal fade" id="editEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEquipmentModalLabel">แก้ไขข้อมูลครุภัณฑ์</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_equipment.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="equ_id" id="edit_equ_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">ชื่อครุภัณฑ์</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_type">ประเภท</label>
                        <input type="text" class="form-control" id="edit_type" name="type" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_condition">สภาพ</label>
                        <select class="form-control" id="edit_condition" name="condition" required>
                            <option value="ดี">ดี</option>
                            <option value="พอใช้">พอใช้</option>
                            <option value="ต้องซ่อม">ต้องซ่อม</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_location">สถานที่</label>
                        <input type="text" class="form-control" id="edit_location" name="location" required>
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

<!-- Modal ลบครุภัณฑ์ -->
<div class="modal fade" id="deleteEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="deleteEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEquipmentModalLabel">ยืนยันการลบครุภัณฑ์</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_equipment.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="equ_id" id="delete_equ_id">
                <div class="modal-body">
                    <p>คุณต้องการลบครุภัณฑ์ "<span id="delete_equ_name"></span>" ใช่หรือไม่?</p>
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
        $('#equipmentTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        // Handle Edit Equipment
        $('.edit-equipment').click(function() {
            const equipmentData = $(this).data('equipment');
            $('#edit_equ_id').val(equipmentData.equ_id);
            $('#edit_name').val(equipmentData.equ_name);
            $('#edit_type').val(equipmentData.equ_type);
            $('#edit_condition').val(equipmentData.equ_condition);
            $('#edit_location').val(equipmentData.equ_location);
        });

        // Handle Delete Equipment
        $('.delete-equipment').click(function() {
            const equId = $(this).data('id');
            const equName = $(this).data('name');
            const equStatus = $(this).data('status');

            if (equStatus === 'ถูกยืม') {
                alert("ไม่สามารถลบครุภัณฑ์ '" + equName + "' ได้ เนื่องจากมีการยืมที่เกี่ยวข้อง");
            } else {
                $('#delete_equ_id').val(equId);
                $('#delete_equ_name').text(equName);
            }
        });
    });

    function updateStatus(equId, status) {
        $.post('manage_equipment.php', {
            action: 'update_status',
            equ_id: equId,
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