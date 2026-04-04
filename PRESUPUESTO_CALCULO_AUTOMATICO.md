# 💰 Cálculo Automático de Presupuesto Real

**Commit:** `35b5f4c`  
**Fecha:** 3 de Abril de 2026  
**Cambios:** Budget calculation automática en tiempo real

---

## 🎯 Objetivo

Combinar B+C: Mostrar presupuesto disponible + calcular automáticamente **total = monto_máximo × cupo_límite** con validación dinámica en tiempo real.

---

## ✨ Características Implementadas

### 1. Sección Visual "Presupuesto Real"

**Ubicación:** form.blade.php, entre categoría presupuestaria y monto_inicial_asignado

**Contenido:**
```
┌─────────────────────────────────────────────────┐
│ Cálculo de Presupuesto Real                     │
├─────────────────────────────────────────────────┤
│                                                 │
│  Presupuesto          Monto por                │
│  Disponible           Beneficiario             │
│  $50,000              $500                     │
│                                                 │
│  Cantidad Máx.        💰 TOTAL NECESARIO       │
│  Beneficiarios        $50,000                  │
│  100                  (Monto × Cantidad)      │
│                                                 │
├─────────────────────────────────────────────────┤
│  ✅ Presupuesto Suficiente                      │
│  Restante después: $0                           │
└─────────────────────────────────────────────────┘
```

### 2. Cálculo Automático

**Fórmula:**
```
total = monto_máximo × cupo_límite
```

**Ejemplo:**
```
monto_máximo = $500 por beneficiario
cupo_límite = 100 beneficiarios
────────────────────────────
TOTAL = $50,000
```

**Actualización:** En tiempo real
- Al cambiar monto_máximo → recalcula automáticamente
- Al cambiar cupo_límite → recalcula automáticamente
- Al seleccionar categoría → muestra presupuesto disponible + recalcula

### 3. Validación Visual Dinámmica

#### Caso A: Presupuesto Suficiente ✅
```
Presupuesto disponible: $100,000
Total necesario: $50,000
Diferencia: $50,000

Mostrar: ✅ Presupuesto Suficiente
         Restante: $50,000
```

#### Caso B: Presupuesto Insuficiente ⚠️
```
Presupuesto disponible: $40,000
Total necesario: $50,000
Diferencia: -$10,000

Mostrar: ⚠️ Presupuesto Insuficiente
         Faltante: $10,000
         
Marcar inputs como inválidos (rojo)
```

### 4. Campo monto_inicial_asignado Automático

**Cambio:** Cambio de manual a automático

**Antes:**
```blade
<input id="monto_inicial_asignado" type="number" name="monto_inicial_asignado">
<!-- Usuario debe ingresar manualmente -->
```

**Después:**
```blade
<input id="monto_inicial_asignado" type="number" readonly 
       value="{{ total_calculado }}">
<!-- Se actualiza automáticamente cuando cambian monto o cupo -->
```

**Etiqueta:** "Monto reservado (automático)"  
**Descripción:** "Se calcula como: Monto máximo × Cantidad máxima beneficiarios"

---

## 🔧 Implementación Técnica

### Frontend (JavaScript en form.blade.php)

**Función Principal:** `actualizarCalculoPresupuesto()`

```javascript
function actualizarCalculoPresupuesto() {
    // 1. Obtener valores
    const montoMaximo = parseFloat(inputMontoMaximo.value) || 0;
    const cupoLimite = parseFloat(inputCupoLimite.value) || 1;
    const presupuestoDisponible = parseFloat(selectedOption.dataset.disponible) || 0;
    
    // 2. Calcular total
    const totalCalculado = montoMaximo * cupoLimite;
    
    // 3. Actualizar campo automaticamente
    inputMontoInicial.value = totalCalculado.toFixed(2);
    
    // 4. Mostrar en pantalla
    txtMontoTotal.textContent = totalCalculado.toFixed(2);
    
    // 5. Validar presupuesto
    if (totalCalculado > presupuestoDisponible) {
        mostrarAlertaPresupuestoInsuficiente();
    } else {
        mostrarResumenPresupuestoSuficiente();
    }
}
```

**Event Listeners:**
```javascript
selectCategoria.addEventListener('change', actualizarCalculoPresupuesto);
inputMontoMaximo.addEventListener('input', actualizarCalculoPresupuesto);
inputMontoMaximo.addEventListener('change', actualizarCalculoPresupuesto);
inputCupoLimite.addEventListener('input', actualizarCalculoPresupuesto);
inputCupoLimite.addEventListener('change', actualizarCalculoPresupuesto);
selectTipoApoyo.addEventListener('change', actualizarCalculoPresupuesto);
```

### Backend (ApoyoController)

**Validación en store():**

```php
if ($data['tipo_apoyo'] === 'Económico' && $data['id_categoria']) {
    // Calcular total: monto_máximo × cupo_límite
    $totalNecesario = ($data['monto_maximo'] ?? 0) * ($data['cupo_limite'] ?? 1);
    
    // Validar presupuesto disponible
    $validacion = $inventarioService->validarPresupuestoDisponible(
        $data['id_categoria'],
        $totalNecesario
    );
    
    if (!$validacion['valido']) {
        return response()->json([
            'success' => false,
            'message' => "Presupuesto insuficiente. Necesita: $" . 
                        number_format($totalNecesario, 2) . 
                        " pero disponible es: $" . 
                        number_format($validacion['disponible'] ?? 0, 2),
        ], 422);
    }
}
```

**Reserva de presupuesto:**

```php
if ($data['tipo_apoyo'] === 'Económico') {
    // Total a reservar = monto_máximo × cupo_límite
    $totalAReservar = ($data['monto_maximo'] ?? 0) * ($data['cupo_limite'] ?? 1);
    
    $inventarioService->reservarPresupuestoApoyo(
        $apoyo->id_apoyo,
        $data['id_categoria'],
        $totalAReservar,  // <-- Total calculado
        Auth::user()->id_usuario ?? Auth::id()
    );
}
```

---

## 📊 Flujo de Datos

```
Usuario Llena Formulario
│
├─ Selecciona tipo_apoyo = "Económico"
│  └─ Form.blade.php muestra "Presupuesto Real"
│
├─ Selecciona categoría
│  └─ JS obtiene presupuesto disponible (data-disponible)
│  └─ Actualiza txt-presupuesto-disponible
│
├─ Ingresa monto_máximo = 500
│  └─ Event: input + change
│  └─ JS recalcula: 500 × cupo_límite
│
├─ Ingresa cupo_límite = 100
│  └─ Event: input + change
│  └─ JS recalcula: monto × 100 = 50,000
│  └─ Actualiza monto_inicial_asignado = 50,000
│  └─ Valida contra presupuesto_disponible
│
└─ Presiona "Guardar"
   └─ ApoyoController::store()
   └─ Valida nuevamente en servidor
   └─ Reserva total = 50,000
```

---

## 🎨 Estilos Visual

### Presupuesto Suficiente (Verde)
```
Fondo: bg-white, Border: border-2 border-green-300
Ícono: ✅ (checkmark verde)
Título: text-green-800
Texto: Presupuesto disponible después de esta asignación: $50,000
```

### Presupuesto Insuficiente (Amarillo/Rojo)
```
Fondo: bg-white, Border: border-2 border-yellow-300
Ícono: ⚠️ (warning)
Título: text-yellow-800
Texto: El total necesario excede presupuesto disponible
       Faltante: $10,000

Inputs: border-red-500, bg-red-50
```

---

## ✅ Validaciones (Multi-capa)

| Nivel | Ubicación | Validación |
|-------|-----------|-----------|
| 1 | Frontend (HTML5) | type="number", min="0" |
| 2 | Frontend (JS) | Visualización real-time de total |
| 3 | Frontend (JS) | Validación contra presupuesto_disponible |
| 4 | Backend (PHP) | Validar rules en store() |
| 5 | Backend (Service) | validarPresupuestoDisponible() |
| 6 | Database (SQL) | FK constraints, triggers |

---

## 📝 Ejemplo de Uso

### Escenario 1: Crear Apoyo Económico válido

```
1. Usuario click "Nuevo Apoyo"
2. Selecciona tipo_apoyo = "Económico"
3. Elige categoría presupuestaria:
   - Nombre: "Educación"
   - Presupuesto disponible: $100,000
   └─ Vista actualiza: txtPresupuestoDisponible = $100,000
   
4. Ingresa monto_máximo = $500
5. Ingresa cupo_límite = 100
   └─ JS recalcula: 500 × 100 = $50,000
   └─ Actualiza input monto_inicial_asignado = $50,000
   └─ Verifica: 50,000 < 100,000 ✅
   └─ Muestra: "Presupuesto Suficiente - Restante: $50,000"
   
6. Usuario click "Guardar"
7. ApoyoController::store() recibe:
   - monto_maximo: 500
   - cupo_limite: 100
   - monto_inicial_asignado: 50,000 (readonly, generado en JS)
   
8. Valida en backend: 500 × 100 = 50,000 ≤ 100,000 ✅
9. Reserva presupuesto: presupuesto_apoyos.monto = 50,000
10. ✅ Apoyo creado exitosamente
```

### Escenario 2: Intentar crear apoyo que excede presupuesto

```
1. Usuario ingresa monto_máximo = $600
2. Usuario ingresa cupo_límite = 200
   └─ JS recalcula: 600 × 200 = $120,000
   └─ Verifica: 120,000 > 100,000 ❌
   └─ Muestra alerta rojo: 
      "⚠️ Presupuesto Insuficiente"
      "Faltante: $20,000"
   └─ Inputs monto_maximo y cupo_limite se ponen rojos
   
3. Usuario intenta click "Guardar"
   └─ JavaScript cliente podría prevenir (no obligatorio)
   
4. Backend recibe datos:
   └─ ApoyoController valida: 120,000 > 100,000
   └─ Retorna 422 con mensaje:
      "Presupuesto insuficiente. Necesita: $120,000 but available: $100,000"
   
5. Frontend muestra error al usuario
6. Usuario corrige valores (ej: cupo_límite = 150)
   └─ Total: 600 × 150 = $90,000 ✅
7. Intenta guardar de nuevo ✅ Éxito
```

---

## 🔄 Comparación: Antes vs Después

| Aspecto | Antes | Después |
|--------|-------|---------|
| Ingreso monto_inicial | Manual (usuario digita) | Automático (calculado) |
| Cálculo total | En backend al guardar | En tiempo real (JS) |
| Validación presupuesto | Solo al guardar | Visual realtime + backend |
| Retroalimentación | Error al guardar | Instantáneo visual |
| Campo monto_inicial | Editable | Readonly |
| Presupuesto disponible | Mostrado en option | Mostrado + actualizado |

---

## 🧪 Testing Manual

```
1. Abrir /apoyos/create en navegador
2. Seleccionar tipo_apoyo = "Económico"
   └─ Verificar: Sección "Presupuesto Real" aparece
   
3. Seleccionar categoría con presupuesto disponible
   └─ Verificar: txtPresupuestoDisponible actualizado
   
4. Ingresar monto_máximo = 500
   └─ Verificar: txtMontoBeneficiario = 500
   
5. Ingresar cupo_límite = 50
   └─ Verificar:
      - txtCantidadBeneficiarios = 50
      - txtMontoTotal = 25,000
      - inputMontoInicial.value = 25,000.00
      
6. Si presupuesto es suficiente:
   └─ Verificar: divResumenFinal visible (verde)
   └─ Verificar: txtPresupuestoRestante correcto
   
7. Aumentar cupo_límite para exceder presupuesto
   └─ Verificar:
      - divValidacionPresupuesto visible (amarillo)
      - txtPresupuestoFaltante correcto
      - Inputs se ponen rojos
      
8. Guardar con presupuesto válido
   └─ Verificar: apoyo creado, presupuesto_apoyos con monto correcto
   
9. Guardar con presupuesto inválido
   └─ Verificar: Error 422 con mensaje presupuesto insuficiente
```

---

## 💡 Ventajas

✅ **Transparencia:** Usuario ve exactamente cuánto gastará  
✅ **Feedback Inmediato:** No espera a guardar para saber si hay error  
✅ **Prevención de Errores:** Evita guardar apoyos inválidos  
✅ **Automatización:** Menos campos para llenar manualmente  
✅ **Validación Multicapa:** Frontend + Backend seguro  
✅ **Presupuesto Protegido:** No se puede exceder presupuesto categoría  

---

## 📌 Notas Importantes

1. **monto_inicial_asignado es readonly:** No se puede editar manualmente
2. **Cálculo ocurre en tiempo real:** No requiere hacer click en botón
3. **Validación es bilateral:** Frontend + Backend para seguridad
4. **Presupuesto_categorias requiere data-disponible:** Verificar que esta columna existe
5. **Funciona solo para apoyos tipo Económico:** En Especie se oculta la sección

---

## 🔗 Archivos Modificados

- ✅ `resources/views/apoyos/form.blade.php` (HTML + JS)
- ✅ `app/Http/Controllers/ApoyoController.php` (Validación backend)

## 📊 Líneas de Código

- HTML/Blade: ~150 líneas (sección + campos)
- JavaScript: ~110 líneas (lógica cálculo)
- PHP: ~20 líneas (validación mejorada)
- **Total:** +280 líneas

---

**Commit:** `35b5f4c`  
**Status:** ✅ COMPLETADO Y LISTO PARA TESTING

