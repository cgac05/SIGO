# 📋 GUÍA DE DEMOSTRACIÓN - FOLIO 1008

## ✅ ESTADO ACTUAL
- **Folio:** 1008
- **Estado:** DOCUMENTOS_VERIFICADOS (Listo para firmar)
- **CUV:** NULL (Sin firmar aún)
- **Monto:** $100,000
- **Presupuesto Disponible:** $400,000

## 🔐 CREDENCIALES DE PRUEBA

```
Email:     directivo@test.com
Password:  password123
```

## 📊 DATOS DE LA SOLICITUD

| Campo | Valor |
|-------|-------|
| **Beneficiario CURP** | AICC050509HNTVMHA5 |
| **Nombre Beneficiario** | Director Prueba Test |
| **Apoyo** | Apoyo al talento y emprendimiento estatal (ID: 5) |
| **Monto Entregado** | $100,000 |
| **Presupuesto Solicitado** | $500,000 |
| **Presupuesto Aprobado** | $100,000 |
| **Documentos Verificados** | 1 ✓ |
| **Estado Archivo** | Válido |

## 🔗 FLUJO DE DEMOSTRACIÓN

### 1. **Acceso al Sistema**
```
URL: http://localhost:8000/login
Email: directivo@test.com
Password: password123
```

### 2. **Ir a Solicitudes**
```
URL: http://localhost:8000/solicitudes/proceso
Verás dos pestañas:
  - ⏳ Pendientes de Firma (mostrará folio 1008)
  - ✓ Firmadas (vacía)
```

### 3. **Abrir Solicitud**
```
Haz clic en el folio 1008
Verás toda la información del beneficiario
```

### 4. **Firmar Solicitud**
```
Sección: 🔐 Fase 2: Firma

Pasos:
1. Haz clic en "👁️ Ver Resumen"
   - Aparece modal con información
   - ⚠️ Advertencia sobre autorización
   
2. Cierra el modal
3. Ingresa tu contraseña: password123
4. Haz clic en "✓ Firmar y Generar CUV"

Resultado esperado:
- ✓ Solicitud Firmada Exitosamente
- Aparece el CUV generado (formato: FOLIO-YYYYMMDD-HASH8)
  Ej: 1008-20260413-a1b2c3d4
```

### 5. **Verificar en Bandeja**
```
Regresa a: http://localhost:8000/solicitudes/proceso
Haz clic en pestaña "✓ Firmadas"
Verás folio 1008 con el CUV visible
```

## 🎯 PUNTOS CLAVE A DEMOSTRAR

1. ✅ **Separación de solicitudes:**
   - Pendientes: Las que falta firmar
   - Firmadas: Firmadas con CUV

2. ✅ **Proceso de firma:**
   - Revisión de datos antes de firmar
   - Validación de contraseña
   - Generación de CUV único

3. ✅ **Visualización:**
   - En pendientes muestra: Beneficiario, Apoyo, Monto
   - En firmadas muestra: Beneficiario, Apoyo, CUV

4. ✅ **Interfaz profesional:**
   - Cards responsive
   - Pestañas claras
   - Badges con contadores
   - Diseño limpio con Tailwind CSS

## 🔄 DESPUÉS DE LA DEMOSTRACIÓN

Para volver a preparar para otra demostración:
```bash
php prepare_demo_1008.php
```

Esto resetea el folio 1008 a estado DOCUMENTOS_VERIFICADOS sin firma.

## 📞 SOPORTE DURANTE DEMOSTRACIÓN

Si algo falla:
1. Limpia caché: `php artisan optimize:clear`
2. Recarga página: `Ctrl+Shift+R`
3. Verifica login: Asegúrate de estar logueado como directivo
4. URL correcta: `http://localhost:8000/solicitudes/proceso/1008`

---

**Última actualización:** 13/04/2026 18:40
**Estado:** ✅ LISTO PARA DEMOSTRACIÓN
