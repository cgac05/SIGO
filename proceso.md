Especificaciones Técnicas: Proceso de Cierre y Validación SIGO

1. Contexto del Proyecto

Este documento detalla la arquitectura y diseño para la conclusión del flujo de solicitudes en el sistema SIGO. El sistema debe transitar de una captación de datos a un flujo de trabajo (workflow) institucional que garantice la integridad de la información, la trazabilidad administrativa y el cumplimiento de las normativas de transparencia vigentes. Esta fase integra la revisión documental, la validación directiva mediante firma electrónica y el cierre financiero.

2. Gestión de Hitos Dinámicos (Control del Proceso)

El proceso se rige por la tabla dbo.Hitos_Apoyo. El diseño debe permitir una visualización tipo stepper o línea de tiempo interactiva.

Diseño y Comportamiento UI:

Componente Timeline: Utilizar un componente de línea de tiempo con Tailwind (space-y-8). Los hitos completados en verde (text-green-600), el actual en azul pulsante (animate-pulse) y futuros en gris.

Validación de Bloqueo: El backend debe rechazar acciones si el hito actual no corresponde.

Reglas de Negocio:

Validar rangos de fecha_inicio y fecha_fin.

Hitos base: PUBLICACION, RECEPCION, ANALISIS_ADMIN, RESULTADOS y CIERRE.

3. Fase 1: Revisión Administrativa y Expediente Digital

Rol: Personal Administrativo.

Requerimientos Técnicos:

Visor de Documentos Dual: Pantalla dividida. Izquierda: checklist de requisitos; Derecha: visor Google Drive (webViewLink).

Acciones:

Aprobar: Service Job ejecuta Copy del archivo a la carpeta Expediente_Oficial_SIGO_[ID_APOYO].

Inmutabilidad: Almacenar el file_id de la copia para que el expediente sea independiente del usuario.

Descarte: Si permite_correcciones = 0, el rechazo es definitivo.

4. Fase 2: Análisis Directivo y Firma Electrónica

Rol: Directivo.

Protocolo de Firma:

Re-autenticación: Solicitar contraseña en modal backdrop-blur-sm.

Sello Digital: SHA-256 de ID_Solicitud + JSON_Documentos + ID_Directivo + Salt.

CUV: Código de 16-20 caracteres para transparencia.

Logs: Registro de IP, Navegador y Timestamp del servidor.

5. Fase 3: Recursos Financieros y Salida de Recurso

Rol: Recursos Financieros.

Datos y Diseño:

Entrada: Máscaras para fechas y montos.

Transparencia: Exportación a CSV/XLS para el Padrón de Beneficiarios.

QR: PDF final con QR hacia la URL de validación pública.

6. Fase 4: Módulos de Valor Agregado (Sugerencias Pro)

6.1. Motor de Notificaciones Omnicanal

Eventos: Notificar al beneficiario cuando su documento sea "Observado" o su apoyo sea "Autorizado".

Canales: Correo electrónico (Laravel Mail) y Notificaciones en tiempo real (Pusher/Websockets) dentro del sistema.

6.2. Foliado Institucional Automático

Implementar un generador de folios que sigan la nomenclatura: SIGO-[AÑO]-[MUNICIPIO]-[CONSECUTIVO] (ej. SIGO-2026-XAL-0015).

6.3. Foliado y Marcas de Agua en Documentos

Al copiar archivos al Expediente Oficial, utilizar una librería (como Spatie/Pdf-Watermark) para estampar en el PDF: "Documento Validado por SIGO - [Fecha] - CUV: [Código]".

6.4. Dashboard de Indicadores (KPIs) para el Directivo

Gráficas (Chart.js): * Presupuesto Total vs. Ejercido.

Solicitudes por Género/Zona Geográfica.

Tiempo promedio de respuesta administrativa.

7. Fase 5: Transparencia Proactiva y Seguridad

7.1. Portal Público de Validación

Una ruta pública sigo.gob.mx/validar donde cualquier ciudadano pueda ingresar el CUV y verificar:

Nombre del apoyo.

Estatus (Autorizado/Entregado).

Monto otorgado (opcional según política de datos personales).

7.2. Respaldo de Base de Datos (Snapshot de Solicitud)

Al momento del cierre financiero, guardar un Snapshot en formato JSON de toda la solicitud en una tabla de Historico_Cierre. Esto evita que cambios accidentales en otras tablas afecten el registro legal del apoyo entregado.

8. Estructura de Base de Datos (Adiciones)

Tabla: dbo.Seguimiento_Solicitud

Campo

Tipo

Descripción

id_seguimiento

INT PK

Autoincrementable.

fk_id_solicitud

INT FK

Relación con la solicitud.

sello_digital

NVARCHAR(64)

Hash de la transacción.

metadata_seguridad

JSON

IP, UserAgent, Fingerprint.

Tabla: dbo.Notificaciones

Campo

Tipo

Descripción

id_notificacion

INT PK

Identificador.

fk_id_usuario

INT FK

Destinatario.

mensaje

TEXT

Contenido de la alerta.

leido

BIT

Estatus de lectura.