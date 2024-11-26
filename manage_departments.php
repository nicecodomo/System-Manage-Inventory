<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/UserManagement.php");

$database = new Database();
$db = $database->getConnection();
$userManager = new UserManagement($db);

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = $userManager->addDepartment(
                    $_POST['dep_name'],
                    $_POST['dep_contact']
                );
                if ($result) {
                    $_SESSION['success'] = "เพิ่มแผนกสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มแผนก";
                }
                break;

            case 'edit':
                $result = $userManager->updateDepartment(
                    $_POST['dep_id'],
                    $_POST['dep_name'],
                    $_POST['dep_contact']
                );
                if ($result) {
                    $_SESSION['success'] = "อัปเดตข้อมูลแผนกสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
                }
                break;

            case 'delete':
                $result = $userManager->deleteDepartment($_POST['dep_id']);
                if ($result) {
                    $_SESSION['success'] = "ลบแผนกสำเร็จ";
                } else {
                    $_SESSION['error'] = "ไม่สามารถลบแผนกได้ เนื่องจากมีผู้ใช้งานในแผนกนี้";
                }
                break;
        }
        header("Location: manage_departments.php");
        exit();
    }
}

// ดึงข้อมูลแผนกทั้งหมด
$departments = $userManager->getAllDepartmentsWithUserCount();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">จัดการแผนก</h1>
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

        <!-- ปุ่มเพิ่มแผนกใหม่ -->
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addDepartmentModal">
                <i class="fas fa-plus"></i> เพิ่มแผนกใหม่
            </button>
        </div>

        <!-- ตารางแสดงรายการแผนก -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="departmentsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ชื่อแผนก</th>
                            <th>เบอร์โทร</th>
                            <th>จำนวนผู้ใช้งาน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dept['dep_name']); ?></td>
                                <td><?php echo htmlspecialchars($dept['dep_contact']); ?></td>
                                <td><?php echo $dept['user_count']; ?> คน</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info edit-department"
                                        data-department='<?php echo json_encode($dept); ?>'
                                        data-toggle="modal" data-target="#editDepartmentModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($dept['user_count'] == 0): ?>
                                        <button type="button" class="btn btn-sm btn-danger delete-department"
                                            data-toggle="modal"
                                            data-target="#deleteDepartmentModal"
                                            data-dep-id="<?php echo $dept['dep_id']; ?>"
                                            data-dep-name="<?php echo htmlspecialchars($dept['dep_name']); ?>">
                                            <i class="fas fa-trash"></i>
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

<!-- Modal เพิ่มแผนก -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">เพิ่มแผนกใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_departments.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="dep_name">ชื่อแผนก</label>
                        <input type="text" class="form-control" id="dep_name" name="dep_name" required>
                    </div>
                    <div class="form-group">
                        <label for="dep_contact">เบอร์โทร</label>
                        <input class="form-control" id="dep_contact" name="dep_contact" rows="3" required>
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

<!-- Modal แก้ไขแผนก -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">แก้ไขข้อมูลแผนก</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_departments.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="dep_id" id="edit_dep_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_dep_name">ชื่อแผนก</label>
                        <input type="text" class="form-control" id="edit_dep_name" name="dep_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_dep_contact">เบอร์โทร</label>
                        <input class="form-control" id="edit_dep_contact" name="dep_contact" rows="3" required>
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

<!-- Modal ลบแผนก -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDepartmentModalLabel">ยืนยันการลบแผนก</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_departments.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="dep_id" id="delete_dep_id">
                <div class="modal-body">
                    <p>คุณต้องการลบแผนก "<span id="delete_dep_name"></span>" ใช่หรือไม่?</p>
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
        $('#departmentsTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        // Handle Edit Department
        $('.edit-department').click(function() {
            const deptData = $(this).data('department');
            $('#edit_dep_id').val(deptData.dep_id);
            $('#edit_dep_name').val(deptData.dep_name);
            $('#edit_dep_contact').val(deptData.dep_contact);
        });

        // Handle Delete Department
        $('.delete-department').click(function() {
            const depId = $(this).data('dep-id');
            const depName = $(this).data('dep-name');
            $('#delete_dep_id').val(depId);
            $('#delete_dep_name').text(depName);
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>