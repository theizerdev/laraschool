# Funcionalidad de Constancia de Solvencia por WhatsApp

## 🎯 Descripción

Esta funcionalidad permite**enviar una constancia de solvencia académica** directamente por WhatsApp al estudiante o representante, cuando la matrícula está solvente.

---

## ✅ Características Principales

### 1️⃣ **Detección Automática del Destinatario**

El sistema determina automáticamente a quién enviar el mensaje:

- **Si el estudiante es MENOR de edad**: Envía al representante
- **Si el estudiante es MAYOR de edad**: Envía al estudiante

```php
// Lógica de selección
if($estudiante->es_menor_de_edad && $estudiante->representante) {
    // Enviar al representante
} else {
    // Enviar al estudiante
}
```

### 2️⃣ **Mensaje Personalizado**

La constancia incluye:

- 🎓 Encabezado institucional
- 📄 Datos completos del estudiante
- ✅ Estado financiero (SOLVENTE)
- 📊 Resumen financiero detallado
- 🔖 Código de verificación único
- 📅 Fecha y hora de emisión

### 3️⃣ **Validación de Solvencia**

El sistema **SOLO permite enviar** si la matrícula está solvente:

```php
if (!$matricula->solvente) {
    session()->flash('warning', 'La matrícula NO está solvente...');
   return;
}
```

---

## 📋 Mensaje Ejemplo

```
🎓 *CONSTANCIA DE SOLVENCIA ACADÉMICA*

U.E. JOSÉ MARÍA VARGAS
RIF: J-12345678-9

📄 *Datos del Estudiante:*
• Nombre: JUAN PÉREZ GÓMEZ
• Cédula: V-12.345.678
• Programa: Educación Media General
• Período: 2025-2026

✅ *ESTADO FINANCIERO: SOLVENTE*

Por medio de la presente se hace constar que el(la) 
estudiante JUAN PÉREZ GÓMEZ se encuentra SOLVENTE con 
esta institución educativa en concepto de pagos académicos.

📊 *Resumen Financiero:*
• Total Cuotas: Bs. 1.500,00
• Total Pagado: Bs. 1.500,00
• Saldo Pendiente: Bs. 0,00
• Progreso: 100,00%

📅 Fecha de Emisión: 10/03/2026 22:35:29
🔖 Código de Verificación: SOLV-65F3A2B1

_Esta constancia se emite a solicitud del interesado 
para los fines que estime convenientes._

*U.E. JOSÉ MARÍA VARGAS*
```

---

## 🔧 Implementación Técnica

### Archivos Modificados/Creados:

| Archivo | Tipo | Propósito |
|---------|------|-----------|
| [`Show.php`](c:\laragon\www\vargas\app\Livewire\Admin\Matriculas\Show.php) | Modificado | Método `enviarNotificacionSolvencia()` |
| [`Student.php`](c:\laragon\www\vargas\app\Models\Student.php) | Modificado | Accessor `representante` |
| [`show.blade.php`](c:\laragon\www\vargas\resources\views\livewire\admin\matriculas\show.blade.php) | Modificado | Botón para enviar constancia |

---

## 💻 Métodos Implementados

### 1. `enviarNotificacionSolvencia()`

**Propósito:**Método principal que coordina el envío de la constancia.

**Ubicación:** `app/Livewire/Admin/Matriculas/Show.php`

**Flujo:**
```php
public function enviarNotificacionSolvencia()
{
    // 1. Verificar solvencia
   if (!$matricula->solvente) { ... }
    
    // 2. Obtener destinatario
    $destinatario = $this->obtenerDestinatarioMensaje();
    
    // 3. Formatear teléfono
    $telefono = $this->formatPhoneNumber($destinatario['telefono']);
    
    // 4. Generar mensaje
    $mensaje = $this->generarMensajeSolvencia($destinatario['nombre']);
    
    // 5. Enviar WhatsApp
    $resultado = $this->enviarWhatsApp($telefono, $mensaje);
    
    // 6. Mostrar resultado
   if($resultado['sent']) { ... }
}
```

---

### 2. `obtenerDestinatarioMensaje()`

**Propósito:** Determina si enviar al estudiante o representante.

**Retorna:** Array con datos del destinatario

```php
private function obtenerDestinatarioMensaje(): array
{
    $estudiante = $this->matricula->estudiante;
    
  if($estudiante->es_menor_de_edad && $estudiante->representante) {
      return [
            'nombre' => $estudiante->representante['nombre_completo'],
            'telefono' => $estudiante->representante['telefono'],
            'relacion' => 'representante'
        ];
    }
    
  return [
        'nombre' => $estudiante->nombres. ' ' . $estudiante->apellidos,
        'telefono' => $estudiante->telefono,
        'relacion' => 'estudiante'
    ];
}
```

---

### 3. `generarMensajeSolvencia(string $nombreDestinatario)`

**Propósito:** Genera el mensaje formateado de la constancia.

**Características:**
- Usa emojis para mejor legibilidad
- Formato Markdown para WhatsApp
- Incluye código de verificación único
- Muestra resumen financiero completo

---

### 4. `enviarWhatsApp(string $telefono, string $mensaje)`

**Propósito:** Envía el mensaje a través de la API de WhatsApp.

**Configuración:**
```php
$apiUrl = config('whatsapp.api_url', 'http://localhost:3001');
$apiKey = config('whatsapp.api_key', 'test-api-key-vargas-centro');
```

**Request:**
```php
$response = \Http::withHeaders([
    'X-API-Key' => $apiKey,
    'Content-Type' => 'application/json'
])
->timeout(10)
->post($apiUrl . '/api/send/message', [
    'phone' => $telefono,
    'message' => $mensaje
]);
```

---

### 5. `formatPhoneNumber($number)`

**Propósito:** Formatea el número de teléfono para WhatsApp.

**Lógica:**
1. Obtiene código de país de la empresa
2. Elimina caracteres no numéricos
3. Agrega código de país si falta
4. Retorna formato internacional (ej: +584121234567)

---

### 6. `getRepresentanteAttribute()` (Student Model)

**Propósito:** Proporciona datos estructurados del representante.

**Retorna:**
```php
[
    'nombres' => 'MARÍA RODRÍGUEZ',
    'apellidos' => 'GÓMEZ',
    'nombre_completo' => 'MARÍA RODRÍGUEZ GÓMEZ',
    'documento_identidad' => 'V-8.765.432',
    'telefono' => '0412-1234567',
    'correo' => 'maria@example.com',
    'direccion' => 'Calle Principal #123'
]
```

---

## 🎨 Interfaz de Usuario

### Botón en la Vista

**Ubicación:** Tarjeta de Estado Financiero

**Condiciones:**
- ✅ Solo visible si la matrícula está SOLVENTE
- 🟢 Botón verde con ícono de WhatsApp
- ⏳ Muestra spinner mientras envía

**Código:**
```blade
@if($matricula->solvente)
<div class="alert alert-success">
    <button wire:click="enviarNotificacionSolvencia" 
            wire:loading.attr="disabled"
            class="btn btn-success btn-sm">
        <i class="ri ri-whatsapp-line me-1"></i>
        <span wire:loading.remove>Enviar Constancia</span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm"></span>
            Enviando...
        </span>
    </button>
</div>
@endif
```

---

## 🔒 Validaciones y Seguridad

### 1. **Verificación de Solvencia**
```php
if (!$matricula->solvente) {
    session()->flash('warning', 'La matrícula NO está solvente...');
   return;
}
```

### 2. **Código de Verificación Único**
```php
$codigoVerificacion = strtoupper(uniqid('SOLV-'));
// Ejemplo: SOLV-65F3A2B1
```

### 3. **Registro de Envío (Log)**
```php
\Log::info('Constancia de solvencia enviada', [
    'matricula_id' => $this->matricula->id,
    'estudiante_id' => $this->matricula->estudiante_id,
    'destinatario' => $destinatario['nombre'],
    'relacion' => $destinatario['relacion'],
    'fecha' => now()
]);
```

---

## 📱 Flujo de Uso

### Paso a Paso:

1. **Usuario navega** a `/admin/matriculas/{id}`
2. **Sistema verifica** estado de solvencia
3. **Si está SOLVENTE:**
   - Muestra badge verde "SOLVENTE"
   - Muestra botón "Enviar Constancia"
4. **Usuario hace clic** en el botón
5. **Sistema:**
   - Valida que siga solvente
   - Determina destinatario (estudiante/representante)
   - Genera mensaje personalizado
   -Formatea número de teléfono
   - Envía por WhatsApp API
   - Registra el envío
6. **Muestra resultado:**
   - ✅ "Constancia enviada exitosamente"
   - ❌ "Error al enviar: [motivo]"

---

## ⚙️ Configuración Requerida

### Variables de Entorno (.env)

```env
WHATSAPP_API_URL=http://localhost:3001
WHATSAPP_API_KEY=test-api-key-vargas-centro
```

### Config File(config/whatsapp.php)

```php
return [
    'api_url' => env('WHATSAPP_API_URL', 'http://localhost:3001'),
    'api_key' => env('WHATSAPP_API_KEY', 'test-api-key-vargas-centro'),
];
```

---

## 🧪 Pruebas

### Caso de Prueba #1: Estudiante Mayor de Edad

**Datos:**
- Estudiante: 25 años
- Teléfono: 0412-1234567
- Matrícula: SOLVENTE

**Resultado Esperado:**
- ✅ Mensaje enviado al estudiante
- ✅ Teléfono formateado: +584121234567

---

### Caso de Prueba #2: Estudiante Menor de Edad

**Datos:**
- Estudiante: 15 años
- Representante: María Gómez
- Teléfono Rep: 0414-7654321
- Matrícula: SOLVENTE

**Resultado Esperado:**
- ✅ Mensaje enviado al representante
- ✅ Nombre: "María Gómez"
- ✅ Teléfono: +584147654321

---

### Caso de Prueba #3: Matrícula con Deudas

**Datos:**
- Saldo pendiente: $500
- Matrícula: CON DEUDAS

**Resultado Esperado:**
- ❌ No muestra botón de enviar
- ⚠️ Muestra alerta amarilla "CON DEUDAS"

---

## 🛠️ Troubleshooting

### Problema: "Invalid route action: [App\Livewire\Admin\Matriculas\Show]"

**Causa:** Problema con caché de rutas de Laravel/Livewire

**Solución:**
```bash
# Limpiar todas las cachés
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Eliminar archivos cache manualmente
Remove-Item bootstrap/cache/*.php -Force

# Regenerar autoload
composer dump-autoload

# Redescubrir componentes Livewire
php artisan livewire:discover
```

---

### Problema: No se envía el WhatsApp

**Posibles causas:**
1. API de WhatsApp no disponible
2. Número de teléfono inválido
3. Timeout de conexión

**Debugging:**
```php
// Verificar configuración
dd(config('whatsapp.api_url'));

// Verificar teléfono formateado
dd($telefono);

// Verificar respuesta
dd($resultado);
```

**Solución:**
- Verificar que la API esté corriendo
- Validar número de teléfono
- Aumentar timeout si es necesario

---

### Problema: Datos del representante null

**Causa:** Campos vacíos en la base de datos

**Verificar:**
```sql
SELECT 
   representante_nombres,
   representante_apellidos,
   representante_telefonos
FROM students
WHERE id = X;
```

**Solución:**
- Actualizar datos del estudiante
- Verificar que el formulario capture los datos

---

## 📊 Métricas y Reportes

### Registro de Envíos

Opcionalmente se puede crear una tabla para registrar todos los envíos:

```php
Schema::create('constancias_solvencia', function (Blueprint $table) {
    $table->id();
    $table->foreignId('matricula_id')->constrained();
    $table->foreignId('user_id')->constrained(); // Quién envió
    $table->string('destinatario_nombre');
    $table->string('destinatario_telefono');
    $table->string('relacion'); // estudiante | representante
    $table->string('codigo_verificacion');
    $table->timestamp('fecha_envio');
    $table->enum('estado', ['enviado', 'fallido']);
    $table->text('error')->nullable();
    $table->timestamps();
});
```

---

## 🚀 Mejoras Futuras

### 1. **PDF Adjunto**
- Generar PDF formal de la constancia
- Adjuntar al mensaje de WhatsApp
- Incluir firma digital

### 2. **Códigos QR de Verificación**
- Generar QR con código de verificación
- Permitir validación online de la constancia
- URL: `uevargas.edu.ve/verificar/SOLV-XXXXX`

### 3. **Historial de Constancias**
- Listado de todas las constancias enviadas
- Filtros por fecha, estudiante, estado
- Reenviar constancias anteriores

### 4. **Plantillas Personalizables**
- Permitir editar texto de la constancia
- Múltiples plantillas por tipo
- Variables dinámicas adicionales

### 5. **Envío Masivo**
- Seleccionar múltiples estudiantes solventes
- Enviar constancias en lote
- Programar envíos automáticos

---

## ✅ Checklist de Implementación

- [x] Método `enviarNotificacionSolvencia()` creado
- [x] Método `obtenerDestinatarioMensaje()` implementado
- [x] Método `generarMensajeSolvencia()` creado
- [x] Método `enviarWhatsApp()` funcional
- [x] Método `formatPhoneNumber()` operativo
- [x] Accessor `getRepresentanteAttribute()` agregado
- [x] Botón en vista agregado
- [x] Validación de solvencia implementada
- [x] Registro de envíos en logs
- [x] Documentación creada

---

## 📞 Soporte

Para asistencia técnica o reportar problemas:

1. **Verificar logs:** `storage/logs/laravel.log`
2. **Revisar configuración:** `config/whatsapp.php`
3. **Testear API:** `curl -X POST http://localhost:3001/api/send/message`
4. **Contactar:** Equipo de desarrollo U.E. José María Vargas

---

**Fecha de Implementación:** 2026-03-10  
**Versión:** 1.0.0  
**Estado:** ✅ Implementado y Funcional  
**Próxima Revisión:** 2026-04-10