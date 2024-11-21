<?php

class InventoryManagement
{
    private $conn;
    private $materials_table = "tb_material";
    private $equipment_table = "tb_equipment";
    private $loan_table = "tb_loan";
    private $material_request_table = "tb_material_request";
    private $approval_table = "tb_approval";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // จัดการวัสดุ
    public function addMaterial($name, $quantity, $unit, $description)
    {
        $query = "INSERT INTO {$this->materials_table} 
                 (mat_name, mat_quantity, mat_unit, mat_description) 
                 VALUES (:name, :quantity, :unit, :description)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":unit", $unit);
        $stmt->bindParam(":description", $description);

        return $stmt->execute();
    }

    public function updateMaterialStock($materialId, $newQuantity)
    {
        $query = "UPDATE {$this->materials_table} 
                 SET mat_quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
                 WHERE mat_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quantity", $newQuantity);
        $stmt->bindParam(":id", $materialId);

        return $stmt->execute();
    }

    // จัดการครุภัณฑ์
    public function addEquipment($name, $type, $condition, $location)
    {
        $query = "INSERT INTO {$this->equipment_table} 
                 (equ_name, equ_type, equ_condition, equ_location, equ_status, status) 
                 VALUES (:name, :type, :condition, :location, 'ว่าง', 'active')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":condition", $condition);
        $stmt->bindParam(":location", $location);

        return $stmt->execute();
    }

    public function updateEquipmentStatus($equipmentId, $newStatus)
    {
        $query = "UPDATE {$this->equipment_table} 
                 SET equ_status = :status, updated_at = CURRENT_TIMESTAMP 
                 WHERE equ_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $newStatus);
        $stmt->bindParam(":id", $equipmentId);

        return $stmt->execute();
    }

    //ฟังก์ชันอัปเดตสถานะ
    public function updateStatus($equId, $status)
    {
        $query = "UPDATE {$this->equipment_table} SET status = :status WHERE equ_id = :equId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":equId", $equId);
        return $stmt->execute();
    }

    // จัดการการยืมครุภัณฑ์
    public function requestEquipment($userId, $equipmentId, $returnDate)
    {
        // ตรวจสอบสถานะของครุภัณฑ์ก่อนทำการยืม
        $query = "SELECT equ_status FROM {$this->equipment_table} WHERE equ_id = :equipmentId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":equipmentId", $equipmentId);
        $stmt->execute();
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าสถานะเป็น "ว่าง" ให้ทำการยืม
        if ($equipment && $equipment['equ_status'] === 'ว่าง') {
            $query = "INSERT INTO {$this->loan_table} 
                     (loan_date, loan_return_date, loan_status, user_id, equ_id) 
                     VALUES (CURRENT_TIMESTAMP, :returnDate, 'รออนุมัติ', :userId, :equipmentId)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":returnDate", $returnDate);
            $stmt->bindParam(":userId", $userId);
            $stmt->bindParam(":equipmentId", $equipmentId);

            if ($stmt->execute()) {
                // อัพเดทสถานะครุภัณฑ์เป็น "ถูกยืม"
                return $this->updateEquipmentStatus($equipmentId, 'ถูกยืม');
            }
        }
        return false; // ถ้าสถานะไม่ใช่ "ว่าง" หรือเกิดข้อผิดพลาด
    }

    // // จัดการการอนุมัติ
    // public function approveRequest($approverId, $requestId, $type = 'material')
    // {
    //     if ($type === 'material') {
    //         $query = "INSERT INTO {$this->approval_table} 
    //                  (app_date, approver_id, req_id) 
    //                  VALUES (CURRENT_TIMESTAMP, :approverId, :requestId)";
    //     } else {
    //         $query = "INSERT INTO {$this->approval_table} 
    //                  (app_date, approver_id, loan_id) 
    //                  VALUES (CURRENT_TIMESTAMP, :approverId, :requestId)";
    //     }

    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bindParam(":approverId", $approverId);
    //     $stmt->bindParam(":requestId", $requestId);

    //     return $stmt->execute();
    // }

    // จัดการการคืนครุภัณฑ์
    public function returnEquipment($loanId, $condition)
    {
        // อัปเดตสถานะการยืมเป็น "คืนแล้ว"
        $query = "UPDATE {$this->loan_table} 
                  SET loan_status = 'คืนแล้ว' 
                  WHERE loan_id = :loanId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":loanId", $loanId);

        if ($stmt->execute()) {
            // ดึง equipment_id จาก loan record
            $query = "SELECT equ_id FROM {$this->loan_table} WHERE loan_id = :loanId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":loanId", $loanId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // อัปเดตสถานะครุภัณฑ์เป็น "ว่าง"
                $this->updateEquipmentStatus($result['equ_id'], 'ว่าง');

                // อัปเดตสภาพครุภัณฑ์ตามที่เลือก
                return $this->updateEquipmentCondition($result['equ_id'], $condition);
            }
        }
        return false;
    }

    // ฟังก์ชันสำหรับอัปเดตสภาพครุภัณฑ์
    public function updateEquipmentCondition($equipmentId, $condition)
    {
        $query = "UPDATE {$this->equipment_table} 
                  SET equ_condition = :condition, updated_at = CURRENT_TIMESTAMP 
                  WHERE equ_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":condition", $condition);
        $stmt->bindParam(":id", $equipmentId);

        return $stmt->execute();
    }

    // ดึงข้อมูลการยืมที่ยังไม่ได้คืน
    public function getActiveLoanRequests()
    {
        $query = "SELECT l.*, e.equ_name, u.user_name 
                 FROM {$this->loan_table} l 
                 JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id 
                 JOIN tb_user u ON l.user_id = u.user_id 
                 WHERE l.loan_status = 'รออนุมัติ'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงรายงานสถานะวัสดุคงเหลือ
    public function getMaterialInventoryReport()
    {
        $query = "SELECT m.*, 
                        COALESCE(SUM(r.req_quantity), 0) as total_requested
                 FROM {$this->materials_table} m
                 LEFT JOIN {$this->material_request_table} r 
                    ON m.mat_id = r.mat_id 
                    AND r.req_status = 'อนุมัติ'
                 GROUP BY m.mat_id
                 ORDER BY m.mat_quantity ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงรายงานสถานะครุภัณฑ์
    public function getEquipmentStatusReport()
    {
        $query = "SELECT e.*,
                        CASE 
                            WHEN l.loan_id IS NOT NULL AND l.loan_status = 'รออนุมัติ' 
                            THEN 'รออนุมัติ'
                            ELSE e.equ_status
                        END as current_status,
                        u.user_name as borrowed_by,
                        l.loan_return_date
                 FROM {$this->equipment_table} e
                 LEFT JOIN {$this->loan_table} l 
                    ON e.equ_id = l.equ_id 
                    AND l.loan_status = 'รออนุมัติ'
                 LEFT JOIN tb_user u 
                    ON l.user_id = u.user_id
                 ORDER BY e.equ_id ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงรายการวัสดุที่ใกล้หมด
    public function getLowStockMaterials($threshold = 10)
    {
        $query = "SELECT m.*
                 FROM {$this->materials_table} m
                 WHERE m.mat_quantity <= :threshold
                 ORDER BY m.mat_quantity ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":threshold", $threshold);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงรายการยืมที่เกินกำหนด
    public function getOverdueLoans()
    {
        $query = "SELECT l.*, e.equ_name, u.user_name,
                        DATEDIFF(CURRENT_DATE, l.loan_return_date) as days_overdue
                 FROM {$this->loan_table} l
                 JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id
                 JOIN tb_user u ON l.user_id = u.user_id
                 WHERE l.loan_status = 'รออนุมัติ'
                 AND l.loan_return_date < CURRENT_DATE
                 ORDER BY l.loan_return_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงสถิติการยืม-คืนรายเดือน
    public function getMonthlyLoanStats()
    {
        $query = "SELECT 
                    DATE_FORMAT(loan_date, '%Y-%m') as month,
                    COUNT(*) as total_loans,
                    SUM(CASE WHEN loan_status = 'คืนแล้ว' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN loan_status = 'รออนุมัติ' THEN 1 ELSE 0 END) as active
                 FROM {$this->loan_table}
                 GROUP BY DATE_FORMAT(loan_date, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 12";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงสถิติการเบิกวัสดุรายเดือน
    public function getMonthlyMaterialRequestStats()
    {
        $query = "SELECT 
                    DATE_FORMAT(req_date, '%Y-%m') as month,
                    COUNT(*) as total_requests,
                    SUM(req_quantity) as total_quantity
                 FROM {$this->material_request_table}
                 WHERE req_status = 'อนุมัติ'
                 GROUP BY DATE_FORMAT(req_date, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 12";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงคำขอล่าสุด
    public function getRecentRequests($limit = 10)
    {
        $query = "SELECT 
                    CASE 
                        WHEN mr.req_id IS NOT NULL THEN mr.req_date
                        ELSE l.loan_date
                    END as request_date,
                    u.user_name,
                    CASE 
                        WHEN mr.req_id IS NOT NULL THEN m.mat_name
                        ELSE e.equ_name
                    END as item_name,
                    CASE 
                        WHEN mr.req_id IS NOT NULL THEN 'เบิกวัสดุ'
                        ELSE 'ยืมครุภัณฑ์'
                    END as request_type,
                    CASE 
                        WHEN mr.req_id IS NOT NULL THEN mr.req_status
                        ELSE l.loan_status
                    END as status
                 FROM (
                    SELECT req_id, req_date, user_id, mat_id, req_status, 'material' as type
                    FROM {$this->material_request_table}
                    UNION ALL
                    SELECT loan_id, loan_date, user_id, equ_id, loan_status, 'equipment' as type
                    FROM {$this->loan_table}
                 ) requests
                 LEFT JOIN {$this->material_request_table} mr ON requests.req_id = mr.req_id AND requests.type = 'material'
                 LEFT JOIN {$this->loan_table} l ON requests.req_id = l.loan_id AND requests.type = 'equipment'
                 LEFT JOIN tb_user u ON requests.user_id = u.user_id
                 LEFT JOIN {$this->materials_table} m ON mr.mat_id = m.mat_id
                 LEFT JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id
                 ORDER BY request_date DESC
                 LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงข้อมูลวัสดุทั้งหมด (เพิ่มพารามิเตอร์เพื่อกรองสถานะ)
    public function getAllMaterials($includeDeleted = true)
    {
        $query = "SELECT * FROM {$this->materials_table}";

        // ถ้าไม่รวมสถานะ deleted
        if (!$includeDeleted) {
            $query .= " WHERE status != 'deleted'";
        } else {
            // ถ้ารวมเฉพาะสถานะ deleted
            $query .= " WHERE status = 'deleted'";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // อัพเดทข้อมูลวัสดุ
    public function updateMaterial($matId, $name, $quantity, $unit, $description)
    {
        $query = "UPDATE {$this->materials_table} 
                 SET mat_name = :name,
                     mat_quantity = :quantity,
                     mat_unit = :unit,
                     mat_description = :description,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE mat_id = :matId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":unit", $unit);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":matId", $matId);

        return $stmt->execute();
    }

    // ลบวัสดุ
    public function deleteMaterial($matId)
    {
        $query = "UPDATE {$this->materials_table} SET status = 'deleted' WHERE mat_id = :matId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":matId", $matId);
        return $stmt->execute();
    }

    public function addMaterialStock($matId, $addQuantity)
    {
        $query = "UPDATE {$this->materials_table} 
                 SET mat_quantity = mat_quantity + :add_quantity,
                     updated_at = CURRENT_TIMESTAMP 
                 WHERE mat_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":add_quantity", $addQuantity);
        $stmt->bindParam(":id", $matId);

        return $stmt->execute();
    }

    // ดึงข้อมูลครุภัณฑ์ทั้งหมด (เพิ่มพารามิเตอร์เพื่อกรองสถานะ)
    public function getAllEquipment($includeDeleted = true)
    {
        $query = "SELECT * FROM {$this->equipment_table}";

        // ถ้าไม่รวมสถานะ deleted
        if (!$includeDeleted) {
            $query .= " WHERE status != 'deleted'";
        } else {
            // ถ้ารวมเฉพาะสถานะ deleted
            $query .= " WHERE status = 'deleted'";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // อัพเดทข้อมูลครุภัณฑ์
    public function updateEquipment($equId, $name, $type, $condition, $location)
    {
        $query = "UPDATE {$this->equipment_table} 
                 SET equ_name = :name,
                     equ_type = :type,
                     equ_condition = :condition,
                     equ_location = :location,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE equ_id = :equId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":condition", $condition);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":equId", $equId);

        return $stmt->execute();
    }

    // ลบครุภัณฑ์ (เปลี่ยนสถานะเป็น deleted)
    public function deleteEquipment($equId)
    {
        $query = "UPDATE {$this->equipment_table} SET status = 'deleted' WHERE equ_id = :equId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":equId", $equId);
        return $stmt->execute();
    }

    // ปิดการใช้งาน
    public function deactivateEquipment($equId)
    {
        $query = "UPDATE {$this->equipment_table} SET status = 'inactive' WHERE equ_id = :equId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":equId", $equId);
        return $stmt->execute();
    }

    // ฟังก์ชันลดจำนวนวัสดุ
    public function reduceMaterialQuantity($materialId, $quantity)
    {
        $query = "UPDATE tb_material SET mat_quantity = mat_quantity - :quantity WHERE mat_id = :material_id AND mat_quantity >= :quantity";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':material_id', $materialId);
        return $stmt->execute();
    }

    public function getPendingLoans()
    {
        $query = "SELECT l.*, u.user_name, e.equ_name 
                  FROM {$this->loan_table} l 
                  JOIN tb_user u ON l.user_id = u.user_id 
                  JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id 
                  WHERE l.loan_status = 'รออนุมัติ'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApprovedLoans()
    {
        $query = "SELECT l.*, u.user_name, e.equ_name 
                  FROM {$this->loan_table} l 
                  JOIN tb_user u ON l.user_id = u.user_id 
                  JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id 
                  WHERE l.loan_status = 'อนุมัติแล้ว'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReturnedLoans()
    {
        $query = "SELECT l.*, u.user_name, e.equ_name 
                  FROM {$this->loan_table} l 
                  JOIN tb_user u ON l.user_id = u.user_id 
                  JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id 
                  WHERE l.loan_status = 'คืนแล้ว'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRejectedLoans()
    {
        $query = "SELECT l.*, u.user_name, e.equ_name 
                  FROM {$this->loan_table} l 
                  JOIN tb_user u ON l.user_id = u.user_id 
                  JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id 
                  WHERE l.loan_status = 'ไม่อนุมัติ'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // อัปเดตสถานะการยืม
    public function updateLoanStatus($loanId, $status)
    {
        $query = "UPDATE {$this->loan_table} 
                  SET loan_status = :status 
                  WHERE loan_id = :loanId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":loanId", $loanId);

        return $stmt->execute();
    }

    // ฟังก์ชันสำหรับอนุมัติการยืม
    public function approveLoan($approverId, $loanId)
    {
        // อัปเดตสถานะการยืมเป็น "อนุมัติแล้ว"
        $this->updateLoanStatus($loanId, 'อนุมัติแล้ว');

        // บันทึกการอนุมัติ
        return $this->logApproval($approverId, $loanId);
    }

    // ฟังก์ชันบันทึกการอนุมัติ
    private function logApproval($approverId, $loanId)
    {
        $query = "INSERT INTO {$this->approval_table} (app_date, approver_id, loan_id) 
                  VALUES (CURRENT_TIMESTAMP, :approverId, :loanId)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":approverId", $approverId);
        $stmt->bindParam(":loanId", $loanId);
        return $stmt->execute();
    }

    // ฟังก์ชันสำหรับไม่อนุมติการยืม
    public function rejectLoan($loanId)
    {
        // อัปเดตสถานะการยืมเป็น "ไม่อนุมัติ"
        $this->updateLoanStatus($loanId, 'ไม่อนุมัติ');

        // ดึงข้อมูลครุภัณฑ์ที่เกี่ยวข้อง
        $query = "SELECT equ_id FROM {$this->loan_table} WHERE loan_id = :loanId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":loanId", $loanId);
        $stmt->execute();
        $loan = $stmt->fetch(PDO::FETCH_ASSOC);

        // เปลี่ยนสถานะครุภัณฑ์เป็น "ว่าง"
        if ($loan) {
            $equId = $loan['equ_id'];
            $query = "UPDATE {$this->equipment_table} SET equ_status = 'ว่าง' WHERE equ_id = :equId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":equId", $equId);
            $stmt->execute();
        }

        return true;
    }

    // ฟังก์ชันสำหรับคืนครุภัณฑ์
    public function returnLoan($loanId)
    {
        $this->updateLoanStatus($loanId, 'คืนแล้ว');

        $query = "SELECT equ_id FROM {$this->loan_table} WHERE loan_id = :loanId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":loanId", $loanId);
        $stmt->execute();
        $loan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($loan) {
            $equId = $loan['equ_id'];
            $query = "UPDATE {$this->equipment_table} SET equ_status = 'ว่าง' WHERE equ_id = :equId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":equId", $equId);
            $stmt->execute();
        }

        return true;
    }

    // ฟังก์ชันสำหรับกู้คืนครุภัณฑ์
    public function restoreEquipment($equId)
    {
        $query = "UPDATE {$this->equipment_table} SET status = 'active' WHERE equ_id = :equId AND status = 'deleted'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":equId", $equId);
        return $stmt->execute();
    }

    // ฟังก์ชันสำหรับกู้คืนวัสดุ
    public function restoreMaterial($matId)
    {
        $query = "UPDATE {$this->materials_table} SET status = 'active' WHERE mat_id = :matId AND status = 'deleted'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":matId", $matId);
        return $stmt->execute();
    }

    // ฟังก์ชันสำหรับปิดการใช้งานวัสดุ
    public function deactivateMaterial($matId)
    {
        $query = "UPDATE {$this->materials_table} SET status = 'inactive' WHERE mat_id = :matId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":matId", $matId);
        return $stmt->execute();
    }

    // ฟังก์ชันสำหรับอัปเดตสถานะวัสดุ
    public function updateMaterialStatus($matId, $status)
    {
        $query = "UPDATE {$this->materials_table} SET status = :status WHERE mat_id = :matId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":matId", $matId);
        return $stmt->execute();
    }
}
