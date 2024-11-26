<?php
    class Database
    {
        private $host;
        private $db;
        private $username;
        private $password;
        public $conn;

        public function __construct()
        {
            // Load configuration
            $config = require 'class/Config.php';
            $this->host = $config['DB_HOST'];
            $this->db = $config['DB_NAME'];
            $this->username = $config['DB_USERNAME'];
            $this->password = $config['DB_PASSWORD'];

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
