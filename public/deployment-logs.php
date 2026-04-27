<?php
/**
 * TEMPORAL: Visor de logs de deployment de EB
 * Acceso: /deployment-logs.php
 * Eliminar después de verificar que todo funciona
 */

$logDir = dirname(__DIR__) . '/storage/logs/eb_fixes';
$tmpDir = '/tmp';

// Simular acceso a directorios remotos (si estamos en EB)
$ebLogsDir = '/var/app/current/storage/logs/eb_fixes';
if (is_dir($ebLogsDir)) {
    $logDir = $ebLogsDir;
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Deployment - SIGO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.5;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #fff; margin-bottom: 20px; border-bottom: 2px solid #0078d4; padding-bottom: 10px; }
        .log-section { 
            background: #2d2d2d;
            border: 1px solid #3e3e3e;
            border-radius: 5px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .log-header {
            background: #0078d4;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .log-header:hover { background: #106ebe; }
        .log-content {
            padding: 15px;
            max-height: 600px;
            overflow-y: auto;
            background: #1e1e1e;
            display: none;
        }
        .log-content.active { display: block; }
        .log-content pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #9cdcfe; }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-ready { background: #4ec9b0; color: white; }
        .status-progress { background: #dcdcaa; color: #1e1e1e; }
        .status-error { background: #f48771; color: white; }
        .summary {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #0078d4;
        }
        .summary h3 { color: #fff; margin-bottom: 10px; }
        .summary p { margin: 5px 0; }
        .toggle-btn { cursor: pointer; user-select: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Logs de Deployment - SIGO</h1>
        
        <div class="summary">
            <h3>📝 Resumen de Configuraciones Desplegadas</h3>
            <p><strong>Branch:</strong> Produccion</p>
            <p><strong>Commits:</strong></p>
            <ul style="margin-left: 20px;">
                <li>06_pdo_sqlsrv_fix.config - Fix para pdo_sqlsrv.so</li>
                <li>01_laravel_setup.config - Nginx routing (mejorado)</li>
                <li>07_build_assets.config - Compilar CSS/JS Tailwind</li>
                <li>08_configure_app_url.config - APP_URL dinámico</li>
            </ul>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                ℹ️ Los logs de /tmp se pueden limpiar durante reboot de EB. 
                Los logs de /var/app/current/storage/logs/eb_fixes/ son persistentes.
            </p>
        </div>

        <?php
        // Función para mostrar un archivo de log
        function showLog($title, $filePath, $icon = '📋') {
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $size = filesize($filePath);
                $modified = date('Y-m-d H:i:s', filemtime($filePath));
                
                // Colorear output
                $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                $content = preg_replace('/^(✓|✅).*/m', '<span class="success">$0</span>', $content);
                $content = preg_replace('/^(❌|Error|ERROR).*/m', '<span class="error">$0</span>', $content);
                $content = preg_replace('/^(⚠|Warning|WARN).*/m', '<span class="warning">$0</span>', $content);
                
                echo '<div class="log-section">';
                echo '<div class="log-header toggle-btn">';
                echo "<span>$icon $title <span class=\"status-badge status-ready\">$(wc -l < '$filePath') líneas</span></span>";
                echo "<span style=\"font-size: 12px; color: #aaa;\">$modified | $size bytes</span>";
                echo '</div>';
                echo '<div class="log-content"><pre>' . $content . '</pre></div>';
                echo '</div>';
                
                return true;
            }
            return false;
        }

        // Archivos a mostrar (en orden de importancia)
        $logs = [
            ['📦 Compilación de Assets', '/var/app/current/storage/logs/eb_fixes/asset_build.log', '🎨'],
            ['🌐 Configuración APP_URL', '/var/app/current/storage/logs/eb_fixes/app_url_verify.log', '🔗'],
            ['🔧 Verificación Nginx', '/var/app/current/storage/logs/eb_fixes/nginx_validation.log', '⚙️'],
            ['📡 Verificación de Drivers', '/var/app/current/storage/logs/eb_verification.log', '📡'],
            ['🛠️ Instalación de Dependencias', '/tmp/install_deps.log', '📦'],
            ['🔌 Instalación de Drivers DNF', '/tmp/dnf_drivers.log', '🔌'],
            ['🐘 Instalación PECL sqlsrv', '/tmp/sqlsrv_install.log', '🐘'],
            ['🗄️ Instalación PECL pdo_sqlsrv', '/tmp/pdo_sqlsrv_install.log', '🗄️'],
            ['🔄 Reinicio PHP-FPM', '/tmp/php_restart.log', '🔄'],
            ['🌐 Reinicio Nginx', '/tmp/nginx_restart.log', '🌐'],
        ];

        $logsFound = 0;
        foreach ($logs as [$title, $path, $icon]) {
            if (showLog($title, $path, $icon)) {
                $logsFound++;
            }
        }

        if ($logsFound === 0) {
            echo '<div class="summary" style="border-left-color: #dcdcaa;">';
            echo '<h3>⏳ Deployment en Progreso</h3>';
            echo '<p>Los logs aún no están disponibles. El deployment tarda 10-15 minutos.</p>';
            echo '<p>Actualiza esta página en unos momentos:</p>';
            echo '<p style="margin-top: 10px;"><strong>Comandos ejecutándose:</strong></p>';
            echo '<ul style="margin-left: 20px;">';
            echo '<li>Instalación de drivers SQL Server</li>';
            echo '<li>Compilación de assets (npm build)</li>';
            echo '<li>Configuración de Nginx</li>';
            echo '<li>Detección de APP_URL</li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <div class="summary" style="border-left-color: #4ec9b0;">
            <h3>✅ Verificaciones Recomendadas</h3>
            <p><strong>1. Login/Register:</strong></p>
            <ul style="margin-left: 20px;">
                <li>URL: <code>/login</code></li>
                <li>Espera: Debe cargar (no 404)</li>
            </ul>
            <p style="margin-top: 10px;"><strong>2. Dashboard con estilos:</strong></p>
            <ul style="margin-left: 20px;">
                <li>URL: <code>/dashboard</code></li>
                <li>Espera: CSS/Tailwind aplicados</li>
            </ul>
            <p style="margin-top: 10px;"><strong>3. Drivers SQL Server:</strong></p>
            <ul style="margin-left: 20px;">
                <li>URL: <code>/healthcheck.php</code></li>
                <li>Espera: pdo_sqlsrv LOADED</li>
            </ul>
        </div>
    </div>

    <script>
        // Toggle de logs
        document.querySelectorAll('.log-header').forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                content.classList.toggle('active');
                this.style.opacity = content.classList.contains('active') ? '1' : '0.8';
            });
        });

        // Auto-refresh cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
