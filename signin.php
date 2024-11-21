<?php
ob_start();
require("sys_header.inc.php");

// ถ้ามีการล็อกอินอยู่แล้วให้ไปหน้าที่เหมาะสม
if (isset($_SESSION['userid'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: index.php");
            break;
        case 'staff':
            header("Location: index_staff.php");
            break;
        case 'user':
            header("Location: index_user.php");
            break;
        default:
            header("Location: index.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ - ระบบจัดการวัสดุครุภัณฑ์</title>

    <!-- Custom CSS -->
    <style>
        .login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            width: 400px;
        }

        .login-logo {
            margin-bottom: 25px;
        }

        .login-logo img {
            width: 80px;
            margin-bottom: 15px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .btn-signin {
            background: #4e73df;
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-signin:hover {
            background: #2e59d9;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 10px;
        }
    </style>
</head>

<body class="login-page">
    <div class="login-box">
        <div class="login-logo text-center">
            <img src="dist/img/university.png" alt="Logo">
            <h4 class="text-white mb-4">ระบบจัดการวัสดุครุภัณฑ์</h4>
        </div>

        <?php
        include_once("class/Database.php");
        include_once("class/UserManagement.php");

        $database = new Database();
        $db = $database->getConnection();
        $userManager = new UserManagement($db);

        if (isset($_POST['signin'])) {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            if (empty($username) || empty($password)) {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> กรุณากรอกข้อมูลให้ครบถ้วน
                      </div>';
            } else {
                $result = $userManager->login($username, $password);
                if ($result) {
                    if ($result['user_status'] === 'inactive' && $result['user_id'] != 1) {
                        echo '<div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> บัญชีผู้ใช้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ
                              </div>';
                    } else {
                        $_SESSION['userid'] = $result['user_id'];
                        $_SESSION['user_role'] = $result['user_role'];
                        $_SESSION['username'] = $result['user_username'];

                        // Redirect ตาม role อย่างชัดเจน
                        switch ($result['user_role']) {
                            case 'admin':
                                header("Location: index.php"); // หน้าแรกสำหรับ admin
                                break;
                            case 'staff':
                                header("Location: index_staff.php"); // หน้าแรกสำหรับเจ้าหน้าที่
                                break;
                            case 'user':
                                header("Location: index_user.php"); // หน้าแรกสำหรับผู้ใช้ทั่วไป
                                break;
                            default:
                                // กรณีไม่มี role ที่ถูกต้อง
                                session_destroy();
                                header("Location: signin.php?error=invalid_role");
                                break;
                        }
                        exit();
                    }
                } else {
                    echo '<div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง
                          </div>';
                }
            }
        }
        ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                </div>
                <input type="text"
                    name="username"
                    class="form-control"
                    placeholder="ชื่อผู้ใช้"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required>
            </div>

            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                </div>
                <input type="password"
                    name="password"
                    class="form-control"
                    placeholder="รหัสผ่าน"
                    required>
                <div class="input-group-append">
                    <span class="input-group-text" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" name="signin" class="btn btn-primary btn-block btn-signin">
                        <i class="fas fa-sign-in-alt mr-2"></i> เข้าสู่ระบบ
                    </button>
                </div>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-white">
                <small>หากมีปัญหาในการเข้าสู่ระบบ กรุณาติดต่อผู้ดูแลระบบ</small>
            </p>
        </div>
    </div>

    <!-- Toggle Password Script -->
    <script>
        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>

    <?php
    ob_end_flush();
    require('sys_footer.inc.php');
    ?>
</body>

</html>