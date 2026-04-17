# Reporte: Bug de Estado de Solicitudes - Folio 1014 en Subsanación Después de Aprobación

## 🐛 Problema Reportado

Folio 1014 fue **aprobado por admin** (todos documentos aceptados), pero al abrirse en la vista de **directivo**, aparecía como si estuviera **"En Subsanación"** en lugar de "Aprobado".

## 🔍 Causa Raíz

**Error en la tabla de catálogos de estados**: Los IDs en `Cat_EstadosSolicitud` estaban mal mapeados:

| ID BD | Nombre Estado | Significado |
|-------|------|---------|
| 1 | Pendiente | Sin revisar |
| 2 | Validado | Revisado pero pendiente correcciones |
| **3** ⚠️ | **En Subsanación** | Necesita correcciones del beneficiario |
| **4** ✅ | **Aprobado** | Completamente aprobado |
| 5 | Rechazado | No aprobado |

**El bug**: El código actualizaba a `fk_id_estado = 3` cuando aprobaba todos documentos, pero 3 = "En Subsanación", no "Aprobado".

**Ubicaciones del bug**:
- `AdministrativeVerificationService.php` línea 150 (ORIGEN del bug - cuando aprueban documentos)
- `FirmaElectronicaService.php` línea 213
- `ApoyoController.php` línea 1170
- `SolicitudProcesoController.php` líneas 705, 725, 800
- `admin/padron/show.blade.php` línea 277

## ✅ Solución Aplicada

### 1. **Corrección de Código** - Cambiar ID 3 → 4
Se actualizó toda referencia a aprobación para usar `fk_id_estado = 4` en lugar de 3:

```php
// ANTES (❌ incorrecto)
$solicitud->update(['fk_id_estado' => 3]); // En Subsanación

// DESPUÉS (✅ correcto)
$solicitud->update(['fk_id_estado' => 4]); // Aprobado
```

### 2. **Corrección de Datos** - Actualizar BD
Se identificaron y corrigieron 3 solicitudes afectadas:
- **Folio 1000**: 1/1 docs aceptados → Actualizado a estado 4
- **Folio 1007**: 1/1 docs aceptados → Actualizado a estado 4
- **Folio 1014**: 1/1 docs aceptados → Actualizado a estado 4

### 3. **Herramientas Creadas para Monitoreo**

#### Comando: Verificar estado individual
```bash
php artisan verify:estado {folio}
```
Muestra estado, documentos y presupuesto. Detecta discrepancias.

#### Comando: Buscar y corregir todas
```bash
php artisan fix:all-estados
```
Identifica todas las solicitudes con estado incorrecto (ID 3 cuando todos docs aceptados) y permite actualizar en batch.

## 🔄 Flujo Corregido

```
Admin aprueba documento
  ↓
AdministrativeVerificationService.verifyDocument('aceptado')
  ↓
✅ Verifica: ¿TODOS documentos aceptados?
  ↓
  SÍ → Actualiza fk_id_estado = 4 (Aprobado) ✅
  NO → Mantiene estado actual

Directivo ve solicitud
  ↓
SolicitudProcesoController::show() obtiene estado del BD
  ↓
Estado 4 = "Aprobado" ✅ (Correcto)
```

## 📊 Verificación

Después de las correcciones:
- ✅ Folio 1000: `fk_id_estado = 4` (Aprobado)
- ✅ Folio 1007: `fk_id_estado = 4` (Aprobado)
- ✅ Folio 1014: `fk_id_estado = 4` (Aprobado)

Todas las solicitudes ahora muestran "Aprobado" en la vista del directivo cuando todos sus documentos están aceptados.

## 📁 Archivos Modificados

1. `app/Services/AdministrativeVerificationService.php` - Cambio lógica de actualización
2. `app/Services/FirmaElectronicaService.php` - Cambio ID 3→4
3. `app/Http/Controllers/ApoyoController.php` - Cambio búsqueda de aprobadas
4. `app/Http/Controllers/SolicitudProcesoController.php` - Cambios múltiples de búsqueda
5. `resources/views/admin/padron/show.blade.php` - Cambio verificación de estado
6. `app/Console/Commands/VerifyEstadoSolicitud.php` - **Nuevo comando diagnóstico**
7. `app/Console/Commands/FixAllEstadosSolicitudes.php` - **Nuevo comando de corrección**

## 🎯 Impacto

- **Antes**: Solicitudes aprobadas por admin aparecían como "En Subsanación" para directivo
- **Después**: Solicitudes aprobadas muestran estado correcto "Aprobado"
- **Futuro**: Nuevas aprobaciones van directamente a estado 4 (Aprobado)

---

**Fecha de Reporte**: 16 Abril 2026  
**Status**: ✅ RESUELTO  
**Solicitudes Corregidas**: 3 (1000, 1007, 1014)
