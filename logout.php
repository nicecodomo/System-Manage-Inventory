<?php
session_start();

// บันทึกข้อมูลการออกจากระบบ (ถ้าต้องการ)
if (isset($_SESSION['userid'])) {
    include_once("class/Database.php");
    include_once("class/UserManagement.php");

    $database = new Database();
    $db = $database->getConnection();

    $userManager = new UserManagement($db);

    // บันทึกเวลาออกจากระบบ
    // $userManager->updateLastLogout($_SESSION['userid']);
}

// ล้าง session ทั้งหมด
$_SESSION = array();

// ลบ cookie ของ session (ถ้ามี)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// ทำลาย session
session_destroy();

// ใช้ SweetAlert2 แสดงข้อความก่อน redirect
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ออกจากระบบ</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        Swal.fire({
            title: 'ออกจากระบบสำเร็จ',
            text: 'กำลังกลับไปยังหน้าเข้าสู่ระบบ...',
            icon: 'success',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = 'signin.php';
        });
    </script>
</body>

</html>