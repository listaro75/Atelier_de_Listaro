<?php
// Fonction pour récupérer les informations système du Raspberry Pi
class RaspberryPiStats {
    
    /**
     * Obtenir la température du CPU
     */
    public static function getCpuTemperature() {
        try {
            if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
                $temp = file_get_contents('/sys/class/thermal/thermal_zone0/temp');
                return round($temp / 1000, 1); // Conversion en Celsius
            }
        } catch (Exception $e) {
            error_log("Erreur lecture température: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Obtenir l'utilisation CPU
     */
    public static function getCpuUsage() {
        try {
            $load = sys_getloadavg();
            if ($load !== false && count($load) >= 3) {
                return [
                    '1min' => round($load[0] * 100, 1),
                    '5min' => round($load[1] * 100, 1),
                    '15min' => round($load[2] * 100, 1)
                ];
            }
        } catch (Exception $e) {
            error_log("Erreur lecture CPU: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Obtenir l'utilisation de la RAM
     */
    public static function getMemoryUsage() {
        try {
            $meminfo = file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
                preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
                
                if ($total && $available) {
                    $total_mb = round($total[1] / 1024, 0);
                    $available_mb = round($available[1] / 1024, 0);
                    $used_mb = $total_mb - $available_mb;
                    $usage_percent = round(($used_mb / $total_mb) * 100, 1);
                    
                    return [
                        'total' => $total_mb,
                        'used' => $used_mb,
                        'available' => $available_mb,
                        'percent' => $usage_percent
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lecture RAM: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Obtenir l'utilisation du stockage
     */
    public static function getDiskUsage($path = '/') {
        try {
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            
            if ($total !== false && $free !== false) {
                $used = $total - $free;
                $usage_percent = round(($used / $total) * 100, 1);
                
                return [
                    'total' => self::formatBytes($total),
                    'used' => self::formatBytes($used),
                    'free' => self::formatBytes($free),
                    'percent' => $usage_percent,
                    'total_raw' => $total,
                    'used_raw' => $used,
                    'free_raw' => $free
                ];
            }
        } catch (Exception $e) {
            error_log("Erreur lecture disque: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Obtenir l'uptime du système
     */
    public static function getUptime() {
        try {
            if (file_exists('/proc/uptime')) {
                $uptime_seconds = floatval(file_get_contents('/proc/uptime'));
                return self::formatUptime($uptime_seconds);
            }
        } catch (Exception $e) {
            error_log("Erreur lecture uptime: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Obtenir les informations sur le système
     */
    public static function getSystemInfo() {
        try {
            $info = [];
            
            // Version du kernel
            $info['kernel'] = php_uname('r');
            
            // Nom du système
            $info['hostname'] = php_uname('n');
            
            // Architecture
            $info['architecture'] = php_uname('m');
            
            // Version PHP
            $info['php_version'] = PHP_VERSION;
            
            // Modèle du Raspberry Pi (si disponible)
            if (file_exists('/proc/device-tree/model')) {
                $model = file_get_contents('/proc/device-tree/model');
                $info['pi_model'] = trim(str_replace("\0", '', $model));
            }
            
            return $info;
        } catch (Exception $e) {
            error_log("Erreur lecture système: " . $e->getMessage());
        }
        return [];
    }
    
    /**
     * Obtenir les informations réseau
     */
    public static function getNetworkInfo() {
        try {
            $info = [];
            
            // Adresse IP locale
            $local_ip = $_SERVER['SERVER_ADDR'] ?? 'N/A';
            if ($local_ip === '::1' || $local_ip === '127.0.0.1') {
                // Essayer d'obtenir l'IP via hostname
                $local_ip = gethostbyname(gethostname());
            }
            $info['local_ip'] = $local_ip;
            
            // Adresse IP publique (depuis les headers si derrière un proxy)
            $public_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'N/A';
            $info['client_ip'] = $public_ip;
            
            return $info;
        } catch (Exception $e) {
            error_log("Erreur lecture réseau: " . $e->getMessage());
        }
        return [];
    }
    
    /**
     * Formater les bytes en unités lisibles
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Formater l'uptime en format lisible
     */
    private static function formatUptime($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        $parts = [];
        if ($days > 0) $parts[] = $days . 'j';
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($minutes > 0) $parts[] = $minutes . 'm';
        
        return implode(' ', $parts) ?: '< 1m';
    }
    
    /**
     * Obtenir toutes les statistiques
     */
    public static function getAllStats() {
        return [
            'temperature' => self::getCpuTemperature(),
            'cpu' => self::getCpuUsage(),
            'memory' => self::getMemoryUsage(),
            'disk' => self::getDiskUsage(),
            'uptime' => self::getUptime(),
            'system' => self::getSystemInfo(),
            'network' => self::getNetworkInfo(),
            'timestamp' => time()
        ];
    }
}
?>
