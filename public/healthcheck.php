<?php
/**
 * Health Check - Diagnóstico SQL Server
 * Acceso directo sin dependencias de Laravel
 * URL: https://sigo-app-env.eba-pjwh8vad.us-east-1.elasticbeanstalk.com/healthcheck.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGO - Health Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { 
            color: #00ff00; 
            border-bottom: 3px solid #00ff00;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(0,255,0,0.3);
        }
        .section { 
            background: rgba(0,0,0,0.5);
            border: 1px solid #00ff00;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .section h2 { 
            color: #00ffff;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .ok { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffff00; }
        .info { color: #00ffff; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; border-bottom: 1px solid #444; }
        td:first-child { color: #00ffff; font-weight: bold; width: 40%; }
        pre { 
            background: rgba(0,0,0,0.7);
            border: 1px solid #00ff00;
            padding: 10px;
            overflow-x: auto;
            margin: 10px 0;
            border-radius: 3px;
        }
        .timestamp { color: #888; font-size: 0.9em; }
        .status-box {
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            border-left: 4px solid #888;
        }
        .status-ok { border-left-color: #00ff00; background: rgba(0,255,0,0.1); }
        .status-error { border-left-color: #ff0000; background: rgba(255,0,0,0.1); }
        .status-warning { border-left-color: #ffff00; background: rgba(255,255,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 SIGO - Health Check & Diagnostics</h1>
    <p class="timestamp">Generated: <?= date('Y-m-d H:i:s') ?> | Server: <?= gethostname() ?></p>

    <!-- SECCIÓN 1: ESTADO DEL SERVIDOR -->
    <div class="section">
        <h2>📊 Server Information</h2>
        <table>
            <tr>
                <td>Hostname</td>
                <td><?= gethostname() ?></td>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><?= PHP_VERSION ?></td>
            </tr>
            <tr>
                <td>Server API</td>
                <td><?= php_sapi_name() ?></td>
            </tr>
            <tr>
                <td>OS</td>
                <td><?= php_uname() ?></td>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <td>Current Script</td>
                <td><?= __FILE__ ?></td>
            </tr>
        </table>
    </div>

    <!-- SECCIÓN 2: EXTENSIONES PHP -->
    <div class="section">
        <h2>🔌 PHP Extensions</h2>
        
        <?php
        $critical_extensions = [
            'sqlsrv' => 'SQL Server Extension',
            'pdo_sqlsrv' => 'PDO SQL Server Driver',
            'pdo' => 'PHP Data Objects',
            'odbc' => 'ODBC Driver',
        ];
        
        foreach ($critical_extensions as $ext => $desc) {
            $loaded = extension_loaded($ext);
            $class = $loaded ? 'status-ok' : 'status-error';
            $status = $loaded ? '<span class="ok">✓ LOADED</span>' : '<span class="error">✗ NOT LOADED</span>';
            $version = $loaded && function_exists('phpversion') ? phpversion($ext) : '-';
            echo "<div class='status-box $class'><strong>$ext</strong>: $status ($desc) - v$version</div>";
        }
        ?>
    </div>

    <!-- SECCIÓN 3: DIRECTORIO DE EXTENSIONES -->
    <div class="section">
        <h2>📁 Extension Directory</h2>
        <?php
        $ext_dir = ini_get('extension_dir');
        echo "<table><tr><td>Path</td><td>$ext_dir</td></tr>";
        echo "<tr><td>Exists</td><td>" . (is_dir($ext_dir) ? '<span class="ok">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</td></tr>";
        echo "<tr><td>Writable</td><td>" . (is_writable($ext_dir) ? '<span class="ok">✓ YES</span>' : '<span class="warning">⚠ NO</span>') . "</td></tr></table>";
        
        if (is_dir($ext_dir)) {
            echo "<p><strong>Files in extension directory:</strong></p><pre>";
            $files = @scandir($ext_dir);
            $sqlsrv_files = array_filter($files, fn($f) => strpos($f, 'sqlsrv') !== false);
            if (!empty($sqlsrv_files)) {
                echo implode("\n", $sqlsrv_files);
            } else {
                echo "(No sqlsrv files found)";
            }
            echo "</pre>";
        }
        ?>
    </div>

    <!-- SECCIÓN 4: ARCHIVOS DE CONFIGURACIÓN PHP -->
    <div class="section">
        <h2>⚙️ PHP Configuration Files</h2>
        <?php
        $config_dirs = ['/etc/php.ini', '/etc/php.d', '/etc/php/conf.d', '/usr/local/etc/php/conf.d'];
        
        foreach ($config_dirs as $dir) {
            if (is_file($dir)) {
                echo "<p><strong>File: $dir</strong></p>";
                echo "<pre>" . htmlspecialchars(file_get_contents($dir)) . "</pre>";
            } elseif (is_dir($dir)) {
                echo "<p><strong>Directory: $dir</strong></p>";
                $files = @scandir($dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filepath = "$dir/$file";
                        if (strpos($file, 'sqlsrv') !== false || strpos($file, '30-') !== false) {
                            echo "<p style='color: #00ffff;'>→ <strong>$file</strong></p>";
                            echo "<pre>" . htmlspecialchars(file_get_contents($filepath)) . "</pre>";
                        }
                    }
                }
            }
        }
        ?>
    </div>

    <!-- SECCIÓN 5: PDO DRIVERS -->
    <div class="section">
        <h2>🗄️ PDO Drivers</h2>
        <?php
        $pdo_drivers = PDO::getAvailableDrivers();
        echo "<p><strong>Available PDO drivers:</strong></p>";
        echo "<pre>";
        foreach ($pdo_drivers as $driver) {
            $highlight = (strpos($driver, 'sqlsrv') !== false) ? '→ ' : '  ';
            echo "$highlight$driver\n";
        }
        echo "</pre>";
        
        if (in_array('sqlsrv', $pdo_drivers)) {
            echo '<div class="status-box status-ok"><span class="ok">✓</span> sqlsrv is available as PDO driver</div>';
        } else {
            echo '<div class="status-box status-error"><span class="error">✗</span> sqlsrv NOT available as PDO driver</div>';
        }
        ?>
    </div>

    <!-- SECCIÓN 6: INTENTAR CONEXIÓN -->
    <div class="section">
        <h2>🔗 Database Connection Test</h2>
        <?php
        // Leer variables de entorno desde diferentes fuentes
        $env_file = '/var/app/current/.env';
        if (file_exists($env_file)) {
            echo "<p class='info'>ℹ️  Found .env file</p>";
            $env_content = file_get_contents($env_file);
            preg_match('/DB_HOST=(.*)/', $env_content, $m); $db_host = trim($m[1] ?? '');
            preg_match('/DB_DATABASE=(.*)/', $env_content, $m); $db_database = trim($m[1] ?? '');
            preg_match('/DB_USERNAME=(.*)/', $env_content, $m); $db_username = trim($m[1] ?? '');
            preg_match('/DB_PASSWORD=(.*)/', $env_content, $m); $db_password = trim($m[1] ?? '');
        }
        
        // Fallback a valores por defecto o variables de entorno
        $db_host = $db_host ?? $_ENV['DB_HOST'] ?? 'bdsigoprod.ca7cms0eernu.us-east-1.rds.amazonaws.com';
        $db_database = $db_database ?? $_ENV['DB_DATABASE'] ?? 'BD_SIGO';
        $db_username = $db_username ?? $_ENV['DB_USERNAME'] ?? '';
        $db_password = $db_password ?? $_ENV['DB_PASSWORD'] ?? '';
        
        echo "<table>";
        echo "<tr><td>Host</td><td>$db_host</td></tr>";
        echo "<tr><td>Database</td><td>$db_database</td></tr>";
        echo "<tr><td>Username</td><td>" . ($db_username ? "***" : "NOT SET") . "</td></tr>";
        echo "<tr><td>Password</td><td>" . ($db_password ? "***" : "NOT SET") . "</td></tr>";
        echo "</table>";
        
        if (!extension_loaded('pdo_sqlsrv')) {
            echo '<div class="status-box status-error"><span class="error">✗</span> Cannot test: pdo_sqlsrv not loaded</div>';
        } elseif (!$db_username) {
            echo '<div class="status-box status-warning"><span class="warning">⚠</span> Credentials not configured</div>';
        } else {
            try {
                echo "<p class='info'>Attempting connection...</p>";
                $dsn = "sqlsrv:Server=$db_host,1433;Database=$db_database";
                $pdo = new PDO($dsn, $db_username, $db_password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                
                echo '<div class="status-box status-ok"><span class="ok">✓</span> Database connection successful!</div>';
                
                // Prueba simple
                $stmt = $pdo->query("SELECT CURRENT_TIMESTAMP as server_time");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<table>";
                echo "<tr><td>Server Time</td><td>" . $result['server_time'] . "</td></tr>";
                echo "</table>";
                
            } catch (PDOException $e) {
                echo '<div class="status-box status-error">';
                echo '<span class="error">✗</span> Connection failed:<br>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '</div>';
            }
        }
        ?>
    </div>

    <!-- SECCIÓN 7: LOGS DE INSTALACIÓN -->
    <div class="section">
        <h2>📋 Installation Logs</h2>
        <?php
        $log_dir = '/var/app/current/storage/logs/eb_install';
        if (is_dir($log_dir)) {
            $logs = @scandir($log_dir);
            $logs = array_filter($logs, fn($f) => $f !== '.' && $f !== '..');
            
            if (empty($logs)) {
                echo '<p class="warning">⚠️  No logs found in ' . $log_dir . '</p>';
            } else {
                echo "<p><strong>Log files found:</strong></p>";
                rsort($logs); // Ordenar por más reciente primero
                
                foreach (array_slice($logs, 0, 3) as $log) { // Mostrar últimos 3
                    $filepath = "$log_dir/$log";
                    echo "<p style='color: #00ffff;'><strong>→ $log</strong> (" . date('Y-m-d H:i', filemtime($filepath)) . ")</p>";
                    $content = file_get_contents($filepath);
                    echo "<pre>" . htmlspecialchars(substr($content, -2000)) . "</pre>"; // Últimas 2000 chars
                }
            }
        } else {
            echo '<p class="warning">⚠️  Log directory not found: ' . $log_dir . '</p>';
        }
        ?>
    </div>

    <!-- SECCIÓN 8: SISTEMA DE ARCHIVOS -->
    <div class="section">
        <h2>📂 File System</h2>
        <?php
        $critical_paths = [
            '/var/app/current' => 'Laravel App',
            '/var/app/current/storage' => 'Storage',
            '/var/app/current/storage/logs' => 'Logs',
            '/var/app/current/public' => 'Public',
            '/etc/nginx' => 'Nginx Config',
            '/etc/php.d' => 'PHP Config',
        ];
        
        echo "<table>";
        foreach ($critical_paths as $path => $desc) {
            $exists = file_exists($path) ? '<span class="ok">✓</span>' : '<span class="error">✗</span>';
            $type = is_file($path) ? 'File' : (is_dir($path) ? 'Dir' : 'N/A');
            $writable = is_writable($path) ? '<span class="ok">✓</span>' : '<span class="error">✗</span>';
            echo "<tr><td>$desc</td><td>$exists</td><td>$type</td><td>Writable: $writable</td></tr>";
        }
        echo "</table>";
        ?>
    </div>

    <!-- SECCIÓN 9: COMANDOS ÚTILES -->
    <div class="section">
        <h2>🎯 Useful Commands (via SSH)</h2>
        <pre>
# Ver módulos PHP
php -m | grep sqlsrv

# Ver configuración PHP
php -i | grep -i sqlsrv

# Ver logs de instalación
tail -100 /var/app/current/storage/logs/eb_install/*.log

# Ver estado de servicios
systemctl status php-fpm
systemctl status nginx

# Ver logs de errores
tail -100 /var/log/php-fpm/error.log
tail -100 /var/log/nginx/error.log

# Verificar drivers ODBC
odbcinst -q -l -d

# Verificar conexión a RDS
telnet bdsigoprod.ca7cms0eernu.us-east-1.rds.amazonaws.com 1433
        </pre>
    </div>

    <!-- FOOTER -->
    <div style="text-align: center; margin-top: 40px; color: #666; border-top: 1px solid #444; padding-top: 20px;">
        <p>Health Check page - For troubleshooting only</p>
        <p style="font-size: 0.9em;">Delete this file after debugging: <code>/public/healthcheck.php</code></p>
    </div>
</div>

</body>
</html>
