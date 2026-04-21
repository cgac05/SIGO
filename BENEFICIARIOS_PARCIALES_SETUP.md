# BENEFICIARIOS PARCIALES - INSTRUCCIONES DE CONFIGURACIÓN

## ❌ PROBLEMA ACTUAL
La BD tiene restricciones que impiden crear beneficiarios sin usuario del sistema.

## ✅ SOLUCIÓN
Dos columnas necesitan ser NULLABLE:
1. `Beneficiarios.fk_id_usuario` - Permite registrar beneficiarios sin usuario del sistema
2. `claves_seguimiento_privadas.beneficiario_id` - Permite generar claves para beneficiarios sin registro

---

## 📋 PASO 1: Ejecuta esto en SQL Server Management Studio

Abre SQL Server Management Studio → copia y pega esto:

```sql
-- Beneficiarios: permitir fk_id_usuario = NULL
ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL;

-- claves_seguimiento_privadas: permitir beneficiario_id = NULL
ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NULL;
```

Presiona **F5** para ejecutar.

---

## 📋 PASO 2: Verifica los cambios (Opcional)

Si quieres verificar que los cambios se aplicaron correctamente, ejecuta:

```sql
SELECT COLUMN_NAME, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME IN ('Beneficiarios', 'claves_seguimiento_privadas')
AND COLUMN_NAME IN ('fk_id_usuario', 'beneficiario_id')
ORDER BY TABLE_NAME;
```

Deberías ver:
- Beneficiarios.fk_id_usuario | YES
- claves_seguimiento_privadas.beneficiario_id | YES

---

## 🎯 QUÉ SUCEDE DESPUÉS

Una vez que ejecutes esos SQL, el sistema funcionará así:

### Caso: Beneficiario NO REGISTRADO (entrada manual)
1. Admin entra en http://localhost:8000/admin/caso-a/momento-uno
2. Busca un CURP pero NO lo encuentra en el sistema
3. Muestra el formulario para captura manual
4. Admin ingresa:
   - Nombre Completo
   - CURP (obligatorio, 18 caracteres)
   - Email (opcional)
   - Teléfono (opcional, formato: (123) 456-7890)
5. Admin selecciona apoyo y documentos
6. **AL GUARDAR:**
   - ✅ Crea registro PARCIAL en Beneficiarios (sin usuario)
   - ✅ Crea Solicitud con ese CURP
   - ✅ Genera folio + clave privada
   - ✅ Redirige a página de resumen

### Caso: Beneficiario REGISTRADO
- Funciona igual que antes
- Usa CURP del registro existente
- Vincula a usuario del sistema

---

## 📝 CAMBIOS DE CÓDIGO

Estos archivos ya fueron actualizados:
- ✅ app/Services/CasoADocumentService.php
  - Método `crearBeneficiarioPartial()` - Crea registro sin usuario
  - Método `crearExpedientePresencial()` - Maneja ambos casos
  - Permite NULL en beneficiario_id_clave

---

## ⚠️ IMPORTANTE

La migración de Laravel está lista para ejecutar después de hacer los cambios SQL manuales:
```bash
php artisan migrate --path=database/migrations/2026_04_18_165235_make_beneficiario_id_nullable_in_claves_seguimiento_privadas.php
```

Esto actualiza el registro de migraciones de Laravel, pero **ya habrán sido ejecutados los cambios SQL en la BD**.
