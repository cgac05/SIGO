# 🚀 GUÍA DE USO - Bandeja Unificada de Solicitudes

**Implementado:** 13 de Abril, 2026  
**Status:** ✅ LISTO PARA USAR

---

## 📍 ¿DÓNDE ACCEDO?

### Opción 1: URL Directa
```
http://localhost/SIGO/solicitudes/proceso
```

### Opción 2: Desde el Menú
```
1. Login en SIGO
2. Menú principal → Solicitudes
3. Sub-menú → Proceso (o Bandeja)
```

### Requisitos
- ✅ Usuario autenticado
- ✅ Rol: Directivo (2) o Admin (3)

---

## 📋 PANTALLA PRINCIPAL: BANDEJA

### 1. Encabezado
```
"Bandeja de Solicitudes"
Descripción según tu rol (Administrador o Directivo)
```

### 2. Estadísticas Rápidas
```
┌──────────────────┬──────────────────┬──────────────────┐
│ PENDIENTES ⏳    │ APROBADAS ✅     │ RECHAZADAS ✗     │
│ 5 Solicitudes    │ 12 Hoy           │ 2 Hoy            │
└──────────────────┴──────────────────┴──────────────────┘

Datos actuales de lo que necesita tu atención.
```

### 3. Filtros (Opcional)
```
Buscar por:
  [Folio: 1234     ]  ← Número exacto de solicitud
  [Estado: ▼       ]  ← En Análisis | Pendiente Firma | Aprobada | Rechazada
  [Apoyo: ▼        ]  ← Tipo de apoyo (Beca, Útiles, etc)
  [Beneficiario: ___] ← Nombre o apellido

[🔎 Buscar]  [↺ Limpiar]
```

### 4. Tabla de Solicitudes
```
┌──────┬────────────────┬────────────┬──────────┬─────────┬──────────┬────────┐
│Folio │ Beneficiario   │ Apoyo      │ Monto    │ Estado  │ Fecha    │ Acción │
├──────┼────────────────┼────────────┼──────────┼─────────┼──────────┼────────┤
│#1234 │ Juan Pérez     │ Beca       │ $5,000   │ ⏳ Pend │ 10/04/26 │ Ver → │
│#1235 │ Ana García     │ Útiles     │ $2,000   │ ✅ Aprob│ 11/04/26 │ Ver → │
│#1236 │ Carlos López   │ Transporte │ $1,500   │ ✗ Rech │ 12/04/26 │ Ver → │
└──────┴────────────────┴────────────┴──────────┴─────────┴──────────┴────────┘

Página 1 de 5 | [1] [2] [3] [4] [5]
```

### 5. Botón "Ver Detalles →"
Abre la vista completa de la solicitud.

---

## 🔍 VISTA DETALLADA: SOLICITUD COMPLETA

### Encabezado
```
SOLICITUD #1234
Juan Pérez López
CURP: PXXX850101HXXX001

[Estado: ✅ APROBADA] o [⏳ EN ANÁLISIS] o [✗ RECHAZADA]
```

### Sección Izquierda (2/3 del ancho)

#### 1. Información General
```
┌─────────────────────────────────────────┐
│ 📋 INFORMACIÓN GENERAL                │
├─────────────────────────────────────────┤
│ Apoyo Solicitado:    Beca Escolar       │
│ Monto Solicitado:    $5,000             │
│ Fecha de Solicitud:  10/04/2026 14:30   │
│ CUV:                 abc123def456...    │
│                      (si ya está firmada)
└─────────────────────────────────────────┘
```

#### 2. Documentos Enviados
```
┌─────────────────────────────────────────┐
│ 📄 DOCUMENTOS ENVIADOS                │
├─────────────────────────────────────────┤
│ 📕 Comprobante_Escolar.pdf              │
│    [👁️ Ver] [⬇️ Descargar]            │
│                                         │
│ 🖼️  Foto_Identificacion.jpg            │
│    [👁️ Ver] [⬇️ Descargar]            │
│                                         │
│ 📎 Constancia_Domicilio.docx           │
│    [👁️ Ver] [⬇️ Descargar]            │
└─────────────────────────────────────────┘

Puedes:
  - Ver archivos (abrirá en nueva ventana/tab)
  - Descargar (guardará en tu computadora)
```

#### 3. Historial de Apoyos Previos
```
┌─────────────────────────────────────────┐
│ 📜 HISTORIAL DE APOYOS                │
├─────────────────────────────────────────┤
│ ✅ PRIMER APOYO PARA ESTE BENEFICIARIO  │
│                                         │
│ O bien:                                 │
│                                         │
│ Apoyo Previo 1: Beca 2025               │
│  Folio: #1000  |  Monto: $3,000        │
│  Fecha: 15/05/2025  |  CUV: xyz789...  │
│                                         │
│ Apoyo Previo 2: Escuela Gratuita        │
│  Folio: #0999  |  Monto: $2,000        │
│  Fecha: 01/04/2025  |  CUV: abc456...  │
│                                         │
│ Total de apoyos previos: 2              │
└─────────────────────────────────────────┘
```

### Sección Derecha (1/3 del ancho)

#### Panel de Presupuesto
```
┌─────────────────────────────────────────┐
│ 💰 PRESUPUESTO                        │
├─────────────────────────────────────────┤
│                                         │
│ Monto a Autorizar:                      │
│ ┌─────────────────────────────────────┐
│ │        $5,000                       │ (AZUL)
│ └─────────────────────────────────────┘
│                                         │
│ Disponible en Apoyo:                    │
│ $25,000  ✅ Suficiente                  │
│                                         │
│ Disponible en Categoría:                │
│ $18,000  ✅ Suficiente                  │
│                                         │
│ ┌─────────────────────────────────────┐
│ │ ✅ PRESUPUESTO DISPONIBLE            │ (VERDE)
│ │ Puedes proceder a firmar             │
│ └─────────────────────────────────────┘
│                                         │
│ O si NO hay presupuesto:                │
│ ┌─────────────────────────────────────┐
│ │ ✗ PRESUPUESTO INSUFICIENTE           │ (ROJO)
│ │ No se puede autorizar                │
│ └─────────────────────────────────────┘
└─────────────────────────────────────────┘
```

#### FASE 2: Firma Digital (Si aplica)
```
┌─────────────────────────────────────────┐
│ 🔐 FASE 2: FIRMA DIGITAL              │
├─────────────────────────────────────────┤
│                                         │
│ [👁️ Ver Resumen Completo]             │
│         (Abre modal con detalles)       │
│                                         │
│ Contraseña:                             │
│ [********************]                │
│                                         │
│ [✓ Firmar y Generar CUV]               │
│                                         │
│ O si ya está firmado:                   │
│ "Esta solicitud ya ha sido procesada"   │
└─────────────────────────────────────────┘
```

---

## 🔐 ¿CÓMO FIRMAR UNA SOLICITUD?

### Paso 1: Revisar Información
```
✅ Verifica:
   - Nombre del beneficiario correcto
   - Monto solicitado correcto
   - Documentos completos y válidos
   - Presupuesto disponible (panel derecho)
   - Historial de apoyos previos
```

### Paso 2: Clickea "Ver Resumen Completo"
```
Se abrirá un modal con:

┌────────────────────────────────────────┐
│ 📋 RESUMEN DE AUTORIZACIÓN            │
├────────────────────────────────────────┤
│                                        │
│ Folio:        #1234                   │
│ Fecha:        13/04/2026              │
│                                        │
│ Beneficiario: Juan Pérez López        │
│ CURP: PXXX850101HXXX001               │
│                                        │
│ Apoyo:        Beca Escolar            │
│                                        │
│ MONTO A AUTORIZAR:                     │
│ ┌────────────────────────────────────┐
│ │             $5,000                 │ (VERDE)
│ └────────────────────────────────────┘
│                                        │
│ ⚠️ ADVERTENCIA IMPORTANTE:              │
│ Al firmar, está autorizando            │
│ IRREVOCABLEMENTE el desembolso de      │
│ $5,000 a Juan Pérez López.             │
│ Esta acción será auditada              │
│ permanentemente.                       │
│                                        │
│ [Revisar de Nuevo]  [Continuar]       │
└────────────────────────────────────────┘
```

### Paso 3: Ingresa Contraseña
```
Después de acpt el resumen:

┌────────────────────────────────────────┐
│ Contraseña:                            │
│ [********************]               │
│                                        │
│ Introduce tu contraseña de SIGO        │
│ Para confirmar que SÍ autorizas esto   │
└────────────────────────────────────────┘
```

### Paso 4: Clickea "Firmar y Generar CUV"
```
El sistema:
  1. Valida tu contraseña
  2. Genera un CUV único (Código Único de Verificación)
  3. Registra la firma en auditoría
  4. Asigna el presupuesto
  5. Cambia estado a APROBADA
  
Verás mensaje:
"✓ Solicitud firmada exitosamente. CUV: abc123def456xyz..."
```

### Resultado Final
```
┌────────────────────────────────────────┐
│ ✅ SOLICITUD APROBADA                 │
├────────────────────────────────────────┤
│                                        │
│ Tu firma digital se ha registrado.      │
│                                        │
│ CUV GENERADO:                          │
│ abc123def456xyz789...                  │
│                                        │
│ Este código es único y permanente.      │
│ Úsalo para referencias futuras.        │
│                                        │
│ El beneficiario recibirá notificación  │
│ y podrá descargar su comprobante.      │
└────────────────────────────────────────┘
```

---

## ❓ PREGUNTAS FRECUENTES

### P: ¿Qué si no tengo presupuesto disponible?
**R:** No podrás firmar. El botón estará deshabilitado (gris). Contacta a coordinación para aumentar presupuesto.

### P: ¿Puedo deshacer una firma?
**R:** NO. Una vez firmado, es **IRREVOCABLE** y quedará en auditoría permanente. Asegúrate bien antes de firmar.

### P: ¿Dónde veo el historial de mis firmas?
**R:** En el sistema de auditoría (admin panel). Se registra: quién, cuándo, desde dónde, qué navegador.

### P: ¿El CUV para qué sirve?
**R:** Es un identificador único para:
  - Validar la solicitud ante terceros
  - Auditoría de firmas
  - Referencia en reportes
  - Comprobante para el beneficiario

### P: ¿A quién ve el beneficiario?
**R:** El beneficiario verá que su solicitud fue aprobada y recibirá un comprobante con el CUV.

### P: ¿Puedo filtrar por varias condiciones a la vez?
**R:** SÍ. Completa los filtros que quieras y clickea "Buscar".
Ejemplo: Folio = 1234 AND Estado = Pendiente Firma

### P: ¿Los documentos son públicos?
**R:** NO. Solo tú (como directivo/admin) ves los documentos privados del beneficiario.

---

## 📊 FLUJO RESUMIDO

```
1. BANDEJA
   └─ Ves todas tus solicitudes pendientes
   └─ Puedes filtrar y buscar

2. SELECCIONA UNA SOLICITUD
   └─ Clickea "Ver Detalles →"

3. REVISA INFORMACIÓN COMPLETA
   ├─ Datos beneficiario
   ├─ Documentos enviados (puedes verlos)
   ├─ Presupuesto disponible
   └─ Historial de apoyos previos

4. SI PRESUPUESTO ESTÁ OK
   └─ Procede a firmar

5. VERIFICA RESUMEN
   └─ Confirma que todo esté correcto
   └─ Lee advertencia: ES IRREVOCABLE

6. INGRESA CONTRASEÑA
   └─ Confirma que SÍ autorizas

7. CLICK EN "FIRMAR"
   └─ ¡LISTO! Solicitud aprobada ✅
   └─ CUV generado automáticamente

8. BENEFICIARIO NOTIFICADO
   └─ Recibirá notificación
   └─ Podrá descargar comprobante
```

---

## 🎯 CONSEJOS

```
✅ ANTES DE FIRMAR:
  - Lee todo cuidadosamente
  - Verifica que el monto sea correcto
  - Confirma que los documentos son válidos
  - Revisa si ya le diste dinero a este beneficiario
  - Asegúrate de tener presupuesto

⚠️ RECUERDA:
  - Una firma = PERMANENTE
  - Irrevocable = No se puede deshacer
  - Auditada = Quedará registrada
  - Responsabilidad = Legal

🔐 SEGURIDAD:
  - No compartas tu contraseña
  - Cierra sesión después de terminar
  - No dejes el navegador sin supervisar
  - Los datos del beneficiario son privados (LGPDP)
```

---

## 📞 SOPORTE

```
Si tienes problemas:
  1. Verifica estar autenticado
  2. Verifica que tu rol sea Directivo (2) o Admin (3)
  3. Intenta refrescar la página (F5)
  4. Contacta al equipo técnico
  
Errores comunes:
  - "No autorizado" → Verifíca tu rol
  - "Presupuesto insuficiente" → Contacta coordinación
  - "Error en firma" → Contacta soporte técnico
```

---

**¡Listo! Ahora puedes usar la bandeja unificada de solicitudes 🚀**
