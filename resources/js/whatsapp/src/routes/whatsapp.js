const express = require('express');
const rateLimit = require('express-rate-limit');
const { body, validationResult } = require('express-validator');
const router = express.Router();
const logger = require('../utils/logger');
const { validateApiKey } = require('./companies');
const rateLimitByCompany = require('../middleware/rateLimitByCompany');
const WhatsAppController = require('../controllers/WhatsAppController');

// Aplicar validación de API key a todas las rutas
router.use(validateApiKey);



// Rutas multi-tenant
router.get('/status', WhatsAppController.getStatus);
router.post('/connect', WhatsAppController.connect);
router.delete('/disconnect', WhatsAppController.disconnect);
router.post('/force-reconnect', WhatsAppController.forceReconnect);
router.get('/qr', WhatsAppController.getQRCode);
router.post(
  '/send',
  rateLimitByCompany,
  [
    body('to')
      .exists().withMessage('El campo "to" es requerido')
      .isString().withMessage('"to" debe ser texto')
      .trim()
      .matches(/^\+?\d{10,15}$/).withMessage('Número "to" inválido (10-15 dígitos)'),
    body('message')
      .exists().withMessage('El campo "message" es requerido')
      .isString().withMessage('"message" debe ser texto')
      .isLength({ min: 2, max: 4096 }).withMessage('Longitud de "message" fuera de rango'),
    body('type')
      .optional()
      .isIn(['text', 'image', 'document', 'audio', 'video']).withMessage('Tipo de mensaje inválido'),
    body('mediaUrl')
      .optional()
      .isURL().withMessage('mediaUrl debe ser una URL válida')
  ],
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      logger.warn('Validación de payload fallida en /send', { errors: errors.array() });
      return res.status(400).json({
        success: false,
        error: 'Error de validación',
        details: errors.array()
      });
    }
    return WhatsAppController.sendMessage(req, res);
  }
);
router.post(
  '/send-document',
  rateLimitByCompany,
  WhatsAppController.upload.single('document'),
  [
    body('to')
      .exists().withMessage('El campo "to" es requerido')
      .isString().withMessage('"to" debe ser texto')
      .trim()
      .matches(/^\+?\d{10,15}$/).withMessage('Número "to" inválido (10-15 dígitos)'),
    body('caption')
      .optional()
      .isString().isLength({ max: 1024 }).withMessage('Caption demasiado largo')
  ],
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      logger.warn('Validación de payload fallida en /send-document', { errors: errors.array() });
      return res.status(400).json({
        success: false,
        error: 'Error de validación',
        details: errors.array()
      });
    }
    return WhatsAppController.sendDocument(req, res);
  }
);
router.get('/messages', WhatsAppController.getMessages);

// Mensajes interactivos (botones)
router.post(
  '/send-interactive',
  rateLimitByCompany,
  [
    body('to').exists().isString().trim().matches(/^\+?\d{10,15}$/),
    body('body').exists().isString().isLength({ min: 2, max: 4096 }),
    body('buttons').isArray({ min: 1, max: 3 })
  ],
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      logger.warn('Validación de payload fallida en /send-interactive', { errors: errors.array() });
      return res.status(400).json({ success: false, error: 'Error de validación', details: errors.array() });
    }
    return WhatsAppController.sendInteractive(req, res);
  }
);

// Plantillas Cloud API (opcional)
router.post(
  '/send-template',
  rateLimitByCompany,
  [
    body('to').exists().isString().trim().matches(/^\+?\d{10,15}$/),
    body('template_name').exists().isString(),
    body('language').exists().isString(),
    body('components').optional().isArray()
  ],
  (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      logger.warn('Validación de payload fallida en /send-template', { errors: errors.array() });
      return res.status(400).json({ success: false, error: 'Error de validación', details: errors.array() });
    }
    return WhatsAppController.sendTemplate(req, res);
  }
);








module.exports = router;