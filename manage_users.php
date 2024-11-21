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
                $result = $userManager->addUser(
                    $_POST['username'],
                    $_POST['password'],
                    $_POST['name'],
                    $_POST['role'],
                    $_POST['department'],
                    $_POST['contact']
                );
                if ($result) {
                    $_SESSION['success'] = "เพิ่มผู้ใช้งานสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้งาน";
                }
                break;

            case 'edit':
                $result = $userManager->updateUser(
                    $_POST['user_id'],
                    $_POST['name'],
                    $_POST['department'],
                    $_POST['contact'],
                    $_POST['role']
                );
                if ($result) {
                    $_SESSION['success'] = "อัปเดตข้อมูลผู้ใช้งานสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
                }
                break;

            case 'delete':
                $result = $userManager->deleteUser($_POST['user_id']);
                if ($result) {
                    $_SESSION['success'] = "ลบผู้ใช้งานสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบผู้ใช้งาน";
                }
                break;

            case 'change_status':
                $result = $userManager->updateUserStatus(
                    $_POST['user_id'],
                    $_POST['status']
                );
                if ($result) {
                    $_SESSION['success'] = "อัปเดตสถานะผู้ใช้งานสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตสถานะ";
                }
                break;
        }
        header("Location: manage_users.php");
        exit();
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = $userManager->getAllUsers();
$departments = $userManager->getAllDepartments();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">จัดการผู้ใช้งาน</h1>
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

        <!-- ปุ่มเพิ่มผู้ใช้ใหม่ -->
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus"></i> เพิ่มผู้ใช้งานใหม่
            </button>
        </div>

        <!-- ตารางแสดงรายการผู้ใช้ -->
        <div class="card">
            <div class="card-body table-responsive">
                <table id="usersTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>แผนก</th>
                            <th>สิทธิ์การใช้งาน</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_username']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_contact']); ?></td>
                                <td><?php echo htmlspecialchars($user['department_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['user_role'] === 'admin' ? 'danger' : ($user['user_role'] === 'staff' ? 'warning' : 'info'); ?>">
                                        <?php echo htmlspecialchars($user['user_role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- แสดงสถานะปัจจุบัน -->
                                    <span class="badge badge-<?php echo $user['user_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['user_status'] === 'active' ? 'ใช้งาน' : 'ระงับการใช้งาน'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info edit-user"
                                        data-user='<?php echo json_encode($user); ?>'
                                        data-toggle="modal" data-target="#editUserModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['user_id'] != 1): ?>

                                        <!-- ปุ่มเปลี่ยนสถานะ -->
                                        <button type="button"
                                            class="btn btn-sm <?php echo $user['user_status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> change-status"
                                            data-user-id="<?php echo $user['user_id']; ?>"
                                            data-status="<?php echo $user['user_status'] === 'active' ? 'inactive' : 'active'; ?>"
                                            data-toggle="tooltip"
                                            title="<?php echo $user['user_status'] === 'active' ? 'ระงับการใช้งาน' : 'เปิดใช้งาน'; ?>">
                                            <i class="fas <?php echo $user['user_status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
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

<!-- Modal เพิ่มผู้ใช้ -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">เพิ่มผู้ใช้งานใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_users.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="5" required pattern=".{5,}" title="รหัสผ่านต้องมีความยาวอย่างน้อย 5 ตัวอักษร">
                        <small class="form-text text-muted">รหัสผ่านต้องมีความยาวอย่างน้อย 5 ตัวอักษร</small>
                    </div>
                    <div class="form-group">
                        <label for="name">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="contact" name="contact" required>
                    </div>
                    <div class="form-group">
                        <label for="department">แผนก</label>
                        <select class="form-control" id="department" name="department" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dep_id']; ?>">
                                    <?php echo htmlspecialchars($dept['dep_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="role">สิทธิ์การใช้งาน</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="user">ผู้ใช้งานทั่วไป</option>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
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

<!-- Modal แก้ไขผู้ใช้ -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">แก้ไขข้อมูลผู้ใช้</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_users.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_contact">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="edit_contact" name="contact" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_department">แผนก</label>
                        <select class="form-control" id="edit_department" name="department" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dep_id']; ?>">
                                    <?php echo htmlspecialchars($dept['dep_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">สิทธิ์การใช้งาน</label>
                        <select class="form-control" id="edit_role" name="role" required>
                            <option value="user">ผู้ใช้งานทั่วไป</option>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">รหัสผ่านใหม่</label>
                        <input type="password"
                            class="form-control"
                            id="edit_password"
                            name="password"
                            minlength="5"
                            pattern=".{5,}"
                            title="รหัสผ่านต้องมีความยาวอย่างน้อย 5 ตัวอักษร"
                            placeholder="(เว้นว่างถ้าไม่ต้องการเปลี่ยน)">
                        <small class="form-text text-muted">รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 5 ตัวอักษร</small>
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

<!-- Modal ลบผู้ใช้ -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">ยืนยันการลบผู้ใช้</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="manage_users.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-body">
                    <p>คุณต้องการลบผู้ใช้ "<span id="delete_user_name"></span>" ใช่หรือไม่?</p>
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
        $('#usersTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
            }
        });

        // Handle Edit User
        $('.edit-user').click(function() {
            const userData = $(this).data('user');
            $('#edit_user_id').val(userData.user_id);
            $('#edit_name').val(userData.user_name);
            $('#edit_contact').val(userData.user_contact);
            $('#edit_department').val(userData.dep_id);
            $('#edit_role').val(userData.user_role);
        });

        // Handle Delete User
        $('.delete-user').click(function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');
            $('#delete_user_id').val(userId);
            $('#delete_user_name').text(userName);
        });

        // Handle Status Change
        $('.change-status').click(function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            const newStatus = $(this).data('status');
            const statusText = newStatus === 'active' ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';

            if (confirm(`ต้องการ${statusText}ผู้ใช้งานนี้หรือไม่?`)) {
                // สร้างฟอร์มและส่งข้อมูล
                const form = $('<form>', {
                    'method': 'POST',
                    'action': 'manage_users.php'
                }).append(
                    $('<input>', {
                        'type': 'hidden',
                        'name': 'action',
                        'value': 'change_status'
                    }),
                    $('<input>', {
                        'type': 'hidden',
                        'name': 'user_id',
                        'value': userId
                    }),
                    $('<input>', {
                        'type': 'hidden',
                        'name': 'status',
                        'value': newStatus
                    })
                );

                // แนบฟอร์มไปที่ body และส่ง
                $('body').append(form);
                form.submit();
            }
        });

        // เปิดใช้งาน tooltip
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>