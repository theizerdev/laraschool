# WhatsApp API v2 - Instituto Vargas

API completa de WhatsApp usando Baileys para el U.E JOSE MARIA VARGAS con soporte multi-empresa dinámico.

## ✨ Características Principales

- 🔐 **Autenticación Multi-Empresa Dinámica**: Cada empresa tiene su propia API key única
- 🔄 **Sincronización Automática**: Las empresas se sincronizan automáticamente desde Laravel
- 📱 **Gestión Multi-Empresa**: Soporte para múltiples empresas/instituciones
- 🔒 **Seguridad Avanzada**: Rate limiting por empresa y validación de API keys
- 📊 **Monitoreo Completo**: Logs detallados y health checks
- ⚡ **Integración Laravel**: Comandos Artisan para sincronización
- 🌐 **API RESTful**: Endpoints claros y bien documentados
- 📈 **Métricas y Monitoreo**: Endpoint `/metrics` compatible con Prometheus y latencia por petición
- 🧪 **Pruebas**: Suite de tests unitarios e integración con cobertura objetivo ≥80%

## 🚀 Inicio Rápido

### 1. Instalación
```bash
npm install
```

### 2. Configuración
Copia `.env.example` a `.env` y configura:
```bash
cp .env.example .env
```

### 3. Base de Datos
Ejecuta las migraciones:
```bash
# Crear base de datos 'larawhatsapp' en MySQL
# Ejecutar migrations/001_create_tables.sql
# Ejecutar migrations/002_add_companies.sql
```

### 4. Iniciar Servidor
```bash
npm start
# o para desarrollo:
npm run dev
# pruebas
npm test
```

## 📱 Uso de la API

### Autenticación Multi-Empresa Dinámica
Todas las rutas requieren headers dinámicos por empresa:
```
X-API-Key: {api_key_de_la_empresa}
X-Company-Id: {id_de_la_empresa}
```

Las API keys se generan automáticamente cuando se crea una empresa en Laravel o se sincroniza con los comandos disponibles.

### Endpoints Principales

#### Estado de WhatsApp
```bash
GET /api/whatsapp/status
```

#### Obtener Código QR
```bash
GET /api/whatsapp/qr
```

#### Enviar Mensaje
```bash
POST /api/whatsapp/send
{
  "to": "4121234567",
  "message": "Hola desde WhatsApp API"
}
# Validaciones:
# - to: 10-15 dígitos (puede incluir +), se normaliza a JID internamente
# - message: 2..4096 caracteres
# - type: opcional ['text','image','document','audio','video']
# - Country code: opcional vía header X-Country-Code, fallback a DEFAULT_COUNTRY_CODE/.env o 58
```

#### Ver Mensajes
```bash
GET /api/whatsapp/messages?page=1&limit=50
```

### 🧪 Prueba Rápida
```bash
node test-api.js
```

## 🔄 Sincronización Dinámica de Empresas

### Creación Automática en Laravel
Cuando se crea una empresa en el panel de administración de Laravel:
- ✅ Se genera automáticamente una API key única para WhatsApp
- ✅ Se sincroniza inmediatamente con la base de datos `larawhatsapp`
- ✅ Se inserta en la tabla `companies` con los datos correctos

### Comandos de Sincronización

#### Sincronizar empresa específica
```bash
php artisan whatsapp:sync-company 2
# Sincroniza la empresa con ID 2
```

#### Sincronizar todas las empresas sin API key
```bash
php artisan whatsapp:sync-company
# Sincroniza todas las empresas que no tengan API key configurada
```

#### Sincronizar todas las empresas (comando alternativo)
```bash
php artisan whatsapp:sync-companies --empresa=2
# Sincroniza la empresa con ID 2
```

#### Sincronizar todas las empresas (incluso las que ya tienen API key)
```bash
php artisan whatsapp:sync-companies --all
# Sincroniza todas las empresas del sistema
```

#### Sincronización masiva con progreso
```bash
php artisan whatsapp:sync-all-companies
# Sincroniza todas las empresas sin API key con barra de progreso

php artisan whatsapp:sync-all-companies --force
# Fuerza la sincronización de todas las empresas
```

## 🔧 Configuración Avanzada

### Variables de Entorno Importantes
- `PORT`: Puerto del servidor (default: 3001)
- `DB_*`: Configuración de MySQL para la base de datos `larawhatsapp`
- `WHATSAPP_SESSION_NAME`: Nombre de la sesión
- `CORS_ORIGIN`: Origen permitido para CORS

### Configuración de Seguridad Anti-Bloqueo
```bash
# Delay entre mensajes al mismo número (milisegundos)
ANTI_BLOCK_MESSAGE_DELAY_MS=30000

# Límite de mensajes por usuario
ANTI_BLOCK_MAX_PER_HOUR=10
ANTI_BLOCK_MAX_PER_DAY=50

# Horario comercial permitido (24h format)
ANTI_BLOCK_BUSINESS_HOURS_START=7
ANTI_BLOCK_BUSINESS_HOURS_END=22
```

### Base de Datos Multi-Empresa
La API utiliza la base de datos `larawhatsapp` con la tabla `companies` que contiene:
- **id**: ID de la empresa (coincide con Laravel)
- **name**: Nombre de la empresa
- **apiKey**: API key única para autenticación
- **webhookUrl**: URL para recibir eventos de WhatsApp
- **rateLimitPerMinute**: Límite de mensajes por minuto
- **dailyMessageLimit**: Límite de mensajes diarios por compañía
- **isActive**: Estado de la empresa (activa/inactiva)

## 📊 Monitoreo

### Health Check
```bash
GET /health
```

### Métricas (Prometheus)
```bash
GET /metrics
```
- Incluye `http_requests_total` por método/ruta/status
- `http_request_duration_seconds` con buckets de latencia
- `whatsapp_messages_sent_total` y `whatsapp_messages_failed_total`

### Logs
Los logs se guardan en `logs/`:
- `combined-YYYY-MM-DD.log`: Todos los logs
- `error-YYYY-MM-DD.log`: Solo errores
- `whatsapp-YYYY-MM-DD.log`: Logs específicos de WhatsApp

### Monitoreo del Sistema Anti-Bloqueo
```bash
# Ver estadísticas del sistema de protección (requiere autenticación)
GET /api/stats/anti-block
# Headers requeridos: Authorization: Bearer {jwt_token}

# Respuesta ejemplo:
{
  "success": true,
  "antiBlock": {
    "messageQueue": 15,
    "userLimits": 23,
    "companyDailyLimits": 3,
    "blockedMessages": 45,
    "protectedMessages": 1234
  }
}
```

## 🔒 Seguridad

### 🛡️ Protección Anti-Bloqueo de WhatsApp (CRÍTICO)
La API incluye un sistema avanzado para prevenir el bloqueo de cuentas de WhatsApp:

#### Límites de Mensajes
- **Por usuario**: Máximo 10 mensajes/hora, 50 mensajes/día por número
- **Delay obligatorio**: 30 segundos entre mensajes al mismo número
- **Horario comercial**: Solo envía mensajes de 7 AM a 10 PM (lunes a sábado)
- **Detección de spam**: Bloquea mensajes con patrones sospechosos

#### Validaciones Automáticas
- **Números válidos**: Solo acepta números con formato correcto
- **Contenido**: Rechaza mensajes vacíos o con spam
- **Duplicados**: Detecta y bloquea mensajes duplicados
- **Números de prueba**: Bloquea números de prueba comunes

#### Respuestas de Bloqueo
```json
{
  "success": false,
  "error": "Mensajes no permitidos fuera del horario comercial (7:00 - 22:00)",
  "code": "ANTI_BLOCK_PROTECTION",
  "company": "Nombre de la Empresa"
}
```

### 🔐 Autenticación Multi-Empresa
- **API Keys únicas**: Cada empresa tiene su propia API key
- **Validación dinámica**: Verificación automática contra base de datos
- **Headers requeridos**: `X-API-Key` y `X-Company-Id` obligatorios
- **Empresas inactivas**: Bloqueadas automáticamente

### ⚡ Rate Limiting
- **Global**: 100 requests/minuto por IP
- **Por empresa**: Configurable (default: 60 mensajes/minuto)
- **Por usuario**: 10 mensajes/hora, 50 mensajes/día
- **Por compañía**: 500 mensajes/día (configurable)

### 🔒 Seguridad de Aplicación
- **Helmet.js**: Headers de seguridad HTTP
- **CORS**: Orígenes permitidos configurables
- **JWT**: Autenticación para rutas administrativas
- **Validación**: express-validator para todos los inputs
- **Logs de seguridad**: Registro de intentos fallidos y accesos sospechosos

## 📁 Estructura del Proyecto

```
src/
├── config/          # Configuración de DB y Redis
├── controllers/     # Controladores de rutas
├── middleware/      # Middlewares personalizados
├── models/          # Modelos de Sequelize
├── routes/          # Definición de rutas
├── services/        # Lógica de negocio
└── utils/           # Utilidades y helpers
```

## 💡 Ejemplos de Uso Multi-Empresa

### Obtener estado de WhatsApp para empresa específica
```bash
# Empresa 1
curl -H "X-API-Key: wa_1_a1b2c3d4e5f6" -H "X-Company-Id: 1" http://localhost:3001/api/whatsapp/status

# Empresa 2
curl -H "X-API-Key: wa_2_x7y8z9w0q1r2" -H "X-Company-Id: 2" http://localhost:3001/api/whatsapp/status
```

### Enviar mensaje desde empresa diferente
```bash
# Empresa 1
curl -X POST http://localhost:3001/api/whatsapp/send \
  -H "X-API-Key: wa_1_a1b2c3d4e5f6" \
  -H "X-Company-Id: 1" \
  -H "Content-Type: application/json" \
  -d '{"to": "4121234567", "message": "Hola desde Empresa 1"}'

# Empresa 2
curl -X POST http://localhost:3001/api/whatsapp/send \
  -H "X-API-Key: wa_2_x7y8z9w0q1r2" \
  -H "X-Company-Id: 2" \
  -H "Content-Type: application/json" \
  -d '{"to": "4121234567", "message": "Hola desde Empresa 2"}'
```

### Ver mensajes por empresa
```bash
# Mensajes de Empresa 1
curl -H "X-API-Key: wa_1_a1b2c3d4e5f6" -H "X-Company-Id: 1" "http://localhost:3001/api/whatsapp/messages?page=1&limit=50"

# Mensajes de Empresa 2
curl -H "X-API-Key: wa_2_x7y8z9w0q1r2" -H "X-Company-Id: 2" "http://localhost:3001/api/whatsapp/messages?page=1&limit=50"
```

## 🐛 Solución de Problemas

### Error: "API key inválida"
- Verifica que uses el header `X-API-Key` con la API key correcta de la empresa
- Asegúrate de incluir `X-Company-Id` con el ID correcto de la empresa
- Sincroniza la empresa con: `php artisan whatsapp:sync-company {id}`

### WhatsApp no conecta
- Revisa los logs en `logs/whatsapp-*.log`
- Verifica que el directorio `storage/sessions` tenga permisos
- Escanea el código QR desde `/api/whatsapp/qr`
- Asegúrate de que la empresa esté sincronizada en la base de datos `larawhatsapp`

### Empresa no aparece en WhatsApp API
- Ejecuta: `php artisan whatsapp:sync-company {id}` para sincronizar una empresa específica
- Ejecuta: `php artisan whatsapp:sync-all-companies` para sincronizar todas las empresas
- Verifica que la empresa tenga `whatsapp_api_key` configurada en Laravel

### Base de datos
- Verifica que MySQL esté ejecutándose
- Confirma las credenciales en `.env` apunten a `larawhatsapp`
- Verifica que la tabla `companies` exista en la base de datos `larawhatsapp`
- Ejecuta las migraciones SQL manualmente si es necesario

### Sincronización de empresas
- **Error al crear empresa**: Verifica que la conexión `whatsapp_api` esté configurada en `config/database.php`
- **API key no generada**: Asegúrate de que el componente Livewire de creación de empresas esté actualizado
- **Sincronización fallida**: Revisa los logs de Laravel en `storage/logs/laravel.log`

### Errores de Anti-Bloqueo
- **"Mensajes no permitidos fuera del horario comercial"**: Solo se permiten mensajes de 7 AM a 10 PM, lunes a sábado
- **"Demasiados mensajes al mismo usuario"**: Espera 30 segundos entre mensajes al mismo número
- **"Límite de mensajes por usuario excedido"**: Máximo 10 mensajes/hora o 50 mensajes/día por usuario
- **"Mensaje duplicado detectado"**: No se permite enviar el mismo mensaje repetidamente
- **"Número de prueba no permitido"**: Números como 1234567890 están bloqueados por seguridad
- **"Patrón spam detectado"**: El contenido del mensaje parece spam

### Solución rápida para errores de anti-bloqueo
1. **Verifica el horario**: Asegúrate de estar en horario comercial (7 AM - 10 PM)
2. **Espacio entre mensajes**: Espera 30 segundos antes de reenviar al mismo número
3. **Verifica límites**: No excedas 10 mensajes/hora o 50 mensajes/día por usuario
4. **Revisa el contenido**: Evita mensajes vacíos, spam o duplicados
5. **Contacta soporte**: Si el error persiste, revisa los logs en `logs/error-*.log`
