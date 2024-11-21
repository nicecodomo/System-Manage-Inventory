<?php
include_once("sys_header.inc.php");
ob_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/UserManagement.php");

$database = new Database();
$db = $database->getConnection();
$userManager = new UserManagement($db);

// ดึงข้อมูลผู้ใช้
$userData = $userManager->getUserById($_SESSION['userid']);
$departments = $userManager->getAllDepartments();

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $result = $userManager->updateProfile(
                    $_SESSION['userid'],
                    $_POST['name'],
                    $_POST['contact']
                );
                if ($result) {
                    $_SESSION['success'] = "อัปเดตข้อมูลส่วนตัวสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
                }
                break;

            case 'change_password':
                // ตรวจสอบรหัสผ่านเดิม
                if ($userManager->verifyPassword($_SESSION['userid'], $_POST['current_password'])) {
                    // ตรวจสอบว่ารหัสผ่านใหม่ตรงกัน
                    if ($_POST['new_password'] === $_POST['confirm_password']) {
                        $result = $userManager->changePassword($_SESSION['userid'], $_POST['new_password']);
                        if ($result) {
                            $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จ";
                        } else {
                            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
                        }
                    } else {
                        $_SESSION['error'] = "รหัสผ่านใหม่ไม่ตรงกัน";
                    }
                } else {
                    $_SESSION['error'] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
                }
                break;
        }
        header("Location: profile.php");
        exit();
    }
}
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">โปรไฟล์ของฉัน</h1>
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

        <div class="row">
            <!-- ข้อมูลส่วนตัว -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">ข้อมูลส่วนตัว</h3>
                    </div>
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="username">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['user_username']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="name">ชื่อ-นามสกุล</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($userData['user_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="contact">เบอร์โทรศัพท์</label>
                                <input type="text" class="form-control" id="contact" name="contact"
                                    value="<?php echo htmlspecialchars($userData['user_contact']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>แผนก</label>
                                <input type="text" class="form-control" 
                                    value="<?php 
                                        foreach ($departments as $dept) {
                                            if ($dept['dep_id'] == $userData['dep_id']) {
                                                echo htmlspecialchars($dept['dep_name']);
                                                break;
                                            }
                                        }
                                    ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>สิทธิ์การใช้งาน</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['user_role']); ?>" readonly>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- เปลี่ยนรหัสผ่าน -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">เปลี่ยนรหัสผ่าน</h3>
                    </div>
                    <form action="profile.php" method="POST" id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="current_password">รหัสผ่านปัจจุบัน</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">รหัสผ่านใหม่</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">รหัสผ่านต้องมีความยาวอย่างน้อย 5 ตัวอักษร</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom Scripts -->
<script>
$(document).ready(function() {
    // ตรวจสอบการกรอกรหัสผ่าน
    $('#passwordForm').on('submit', function(e) {
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();

        if (newPassword.length < 5) {
            e.preventDefault();
            alert('รหัสผ่านต้องมีความยาวอย่างน้อย 5 ตัวอักษร');
            return false;
        }

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('รหัสผ่านใหม่ไม่ตรงกัน');
            return false;
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?> 