#!/bin/bash
# Script para obtener logs y diagnóstico rápido de AWS Elastic Beanstalk
# Uso: ./get-eb-logs.sh

set -e

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  ELASTIC BEANSTALK - LOG & DIAGNOSTICS COLLECTOR            ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

OUTPUT_DIR="eb_diagnostics_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$OUTPUT_DIR"

echo "📂 Output directory: $OUTPUT_DIR"
echo ""

# Función para guardar comando
run_and_save() {
    local cmd="$1"
    local file="$2"
    echo "⏳ Executing: $cmd"
    eval "$cmd" > "$OUTPUT_DIR/$file" 2>&1 || echo "⚠️  Command failed: $cmd"
}

# 1. Información del sistema
echo "━━━ SYSTEM INFORMATION ━━━"
run_and_save "uname -a" "01_system_info.txt"
run_and_save "cat /etc/os-release" "02_os_release.txt"
run_and_save "df -h" "03_disk_usage.txt"
run_and_save "free -h" "04_memory.txt"

# 2. PHP
echo "━━━ PHP INFORMATION ━━━"
run_and_save "php -v" "05_php_version.txt"
run_and_save "php -m" "06_php_modules.txt"
run_and_save "php -i" "07_php_info.txt"
run_and_save "php -r \"echo ini_get('extension_dir');\"" "08_php_extension_dir.txt"

# 3. Configuración de extensiones
echo "━━━ PHP CONFIGURATION ━━━"
run_and_save "cat /etc/php.ini 2>/dev/null" "09_php.ini.txt"
run_and_save "ls -la /etc/php.d/" "10_php.d_contents.txt"
run_and_save "find /etc/php.d/ -name '*.ini' -exec cat {} \\;" "11_php.d_files.txt"
run_and_save "find /usr -name '*sqlsrv*.so' 2>/dev/null" "12_sqlsrv_so_files.txt"

# 4. ODBC
echo "━━━ ODBC DRIVERS ━━━"
run_and_save "odbcinst -j" "13_odbc_version.txt"
run_and_save "odbcinst -q -l -d" "14_odbc_drivers.txt"
run_and_save "odbcinst -q -l -s" "15_odbc_dsn.txt"

# 5. Logs de instalación de EB
echo "━━━ ELASTIC BEANSTALK LOGS ━━━"
if [ -d "/var/app/current/storage/logs/eb_install" ]; then
    run_and_save "ls -lah /var/app/current/storage/logs/eb_install/" "16_eb_install_logs_list.txt"
    run_and_save "cat /var/app/current/storage/logs/eb_install/*.log" "17_eb_install_logs_content.txt"
else
    echo "No eb_install logs found" > "$OUTPUT_DIR/16_eb_install_logs_list.txt"
fi

# 6. Logs de sistema
echo "━━━ SYSTEM LOGS ━━━"
run_and_save "tail -500 /var/log/eb-engine.log 2>/dev/null" "18_eb_engine.log.txt"
run_and_save "tail -500 /var/log/eb-activity.log 2>/dev/null" "19_eb_activity.log.txt"
run_and_save "tail -500 /var/log/cloud-init.log 2>/dev/null" "20_cloud_init.log.txt"
run_and_save "tail -500 /var/log/cloud-init-output.log 2>/dev/null" "21_cloud_init_output.log.txt"

# 7. Logs de PHP
echo "━━━ PHP LOGS ━━━"
run_and_save "tail -500 /var/log/php-fpm/error.log 2>/dev/null" "22_php_fpm_error.log.txt"
run_and_save "tail -500 /var/log/php-fpm/www-error.log 2>/dev/null" "23_php_www_error.log.txt"

# 8. Logs de Nginx
echo "━━━ NGINX LOGS ━━━"
run_and_save "tail -500 /var/log/nginx/error.log 2>/dev/null" "24_nginx_error.log.txt"
run_and_save "tail -500 /var/log/nginx/access.log 2>/dev/null" "25_nginx_access.log.txt"
run_and_save "cat /etc/nginx/conf.d/elasticbeanstalk/*.conf 2>/dev/null" "26_nginx_eb_config.txt"

# 9. Estado de servicios
echo "━━━ SERVICE STATUS ━━━"
run_and_save "systemctl status php-fpm" "27_php_fpm_status.txt"
run_and_save "systemctl status nginx" "28_nginx_status.txt"
run_and_save "systemctl list-units --type=service" "29_all_services.txt"

# 10. Logs de Laravel
echo "━━━ LARAVEL LOGS ━━━"
run_and_save "tail -500 /var/app/current/storage/logs/laravel.log 2>/dev/null" "30_laravel.log.txt"
run_and_save "ls -lah /var/app/current/storage/logs/" "31_storage_logs_list.txt"

# 11. Configuración de la aplicación
echo "━━━ APPLICATION CONFIG ━━━"
run_and_save "head -50 /var/app/current/.env" "32_env_sample.txt"
run_and_save "cat /var/app/current/.ebextensions/*.config" "33_ebextensions_config.txt"

# 12. DNF/YUM
echo "━━━ PACKAGE MANAGER ━━━"
run_and_save "dnf list installed | grep -i sqlsrv" "34_installed_sqlsrv.txt"
run_and_save "dnf list available | grep -i sqlsrv 2>/dev/null" "35_available_sqlsrv.txt"
run_and_save "dnf repolist" "36_dnf_repos.txt"

# 13. Prueba de conectividad
echo "━━━ CONNECTIVITY TEST ━━━"
run_and_save "ping -c 3 8.8.8.8" "37_internet_test.txt"
run_and_save "curl -I https://packages.microsoft.com 2>/dev/null" "38_microsoft_repo_test.txt"

# 14. Resumen
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║  DIAGNOSTICS COLLECTED                                       ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📁 All logs saved to: $OUTPUT_DIR/"
echo "📊 Total files: $(ls $OUTPUT_DIR | wc -l)"
echo ""
echo "Key files to check:"
echo "  1. 06_php_modules.txt - Ver si sqlsrv está cargado"
echo "  2. 17_eb_install_logs_content.txt - Logs de instalación de drivers"
echo "  3. 22_php_fpm_error.log.txt - Errores de PHP-FPM"
echo "  4. 24_nginx_error.log.txt - Errores de Nginx"
echo "  5. 30_laravel.log.txt - Errores de Laravel"
echo ""
echo "Quick check:"
grep -l "sqlsrv" $OUTPUT_DIR/* 2>/dev/null || echo "⚠️  sqlsrv not found in any logs"
echo ""
echo "✓ Diagnostics ready for analysis"
echo "📦 Compress for sharing: tar -czf $OUTPUT_DIR.tar.gz $OUTPUT_DIR/"
