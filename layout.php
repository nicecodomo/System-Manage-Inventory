<?php
// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid'])) {
    header("Location: signin.php");
    exit();
}
include_once 'sys_footer.inc.php';
include_once("class/Database.php");
include_once("class/UserManagement.php");
include_once("class/InventoryManagement.php");
include_once("class/RequestManagement.php");

$database = new Database();
$db = $database->getConnection();
$userManager = new UserManagement($db);
$inventoryManager = new InventoryManagement($db);
$requestManager = new RequestManagement($db);

$userData = $userManager->getUserById($_SESSION['userid']);
$userRole = $userData['user_role'];

// ดึงจำนวนคำขอที่รออนุมัติ
$pendingLoanCount = count($inventoryManager->getPendingLoans());
$pendingMaterialCount = count($requestManager->getPendingRequests());
?>

<head>
    <title>ระบบจัดการวัสดุครุภัณฑ์</title>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-black navbar-dark">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" id="toggleDarkMode">
                        <i id="modeIcon" class="fas fa-moon"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i> <?php echo $userData['user_name']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-danger" href="logout.php" onclick="return confirm('ยืนยันการออกจากระบบ?');">
                        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="index.php" class="brand-link text-center">
                <span class="brand-text font-weight-light">ระบบจัดการวัสดุครุภัณฑ์</span>
            </a>

            <div class="sidebar">
                <!-- Admin Sidebar Menu -->
                <?php if ($userRole === 'admin'): ?>
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                            <li class="nav-item">
                                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>หน้าหลัก</p>
                                </a>
                            </li>

                            <li class="nav-header">การจัดการระบบ</li>

                            <li class="nav-item">
                                <a href="manage_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>จัดการผู้ใช้งาน</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="manage_departments.php" class="nav-link">
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>จัดการแผนก</p>
                                </a>
                            </li>

                            <li class="nav-header">รายงาน</li>

                            <li class="nav-item">
                                <a href="report_materials.php" class="nav-link">
                                    <i class="nav-icon fas fa-boxes"></i>
                                    <p>รายงานวัสดุ</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="report_equipment.php" class="nav-link">
                                    <i class="nav-icon fas fa-tools"></i>
                                    <p>รายงานครุภัณฑ์</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="report_loans.php" class="nav-link">
                                    <i class="nav-icon fas fa-clipboard-list"></i>
                                    <p>รายงานการยืม-คืน</p>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Staff Sidebar Menu -->
                <?php if ($userRole === 'staff'): ?>
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column">
                            <li class="nav-item">
                                <a href="index_staff.php" class="nav-link">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>หน้าหลัก</p>
                                </a>
                            </li>

                            <li class="nav-header">การจัดการวัสดุ-ครุภัณฑ์</li>

                            <li class="nav-item">
                                <a href="manage_materials.php" class="nav-link">
                                    <i class="nav-icon fas fa-boxes"></i>
                                    <p>จัดการวัสดุ</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="manage_equipment.php" class="nav-link">
                                    <i class="nav-icon fas fa-tools"></i>
                                    <p>จัดการครุภัณฑ์</p>
                                </a>
                            </li>

                            <li class="nav-header">รายการเบิก-ยืม</li>

                            <li class="nav-item">
                                <a href="approve_requests.php" class="nav-link">
                                    <i class="nav-icon fas fa-eye"></i>
                                    <p>รายการเบิกวัสดุ <?php echo $pendingMaterialCount > 0 ? "($pendingMaterialCount)" : '(0)'; ?></p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="manage_loans.php" class="nav-link">
                                    <i class="nav-icon fas fa-eye"></i>
                                    <p>รายการยืมครุภัณฑ์ <?php echo $pendingLoanCount > 0 ? "($pendingLoanCount)" : '(0)'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- User Sidebar Menu -->
                <?php if ($userRole === 'user'): ?>
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column">
                            <li class="nav-item">
                                <a href="index.php" class="nav-link">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>หน้าหลัก</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="request_material.php" class="nav-link">
                                    <i class="nav-icon fas fa-hand-holding"></i>
                                    <p>เบิกวัสดุ</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="borrow_equipment.php" class="nav-link">
                                    <i class="nav-icon fas fa-hand-holding-medical"></i>
                                    <p>ยืมครุภัณฑ์</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="my_requests.php" class="nav-link">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>ประวัติการทำรายการเบิก</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="my_loans.php" class="nav-link">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>ประวัติการทำรายการยืม</p>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Content -->
        <div class="content-wrapper">
            <main>
                <?php echo $content; ?>
            </main>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                Version 1.0
            </div>
            <strong>Copyright &copy; 2024 ระบบจัดการวัสดุครุภัณฑ์</strong>
        </footer>
    </div>

    <!-- Dark Mode Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleDarkMode');
            const body = document.body;
            const modeIcon = document.getElementById('modeIcon');
            const mode = localStorage.getItem('theme-mode');

            if (mode === 'dark') {
                body.classList.add('dark-mode');
                modeIcon.classList.replace('fa-moon', 'fa-sun');
            }

            toggleButton.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');

                modeIcon.classList.replace(
                    isDark ? 'fa-moon' : 'fa-sun',
                    isDark ? 'fa-sun' : 'fa-moon'
                );

                localStorage.setItem('theme-mode', isDark ? 'dark' : 'light');
            });
        });
    </script>
</body>