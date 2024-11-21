<?php

class RequestManagement
{
    private $db;
    private $materials_table = "tb_material";
    private $material_request_table = "tb_material_request";
    private $approval_table = "tb_approval";

    public function __construct($database)
    {
        $this->db = $database;
    }

    // จัดการการเบิกวัสดุ
    public function requestMaterial($userId, $materialId, $quantity)
    {
        // ตรวจสอบว่ามีวัสดุที่มี mat_id นี้อยู่ใน tb_material
        $query = "SELECT * FROM {$this->materials_table} WHERE mat_id = :materialId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':materialId', $materialId);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return false; // วัสดุไม่พบ
        }

        $query = "INSERT INTO {$this->material_request_table} (user_id, mat_id, req_quantity, req_status, req_date) 
                  VALUES (:userId, :materialId, :quantity, 'รออนุมัติ', CURRENT_TIMESTAMP)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':materialId', $materialId);
        $stmt->bindParam(':quantity', $quantity);

        return $stmt->execute();
    }

    // ฟังก์ชันสำหรับอนุมัติคำขอ
    public function approveRequest($approverId, $requestId)
    {
        $query = "UPDATE {$this->material_request_table} SET req_status = 'อนุมัติ' WHERE req_id = :request_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':request_id', $requestId);
        $stmt->execute();

        // บันทึกการอนุมัติ
        $this->logApproval($approverId, $requestId);
    }

    // ฟังก์ชันสำหรับปฏิเสธคำขอ
    public function rejectRequest($requestId)
    {
        $query = "UPDATE {$this->material_request_table} SET req_status = 'ปฏิเสธ' WHERE req_id = :request_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':request_id', $requestId);
        return $stmt->execute();
    }

    // ฟังก์ชันบันทึกการอนุมัติ
    private function logApproval($approverId, $requestId)
    {
        $query = "INSERT INTO {$this->approval_table} (app_date, approver_id, req_id) 
                  VALUES (CURRENT_TIMESTAMP, :approverId, :requestId)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":approverId", $approverId);
        $stmt->bindParam(":requestId", $requestId);
        return $stmt->execute();
    }

    // ฟังก์ชันดึงข้อมูลคำขอการยืมครุภัณฑ์ของผู้ใช้งาน
    public function getLoanRequestsByUser($userId)
    {
        $query = "SELECT l.loan_id, e.equ_name AS item_name, l.loan_status, l.loan_date, l.loan_return_date 
                  FROM tb_loan l 
                  JOIN tb_equipment e ON l.equ_id = e.equ_id 
                  WHERE l.user_id = :userId
                  ORDER BY l.loan_date DESC"; // เรียงลำดับจากมากไปน้อย

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอล่าสุด
    public function getRecentRequests($limit)
    {
        $query = "SELECT r.req_id, u.user_name, m.mat_name AS item_name, r.req_date, r.req_status 
                  FROM {$this->material_request_table} r 
                  JOIN tb_user u ON r.user_id = u.user_id 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  ORDER BY r.req_date DESC 
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอที่รอการอนุมัติ
    public function getPendingRequests()
    {
        $query = "SELECT r.req_id, u.user_name, m.mat_name, r.req_status, 
        r.req_quantity, r.req_date, m.mat_description 
                  FROM {$this->material_request_table} r 
                  JOIN tb_user u ON r.user_id = u.user_id 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.req_status = 'รออนุมัติ'"; // เปลี่ยนตามสถานะที่ต้องการ

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอที่อนุมัติแล้ว
    public function getApprovedRequests()
    {
        $query = "SELECT r.req_id, u.user_name, m.mat_name, r.req_status, 
        r.req_quantity, r.req_date, m.mat_description
                  FROM {$this->material_request_table} r 
                  JOIN tb_user u ON r.user_id = u.user_id 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.req_status = 'อนุมัติ'"; // เปลี่ยนตามสถานะที่ต้องการ

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอที่ไม่อนุมัติ
    public function getRejectedRequests()
    {
        $query = "SELECT r.req_id, u.user_name, m.mat_name, r.req_status, 
        r.req_quantity, r.req_date, m.mat_description
                  FROM {$this->material_request_table} r 
                  JOIN tb_user u ON r.user_id = u.user_id 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.req_status = 'ปฏิเสธ'"; // เปลี่ยนตามสถานะที่ต้องการ

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRequestById($requestId)
    {
        $query = "SELECT r.req_id, r.req_quantity, r.mat_id, m.mat_name 
                  FROM {$this->material_request_table} r 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.req_id = :requestId";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':requestId', $requestId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอวัสดุทั้งหมดของผู้ใช้งาน
    public function getAllRequestsByUser($userId)
    {
        $query = "SELECT r.req_id, m.mat_name AS item_name, r.req_status, r.req_date, 'วัสดุ' AS type, r.req_quantity 
                  FROM {$this->material_request_table} r 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.user_id = :userId
                  ORDER BY r.req_date DESC"; // เรียงลำดับจากมากไปน้อย

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอที่รออนุมัติวัสดุของผู้ใช้งาน
    public function getPendingRequestsByUser($userId)
    {
        $query = "SELECT r.req_id, m.mat_name AS item_name, r.req_status, r.req_date, 'วัสดุ' AS type, r.req_quantity 
                  FROM {$this->material_request_table} r 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.user_id = :userId AND r.req_status = 'รออนุมัติ'";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอที่อนุมัติแล้วของผู้ใช้งาน
    public function getApprovedRequestsByUser($userId)
    {
        $query = "SELECT r.req_id, m.mat_name AS item_name, r.req_status, r.req_date, 'วัสดุ' AS type, r.req_quantity 
                  FROM {$this->material_request_table} r 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.user_id = :userId AND r.req_status = 'อนุมัติ'";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ฟังก์ชันดึงข้อมูลคำขอที่ไม่อนุมัติของผู้ใช้งาน
    public function getRejectedRequestsByUser($userId)
    {
        $query = "SELECT r.req_id, m.mat_name AS item_name, r.req_status, r.req_date, 'วัสดุ' AS type, r.req_quantity 
                  FROM {$this->material_request_table} r 
                  JOIN {$this->materials_table} m ON r.mat_id = m.mat_id 
                  WHERE r.user_id = :userId AND r.req_status = 'ปฏิเสธ'";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
