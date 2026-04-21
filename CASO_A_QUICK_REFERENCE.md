# Caso A: Quick Reference Guide 📚

## Para Administradores

### 🎯 Los 3 Momentos (Quick Summary)

| Momento | ¿Cuándo? | ¿Dónde? | ¿Qué? | ¿Resultado? |
|---------|----------|--------|------|-----------|
| **1️⃣ Presencial** | Beneficiario presente (5 mins) | `/admin/caso-a/momento-uno` | Admin crea solicitud + genera folio+clave | Ticket impreso |
| **2️⃣ Escaneo** | 24-48 hrs después (batch) | `/admin/caso-a/momento-dos` | Admin escanea documentos | Documentos guardados + auditoría |
| **3️⃣ Consulta** | Cualquier momento (permanente) | `/consulta-privada` (público) | Beneficiario ingresa folio+clave | Dashboard privado sin auth |

---

## 🔑 Paso a Paso

### Momento 1: Crear Presencial

```
1. Accede: http://sigo.injuve.mx/admin/caso-a/momento-uno
2. Click: [Buscar Beneficiario]
   └─ Ingresa: Cédula o nombre
   └─ Sistema: Busca automáticamente (AJAX)
3. Select: Apoyo (ej: "TEP - Jóvenes")
4. Enter: Número documento (ej: C-12345678)
5. Check: Documentos esperados (☑ Cédula ☑ RFC ☑ Comprobante)
6. Click: [GUARDAR EXPEDIENTE]
7. Result: Folio + Clave mostrados
   ├─ Folio: 001-2026-TEP
   ├─ Clave: KX7M-9P2W-5LQ8
   └─ QR: Generado
8. Click: [IMPRIMIR TICKET]
9. Give: Ticket al beneficiario
10. Done: Beneficiario se retira
```

**✅ Checklist Momento 1:**
- [x] Beneficiario está presente (con documentos físicos)
- [x] Documento identidad verificado
- [x] Folio generado
- [x] Clave privada generada
- [x] Ticket impreso (o foto del QR)
- [x] Beneficiario se retira

---

### Momento 2: Escanear Documentos

```
DESPUÉS de 24-48 HORAS (cuando termines de escanear):

1. Accede: http://sigo.injuve.mx/admin/caso-a/momento-dos
2. Enter: Folio (ej: 001-2026-TEP)
   └─ O: Escanea QR si tienes escáner conectado
3. For cada documento:
   a. Drag-drop archivo en área azul (o click)
   b. Select: PDF, JPG, PNG (<5MB)
   c. Sistema valida automáticamente
   d. Click: [✓ ACEPTAR]
4. Result: Documento guardado
   └─ Sistema automático:
      • Watermark: "INJUVE · 001-2026-TEP · [fecha]"
      • QR: Código verificable
      • Hash: SHA256 para integridad
      • Firma: HMAC-SHA256 (no se puede falsificar)
      • Auditoría: Tu IP + navegador registrado
5. Repeat: Para Documento 2, Documento 3, etc.
6. Click: [CONFIRMAR CARGA]
7. Result: Resumen generado
```

**✅ Checklist Momento 2:**
- [x] Folio ingresado correctamente
- [x] ≥2 documentos cargados
- [x] Archivos validados (MIME, tamaño)
- [x] Watermark visible en documentos
- [x] Carga confirmada
- [x] Auditoría registrada

---

### Momento 3: Verificación en Panel Ordinario

```
DESPUÉS de Momento 2 (cuando confirmes carga):

1. Accede: http://sigo.injuve.mx/admin/verificar-documentos
2. Filter: (Opcional) origen_solicitud = 'admin_caso_a'
   └─ Muestra SOLO solicitudes Caso A
3. Find: Folio 001-2026-TEP (busca en lista)
4. Click: [ABRIR]
5. For cada documento:
   a. Review: Contenido + integridad
   b. Check: Watermark visible?
   c. Check: QR verificable?
   d. Action: [✓ APROBAR] o [✗ RECHAZAR]
6. Done: Documento procesado
7. Repeat: Para siguiente documento
8. Result: Solicitud → DOCUMENTOS_VERIFICADOS
```

**⭐ IMPORTANTE:**
- Esta es la MISMA interfaz que para beneficiarios
- Misma lógica de validación
- NO hay interfaz separada para Caso A
- El sistema usa el campo `origen_solicitud` internamente (no visible)

---

## 📱 Momento 3: Para Beneficiarios

```
Beneficiario PUEDE ACCEDER CUANDO QUIERA (sin login):

1. Abre: https://sigo.injuve.mx/consulta-privada
2. Enter: Folio
   └─ De donde: Ticket impreso en Momento 1
3. Enter: Clave privada
   └─ De donde: Ticket impreso en Momento 1
4. Click: [VERIFICAR ACCESO]
5. Result: Dashboard privado
   ├─ Status: "Documentos en verificación"
   ├─ Timeline: "Admin escaneó" → "Verificador aprobó" → "Directivo va a firmar"
   ├─ Documentos:
   │  ├─ ✅ Cédula (Verificado)
   │  ├─ ✅ RFC (Verificado)
   │  └─ ⏳ Comprobante (Pendiente)
   └─ [Verificar Cadena Digital] (comprueba integridad)

**🔐 SEGURIDAD:**
- Sin clave: NO se ve nada (protege datos sensibles)
- 5 intentos fallidos: Cuenta bloqueada
- Cada acceso registrado: IP + navegador + timestamp
```

---

## 🛠️ Troubleshooting

| Problema | Causa | Solución |
|----------|-------|----------|
| "Beneficiario no encontrado" | Cédula incorrecta o no registrado | Verifica cédula exacta en sistema |
| "Apoyo no disponible" | Apoyo cerrado o no en estado RECEPCIÓN | Selecciona otro apoyo activo |
| "Folio ya existe" | Folio duplicado (raro) | Espera 1 min y reinenta |
| "Archivo muy grande" | Documento >5MB | Comprime imagen o PDF |
| "Formato no permitido" | Archivo no es PDF/JPG/PNG | Convierte a formato válido |
| "Intento fallido (4/5)" | Clave incorrecta ingresada | Verifica ticket original |
| "Cuenta bloqueada" | 5 intentos fallidos | Admin: desbloquea en BD (urgente) |
| Watermark no se ve | Problema en servidor | Admin: contacta soporte técnico |

---

## 📞 Contactos Rápidos

### If issues arise:
- **Problema técnico:** Contacta soporte@sigo.injuve.mx
- **Beneficiario olvidó clave:** No se puede recuperar, crea NEW solicitud
- **Auditoría/logs:** Revisa con DB administrator

---

## 📋 Campos Importantes

### Folio
- Formato: `XXX-YYYY-MMM` (ej: 001-2026-TEP)
- Único por beneficiario + apoyo
- Válido permanentemente
- NO se puede cambiar

### Clave Privada
- Formato: 16 caracteres (A-Z, 0-9)
- Generada aleatoriamente
- NO se puede recuperar (sin backup)
- Válida permanentemente

### origen_solicitud
- Valor: `'admin_caso_a'` para Caso A
- Valor: `'beneficiario'` para carga ordinaria
- Uso: Diferenciar para reportes
- Admin no lo ve (interno)

---

## ⚡ Quick Keyboard Shortcuts

| Acción | Shortcut |
|--------|----------|
| Guardar Momento 1 | `Ctrl + Enter` |
| Confirmar carga Momento 2 | `Ctrl + Enter` |
| Buscar beneficiario | `Ctrl + K` |
| Imprimir ticket | `Ctrl + P` |
| Verificar acceso (público) | `Ctrl + Enter` |

---

## 🎓 Training Checklist (2 Horas)

Admin debe completar:
- [ ] Ver video demo (15 mins)
- [ ] Practicar Momento 1 en STAGING (15 mins)
- [ ] Practicar Momento 2 en STAGING (15 mins)
- [ ] Practicar verificación ordinaria (15 mins)
- [ ] Leer security guidelines (15 mins)
- [ ] Aprobar checklist (10 mins)

---

## 🔒 Security Reminders

✅ **DO:**
- Imprimir tickets (no enviar por email)
- Verificar documento identidad en Momento 1
- Registrar auditoría completa
- Usar contraseña fuerte para admin

❌ **DON'T:**
- Compartir folio/clave con terceros
- Guardar clave en notas de texto
- Usar WiFi público sin VPN
- Hacer "testing" en producción
- Modificar directamente la BD (usar interfaz)

---

## 📊 Daily Workflow

```
MAÑANA:
9:00 AM - Momento 1 panel (si beneficiarios llegando)
         ├─ 5-10 mins por beneficiario
         └─ Imprimir tickets

TARDE/NOCHE:
5:00 PM - Momento 2 panel (escanear batch)
         ├─ 30-60 mins según cantidad
         └─ Confirmar carga

SIEMPRE:
        - Verificación ordinaria (cuando sea)
         ├─ Misma interfaz que beneficiarios
         ├─ Filtrar por origin_solicitud si necesario
         └─ Aprobar/rechazar documentos
```

---

## 📱 Mobile Friendly

- ✅ Responsive: funciona en tablet + mobile
- ✅ Consulta privada (Momento 3): optimizado para móvil
- ✅ Admin panels: mejor en desktop (>1024px)

---

## 🌐 URLs de Acceso

| Función | URL | Auth | Disponible |
|---------|-----|------|-----------|
| Momento 1 (crear) | `/admin/caso-a/momento-uno` | ✅ Admin | 24/7 |
| Momento 2 (escanear) | `/admin/caso-a/momento-dos` | ✅ Admin | 24/7 |
| Verificación | `/admin/verificar-documentos` | ✅ Admin | 24/7 |
| Consulta privada | `/consulta-privada` | ❌ Pública | 24/7 |

---

**Last Updated:** 2026-04-18  
**Version:** 1.0 - Production Ready  
**Status:** ✅ Ready for Deployment
