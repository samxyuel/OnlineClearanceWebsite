<?php
// Database configuration for Online Clearance Website (local)
//define('DB_HOST', 'localhost');
//define('DB_NAME', 'online_clearance_db');
//define('DB_USER', 'root');
//define('DB_PASS', '');
//define('DB_CHARSET', 'utf8mb4');

// Database configuration for Online Clearance Website (Current, Online)
define('DB_HOST', 'mysql-clrbasedata.alwaysdata.net');
define('DB_NAME', 'clrbasedata_online');
define('DB_USER', '440799');
define('DB_PASS', 'bawalsqlinjectiondito');
define('DB_CHARSET', 'utf8mb4');

// Database connection class
class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function getDbName() {
        return DB_NAME;
    }
}
?>
