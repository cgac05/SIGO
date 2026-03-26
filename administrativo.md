Protocolo de ImplementaciÃ³n: MÃ³dulo Administrativo de VerificaciÃ³n de DocumentaciÃ³n - SIGO

**ESTADO: âœ… IMPLEMENTADO - Listo para usar**

**Fecha de ImplementaciÃ³n**: Marzo 26, 2026  
**Componentes Completados**: 100%

Este documento especifica la implementaciÃ³n de la interfaz y lÃ³gica de backend para el Actor Administrativo. El objetivo es permitir la visualizaciÃ³n, descarga y validaciÃ³n de los documentos cargados por los beneficiarios, independientemente de su origen de almacenamiento.

1. Objetivos del MÃ³dulo Administrativo

VisualizaciÃ³n Unificada: Cargar en una misma vista documentos almacenados localmente (Servidor SIGO/Azure) y documentos vinculados desde Google Drive.

ValidaciÃ³n de Integridad: Implementar un sistema de estatus (Pendiente, Aceptado, Rechazado) para cada documento.

Seguridad y Trazabilidad: Generar un cÃ³digo QR Ãºnico por cada documento verificado para permitir su validaciÃ³n rÃ¡pida en campo o auditorÃ­a fÃ­sica.

2. Requerimientos de Seguridad (Placeholders)

El agente de IA debe emplear los siguientes marcadores de posiciÃ³n para las credenciales, asegurando que no se guarden en el cÃ³digo fuente:

CLIENT_ID_PLACEHOLDER: ID de cliente para el acceso administrativo a la API de Drive.

CLIENT_SECRET_PLACEHOLDER: Secreto para la comunicaciÃ³n del servidor administrativo.

ENCRYPTION_KEY_QR: Clave para cifrar la informaciÃ³n contenida en el cÃ³digo QR.

3. LÃ³gica de Carga de Documentos (Backend)

El agente debe implementar una lÃ³gica de detecciÃ³n de origen basada en el esquema de la base de datos:

3.1 Carga Local (Azure/Local)

Si el registro contiene una ruta de archivo (file_path), el sistema debe generar una URL temporal firmada para permitir la visualizaciÃ³n del archivo en el navegador del administrador sin exponer la ruta real.

3.2 Carga desde Google Drive

Si el registro contiene un google_file_id, el backend debe usar el Token de Acceso Administrativo para solicitar un "link de visualizaciÃ³n" a la API de Google Drive.

Importante: El administrador debe tener permisos de lectura sobre el archivo. La lÃ³gica debe manejar errores de "Acceso Denegado" si el beneficiario revocÃ³ el permiso.

4. Desarrollo de la Vista (Frontend)

El agente debe generar una interfaz limpia y eficiente:

Panel de Lista: Tabla de beneficiarios con indicadores visuales de "Documentos Pendientes".

Visor de Documentos: Un modal o panel lateral que utilice un <iframe> o un visor de PDF integrado para mostrar el documento sin forzar la descarga inmediata.

Controles de VerificaciÃ³n:

Botones de acciÃ³n rÃ¡pida (Check/Equis).

Campo de "Observaciones" (Obligatorio en caso de rechazo).

5. ImplementaciÃ³n de QR de VerificaciÃ³n

Por cada documento que el administrador marque como "Aceptado", el sistema debe:

Generar un Token de ValidaciÃ³n: Un hash Ãºnico que contenga: ID_Beneficiario + Fecha_VerificaciÃ³n + ID_Admin.

Generar el CÃ³digo QR: Crear una imagen QR que apunte a una ruta pÃºblica de verificaciÃ³n del SIGO: http://localhost:8000/solicitudes/proceso{token}. (de momento para poder seguir en local)

SuperposiciÃ³n (Opcional): El agente debe explorar la posibilidad de generar un PDF de "Acuse de Recibo" que incluya este QR de forma automÃ¡tica.

6. Instrucciones Paso a Paso para la IA

Fase de Datos: Crear/Ajustar el modelo Documento para incluir los campos storage_type (Enum: local, drive), status, admin_observations y verification_token. (verificalo con el modelo en circulaciÃ³n)

Fase de Control: Desarrollar la funciÃ³n showDocument(id) que discrimine el origen y devuelva el stream de datos adecuado.

Fase de Interfaz: DiseÃ±ar la vista administrativa usando los estÃ¡ndares estÃ©ticos de la Plataforma Estatal de Juventud.

Fase de Seguridad: Implementar el middleware que asegure que solo usuarios con el rol ADMIN_ROLE puedan acceder a estas rutas.

Nota de ConfiguraciÃ³n: Se asume que el servidor ya cuenta con la librerÃ­a google/apiclient instalada y una librerÃ­a para generaciÃ³n de QR (como simplesoftwareio/simple-qrcode para Laravel).

muestra una solicitud por una no muestres una lista con todas, agrega filtado por el apoyo, y que el administrativo disponga de un menu en lista para ver que solicitud revisar y que enn la siguiente pantalla se le muestre como ya esta contemplado en /proceso, pero un solo apoyo no como esta ahorita una lista de todos con todos

