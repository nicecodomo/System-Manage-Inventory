<?php
    class Database
    {
        private $host = "localhost";
        private $db = "system_manage_inventory";
        private $username = "root";
        private $password = "";
        public $conn;

        public function __construct()
        {
            // Set ว/ด/ป เวลา ให้เป็นของประเทศไทย
            date_default_timezone_set('Asia/Bangkok');
        }

        public function getConnection()
        {
            try {
                $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db};charset=utf8", $this->username, $this->password);
            } catch(PDOException $ex) {
                die("Connection failed: " . $ex->getMessage());
            }
            return $this->conn;
        }
    }
?>
