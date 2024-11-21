<?php

class ReportManagement
{
    private $conn;
    private $materials_table = "tb_material";
    private $equipment_table = "tb_equipment";
    private $loan_table = "tb_loan";
    private $material_request_table = "tb_material_request";
    private $approval_table = "tb_approval";
    private $department_table = "tb_department";
    private $user_table = "tb_user";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // รายงานสรุปการเบิกวัสดุ
    public function getMaterialRequestReport($startDate, $endDate)
    {
        $query = "SELECT m.mat_name, m.mat_unit, 
                        COUNT(r.req_id) as request_count,
                        SUM(r.req_quantity) as total_quantity,
                        r.req_status,
                        r.req_date,
                        u.user_name as requester_name,
                        d.dep_name as department_name
                 FROM {$this->material_request_table} r
                 JOIN {$this->materials_table} m ON r.mat_id = m.mat_id
                 JOIN tb_user u ON r.user_id = u.user_id
                 JOIN {$this->department_table} d ON u.dep_id = d.dep_id
                 WHERE r.req_date BETWEEN :startDate AND :endDate
                 GROUP BY m.mat_id, r.req_status, d.dep_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":startDate", $startDate);
        $stmt->bindParam(":endDate", $endDate);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // รายงานสรุปการยืมครุภัณฑ์
    public function getEquipmentLoanReport($startDate, $endDate)
    {
        $query = "SELECT e.equ_name,
                        e.equ_type,
                        COUNT(l.loan_id) as loan_count,
                        l.loan_status,
                        u.user_name as borrower_name,
                        d.dep_name as department_name
                 FROM {$this->loan_table} l
                 JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id
                 JOIN tb_user u ON l.user_id = u.user_id
                 JOIN {$this->department_table} d ON u.dep_id = d.dep_id
                 WHERE l.loan_date BETWEEN :startDate AND :endDate
                 GROUP BY e.equ_id, l.loan_status, d.dep_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":startDate", $startDate);
        $stmt->bindParam(":endDate", $endDate);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // รายงานวัสดุคงเหลือ
    public function getMaterialInventoryReport()
    {
        $query = "SELECT mat_name, 
                        mat_quantity, 
                        mat_unit,
                        mat_description,
                        created_at,
                        updated_at
                 FROM {$this->materials_table}
                 ORDER BY mat_quantity ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // รายงานครุภัณฑ์ตามสถานะ
    public function getEquipmentStatusReport()
    {
        $query = "SELECT e.equ_id, e.equ_name, e.equ_type, e.equ_location, e.equ_condition, 
                        e.equ_status, 
                        COUNT(*) as count,
                        -- GROUP_CONCAT(DISTINCT l.user_id) as borrowed_by,
                        GROUP_CONCAT(DISTINCT u.user_name) as borrowed_by,
                        MAX(l.loan_return_date) as loan_return_date
                 FROM {$this->equipment_table} e
                 LEFT JOIN {$this->loan_table} l ON e.equ_id = l.equ_id
                 LEFT JOIN {$this->user_table} u ON l.user_id = u.user_id
                 GROUP BY e.equ_id, e.equ_name, e.equ_type, e.equ_location, e.equ_condition, e.equ_status";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // รายงานการยืมครุภัณฑ์ที่เกินกำหนด
    public function getOverdueLoanReport()
    {
        $query = "SELECT l.loan_id,
                        l.loan_date as borrow_date,
                        l.loan_return_date as due_date,
                        e.equ_name,
                        e.equ_type,
                        e.equ_location,
                        u.user_name,
                        d.dep_name as department_name,
                        u.user_contact,
                        DATEDIFF(CURRENT_DATE, l.loan_return_date) as days_overdue
                 FROM {$this->loan_table} l
                 JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id
                 JOIN tb_user u ON l.user_id = u.user_id
                 JOIN {$this->department_table} d ON u.dep_id = d.dep_id
                 WHERE l.loan_status = 'อนุมัติแล้ว'
                 AND l.loan_return_date < CURRENT_DATE()
                 ORDER BY l.loan_return_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // รายงานสถิติการใช้งานครุภัณฑ์
    public function getEquipmentUsageStats($startDate, $endDate)
    {
        $query = "SELECT e.equ_name,
                        e.equ_type,
                        e.equ_condition,
                        e.equ_location,
                        COUNT(l.loan_id) as usage_count,
                        AVG(DATEDIFF(l.loan_return_date, l.loan_date)) as avg_loan_duration,
                        e.equ_status as current_status
                 FROM {$this->equipment_table} e
                 LEFT JOIN {$this->loan_table} l ON e.equ_id = l.equ_id
                 AND l.loan_date BETWEEN :startDate AND :endDate
                 GROUP BY e.equ_id
                 ORDER BY usage_count DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":startDate", $startDate);
        $stmt->bindParam(":endDate", $endDate);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // รายงานสรุปการอนุมัติ
    public function getApprovalSummaryReport($startDate, $endDate)
    {
        $query = "SELECT a.app_date,
                        CASE 
                            WHEN a.req_id IS NOT NULL THEN 'เบิกวัสดุ'
                            WHEN a.loan_id IS NOT NULL THEN 'ยืมครุภัณฑ์'
                        END as request_type,
                        u.user_name as approver_name,
                        u.user_role as approver_role,
                        d.dep_name as department_name,
                        COUNT(*) as approval_count
                 FROM {$this->approval_table} a
                 JOIN tb_user u ON a.approver_id = u.user_id
                 JOIN {$this->department_table} d ON u.dep_id = d.dep_id
                 WHERE a.app_date BETWEEN :startDate AND :endDate
                 GROUP BY DATE(a.app_date), request_type, a.approver_id
                 ORDER BY a.app_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":startDate", $startDate);
        $stmt->bindParam(":endDate", $endDate);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงสถิติการเบิกวัสดุรายแผนก
    public function getDepartmentMaterialStats()
    {
        $query = "SELECT d.dep_name as department_name,
                        COUNT(r.req_id) as total_requests,
                        SUM(r.req_quantity) as total_quantity
                 FROM {$this->department_table} d
                 LEFT JOIN tb_user u ON d.dep_id = u.dep_id
                 LEFT JOIN {$this->material_request_table} r ON u.user_id = r.user_id
                 WHERE r.req_status = 'อนุมัติ'
                 GROUP BY d.dep_id
                 ORDER BY total_requests DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงสถิติการเบิกวัสดุรายเดือน
    public function getMonthlyMaterialRequestStats()
    {
        $query = "SELECT DATE_FORMAT(req_date, '%Y-%m') as month,
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

    // ดึงสถิติการยืมครุภัณฑ์รายเดือน
    public function getMonthlyLoanStats()
    {
        $query = "SELECT 
                    DATE_FORMAT(loan_date, '%Y-%m') as month,
                    COUNT(*) as total_loans,
                    SUM(CASE WHEN loan_status = 'คืนแล้ว' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN loan_status = 'ถูกยืม' THEN 1 ELSE 0 END) as active,
                    COUNT(DISTINCT equ_id) as unique_equipment,
                    COUNT(DISTINCT user_id) as unique_users
                 FROM {$this->loan_table}
                 GROUP BY DATE_FORMAT(loan_date, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 12";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงประวัติการยืม-คืนครุภัณฑ์
    public function getLoanHistory($startDate, $endDate, $departmentId = 'all', $status = 'all')
    {
        $query = "SELECT l.*,
                        e.equ_name,
                        u.user_name,
                        d.dep_name as department_name,
                        DATEDIFF(CURRENT_DATE, l.loan_return_date) as days_overdue
                 FROM {$this->loan_table} l
                 JOIN {$this->equipment_table} e ON l.equ_id = e.equ_id
                 JOIN tb_user u ON l.user_id = u.user_id
                 JOIN {$this->department_table} d ON u.dep_id = d.dep_id
                 WHERE l.loan_date BETWEEN :startDate AND :endDate";

        if ($departmentId !== 'all') {
            $query .= " AND u.dep_id = :departmentId";
        }

        if ($status !== 'all') {
            if ($status === 'เกินกำหนด') {
                $query .= " AND l.loan_status = 'อนุมัติแล้ว' AND DATEDIFF(CURRENT_DATE, l.loan_return_date) > 0";
            } else {
                $query .= " AND l.loan_status = :status";
            }
        }

        $query .= " ORDER BY l.loan_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":startDate", $startDate);
        $stmt->bindParam(":endDate", $endDate);

        if ($departmentId !== 'all') {
            $stmt->bindParam(":departmentId", $departmentId);
        }

        if ($status !== 'all' && $status !== 'เกินกำหนด') {
            $stmt->bindParam(":status", $status);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงสถิติการยืมตามแผนก
    public function getDepartmentLoanStats()
    {
        $query = "SELECT d.dep_name as department_name,
                        COUNT(l.loan_id) as total_loans,
                        COUNT(DISTINCT u.user_id) as unique_users
                 FROM {$this->department_table} d
                 LEFT JOIN tb_user u ON d.dep_id = u.dep_id
                 LEFT JOIN {$this->loan_table} l ON u.user_id = l.user_id
                 GROUP BY d.dep_id
                 ORDER BY total_loans DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
