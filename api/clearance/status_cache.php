<?php
/**
 * Clearance Status Cache
 * Simple file-based caching for clearance status data
 */

class ClearanceStatusCache {
    private static $cacheDir = __DIR__ . '/../../cache/clearance_status/';
    private static $cacheTimeout = 300; // 5 minutes
    
    /**
     * Get cached clearance status data
     */
    public static function get($userId, $formId = null) {
        $cacheKey = self::generateCacheKey($userId, $formId);
        $cacheFile = self::$cacheDir . $cacheKey . '.json';
        
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            
            // Check if cache is still valid
            if (time() - $cacheData['timestamp'] < self::$cacheTimeout) {
                return $cacheData['data'];
            } else {
                // Cache expired, remove file
                unlink($cacheFile);
            }
        }
        
        return null;
    }
    
    /**
     * Store clearance status data in cache
     */
    public static function set($userId, $data, $formId = null) {
        // Ensure cache directory exists
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        $cacheKey = self::generateCacheKey($userId, $formId);
        $cacheFile = self::$cacheDir . $cacheKey . '.json';
        
        $cacheData = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        file_put_contents($cacheFile, json_encode($cacheData));
    }
    
    /**
     * Clear cache for specific user
     */
    public static function clear($userId, $formId = null) {
        $cacheKey = self::generateCacheKey($userId, $formId);
        $cacheFile = self::$cacheDir . $cacheKey . '.json';
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    /**
     * Clear all cache
     */
    public static function clearAll() {
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Generate cache key
     */
    private static function generateCacheKey($userId, $formId = null) {
        return 'user_' . $userId . ($formId ? '_form_' . $formId : '');
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats() {
        if (!is_dir(self::$cacheDir)) {
            return ['files' => 0, 'size' => 0];
        }
        
        $files = glob(self::$cacheDir . '*.json');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'files' => count($files),
            'size' => $totalSize,
            'size_formatted' => self::formatBytes($totalSize)
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
?>
