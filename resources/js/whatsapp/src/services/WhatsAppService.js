const { 
  default: makeWASocket, 
  DisconnectReason, 
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
  Browsers
} = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const fs = require('fs').promises;
const path = require('path');
const logger = require('../utils/logger');
const QueueService = require('./QueueService');
const Message = require('../models/Message');
const Session = require('../models/Session');

class WhatsAppService {
  constructor(io) {
    this.io = io;
    this.sock = null;
    this.qrCode = null;
    this.isConnected = false;
    this.isConnecting = false;
    this.sessionPath = path.join('storage', 'sessions', process.env.WHATSAPP_SESSION_NAME || 'default');
    this.queueService = QueueService;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.connectionState = 'disconnected';
    this.lastSeen = null;
  }

  async initialize() {
    try {
      logger.whatsapp('Inicializando servicio WhatsApp...');
      
      // Crear directorio de sesión si no existe
      await this.ensureSessionDirectory();
      
      // Obtener versión más reciente de Baileys
      const { version, isLatest } = await fetchLatestBaileysVersion();
      logger.whatsapp(`Usando Baileys v${version.join('.')}, es la última: ${isLatest}`);
      

      
      // Conectar
      await this.connect();
      
    } catch (error) {
      logger.error('Error inicializando WhatsApp service:', error);
      throw error;
    }
  }

  async ensureSessionDirectory() {
    try {
      await fs.access(this.sessionPath);
    } catch {
      await fs.mkdir(this.sessionPath, { recursive: true });
      logger.whatsapp(`Directorio de sesión creado: ${this.sessionPath}`);
    }
  }

  async connect() {
    if (this.isConnecting) {
      logger.whatsapp('Ya hay una conexión en progreso...');
      return;
    }

    this.isConnecting = true;
    this.connectionState = 'connecting';
    
    try {
      logger.whatsapp('Inicializando autenticación...');
      
      // Manejo de sesiones corruptas o error "Bad MAC"
      let state, saveCreds;
      try {
        const authResult = await useMultiFileAuthState(this.sessionPath);
        state = authResult.state;
        saveCreds = authResult.saveCreds;
      } catch (authError) {
        logger.error('Error al cargar autenticación, limpiando sesión:', authError.message);
        if (authError.message?.includes('Bad MAC') || authError.message?.includes('corrupt')) {
          await this.clearCorruptedSession();
          // Reintentar después de limpiar
          const authResult = await useMultiFileAuthState(this.sessionPath);
          state = authResult.state;
          saveCreds = authResult.saveCreds;
        } else {
          throw authError;
        }
      }
      
      logger.whatsapp('Autenticación cargada exitosamente');
      
      // Crear socket
      this.sock = makeWASocket({
        version: (await fetchLatestBaileysVersion()).version,
        auth: {
          creds: state.creds,
          keys: makeCacheableSignalKeyStore(state.keys, {
            trace: () => {},
            debug: () => {},
            info: () => {},
            warn: () => {},
            error: () => {},
            child: () => ({
              trace: () => {},
              debug: () => {},
              info: () => {},
              warn: () => {},
              error: () => {},
              child: () => ({})
            })
          })
        },
        browser: Browsers.macOS('Desktop'),
        printQRInTerminal: false,
        generateHighQualityLinkPreview: true,
        syncFullHistory: false,
        markOnlineOnConnect: true,
        logger: {
          trace: () => {},
          debug: () => {},
          info: () => {},
          warn: () => {},
          error: () => {},
          child: () => ({
            trace: () => {},
            debug: () => {},
            info: () => {},
            warn: () => {},
            error: () => {},
            child: () => ({})
          })
        },
        getMessage: async (key) => {
          return { conversation: 'Mensaje no disponible' };
        }
      });

      // Configurar event handlers
      this.setupEventHandlers(saveCreds);
      
      // Si hay credenciales existentes pero no nos conectamos inmediatamente, 
      // esperar un momento para ver si hay error de autenticación
      if (state.creds && state.creds.registered) {
        logger.whatsapp('Credenciales existentes detectadas, monitoreando conexión...');
        
        // Dar tiempo para que ocurra el error 401 si las credenciales están corruptas
        setTimeout(async () => {
          if (!this.isConnected && this.connectionState === 'connecting') {
            logger.whatsapp('Conexión pendiente con credenciales existentes, verificando estado...');
            
            // Si después de 5 segundos no estamos conectados, forzar reconexión
            setTimeout(async () => {
              if (!this.isConnected && this.connectionState === 'connecting') {
                logger.whatsapp('Timeout en conexión con credenciales existentes, forzando reconexión...');
                await this.forceReconnect();
              }
            }, 5000);
          }
        }, 3000);
      }
      
    } catch (error) {
      logger.error('Error conectando a WhatsApp:', error);
      this.isConnecting = false;
      this.connectionState = 'error';
      throw error;
    }
  }

  setupEventHandlers(saveCreds) {
    // Manejo de actualizaciones de conexión
    this.sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;
      
      logger.whatsapp('Actualización de conexión:', { 
        connection, 
        lastDisconnect: lastDisconnect?.error?.output?.statusCode 
      });

      if (qr) {
        await this.handleQRCode(qr);
      }

      if (connection === 'close') {
        await this.handleDisconnection(lastDisconnect);
      } else if (connection === 'open') {
        await this.handleConnection();
      }
    });

    // Guardar credenciales cuando cambien
    this.sock.ev.on('creds.update', saveCreds);

    // Manejo de mensajes entrantes
    this.sock.ev.on('messages.upsert', async (messageUpdate) => {
      await this.handleIncomingMessages(messageUpdate);
    });

    // Manejo de actualizaciones de mensajes (entregado, leído, etc.)
    this.sock.ev.on('messages.update', async (messageUpdates) => {
      await this.handleMessageUpdates(messageUpdates);
    });

    // Manejo de presencia (en línea, escribiendo, etc.)
    this.sock.ev.on('presence.update', async (presenceUpdate) => {
      logger.whatsapp('Actualización de presencia:', presenceUpdate);
    });

    // Manejo de contactos
    this.sock.ev.on('contacts.upsert', async (contacts) => {
      logger.whatsapp(`Contactos actualizados: ${contacts.length}`);
    });

    // Manejo de chats
    this.sock.ev.on('chats.upsert', async (chats) => {
      logger.whatsapp(`Chats actualizados: ${chats.length}`);
    });
  }

  async handleQRCode(qr) {
    try {
      this.qrCode = await QRCode.toDataURL(qr);
      this.connectionState = 'qr_ready';
      
      logger.whatsapp('Código QR generado');
      
      // Emitir QR a clientes conectados
      this.io.emit('qr-code', {
        qr: this.qrCode,
        timestamp: new Date().toISOString()
      });
      
      // Mostrar QR en terminal para desarrollo
      if (process.env.NODE_ENV === 'development') {
        const QRTerminal = require('qrcode-terminal');
        QRTerminal.generate(qr, { small: true });
      }
      
    } catch (error) {
      logger.error('Error generando código QR:', error);
    }
  }

  async handleConnection() {
    this.isConnected = true;
    this.isConnecting = false;
    this.connectionState = 'connected';
    this.qrCode = null;
    this.reconnectAttempts = 0;
    this.lastSeen = new Date();
    
    logger.whatsapp('✅ Conectado a WhatsApp exitosamente');
    
    // Emitir estado de conexión
    this.io.emit('connection-status', {
      status: 'connected',
      timestamp: new Date().toISOString(),
      user: this.sock.user
    });
  }

  async forceDisconnectAndCleanup() {
    try {
      logger.whatsapp('Forzando desconexión y limpieza por error de autenticación...');
      
      // Cerrar socket si existe
      if (this.sock) {
        await this.sock.logout();
        this.sock = null;
      }
      
      // Limpiar estado
      this.isConnected = false;
      this.isConnecting = false;
      this.qrCode = null;
      this.connectionState = 'disconnected';
      
      // Limpiar sesión corrupta
      await this.clearCorruptedSession();
      
      // Emitir estado
      this.io.emit('connection-status', {
        status: 'disconnected',
        reason: 'authentication_error',
        timestamp: new Date().toISOString()
      });
      
      logger.whatsapp('Desconexión y limpieza completadas');
      
    } catch (error) {
      logger.error('Error al forzar desconexión y limpieza:', error);
    }
  }

  async handleDisconnection(lastDisconnect) {
    this.isConnected = false;
    this.isConnecting = false;
    this.qrCode = null;
    
    const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
    const reason = lastDisconnect?.error?.output?.statusCode;
    
    logger.whatsapp('Desconectado de WhatsApp:', { 
      reason, 
      shouldReconnect,
      reconnectAttempts: this.reconnectAttempts 
    });
    
    // Si es error 401 (unauthorized), limpiar sesión y forzar nueva autenticación
    if (reason === 401) {
      logger.whatsapp('Error 401 detectado, limpiando sesión para forzar nueva autenticación');
      await this.clearCorruptedSession();
      this.reconnectAttempts = 0; // Resetear contador para permitir nueva conexión
    }
    
    this.connectionState = shouldReconnect ? 'reconnecting' : 'disconnected';
    
    // Emitir estado de desconexión
    this.io.emit('connection-status', {
      status: this.connectionState,
      reason,
      timestamp: new Date().toISOString()
    });
    
    if (shouldReconnect && this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
      
      logger.whatsapp(`Reintentando conexión en ${delay}ms (intento ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
      
      setTimeout(() => {
        this.connect();
      }, delay);
    } else {
      this.connectionState = 'disconnected';
      logger.whatsapp('Máximo de reintentos alcanzado o sesión cerrada');
    }
  }

  async clearCorruptedSession() {
    try {
      logger.whatsapp('Limpiando sesión corrupta...');
      
      // Eliminar archivos de credenciales
      const fs = require('fs').promises;
      const path = require('path');
      
      // Listar archivos en el directorio de sesión
      try {
        const files = await fs.readdir(this.sessionPath);
        
        // Eliminar archivos de credenciales (pero mantener logs)
        for (const file of files) {
          if (file.endsWith('.json') && !file.includes('log')) {
            const filePath = path.join(this.sessionPath, file);
            await fs.unlink(filePath);
            logger.whatsapp(`Archivo eliminado: ${file}`);
          }
        }
        
        logger.whatsapp('Sesión corrupta limpiada exitosamente');
        
        // Resetear contador de reintentos
        this.reconnectAttempts = 0;
        
      } catch (dirError) {
        logger.error('Error al leer directorio de sesión:', dirError);
      }
      
    } catch (error) {
      logger.error('Error al limpiar sesión corrupta:', error);
    }
  }

  async handleIncomingMessages(messageUpdate) {
    const { messages, type } = messageUpdate;
    
    for (const message of messages) {
      if (message.key.fromMe) continue; // Ignorar mensajes propios
      
      logger.message('received', {
        from: message.key.remoteJid,
        messageType: Object.keys(message.message || {})[0],
        timestamp: message.messageTimestamp
      });
      
      // Procesar mensaje
      await this.processIncomingMessage(message);
      

    }
  }

  async handleMessageUpdates(messageUpdates) {
    for (const update of messageUpdates) {
      logger.message('updated', {
        messageId: update.key.id,
        status: update.update?.status,
        timestamp: new Date().toISOString()
      });
      
      // Actualizar estado en base de datos
      await Message.update(
        { status: update.update?.status || 'delivered' },
        { where: { messageId: update.key.id } }
      );
    }
  }

  async processIncomingMessage(message) {
    try {
      // Ignorar mensajes de estado y mensajes sin contenido
      if (message.key.remoteJid === 'status@broadcast' || !message.message) {
        return;
      }

      // Extraer información del mensaje
      const messageInfo = {
        id: message.key.id,
        from: message.key.remoteJid,
        timestamp: message.messageTimestamp,
        message: message.message,
        pushName: message.pushName
      };
      
      // Validar que el mensaje tenga contenido
      const messageContent = JSON.stringify(messageInfo.message);
      if (!messageContent || messageContent === 'null') {
        return;
      }
      
      // Guardar en base de datos (evitar errores por duplicado usando upsert)
      await Message.upsert({
        messageId: messageInfo.id,
        from: messageInfo.from,
        to: this.sock.user?.id || 'self',
        message: messageContent,
        type: 'text',
        status: 'delivered',
        companyId: 1
      });
      
      // Emitir a clientes conectados
      this.io.emit('message-received', messageInfo);
      
    } catch (error) {
      logger.error('Error procesando mensaje entrante:', error);
    }
  }

  async sendMessage(to, content, options = {}) {
    if (!this.isConnected) {
      throw new Error('WhatsApp no está conectado');
    }

    try {
      // Formatear número
      const jid = this.formatPhoneNumber(to);
      
      // Preparar mensaje
      let messageContent;
      if (typeof content === 'string') {
        messageContent = { text: content };
      } else {
        messageContent = content;
      }
      
      // Envío con reintentos y backoff exponencial
      const maxRetries = parseInt(process.env.WHATSAPP_MAX_RETRIES) || 3;
      const baseDelay = parseInt(process.env.WHATSAPP_RETRY_DELAY) || 2000; // ms
      let attempt = 0;
      let lastError = null;
      let result = null;
      
      while (attempt <= maxRetries) {
        try {
          result = await this.sock.sendMessage(jid, messageContent, options);
          
          logger.message('sent', {
            to: jid,
            messageId: result.key.id,
            timestamp: new Date().toISOString()
          });
          
          // Persistir éxito
          await Message.create({
            messageId: result.key.id,
            from: this.sock.user?.id || 'self',
            to: jid,
            message: typeof messageContent === 'string' ? messageContent : JSON.stringify(messageContent),
            type: options.type || 'text',
            status: 'sent',
            companyId: options.companyId || 1
          });
          
          return {
            success: true,
            messageId: result.key.id,
            timestamp: result.messageTimestamp
          };
        } catch (err) {
          lastError = err;
          attempt += 1;
          
          // Manejo específico del error "Bad MAC"
          if (err.message?.includes('Bad MAC')) {
            logger.error('Error de autenticación Bad MAC detectado, limpiando sesión...', {
              to: jid,
              attempt: attempt
            });
            
            // Si es el primer intento con Bad MAC, limpiar sesión y reintentar
            if (attempt === 1) {
              await this.forceDisconnectAndCleanup();
              // Reconectar después de limpiar
              try {
                await this.connect();
              } catch (reconnectError) {
                logger.error('Error al reconectar después de limpiar sesión:', reconnectError);
              }
              // Continuar con el siguiente intento
              continue;
            }
          }
          
          // Clasificar error (network/transient)
          const isTransient = this.isTransientError(err);
          
          logger.error('Error enviando mensaje (intento ' + attempt + ')', {
            error: err.message,
            to: jid,
            transient: isTransient
          });
          
          if (!isTransient || attempt > maxRetries) {
            break;
          }
          
          const delay = baseDelay * Math.pow(2, attempt - 1);
          await this.sleep(delay);
        }
      }
      
      // Registrar fallo final
      await Message.create({
        messageId: 'FAILED_' + Date.now().toString(),
        from: this.sock.user?.id || 'self',
        to: jid,
        message: typeof messageContent === 'string' ? messageContent : JSON.stringify(messageContent),
        type: options.type || 'text',
        status: 'failed',
        errorMessage: lastError?.message || 'Error desconocido',
        retryCount: Math.min(maxRetries, attempt),
        companyId: options.companyId || 1
      });
      
      throw lastError || new Error('Fallo desconocido enviando mensaje');
      
    } catch (error) {
      logger.error('Error enviando mensaje:', error);
      throw error;
    }
  }

   formatPhoneNumber(number) {
    // Remover caracteres no numéricos
    let cleaned = number.replace(/\D/g, '')
    
    // Si empieza con 58 (Venezuela), agregar @s.whatsapp.net
    if (cleaned.startsWith('58')) {
      return `${cleaned}@s.whatsapp.net`
    }
    
    // Si empieza con 0, removerlo y agregar @s.whatsapp.net
    if (cleaned.startsWith('0')) {
      cleaned = cleaned.substring(1)
    }
    
    // Por defecto, agregar @s.whatsapp.net
    return `${cleaned}@s.whatsapp.net`
  }
  
  isTransientError(err) {
    // Heurística simple: errores de red/transitorios o de autenticación que pueden resolverse
    const msg = (err?.message || '').toLowerCase();
    return (
      msg.includes('timeout') ||
      msg.includes('network') ||
      msg.includes('socket') ||
      msg.includes('econnreset') ||
      msg.includes('temporary') ||
      msg.includes('rate limit') ||
      msg.includes('bad mac') ||  // Error de autenticación que puede resolverse limpiando la sesión
      msg.includes('mac') ||       // Cualquier error relacionado con MAC
      msg.includes('auth')         // Errores de autenticación generales
    );
  }
  
  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  getStatus() {
    return {
      isConnected: this.isConnected,
      connectionState: this.connectionState,
      qrCode: this.qrCode,
      user: this.sock?.user || null,
      lastSeen: this.lastSeen,
      reconnectAttempts: this.reconnectAttempts
    };
  }

  getQRCode() {
    return this.qrCode;
  }

  async logout() {
    if (this.sock) {
      await this.sock.logout();
      logger.whatsapp('Sesión cerrada exitosamente');
    }
  }

  async forceReconnect() {
    try {
      logger.whatsapp('Forzando reconexión completa...');
      
      // Cerrar sesión actual si existe
      if (this.sock) {
        await this.sock.logout().catch(err => {
          logger.warn('Error al cerrar sesión durante forceReconnect:', err.message);
        });
        this.sock = null;
      }
      
      // Limpiar sesión y resetear estado
      await this.clearCorruptedSession();
      this.isConnected = false;
      this.isConnecting = false;
      this.qrCode = null;
      this.connectionState = 'disconnected';
      this.reconnectAttempts = 0;
      
      // Emitir estado de desconexión
      this.io.emit('connection-status', {
        status: 'disconnected',
        reason: 'force_reconnect',
        timestamp: new Date().toISOString()
      });
      
      // Esperar un momento antes de reconectar
      await this.sleep(2000);
      
      // Iniciar nueva conexión
      await this.connect();
      
      logger.whatsapp('Reconexión forzada completada');
      
    } catch (error) {
      logger.error('Error durante forceReconnect:', error);
      throw error;
    }
  }

  async shutdown() {
    logger.whatsapp('Cerrando servicio WhatsApp...');
    
    if (this.sock) {
      this.sock.end();
    }
    

    logger.whatsapp('Servicio WhatsApp cerrado');
  }
}

module.exports = WhatsAppService;