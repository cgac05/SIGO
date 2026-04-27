#!/bin/bash
# Script de diagnóstico para SQL Server driver en AWS Elastic Beanstalk
# Uso: Ejecutar en SSH en la instancia EC2

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  DIAGNÓSTICO DE DRIVERS SQL SERVER - ELASTIC BEANSTALK        ║"
echo "╚════════════════════════════════════════════════════════════════╝"

LOGFILE="/tmp/sqlserver_diagnostic_$(date +%Y%m%d_%H%M%S).log"
echo "Logs guardados en: $LOGFILE"
echo ""

# Función auxiliar para imprimir secciones
print_section() {
    echo "────────────────────────────────────────────────────────────────"
    echo "→ $1"
    echo "────────────────────────────────────────────────────────────────"
}

# 1. INFORMACIÓN DEL SISTEMA
print_section "1. INFORMACIÓN DEL SISTEMA"
echo "OS: $(cat /etc/os-release | grep PRETTY_NAME)"
echo "Kernel: $(uname -r)"
echo "Cores: $(nproc)"
echo "Memoria: $(free -h | awk 'NR==2{print $2}')"
echo "" | tee -a "$LOGFILE"

# 2. INFORMACIÓN DE PHP
print_section "2. INFORMACIÓN DE PHP"
php -v
echo ""
php -r "echo 'PHP Configuration:\n'; echo 'php.ini: ' . php_ini_loaded_file() . '\n'; echo 'Extension Dir: ' . ini_get('extension_dir') . '\n';"
echo "" | tee -a "$LOGFILE"

# 3. EXTENSIONES CARGADAS
print_section "3. EXTENSIONES PHP"
echo "Todas las extensiones:"
php -m
echo ""
echo "Status de extensiones críticas:"
php -r "
\$critical = ['sqlsrv', 'pdo_sqlsrv', 'pdo', 'odbc'];
foreach (\$critical as \$ext) {
    \$status = extension_loaded(\$ext) ? '✓ CARGADO' : '✗ NO CARGADO';
    echo sprintf('  %-15s %s\n', \$ext . ':', \$status);
}
"
echo "" | tee -a "$LOGFILE"

# 4. ARCHIVOS .SO
print_section "4. ARCHIVOS DE DRIVERS"
echo "Buscando archivos .so de SQL Server..."
find /usr -name "*sqlsrv*.so" 2>/dev/null || echo "No se encontraron archivos .so de sqlsrv"
echo ""
echo "Extensión directory:"
ls -la $(php -r "echo ini_get('extension_dir');") | grep -i sqlsrv || echo "No hay sqlsrv en extension_dir"
echo "" | tee -a "$LOGFILE"

# 5. CONFIGURACIÓN PHP.INI
print_section "5. CONFIGURACIÓN DE EXTENSIONES"
echo "Contenido de /etc/php.d/:"
ls -la /etc/php.d/
echo ""
echo "Archivos de sqlsrv:"
grep -r "sqlsrv" /etc/php.d/ 2>/dev/null || echo "No hay configuración de sqlsrv en php.d"
echo ""
echo "Contenido de /etc/php.ini (si existe):"
grep -i "extension" /etc/php.ini 2>/dev/null | head -20 || echo "No hay php.ini o no tiene extensiones"
echo "" | tee -a "$LOGFILE"

# 6. ESTADO DE SERVICIOS
print_section "6. ESTADO DE SERVICIOS"
systemctl status php-fpm --no-pager 2>/dev/null || service php-fpm status 2>/dev/null || echo "PHP-FPM no encontrado"
echo ""
systemctl status nginx --no-pager 2>/dev/null || service nginx status 2>/dev/null || echo "Nginx no encontrado"
echo "" | tee -a "$LOGFILE"

# 7. DRIVERS ODBC
print_section "7. DRIVERS ODBC"
echo "Versión ODBC:"
odbcinst -j 2>/dev/null || echo "ODBC no instalado"
echo ""
echo "Drivers instalados:"
odbcinst -q -l -d 2>/dev/null || echo "No se pueden listar drivers ODBC"
echo "" | tee -a "$LOGFILE"

# 8. INTENTAR COMPILACIÓN PECL
print_section "8. ESTADO DE PECL"
pecl list 2>/dev/null | grep -i sqlsrv || echo "Pecl no muestra sqlsrv instalado"
echo "" | tee -a "$LOGFILE"

# 9. LOGS DE INSTALACIÓN PREVIOS
print_section "9. LOGS DE INSTALACIÓN PREVIOS"
for logfile in /tmp/*sqlsrv*.log /tmp/*driver*.log /tmp/verification.log; do
    if [ -f "$logfile" ]; then
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "Archivo: $logfile"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        tail -30 "$logfile"
        echo ""
    fi
done
echo "" | tee -a "$LOGFILE"

# 10. PRUEBA DE CONEXIÓN
print_section "10. PRUEBA DE CONEXIÓN A BASE DE DATOS"

if [ -d "/var/app/current" ]; then
    cd /var/app/current
    
    cat > /tmp/test_connection.php << 'PHPEOF'
<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO Drivers disponibles: " . implode(", ", PDO::getAvailableDrivers()) . "\n\n";

if (!extension_loaded('pdo_sqlsrv')) {
    echo "❌ CRÍTICO: pdo_sqlsrv no está cargado\n";
    echo "\nAcciones recomendadas:\n";
    echo "1. Verificar que los drivers están en: " . ini_get('extension_dir') . "\n";
    echo "2. Verificar que /etc/php.d/ tiene la configuración de sqlsrv\n";
    echo "3. Reiniciar PHP-FPM: systemctl restart php-fpm\n";
    exit(1);
}

echo "✓ PDO SQLSrv está cargado\n\n";

// Intentar conexión
try {
    $host = getenv('DB_HOST') ?: 'bdsigoprod.ca7cms0eernu.us-east-1.rds.amazonaws.com';
    $database = getenv('DB_DATABASE') ?: 'BD_SIGO';
    $user = getenv('DB_USERNAME') ?: '';
    $password = getenv('DB_PASSWORD') ?: '';
    
    echo "Intentando conectar a:\n";
    echo "  Host: $host\n";
    echo "  Database: $database\n";
    echo "  User: $user\n\n";
    
    $dsn = "sqlsrv:Server=$host,1433;Database=$database";
    $pdo = new PDO($dsn, $user, $password);
    
    echo "✓ ¡CONEXIÓN EXITOSA!\n";
    
    // Prueba simple
    $stmt = $pdo->query("SELECT TOP 1 CURRENT_TIMESTAMP as test_time");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Query ejecutado correctamente\n";
    echo "  Time: " . $row['test_time'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "  " . $e->getMessage() . "\n\n";
    echo "Código de error SQLSTATE: " . $e->getCode() . "\n";
}
PHPEOF
    
    php /tmp/test_connection.php || true
else
    echo "Laravel app no encontrado en /var/app/current"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  FIN DEL DIAGNÓSTICO                                          ║"
echo "║  Guardado en: $LOGFILE                                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"

# Guardar todo en un archivo
cat > "$LOGFILE" << "DIAGEOF"
[Se guardará el diagnóstico completo aquí]
DIAGEOF

echo "✓ Diagnóstico completado. Comparte el contenido de $LOGFILE si necesitas ayuda."
