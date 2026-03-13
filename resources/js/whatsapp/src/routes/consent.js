const express = require('express');
const router = express.Router();
const { validateApiKey } = require('./companies');
const { body, query, validationResult } = require('express-validator');
const Consent = require('../models/Consent');
const logger = require('../utils/logger');

router.use(validateApiKey);

router.post(
  '/opt-in',
  [body('phone').exists().isString().trim().matches(/^\+?\d{10,15}$/)],
  async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ success: false, error: 'Validación', details: errors.array() });
    try {
      const phone = (req.body.phone || '').replace(/\D/g, '');
      await Consent.upsert({ companyId: req.company.company_id, phone, optedIn: true });
      logger.info('Consent opt-in', { companyId: req.company.company_id, phone });
      return res.json({ success: true });
    } catch (error) {
      logger.error('Consent opt-in error', { error: error.message });
      return res.status(500).json({ success: false, error: error.message });
    }
  }
);

router.post(
  '/opt-out',
  [body('phone').exists().isString().trim().matches(/^\+?\d{10,15}$/)],
  async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ success: false, error: 'Validación', details: errors.array() });
    try {
      const phone = (req.body.phone || '').replace(/\D/g, '');
      await Consent.upsert({ companyId: req.company.company_id, phone, optedIn: false });
      logger.info('Consent opt-out', { companyId: req.company.company_id, phone });
      return res.json({ success: true });
    } catch (error) {
      logger.error('Consent opt-out error', { error: error.message });
      return res.status(500).json({ success: false, error: error.message });
    }
  }
);

router.get(
  '/status',
  [query('phone').exists().isString().trim().matches(/^\+?\d{10,15}$/)],
  async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) return res.status(400).json({ success: false, error: 'Validación', details: errors.array() });
    try {
      const phone = (req.query.phone || '').replace(/\D/g, '');
      const consent = await Consent.findOne({ where: { companyId: req.company.company_id, phone } });
      return res.json({ success: true, optedIn: consent ? !!consent.optedIn : null });
    } catch (error) {
      return res.status(500).json({ success: false, error: error.message });
    }
  }
);

module.exports = router;
