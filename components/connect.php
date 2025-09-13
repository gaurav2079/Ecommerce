<?php
// Check if the class is already defined
if (!class_exists('DatabaseConnection')) {
    class DatabaseConnection {
        private $host = 'localhost';
        private $dbname = 'ns';
        private $username = 'root';
        private $password = '';
        private $conn;
        
        public function __construct() {
            try {
                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname}", 
                    $this->username, 
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        
        public function getConnection() {
            return $this->conn;
        }
    }
}

// Create a single instance
if (!isset($conn)) {
    $db = new DatabaseConnection();
    $conn = $db->getConnection();
}
?>