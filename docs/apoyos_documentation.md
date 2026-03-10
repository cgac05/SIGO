# Documentación técnica: Pantalla "Apoyos"

Fecha: 2026-03-10

Resumen: este documento reúne los archivos implicados en la pantalla de administración de "Apoyos", describe su propósito, puntos clave, validaciones, interacciones y observaciones importantes encontradas en el código.

---

**Archivos principales**

- Archivo: [app/Http/Controllers/ApoyoController.php](app/Http/Controllers/ApoyoController.php)
  - Propósito: controlador que gestiona la UI de administración de apoyos, la API JSON para recarga por AJAX y la creación transaccional de apoyos (con inserciones auxiliares en finanzas/inventario y requisitos).
  - Métodos clave:
    - `index()` — carga `$apoyos` y `$tiposDocumentos` y devuelve la vista `apoyos.index`.
    - `list()` — devuelve JSON con los apoyos para `/apoyos/list` (campos: `id_apoyo`, `nombre_apoyo`, `tipo_apoyo`, `monto_maximo`, `activo`).
    - `store(Request $request)` — valida, guarda en `Apoyos`, inserta en `BD_Finanzas` o `BD_Inventario` según `tipo_apoyo`, inserta filas en `Requisitos_Apoyo` si se especificaron, mantiene transacción DB y devuelve JSON `{ success, message }`.
  - Validaciones importantes (en `store`):
    - `nombre_apoyo`: required|string|max:100
    - `tipo_apoyo`: required|in:Económico,Especie
    - `fechaInicio`, `fechafin`: required|date
    - `foto_ruta`: nullable|image|max:5120
    - `documentos_requeridos.*`: integer|exists:Cat_TiposDocumento,id_tipo_doc
  - Observaciones: maneja `activo` tomando en cuenta que el formulario envía un hidden y un checkbox; procesa la imagen con `store('apoyos','public')` y guarda la ruta con prefijo `storage/`.

-- Comentarios explicativos (prioridad: consultas y secciones importantes) --

- `index()` — Consulta:
  - `DB::table('Apoyos')->orderBy('id_apoyo', 'desc')->get()` devuelve los apoyos en forma ligera (stdClass).
  - Si la vista muestra muchos registros, considerar paginación o seleccionar sólo columnas necesarias.

- `list()` — API AJAX:
  - Diseñada para llamadas frecuentes; devuelve campos mínimos: `id_apoyo`, `nombre_apoyo`, `tipo_apoyo`, `monto_maximo`, `activo`.
  - Mantenerla ligera para optimizar el renderizado cliente.

- `store()` — Transacción y consultas críticas:
  - Flujo:
    1. Validación del request (reglas definidas en el controlador).
    2. `DB::beginTransaction()` para asegurar atomicidad.
    3. Insertar en `Apoyos` con Eloquent (`Apoyo::create`).
    4. Insertar en `BD_Finanzas` o `BD_Inventario` según `tipo_apoyo` (Query Builder).
    5. Insertar filas en `Requisitos_Apoyo` por cada documento seleccionado.
    6. `DB::commit()` si todo OK o `DB::rollBack()` en caso de excepción.
  - Nota: las inserciones auxiliares se realizan con `DB::table()->insert()` para operaciones sencillas y rendimiento.

Estas observaciones están reflejadas y comentadas inline en el código fuente bajo `app/Http/Controllers/ApoyoController.php`.

- Archivo: [app/Models/Apoyo.php](app/Models/Apoyo.php)
  - Propósito: modelo Eloquent para la tabla `Apoyos`.
  - Configuración clave:
    - `$table = 'Apoyos'`, `$primaryKey = 'id_apoyo'`,
    - `public $timestamps = false` (las fechas son manejadas manualmente en el controlador),
    - `$fillable` contiene `nombre_apoyo, tipo_apoyo, monto_maximo, activo, fecha_Creacion, fechaInicio, fechafin, foto_ruta, descripcion`,
    - `$dateFormat` personalizado `Ymd H:i:s`.
  - Notas: el modelo permite asignación masiva de los campos usados por `store()`.

- Archivo: [database/migrations/2026_03_08_000001_create_apoyos_and_aux_tables.php](database/migrations/2026_03_08_000001_create_apoyos_and_aux_tables.php)
  - Propósito: crea las tablas `Apoyos`, `BD_Finanzas` y `BD_Inventario`.
  - Estructura creada:
    - `Apoyos`: `id_apoyo` (PK), `nombre_apoyo` (string 100), `tipo_apoyo` (string 20), `monto_maximo` (decimal 19,4), `activo` (boolean default true).
    - `BD_Finanzas`: `id_finanza`, `fk_id_apoyo`, `monto_asignado`, `monto_ejercido` + FK -> `Apoyos(id_apoyo)` ON DELETE CASCADE.
    - `BD_Inventario`: `id_inventario`, `fk_id_apoyo`, `stock_actual` + FK -> `Apoyos(id_apoyo)` ON DELETE CASCADE.
  - Observación operativa: logs muestran errores de "There is already an object named 'Apoyos'" al correr migraciones repetidas; usar `migrate:rollback` o revisar el estado DB antes de volver a ejecutar.

- Archivo: [app/Models/BDFinanzas.php](app/Models/BDFinanzas.php)
  - Propósito: modelo para `BD_Finanzas` (registro financiero por apoyo). `$fillable`: `fk_id_apoyo`, `monto_asignado`, `monto_ejercido`.

- Archivo: [app/Models/BDInventario.php](app/Models/BDInventario.php)
  - Propósito: modelo para `BD_Inventario` (stock para apoyos en especie). `$fillable`: `fk_id_apoyo`, `stock_actual`.

**Vistas / Front-end**

- Archivo: [resources/views/apoyos/index.blade.php](resources/views/apoyos/index.blade.php)
  - Propósito: interfaz de administración de apoyos. Incluye:
    - Tabla que muestra la lista de apoyos recibida en `$apoyos` (renderizado en servidor) y una función JS para recargar vía AJAX.
    - Botón "Nuevo Apoyo" que abre un `<x-modal name="apoyoModal">` con el formulario de creación.
    - Formulario (`id="apoyo-form"`) con `enctype="multipart/form-data"` que se intercepta por JavaScript y se envía a `route('apoyos.store')` como `FormData`.
    - Campos principales: `nombre_apoyo`, `tipo_apoyo` (Económico/Especie), `monto_maximo`, `monto_inicial_asignado`, `stock_inicial`, `fechaInicio`, `fechafin`, `foto_ruta`, `descripcion`, `documentos_requeridos[]`, `activo`.
    - Script inline:
      - `listUrl = route('apoyos.list')` y `storeUrl = route('apoyos.store')`.
      - `reloadApoyos()` realiza GET a `listUrl` y renderiza filas en `#apoyos-tbody`.
      - Intercepta submit de `#apoyo-form`, envía por `fetch` con cabeceras `X-CSRF-TOKEN` y `Accept: application/json`.
      - Maneja respuestas JSON `{ success, message }` mostrando un modal de éxito y recargando la lista.
  - Observaciones: la vista incluye un pequeño helper PHP `$currency` para formatear montos.

- Archivo: [resources/views/solicitudes/registrar.blade.php](resources/views/solicitudes/registrar.blade.php)
  - Propósito: vista que permite a un beneficiario registrar una `Solicitud` seleccionando un `Apoyo` y subiendo documentos requeridos.
  - Integración: la ruta `/Registrar-Solicitud` inyecta `apoyosJson` con la lista de apoyos activos y sus `requisitos` (datos de `Requisitos_Apoyo` join `Cat_TiposDocumento`), que `registrar.blade.php` usa en Alpine para mostrar inputs dinámicos de archivos.
  - Controlador relacionado: [app/Http/Controllers/SolicitudController.php](app/Http/Controllers/SolicitudController.php) — método `guardar()` que crea la solicitud y guarda los archivos en `Documentos_Expediente`.

**Rutas**

- Archivo: [routes/web.php](routes/web.php)
  - Rutas relevantes para apoyos:
    - `GET /apoyos` -> `ApoyoController@index` (nombre: `apoyos.index`)
    - `POST /apoyos` -> `ApoyoController@store` (nombre: `apoyos.store`)
    - `GET /apoyos/list` -> `ApoyoController@list` (nombre: `apoyos.list`)
  - Ruta auxiliar: `GET /Registrar-Solicitud` prepara e inyecta `apoyosJson` para la vista de solicitudes.

**Componentes Blade reutilizados**

- [resources/views/components/modal.blade.php](resources/views/components/modal.blade.php)
  - Modal genérico con prop `name` que escucha eventos `open-modal` y `close-modal` para abrir/cerrar. Controla foco y bloqueo de scroll.

- Botones y campos:
  - [resources/views/components/primary-button.blade.php](resources/views/components/primary-button.blade.php)
  - [resources/views/components/secondary-button.blade.php](resources/views/components/secondary-button.blade.php)
  - [resources/views/components/input-label.blade.php](resources/views/components/input-label.blade.php)
  - [resources/views/components/text-input.blade.php](resources/views/components/text-input.blade.php)
  - [resources/views/components/input-error.blade.php](resources/views/components/input-error.blade.php)

**Tablas y relaciones DB implicadas**

- `Apoyos` (tabla maestra).
- `BD_Finanzas` (fk a `Apoyos`) — usado si `tipo_apoyo` es `Económico`.
- `BD_Inventario` (fk a `Apoyos`) — usado si `tipo_apoyo` es `Especie`.
- `Requisitos_Apoyo` (relación apoyo ↔ tipo documento) y `Cat_TiposDocumento` (catálogo de documentos).
- `Solicitudes`, `Documentos_Expediente` — relacionados con el flujo de solicitudes.

**Errores y puntos a revisar**

- Migraciones: logs muestran repetidos errores de creación de `Apoyos` si la migración se ejecuta más de una vez sin rollback. Antes de ejecutar migraciones en desarrollo, comprobar existencia de tablas o usar `Schema::hasTable` si se requiere idempotencia.

- Vista: hay entradas en `storage/logs/laravel.log` con "Undefined constant 'open' (View: .../apoyos/index.blade.php)" — revisar la sintaxis Blade/JS en esa vista (posible etiqueta `@php` o mal uso de directivas). En mi inspección actual no veo la constante `open` explícita, pero el error puede provenir de una vista compilada o de caracteres `<` mal colocados (logs también muestran `syntax error, unexpected token "<"` en el controlador en un momento; revisar cambios recientes y archivos cacheados en `storage/framework/views`).

- Formulario/JS: el front espera respuestas JSON con `{ success, message }`. Si se hace una petición no-AJAX (form submit normal), `store()` en el controlador siempre devuelve JSON; esto es coherente con el uso AJAX de la vista, pero si se espera un redirect para flujos no-AJAX, habría que adaptar la respuesta según `wantsJson()`.

**Recomendaciones rápidas**

- Migración: si deseas volver a aplicar la migración en entorno local, ejecutar:

```bash
php artisan migrate:rollback --step=1
php artisan migrate --path=database/migrations/2026_03_08_000001_create_apoyos_and_aux_tables.php
```

- Debug vista: limpiar cache de vistas si aparecen errores extraños:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

- Ajuste de respuestas: si quieres soportar tanto AJAX como formularios normales en `store()`, envolver la respuesta final así:

```php
if ($request->wantsJson()) {
    return response()->json(['success' => true, 'message' => '...']);
}
return redirect()->route('apoyos.index')->with('success', 'Apoyo registrado correctamente.');
```

---

¿Deseas que también genere un diagrama simple de relaciones (entidad-relación) o que agregue pruebas unitarias básicas para `ApoyoController`?