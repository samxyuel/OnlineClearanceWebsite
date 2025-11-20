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
            // Remove charset from DSN to avoid implicit collation setting
            // We'll set it explicitly after connection
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Set charset and collation explicitly BEFORE any other operations
            // This ensures all string operations use the correct collation
            $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
            $this->connection->exec("SET collation_database = 'utf8mb4_unicode_ci'");
            $this->connection->exec("SET character_set_connection = 'utf8mb4'");
            
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

/**
 * Get base URL for API calls
 * Returns /OnlineClearanceWebsite for HTTP (localhost) or empty string for HTTPS (production)
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    // If HTTPS (production), return empty string (root deployment)
    // If HTTP (localhost), return /OnlineClearanceWebsite
    return $protocol === 'https' ? '' : '/OnlineClearanceWebsite';
}

/**
 * Get full API URL for server-side HTTP requests
 * @param string $endpoint - API endpoint path (e.g., 'api/clearance/form_distribution.php')
 * @return string Full API URL
 */
function getApiBaseUrl($endpoint) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = getBaseUrl();
    $cleanEndpoint = ltrim($endpoint, '/');
    return $protocol . '://' . $host . $basePath . '/' . $cleanEndpoint;
}

/**
 * Set dynamic CORS headers - echoes back the request origin if allowed
 * Works for both localhost (HTTP) and production (HTTPS)
 * 
 * @param bool $allowCredentials Whether to allow credentials (cookies, auth headers)
 * @param array $allowedMethods Allowed HTTP methods
 * @param array $allowedHeaders Allowed request headers
 */
function setCorsHeaders($allowCredentials = true, $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'], $allowedHeaders = ['Content-Type', 'Authorization']) {
    // Get the origin from the request
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Define allowed origins (whitelist)
    $allowedOrigins = [
        'http://localhost',
        'http://127.0.0.1',
        'https://www.clearance-gosti.online',
        'https://clearance-gosti.online',
    ];
    
    // Check if origin is in whitelist
    $isAllowed = false;
    foreach ($allowedOrigins as $allowed) {
        // Check exact match or if origin contains allowed origin (for ports)
        if ($origin === $allowed || strpos($origin, $allowed . ':') === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    // For localhost development, be more lenient
    if (!$isAllowed && (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false)) {
        $isAllowed = true;
    }
    
    // Set CORS headers
    if ($isAllowed && !empty($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        // Default: allow same-origin requests
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        header('Access-Control-Allow-Origin: ' . $protocol . '://' . $host);
    }
    
    if ($allowCredentials) {
        header('Access-Control-Allow-Credentials: true');
    }
    
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
    header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
?>
