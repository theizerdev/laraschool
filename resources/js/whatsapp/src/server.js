const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');
const rateLimit = require('express-rate-limit');
const { createServer } = require('http');
const { Server } = require('socket.io');
require('dotenv').config();

const logger = require('./utils/logger');
const { sequelize } = require('./config/database');
const redis = require('./config/redis');
const WhatsAppService = require('./services/WhatsAppService');
const authMiddleware = require('./middleware/auth');
const errorHandler = require('./middleware/errorHandler');

// Importar rutas
const authRoutes = require('./routes/auth');
const whatsappRoutes = require('./routes/whatsapp');
const messageRoutes = require('./routes/messages');
const sessionRoutes = require('./routes/sessions');
const webhookRoutes = require('./routes/webhooks');
const statsRoutes = require('./routes/stats');

class WhatsAppAPIServer {
  constructor() {
    this.app = express();
    this.server = createServer(this.app);
    this.io = new Server(this.server, {
      cors: {
        origin: process.env.CORS_ORIGIN || "http://localhost:8000",
        methods: ["GET", "POST"]
      }
    });
    this.port = process.env.PORT || 3001;
    this.whatsappService = null;
  }

  async initialize() {
    try {
      // Configurar middlewares
      this.setupMiddlewares();
      
      // Configurar rutas
      this.setupRoutes();
      
      // Configurar manejo de errores
      this.setupErrorHandling();
      
      // Conectar a base de datos
      await sequelize.authenticate();
      await sequelize.sync({ alter: true });
      logger.info('✅ Base de datos conectada');
      
      // Crear empresa por defecto si no existe
      const Company = require('./models/Company');
      const defaultCompany = await Company.findOrCreate({
        where: { apiKey: 'test-api-key-vargas-centro' },
        defaults: {
          name: 'U.E VARGAS II',
          apiKey: 'test-api-key-vargas-centro',
          rateLimitPerMinute: 60,
          isActive: true
        }
      });
      
      if (defaultCompany[1]) {
        logger.info('✅ Empresa por defecto creada');
      }
      
      // Redis deshabilitado - usando modo sin cache
      logger.info('✅ Modo sin cache activado');
      
      // Inicializar servicio de WhatsApp
      this.whatsappService = new WhatsAppService(this.io);
      await this.whatsappService.initialize();
      
      // Hacer disponible globalmente
      this.app.locals.whatsappService = this.whatsappService;
      
      logger.info('✅ Servicio WhatsApp inicializado');
      
      // Configurar Socket.IO
      this.setupSocketIO();
      
      // Iniciar servidor
      this.start();
      
    } catch (error) {
      logger.error('❌ Error inicializando servidor:', error);
      process.exit(1);
    }
  }

  setupMiddlewares() {
    // Seguridad
    if (process.env.HELMET_ENABLED === 'true') {
      this.app.use(helmet({
        contentSecurityPolicy: false,
        crossOriginEmbedderPolicy: false
      }));
    }

    // CORS
    this.app.use(cors({
      origin: process.env.CORS_ORIGIN?.split(',') || ['http://localhost:8000'],
      credentials: true,
      methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
      allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With']
    }));

    // Compresión
    this.app.use(compression());

    // Rate limiting global
    const globalLimiter = rateLimit({
      windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 60000,
      max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100,
      message: {
        success: false,
        error: 'Demasiadas solicitudes, intenta más tarde'
      },
      standardHeaders: true,
      legacyHeaders: false
    });
    this.app.use('/api/', globalLimiter);

    // Parsers
    this.app.use(express.json({ limit: '10mb' }));
    this.app.use(express.urlencoded({ extended: true, limit: '10mb' }));

    // Trust proxy si está configurado
    if (process.env.TRUST_PROXY === 'true') {
      this.app.set('trust proxy', 1);
    }

    // Logging de requests
    this.app.use((req, res, next) => {
      logger.info(`${req.method} ${req.path}`, {
        ip: req.ip,
        userAgent: req.get('User-Agent'),
        timestamp: new Date().toISOString()
      });
      next();
    });
  }

  setupRoutes() {
    // Ruta de salud
    this.app.get('/health', (req, res) => {
      res.json({
        success: true,
        message: 'WhatsApp API v2 funcionando correctamente',
        version: process.env.API_VERSION || 'v2',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
      });
    });

    // Rutas de API
    this.app.use('/api/auth', authRoutes);
    this.app.use('/api/whatsapp', whatsappRoutes);
    this.app.use('/api/messages', authMiddleware, messageRoutes);
    this.app.use('/api/sessions', authMiddleware, sessionRoutes);
    this.app.use('/api/webhooks', webhookRoutes);
    this.app.use('/api/stats', authMiddleware, statsRoutes);
    const { router: companiesRouter } = require('./routes/companies');
    this.app.use('/api/companies', companiesRouter);

    // Ruta para servir archivos estáticos
    this.app.use('/uploads', express.static('storage/uploads'));

    // Ruta 404
    this.app.use('*', (req, res) => {
      res.status(404).json({
        success: false,
        error: 'Endpoint no encontrado',
        path: req.originalUrl,
        method: req.method
      });
    });
  }

  setupErrorHandling() {
    this.app.use(errorHandler);

    // Manejo de errores no capturados
    process.on('uncaughtException', (error) => {
      logger.error('Excepción no capturada:', error);
      process.exit(1);
    });

    process.on('unhandledRejection', (reason, promise) => {
      logger.error('Promesa rechazada no manejada:', { reason, promise });
    });

    // Manejo de señales del sistema
    process.on('SIGTERM', () => {
      logger.info('Recibida señal SIGTERM, cerrando servidor...');
      this.gracefulShutdown();
    });

    process.on('SIGINT', () => {
      logger.info('Recibida señal SIGINT, cerrando servidor...');
      this.gracefulShutdown();
    });
  }

  setupSocketIO() {
    this.io.on('connection', (socket) => {
      logger.info(`Cliente conectado: ${socket.id}`);

      socket.on('join-session', (sessionId) => {
        socket.join(`session-${sessionId}`);
        logger.info(`Cliente ${socket.id} se unió a sesión ${sessionId}`);
      });

      socket.on('disconnect', () => {
        logger.info(`Cliente desconectado: ${socket.id}`);
      });
    });
  }

  start() {
    this.server.listen(this.port, () => {
      logger.info(`🚀 WhatsApp API v2 ejecutándose en puerto ${this.port}`);
      logger.info(`📱 Entorno: ${process.env.NODE_ENV || 'development'}`);
      logger.info(`🔗 Health check: http://localhost:${this.port}/health`);
    });
  }

  async gracefulShutdown() {
    logger.info('Iniciando cierre graceful...');
    
    try {
      // Cerrar servidor HTTP
      this.server.close(() => {
        logger.info('Servidor HTTP cerrado');
      });

      // Cerrar conexiones de WhatsApp
      if (this.whatsappService) {
        await this.whatsappService.shutdown();
        logger.info('Servicio WhatsApp cerrado');
      }

      // Cerrar conexiones de base de datos y Redis
      // TODO: Implementar cierre de conexiones

      logger.info('Cierre graceful completado');
      process.exit(0);
    } catch (error) {
      logger.error('Error durante cierre graceful:', error);
      process.exit(1);
    }
  }
}

// Inicializar y ejecutar servidor
const server = new WhatsAppAPIServer();
server.initialize();

module.exports = server;