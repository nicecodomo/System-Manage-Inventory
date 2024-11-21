<?php

class UserManagement
{
    private $conn;
    private $table_name = "tb_user";
    private $department_table = "tb_department";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // เพิ่มผู้ใช้ใหม่
    public function addUser($username, $password, $name, $role, $department, $contact)
    {
        // ตรวจสอบความยาวรหัสผ่าน
        if (strlen($password) < 5) {
            return false;
        }

        if ($this->isUsernameExists($username)) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $status = 'active';

        $query = "INSERT INTO {$this->table_name} 
                 (user_username, user_password, user_name, user_role, dep_id, user_contact, user_status) 
                 VALUES (:username, :password, :name, :role, :dep_id, :contact, :status)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":role", $role);
        $stmt->bindParam(":dep_id", $department);
        $stmt->bindParam(":contact", $contact);
        $stmt->bindParam(":status", $status);

        return $stmt->execute();
    }

    // ตรวจสอบการเข้าสู่ระบบ
    public function login($username, $password)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE user_username = :username LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // ตรวจสอบรหัสผ่าน
            if (password_verify($password, $row['user_password'])) {
                // ส่งข้อมูลผู้ใช้กลับไปตรวจสอบสถานะที่หน้า signin
                return $row;
            }
        }
        return false;
    }

    private function isUsernameExists($username)
    {
        $query = "SELECT user_id FROM {$this->table_name} WHERE user_username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // อัพเดทข้อมูลผู้ใช้
    public function updateUser($userId, $name, $department, $contact, $role, $password = null)
    {
        // ถ้ามีการส่งรหัสผ่านมา ตรวจสอบความยาว
        if (!empty($password) && strlen($password) < 5) {
            return false;
        }

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE {$this->table_name} 
                     SET user_name = :name,
                         user_password = :password,
                         dep_id = :dep_id,
                         user_contact = :contact,
                         user_role = :role
                     WHERE user_id = :userId";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password", $hashedPassword);
        } else {
            $query = "UPDATE {$this->table_name} 
                     SET user_name = :name,
                         dep_id = :dep_id,
                         user_contact = :contact,
                         user_role = :role
                     WHERE user_id = :userId";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":dep_id", $department);
        $stmt->bindParam(":contact", $contact);
        $stmt->bindParam(":role", $role);
        $stmt->bindParam(":userId", $userId);

        return $stmt->execute();
    }

    // เปลี่ยนรหัสผ่าน
    public function changePassword($userId, $newPassword)
    {
        // Hash password ใหม่ก่อนบันทึก
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $query = "UPDATE {$this->table_name} 
                 SET user_password = :password 
                 WHERE user_id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashedPassword); // ใช้ password ที่ hash แล้ว
        $stmt->bindParam(":userId", $userId);

        return $stmt->execute();
    }

    // ดึงข้อมูลผู้ใช้ทั้งหมด
    public function getAllUsers()
    {
        $query = "SELECT u.*, d.dep_name as department_name 
                 FROM {$this->table_name} u 
                 LEFT JOIN {$this->department_table} d ON u.dep_id = d.dep_id 
                 WHERE u.user_id != 1 
                 ORDER BY u.user_id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงข้อมูลแผนกทั้งหมด
    public function getAllDepartments()
    {
        $query = "SELECT * FROM {$this->department_table} ORDER BY dep_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ดึงข้อมูลผู้ใช้ตาม ID
    public function getUserById($userId)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE user_id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ดึงข้อมูลผู้ใช้ตามบทบาท
    public function getUsersByRole($role)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE user_role = :role";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $role);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ลบผู้ใช้
    public function deleteUser($userId)
    {
        // ป้องกันการลบ admin
        if ($userId == 1) {
            return false;
        }

        $query = "DELETE FROM {$this->table_name} WHERE user_id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":userId", $userId);
        return $stmt->execute();
    }

    // อัพเดทโปรไฟล์ผู้ใช้ (สำหรับผู้ใช้ทั่วไป)
    public function updateProfile($userId, $name, $contact)
    {
        $query = "UPDATE {$this->table_name} 
                 SET user_name = :name,
                     user_contact = :contact
                 WHERE user_id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":contact", $contact);
        $stmt->bindParam(":userId", $userId);

        return $stmt->execute();
    }



    // ดึงข้อมูลแผนกพร้อมจำนวนผู้ใช้
    public function getAllDepartmentsWithUserCount()
    {
        $query = "SELECT d.*, COUNT(u.user_id) as user_count 
                 FROM {$this->department_table} d 
                 LEFT JOIN {$this->table_name} u ON d.dep_id = u.dep_id 
                 GROUP BY d.dep_id 
                 ORDER BY d.dep_name";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // เพิ่มแผนกใหม่
    public function addDepartment($name, $contact)
    {
        $query = "INSERT INTO {$this->department_table} 
                 (dep_name, dep_contact) 
                 VALUES (:name, :contact)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":contact", $contact);

        return $stmt->execute();
    }

    // อัพเดทข้อมูลแผนก
    public function updateDepartment($depId, $name, $contact)
    {
        $query = "UPDATE {$this->department_table} 
                 SET dep_name = :name,
                     dep_contact = :contact
                 WHERE dep_id = :depId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":contact", $contact);
        $stmt->bindParam(":depId", $depId);

        return $stmt->execute();
    }

    // ลบแผนก (จะลบได้เมื่อไม่มีผู้ใช้ในแผนกนั้น)
    public function deleteDepartment($depId)
    {
        // เช็คว่ามีผู้ใช้ในแผนกหรือไม่
        $query = "SELECT COUNT(*) as user_count 
                 FROM {$this->table_name} 
                 WHERE dep_id = :depId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":depId", $depId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['user_count'] > 0) {
            return false;
        }

        // ถ้าไม่มีผู้ใช้ในแผนก ให้ลบได้
        $query = "DELETE FROM {$this->department_table} 
                 WHERE dep_id = :depId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":depId", $depId);

        return $stmt->execute();
    }

    // เปลี่ยนสถานะผู้ใช้
    public function updateUserStatus($userId, $status)
    {
        // ป้องกันการเปลี่ยนสถานะ admin หลัก
        if ($userId == 1) {
            return false;
        }

        $query = "UPDATE {$this->table_name} 
                 SET user_status = :status 
                 WHERE user_id = :userId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":userId", $userId);

        return $stmt->execute();
    }

    // ตรวจสอบรหัสผ่าน
    public function verifyPassword($userId, $password)
    {
        $query = "SELECT user_password FROM {$this->table_name} WHERE user_id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return password_verify($password, $row['user_password']);
        }
        return false;
    }
}
