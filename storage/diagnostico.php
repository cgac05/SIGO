<?php
/**
 * Route para diagnóstico rápido de SQL Server drivers
 * 
 * AGREGAR TEMPORALMENTE A: routes/web.php
 * Route::get('/diagnostico-sqlserver', function() {
 *     include storage_path('diagnostico.php');
 * });
 * 
 * Luego borrar después de diagnosticar
 */

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico SQL Server</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; line-height: 1.6; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #0f0; border-radius: 5px; }
        .success { color: #0f0; font-weight: bold; }
        .error { color: #f00; font-weight: bold; }
        .warning { color: #ff0; font-weight: bold; }
        .info { color: #0ff; }
        h2 { color: #0ff; border-bottom: 2px solid #0f0; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td, th { border: 1px solid #0f0; padding: 8px; text-align: left; }
        th { background: #0f0; color: #000; }
        .status-ok { background: rgba(0, 255, 0, 0.1); }
        .status-error { background: rgba(255, 0, 0, 0.1); }
    </style>
</head>
<body>

<h1>🔧 Diagnóstico de SQL Server en AWS</h1>
<p class="info">Generado: <?= date('Y-m-d H:i:s') ?></p>

<!-- SECCIÓN 1: INFORMACIÓN DEL SISTEMA -->
<div class="section">
    <h2>1. Información del Sistema</h2>
    <table>
        <tr>
            <th>Parámetro</th>
            <th>Valor</th>
        </tr>
        <tr class="status-ok">
            <td>PHP Version</td>
            <td><?= PHP_VERSION ?></td>
        </tr>
        <tr>
            <td>OS</td>
            <td><?= php_uname() ?></td>
        </tr>
        <tr>
            <td>Extension Directory</td>
            <td><?= ini_get('extension_dir') ?></td>
        </tr>
        <tr>
            <td>PHP.ini</td>
            <td><?= php_ini_loaded_file() ?></td>
        </tr>
        <tr>
            <td>Server API</td>
            <td><?= php_sapi_name() ?></td>
        </tr>
    </table>
</div>

<!-- SECCIÓN 2: EXTENSIONES CRÍTICAS -->
<div class="section">
    <h2>2. Extensiones Críticas</h2>
    <table>
        <tr>
            <th>Extensión</th>
            <th>Estado</th>
            <th>Información</th>
        </tr>
        <?php
        $extensions = [
            'sqlsrv' => 'SQL Server Extension',
            'pdo_sqlsrv' => 'PDO SQL Server Driver',
            'pdo' => 'PHP Data Objects',
            'odbc' => 'ODBC Extension',
        ];
        
        foreach ($extensions as $ext => $desc) {
            $loaded = extension_loaded($ext);
            $status_class = $loaded ? 'status-ok' : 'status-error';
            $status_text = $loaded ? '<span class="success">✓ CARGADA</span>' : '<span class="error">✗ NO CARGADA</span>';
            $version = $loaded && function_exists('phpversion') ? 'v' . phpversion($ext) : '-';
            echo "<tr class='$status_class'><td>$ext</td><td>$status_text</td><td>$desc ($version)</td></tr>";
        }
        ?>
    </table>
</div>

<!-- SECCIÓN 3: DRIVERS PDO DISPONIBLES -->
<div class="section">
    <h2>3. Drivers PDO Disponibles</h2>
    <?php
    $drivers = PDO::getAvailableDrivers();
    if (!empty($drivers)) {
        echo '<div class="success">✓ Drivers disponibles:</div>';
        echo '<ul>';
        foreach ($drivers as $driver) {
            $highlight = (strpos($driver, 'sqlsrv') !== false) ? 'class="success"' : '';
            echo "<li $highlight>$driver</li>";
        }
        echo '</ul>';
    } else {
        echo '<div class="error">✗ No hay drivers PDO disponibles</div>';
    }
    
    if (!in_array('sqlsrv', $drivers)) {
        echo '<div class="error">❌ CRÍTICO: sqlsrv NO está en los drivers disponibles</div>';
    }
    ?>
</div>

<!-- SECCIÓN 4: CONFIGURACIÓN DE EXTENSIONES -->
<div class="section">
    <h2>4. Archivos de Configuración</h2>
    <?php
    $config_dirs = ['/etc/php.d', '/etc/php/conf.d', '/usr/local/etc/php/conf.d'];
    $found_sqlsrv = false;
    
    foreach ($config_dirs as $dir) {
        if (is_dir($dir)) {
            echo "<p class='info'>Buscando en: $dir</p>";
            $files = glob($dir . '/*.ini');
            if (empty($files)) {
                echo "<p>  (sin archivos .ini)</p>";
            } else {
                foreach ($files as $file) {
                    $content = file_get_contents($file);
                    if (strpos($content, 'sqlsrv') !== false) {
                        echo "<p class='success'>✓ $file - contiene sqlsrv</p>";
                        echo '<pre style="background: #222; padding: 10px; border-radius: 3px;">';
                        echo htmlspecialchars($content);
                        echo '</pre>';
                        $found_sqlsrv = true;
                    }
                }
            }
        }
    }
    
    if (!$found_sqlsrv) {
        echo '<p class="error">⚠️  No se encontró configuración de sqlsrv en directorios .ini</p>';
        echo '<p class="warning">Acción: Crear /etc/php.d/30-sqlsrv.ini con:</p>';
        echo '<pre style="background: #222; padding: 10px; border-radius: 3px;">extension=sqlsrv.so
extension=pdo_sqlsrv.so</pre>';
    }
    ?>
</div>

<!-- SECCIÓN 5: PRUEBA DE CONEXIÓN -->
<div class="section">
    <h2>5. Prueba de Conexión</h2>
    <?php
    if (!extension_loaded('pdo_sqlsrv')) {
        echo '<div class="error">❌ No se puede probar: pdo_sqlsrv no está cargado</div>';
        echo '<p class="warning">Primero instala y carga los drivers, luego recarga esta página.</p>';
    } else {
        echo '<div class="success">✓ pdo_sqlsrv está disponible</div>';
        
        // Intentar conexión
        try {
            $host = env('DB_HOST', 'bdsigoprod.ca7cms0eernu.us-east-1.rds.amazonaws.com');
            $database = env('DB_DATABASE', 'BD_SIGO');
            $user = env('DB_USERNAME', '');
            $password = env('DB_PASSWORD', '');
            
            echo '<p class="info">Intentando conectar a:</p>';
            echo '<table>';
            echo '<tr><td>Host</td><td>' . $host . '</td></tr>';
            echo '<tr><td>Database</td><td>' . $database . '</td></tr>';
            echo '<tr><td>User</td><td>' . ($user ? $user : '<span class="warning">NO CONFIGURADO</span>') . '</td></tr>';
            echo '</table>';
            
            if (!$user) {
                echo '<p class="warning">⚠️  Credenciales no configuradas en .env</p>';
            } else {
                $dsn = "sqlsrv:Server=$host,1433;Database=$database";
                $pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                echo '<p class="success">✓ ¡CONEXIÓN EXITOSA!</p>';
                
                // Prueba simple
                $stmt = $pdo->query("SELECT TOP 1 CURRENT_TIMESTAMP as test_time");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '<p class="success">✓ Query ejecutado correctamente</p>';
                echo '<table>';
                echo '<tr><td>Timestamp del Servidor</td><td>' . $row['test_time'] . '</td></tr>';
                echo '</table>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">❌ ERROR DE CONEXIÓN:</p>';
            echo '<pre style="background: #222; padding: 10px; border-radius: 3px; color: #ff6666;">';
            echo htmlspecialchars($e->getMessage());
            echo '</pre>';
            echo '<p class="warning">Posibles causas:</p>';
            echo '<ul>';
            echo '<li>Credenciales incorrectas</li>';
            echo '<li>RDS SQL Server no es alcanzable desde la red</li>';
            echo '<li>Security Group no permite conexión en puerto 1433</li>';
            echo '<li>TrustServerCertificate=yes no configurado (para certs auto-firmados)</li>';
            echo '</ul>';
        }
    }
    ?>
</div>

<!-- SECCIÓN 6: RECOMENDACIONES -->
<div class="section">
    <h2>6. Recomendaciones</h2>
    <?php
    $issues = [];
    $warnings = [];
    $successes = [];
    
    if (!extension_loaded('sqlsrv')) {
        $issues[] = "❌ sqlsrv no está cargado";
    } else {
        $successes[] = "✓ sqlsrv cargado";
    }
    
    if (!extension_loaded('pdo_sqlsrv')) {
        $issues[] = "❌ pdo_sqlsrv no está cargado";
    } else {
        $successes[] = "✓ pdo_sqlsrv cargado";
    }
    
    if (!in_array('sqlsrv', PDO::getAvailableDrivers())) {
        $issues[] = "❌ sqlsrv no disponible como driver PDO";
    }
    
    if (empty($issues)) {
        echo '<div class="success">✓ Todos los drivers están correctamente instalados y cargados</div>';
        echo '<p>Si aún tienes errores en Laravel:</p>';
        echo '<ol>';
        echo '<li>Ejecuta: <code>php artisan config:cache</code></li>';
        echo '<li>Ejecuta: <code>php artisan cache:clear</code></li>';
        echo '<li>Reinicia PHP-FPM: <code>systemctl restart php-fpm</code></li>';
        echo '<li>Recarga la página</li>';
        echo '</ol>';
    } else {
        echo '<div class="error">Se encontraron problemas:</div>';
        foreach ($issues as $issue) {
            echo "<p class='error'>$issue</p>";
        }
        
        echo '<p class="warning">Para instalar los drivers en AWS:</p>';
        echo '<ol>';
        echo '<li>SSH a tu instancia EC2</li>';
        echo '<li>Ejecuta:<br><code>ACCEPT_EULA=Y dnf install -y msodbcsql18 unixODBC-devel</code></li>';
        echo '<li>Ejecuta:<br><code>dnf install -y php-sqlsrv php-pdo_sqlsrv</code></li>';
        echo '<li>Si lo anterior no funciona, usa PECL:<br><code>pecl install sqlsrv pdo_sqlsrv</code></li>';
        echo '<li>Reinicia PHP-FPM:<br><code>systemctl restart php-fpm</code></li>';
        echo '<li>Recarga esta página</li>';
        echo '</ol>';
    }
    ?>
</div>

</body>
</html>
