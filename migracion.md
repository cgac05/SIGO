Guía de Adaptación: Aplicación PWA SIGO v2.0 (Laravel + Socialite)

Este documento contiene las instrucciones de programación para refactorizar los módulos de autenticación y registro de la PWA, adaptándolos a la arquitectura de Identidad Unificada.

1. Reconfiguración de Modelos (Eloquent)

El agente debe actualizar los modelos para reflejar la relación 1:1 y las llaves primarias manuales.

User.php (Modelo Principal)

// app/Models/User.php
protected $table = 'Usuarios';
protected $primaryKey = 'id_usuario';
protected $fillable = [
    'email', 'password_hash', 'tipo_usuario', 
    'google_id', 'google_token', 'google_refresh_token', 'google_avatar', 'activo'
];

public function personal() {
    return $this->hasOne(Personal::class, 'fk_id_usuario', 'id_usuario');
}

public function beneficiario() {
    return $this->hasOne(Beneficiario::class, 'fk_id_usuario', 'id_usuario');
}


Personal.php y Beneficiario.php

Personal: PK numero_empleado (string), $incrementing = false.

Beneficiario: PK curp (string), $incrementing = false.

Ambos deben tener belongsTo(User::class, 'fk_id_usuario', 'id_usuario').

2. Validación de CURP y Edad (Lógica de Negocio)

Implementar el autómata de validación en una regla personalizada (App\Rules\CurpValida).

Requerimientos de la Regla:

Regex: ^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$.

Cálculo de Edad: Extraer fecha de nacimiento (posiciones 5-10).

Restricción: Validar que la edad esté en el rango [12, 29] años. Si no cumple, rechazar el registro con un mensaje informativo sobre la ley de juventud.

3. Módulo de Autenticación Híbrida

A. Refactorización de Login Manual

Adaptar AuthenticatedSessionController para buscar en la tabla Usuarios.

Seguridad: Validar el token de reCAPTCHA v3 enviado desde el frontend (PWA) antes de consultar la base de datos.

Flujo: Si las credenciales son correctas, verificar el tipo_usuario para redirigir al dashboard correspondiente.

B. Implementación de Google Socialite

Redirect: Socialite::driver('google')->redirect().

Callback Logic:

Buscar usuario por google_id o email.

Si no existe, crear registro en Usuarios con tipo_usuario = 'Beneficiario'.

Flujo de Registro Incompleto: Si un usuario entra por Google pero no tiene un registro vinculado en la tabla Beneficiarios, redirigir forzosamente a /registro/completar-perfil.

4. Módulo de Registro (Dos Pasos)

El registro simple debe evolucionar a un proceso atómico (Transaction):

Paso 1 (Identidad): Crear registro en Usuarios.

Paso 2 (Perfil): - Para beneficiarios: Capturar CURP, teléfono y datos personales. Aplicar la regla CurpValida.

Para personal: El registro suele ser administrativo, pero debe vincularse mediante el numero_empleado.

5. Adaptación PWA y UI/UX

Insignia reCAPTCHA: Asegurar que el script se cargue en la vista de Login y Registro de la PWA.

Botón de Google: Integrar un botón de "Continuar con Google" que mantenga la estética de la PWA.

Persistencia de Sesión: Asegurar que el Remember Me funcione correctamente con la nueva tabla Usuarios para usuarios móviles.

Validaciones Frontend: Implementar validación en tiempo real de la CURP en el formulario para mejorar la UX antes de enviar al servidor.

6. Auditoría

Actualizar el AuditObserver o la lógica de bitácora para que fk_id_usuario capture el id_usuario de la sesión activa de la tabla unificada.

Instrucción para la IA: Priorizar la integridad referencial en SQL Server y asegurar que el middleware de Laravel verifique si el perfil del beneficiario está completo antes de permitir el acceso a las funciones de solicitudes de apoyo.

A continuación se muestra el modelo de datos actual de la base de datos
-- =============================================
-- 1. CATÁLOGOS GLOBALES
-- =============================================

CREATE TABLE Cat_Roles (
    id_rol INT PRIMARY KEY,
    nombre_rol NVARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE Cat_EstadosSolicitud (
    id_estado INT IDENTITY(1,1) PRIMARY KEY,
    nombre_estado NVARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE Cat_Prioridades (
    id_prioridad INT PRIMARY KEY,
    nivel NVARCHAR(20) NOT NULL UNIQUE
);

-- =============================================
-- 2. IDENTIDAD UNIFICADA (SEGURIDAD Y GOOGLE)
-- =============================================

CREATE TABLE Usuarios (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    email NVARCHAR(100) UNIQUE NOT NULL,
    password_hash NVARCHAR(255) NULL, -- Compatible con Laravel Bcrypt/Argon2
    tipo_usuario NVARCHAR(20) NOT NULL CHECK (tipo_usuario IN ('Personal', 'Beneficiario')),
    -- Campos para Integración con Google
    google_id NVARCHAR(255) UNIQUE NULL,
    google_token NVARCHAR(MAX) NULL,
    google_refresh_token NVARCHAR(MAX) NULL,
    google_avatar NVARCHAR(MAX) NULL,
    activo BIT DEFAULT 1,
    fecha_creacion DATETIME2 DEFAULT GETDATE(),
    ultima_conexion DATETIME2
);

-- =============================================
-- 3. ACTORES (ESPECIALIZACIÓN)
-- =============================================

CREATE TABLE Personal (
    numero_empleado VARCHAR(15) PRIMARY KEY, -- PK Natural (No incremental)
    fk_id_usuario INT UNIQUE NOT NULL,
    nombre NVARCHAR(150) NOT NULL,
    apellido_paterno NVARCHAR(50) NOT NULL,
    apellido_materno NVARCHAR(50) NOT NULL,
    fk_rol INT NOT NULL,
    puesto NVARCHAR(100),
    CONSTRAINT FK_Personal_Usuario FOREIGN KEY (fk_id_usuario) REFERENCES Usuarios(id_usuario),
    CONSTRAINT FK_Personal_Rol FOREIGN KEY (fk_rol) REFERENCES Cat_Roles(id_rol)
);

CREATE TABLE Beneficiarios (
    curp CHAR(18) PRIMARY KEY, -- Optimizado: Longitud fija
    fk_id_usuario INT UNIQUE NOT NULL,
    nombre NVARCHAR(150) NOT NULL,
    apellido_paterno NVARCHAR(50) NOT NULL,
    apellido_materno NVARCHAR(50) NOT NULL,
    telefono NVARCHAR(15),
    fecha_nacimiento DATE NOT NULL,
    genero NVARCHAR(10),
    fecha_registro DATETIME2 DEFAULT GETDATE(),
    acepta_privacidad BIT DEFAULT 0,
    CONSTRAINT FK_Beneficiarios_Usuario FOREIGN KEY (fk_id_usuario) REFERENCES Usuarios(id_usuario)
);

-- =============================================
-- 4. GESTIÓN DE APOYOS (AÑO FISCAL)
-- =============================================

CREATE TABLE Apoyos (
    id_apoyo INT IDENTITY(1,1) PRIMARY KEY,
    nombre_apoyo NVARCHAR(100) NOT NULL,
    anio_fiscal INT NOT NULL DEFAULT YEAR(GETDATE()), -- Requisito: Año Fiscal
    tipo_apoyo NVARCHAR(20) CHECK (tipo_apoyo IN ('Económico', 'Especie')),
    monto_maximo MONEY DEFAULT 0,
    cupo_limite INT NULL,
    activo BIT DEFAULT 1,
    fecha_inicio DATETIME2 NOT NULL,
    fecha_fin DATETIME2 NOT NULL
);

-- =============================================
-- 5. TRÁMITES Y AUDITORÍA UNIFICADA
-- =============================================

CREATE TABLE Solicitudes (
    folio INT IDENTITY(1000,1) PRIMARY KEY,
    fk_curp CHAR(18) NOT NULL,
    fk_id_apoyo INT NOT NULL,
    fk_id_estado INT DEFAULT 1, -- Relacionado a Cat_EstadosSolicitud
    fk_id_prioridad INT,
    fecha_creacion DATETIME2 DEFAULT GETDATE(),
    fecha_actualizacion DATETIME2 DEFAULT GETDATE(),
    observaciones_internas NVARCHAR(MAX), -- Datos pendientes sugeridos
    FOREIGN KEY (fk_curp) REFERENCES Beneficiarios(curp),
    FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo),
    FOREIGN KEY (fk_id_estado) REFERENCES Cat_EstadosSolicitud(id_estado),
    FOREIGN KEY (fk_id_prioridad) REFERENCES Cat_Prioridades(id_prioridad)
);

CREATE TABLE Bitacora_Auditoria (
    id_log BIGINT IDENTITY(1,1) PRIMARY KEY,
    fk_id_usuario INT NOT NULL, -- Ahora registra a CUALQUIER usuario
    tabla_afectada NVARCHAR(50),
    accion NVARCHAR(20),
    valor_anterior NVARCHAR(MAX),
    valor_nuevo NVARCHAR(MAX),
    fecha_hora DATETIME2 DEFAULT GETDATE(),
    ip_terminal NVARCHAR(45),
    FOREIGN KEY (fk_id_usuario) REFERENCES Usuarios(id_usuario)
);


----
CREATE TABLE BD_Finanzas (
    id_presupuesto INT IDENTITY(1,1) PRIMARY KEY,
    fk_id_apoyo INT NOT NULL UNIQUE,
    monto_asignado MONEY NOT NULL DEFAULT 0,
    monto_ejercido MONEY NOT NULL DEFAULT 0,
    CONSTRAINT CHK_Presupuesto_Positivo CHECK (monto_asignado >= 0 AND monto_ejercido >= 0),
    CONSTRAINT CHK_Saldo_Coherente CHECK (monto_ejercido <= monto_asignado),
    FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo)
);

CREATE TABLE BD_Inventario (
    id_inventario INT IDENTITY(1,1) PRIMARY KEY,
    fk_id_apoyo INT NOT NULL UNIQUE,
    stock_actual INT NOT NULL DEFAULT 0,
    CONSTRAINT CHK_Stock_Positivo CHECK (stock_actual >= 0),
    FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo)
);
CREATE TABLE Requisitos_Apoyo (
    fk_id_apoyo INT NOT NULL,
    fk_id_tipo_doc INT NOT NULL,
    es_obligatorio BIT DEFAULT 1,
    PRIMARY KEY (fk_id_apoyo, fk_id_tipo_doc),
    FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo),
    FOREIGN KEY (fk_id_tipo_doc) REFERENCES Cat_TiposDocumento(id_tipo_doc)
);
CREATE TABLE Requisitos_Apoyo (
    fk_id_apoyo INT NOT NULL,
    fk_id_tipo_doc INT NOT NULL,
    es_obligatorio BIT DEFAULT 1,
    PRIMARY KEY (fk_id_apoyo, fk_id_tipo_doc),
    FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo),
    FOREIGN KEY (fk_id_tipo_doc) REFERENCES Cat_TiposDocumento(id_tipo_doc)
);

alter table apoyos add foto_ruta nvarchar(max);
alter table apoyos add descripcion nvarchar(max);