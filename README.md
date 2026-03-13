# SIGO - Sistema de Gestión Operativa (INJUVE) 🏢💻

Este es el repositorio oficial de **SIGO**, desarrollado por estudiantes del **Tecnológico Nacional de México, Campus Tepic**. El sistema utiliza Laravel 11, SQL Server y Tailwind CSS para la gestión administrativa del Instituto Nayarita de la Juventud.

---

## 🛠️ Requisitos del Sistema

Antes de iniciar, asegúrate de tener instalado:
* **PHP 8.2+** (Configurado en XAMPP o Laragon)
* **Composer** (Manejador de dependencias de PHP)
* **Node.js & NPM** (Para procesar los estilos de Tailwind)
* **SQL Server** (Express o Developer Edition)
* **Drivers SQL:** Asegúrate de tener habilitadas las extensiones `php_sqlsrv` y `php_pdo_sqlsrv` en tu `php.ini`.

---

## 🚀 Guía de Instalación Rápida

Si acabas de clonar el proyecto o cambiaste a la rama `numeroTelefonoAutomata`, sigue estos pasos en orden:

### 1. Clonar y Cambiar de Rama
```bash
git clone [URL-DEL-REPOSITORIO]
cd sigo
git checkout numeroTelefonoAutomata
APP_NAME=SIGO_INJUVE
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# --- CONFIGURACIÓN DE SQL SERVER (TEC TEPIC) ---
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=BD_SIGO
DB_USERNAME=sa
DB_PASSWORD=TuPasswordSeguro

# --- FIX PARA EVITAR ERRORES DE TABLAS FALTANTES ---
# Usamos 'file' para no requerir las tablas 'sessions' y 'cache' en SQL Server
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file

# Otros servicios (Configuración estándar)
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
MEMCACHED_HOST=127.0.0.1

# Configuración de Correo (Opcional por ahora)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="admin@tectepic.edu.mx"
MAIL_FROM_NAME="${APP_NAME}"

# Configuración de Vite (Frontend)
VITE_APP_NAME="${APP_NAME}"