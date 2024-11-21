<?php
require("sys_header.inc.php");
ob_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['userid']) || $_SESSION['user_role'] !== 'user') {
    header("Location: signin.php");
    exit();
}

include_once("class/Database.php");
include_once("class/InventoryManagement.php");

$database = new Database();
$db = $database->getConnection();
$inventoryManager = new InventoryManagement($db);

// ดึงข้อมูลครุภัณฑ์ที่สามารถยืมได้
$equipment = $inventoryManager->getAllEquipment(false); // หรือใช้ฟังก์ชันที่กรองครุภัณฑ์ที่สามารถยืมได้

?>

<div class="container">
    <h1 class="text-center">ยืมครุภัณฑ์</h1>

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
        <div class="col-md-6">
            <h3>เลือกครุภัณฑ์</h3>
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>ประเภท</th>
                                <th>สถานะ</th>
                                <th>สถานที่</th>
                                <th>เลือก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['equ_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_type']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_status']); ?></td>
                                    <td><?php echo htmlspecialchars($item['equ_location']); ?></td>
                                    <td>
                                        <button class="btn btn-primary add-to-cart" data-id="<?php echo $item['equ_id']; ?>" data-name="<?php echo htmlspecialchars($item['equ_name']); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <h3>ครุภัณฑ์ที่เลือก</h3>
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped" id="selectedEquipmentTable">
                        <thead>
                            <tr>
                                <th>ชื่อครุภัณฑ์</th>
                                <th>วันที่คืน</th>
                                <th>ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="selectedEquipmentBody">
                            <!-- รายการครุภัณฑ์ที่เลือกจะถูกเพิ่มที่นี่ -->
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="submitRequest">ยืมครุภัณฑ์ที่เลือก</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const selectedEquipment = new Set(); // ใช้ Set เพื่อเก็บครุภัณฑ์ที่เลือก

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const equipmentId = this.getAttribute('data-id');
            const equipmentName = this.getAttribute('data-name');

            // ตรวจสอบว่ามีครุภัณฑ์นี้อยู่ใน Set หรือไม่
            if (selectedEquipment.has(equipmentId)) {
                alert('ครุภัณฑ์นี้ถูกเลือกไปแล้ว');
                return; // ถ้ามีแล้วไม่ทำอะไร
            }

            // เพิ่มครุภัณฑ์ใน Set
            selectedEquipment.add(equipmentId);

            // สร้างแถวใหม่ในตารางครุภัณฑ์ที่เลือก
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td hidden>${equipmentId}</td>
                <td>${equipmentName}</td>
                <td>
                    <input type="date" name="return_date[${equipmentId}]" required class="form-control">
                </td>
                <td>
                    <button type="button" class="btn btn-danger remove-from-cart">ลบ</button>
                </td>
            `;
            document.getElementById('selectedEquipmentBody').appendChild(newRow);

            // เพิ่มฟังก์ชันลบ
            newRow.querySelector('.remove-from-cart').addEventListener('click', function() {
                selectedEquipment.delete(equipmentId); // ลบครุภัณฑ์ออกจาก Set
                newRow.remove();
                // เปิดปุ่มเพิ่มครุภัณฑ์อีกครั้ง
                document.querySelector(`.add-to-cart[data-id="${equipmentId}"]`).disabled = false;
            });

            // ปิดปุ่มเพิ่มครุภัณฑ์
            this.disabled = true; // ปิดปุ่มเพิ่มครุภัณฑ์
        });
    });

    document.getElementById('submitRequest').addEventListener('click', function() {
        const selectedRows = document.querySelectorAll('#selectedEquipmentBody tr');
        const equipmentToSubmit = [];

        selectedRows.forEach(row => {
            const equipmentId = row.cells[0].innerText; // ใช้ ID ของครุภัณฑ์
            const returnDate = row.querySelector('input[type="date"]').value;
            equipmentToSubmit.push({
                id: equipmentId,
                returnDate: returnDate
            });
        });

        if (equipmentToSubmit.length > 0) {
            // ส่งข้อมูลไปยังเซิร์ฟเวอร์
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_borrow_equipment.php';

            equipmentToSubmit.forEach(equipment => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'equipment_id[]'; // ส่ง ID ของครุภัณฑ์
                input.value = equipment.id;
                form.appendChild(input);

                const returnDateInput = document.createElement('input');
                returnDateInput.type = 'hidden';
                returnDateInput.name = 'return_date[]'; // ส่งวันที่คืน
                returnDateInput.value = equipment.returnDate;
                form.appendChild(returnDateInput);
            });

            document.body.appendChild(form);
            form.submit();
        } else {
            alert('กรุณาเลือกครุภัณฑ์อย่างน้อยหนึ่งรายการ');
        }
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>