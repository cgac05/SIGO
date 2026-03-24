Documento de Instrucciones para Agente de Desarrollo IA: Migración Vista de Apoyos (SIGO)

1. Contexto del Proyecto

Se requiere finalizar la migración de los módulos de "Apoyos" en el sistema SIGO. Actualmente existen interfaces separadas para Directivos y Beneficiarios. El objetivo es unificar ambas funcionalidades en una Vista Única de Apoyos que detecte el rol del usuario y presente las acciones correspondientes.

2. Requerimientos de Interfaz (UI/UX)

Layout: Pantalla completa (Full Height/Width).

Organización: Estructura basada en columnas.

Componentes Visuales:

Columna Central/Principal: Listado o gestión de apoyos.

Columna Lateral (Chat de Comentarios): Se debe implementar un chat únicamente a nivel visual (Mockup UI). No existe integración con el modelo de datos para esta funcionalidad aún.

Estilo: Mantener la línea gráfica institucional del sistema SIGO.

3. Lógica de Negocio y Roles

La vista debe comportarse de forma dinámica según el perfil que acceda:

A. Perfil Beneficiario

Acción 1 (Solicitar): Botón o formulario para aplicar a un apoyo disponible.

Acción 2 (Cargar): Zona de carga de archivos (Drag & Drop o input) para adjuntar documentación requerida.

Restricción: No debe ver herramientas de creación de convocatorias o edición administrativa.

B. Perfil Directivo

Gestión: Visualización de solicitudes recibidas, estatus de los apoyos y herramientas de creación/edición.

Acceso: Panel completo de administración de la convocatoria.

4. Tareas Técnicas para el Agente

Consolidación de Rutas: Configurar una ruta única /apoyos que reemplace las vistas individuales previas.

State Management del Rol: Implementar la lógica que identifique el roleID o perfil del usuario actual para condicionar el renderizado.

Layout de Columnas: * Crear un contenedor principal con display: flex o grid.

Implementar la sección de chat en un lateral (derecho preferentemente) con un área de mensajes tipo burbuja y un input de texto.

Migración de Componentes: * Extraer los componentes de "Creación" de la vista antigua de directivo.

Extraer los componentes de "Solicitud" y "Carga de archivos" de la vista antigua de beneficiario.

Insertarlos en la nueva estructura unificada.

5. Notas de Implementación (Chat Mock)

Frontend: El chat debe permitir escribir en el input, pero la acción de "enviar" solo debe agregar el mensaje localmente al estado de la vista (sin persistencia en base de datos).

UI: Incluir placeholders o mensajes de ejemplo ("Bienvenido al chat de soporte", "Su documento está en revisión") para demostrar el propósito visual.

6. Criterios de Aceptación

[x] La pantalla ocupa el 100% del viewport.

[x] Un Beneficiario solo puede ver botones de "Solicitar" y "Subir Documentos".

[x] Un Directivo mantiene sus facultades de creación y visualización global.

[x] El chat es visible y estéticamente coherente con el resto del sistema.

[x] No existen errores de consola por dependencias de archivos de las vistas anteriores "incompletas"