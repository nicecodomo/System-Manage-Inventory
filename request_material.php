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

// ดึงข้อมูลวัสดุที่สามารถเบิกได้
$materials = $inventoryManager->getAllMaterials(false); // หรือใช้ฟังก์ชันที่กรองวัสดุที่สามารถเบิกได้

// ตรวจสอบการส่งข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['materials'])) {
    $selectedMaterials = $_POST['materials'];
    $quantities = $_POST['quantity'];
    $_SESSION['cart'] = array();

    foreach ($selectedMaterials as $materialId) {
        if (isset($quantities[$materialId])) {
            $_SESSION['cart'][$materialId] = $quantities[$materialId];
        }
    }
}
?>

<div class="container">
    <h1 class="text-center">เบิกวัสดุ</h1>

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
            <h3>เลือกวัสดุ</h3>
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ชื่อวัสดุ</th>
                                <th>จำนวน</th>
                                <th>หน่วย</th>
                                <th>รายละเอียด</th>
                                <th>เลือก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['mat_name']); ?></td>
                                    <td><?php echo htmlspecialchars($material['mat_quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($material['mat_unit']); ?></td>
                                    <td><?php echo htmlspecialchars($material['mat_description']); ?></td>
                                    <td>
                                        <button class="btn btn-primary add-to-cart" data-id="<?php echo $material['mat_id']; ?>" data-name="<?php echo htmlspecialchars($material['mat_name']); ?>" data-unit="<?php echo htmlspecialchars($material['mat_unit']); ?>" data-quantity="<?php echo htmlspecialchars($material['mat_quantity']); ?>">
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
            <h3>วัสดุที่ต้องการเบิก</h3>
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped" id="selectedMaterialsTable">
                        <thead>
                            <tr>
                                <th>ชื่อวัสดุ</th>
                                <th>จำนวนที่ต้องการเบิก</th>
                                <th>หน่วย</th>
                                <th>ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="selectedMaterialsBody">
                            <!-- รายการวัสดุที่เลือกจะถูกเพิ่มที่นี่ -->
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="submitRequest">เบิกวัสดุที่เลือก</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const selectedMaterials = new Set(); // ใช้ Set เพื่อเก็บวัสดุที่เลือก

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const materialId = this.getAttribute('data-id');
            const materialName = this.getAttribute('data-name');
            const materialUnit = this.getAttribute('data-unit');
            const materialQuantity = this.getAttribute('data-quantity');

            // ตรวจสอบว่ามีวัสดุนี้อยู่ใน Set หรือไม่
            if (selectedMaterials.has(materialId)) {
                alert('วัสดุนี้ถูกเลือกไปแล้ว');
                return; // ถ้ามีแล้วไม่ทำอะไร
            }

            // เพิ่มวัสดุใน Set
            selectedMaterials.add(materialId);

            // สร้างแถวใหม่ในตารางวัสดุที่เลือก
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td hidden>${materialId}</td>
                <td>${materialName}</td>
                <td>
                    <input type="number" name="quantity[${materialId}]" min="1" max="${materialQuantity}" required class="form-control">
                </td>
                <td>${materialUnit}</td>
                <td>
                    <button type="button" class="btn btn-danger remove-from-cart">ลบ</button>
                </td>
            `;
            document.getElementById('selectedMaterialsBody').appendChild(newRow);

            // เพิ่มฟังก์ชันลบ
            newRow.querySelector('.remove-from-cart').addEventListener('click', function() {
                selectedMaterials.delete(materialId); // ลบวัสดุออกจาก Set
                newRow.remove();
                // เปิดปุ่มเพิ่มวัสดุอีกครั้ง
                document.querySelector(`.add-to-cart[data-id="${materialId}"]`).disabled = false;
            });

            // ปิดปุ่มเพิ่มวัสดุ
            this.disabled = true; // ปิดปุ่มเพิ่มวัสดุ
        });
    });

    document.getElementById('submitRequest').addEventListener('click', function() {
        const selectedRows = document.querySelectorAll('#selectedMaterialsBody tr');
        const materialsToSubmit = [];

        selectedRows.forEach(row => {
            const materialId = row.cells[0].innerText; // ใช้ ID ของวัสดุ
            const quantity = row.querySelector('input[type="number"]').value;
            materialsToSubmit.push({
                id: materialId,
                quantity: quantity
            });
        });

        if (materialsToSubmit.length > 0) {
            // ส่งข้อมูลไปยังเซิร์ฟเวอร์
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_request_material.php';

            materialsToSubmit.forEach(material => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'materials[]';
                input.value = material.id; // ส่ง ID ของวัสดุ
                form.appendChild(input);

                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'quantity[' + material.id + ']'; // ใช้ ID ของวัสดุเป็น key
                quantityInput.value = material.quantity;
                form.appendChild(quantityInput);
            });

            document.body.appendChild(form);
            form.submit();
        } else {
            alert('กรุณาเลือกวัสดุอย่างน้อยหนึ่งรายการ');
        }
    });
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>