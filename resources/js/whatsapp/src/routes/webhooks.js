const express = require('express');
const crypto = require('crypto');
const router = express.Router();
const logger = require('../utils/logger');

function verifyHmacSignature(req, secret) {
  try {
    const signature = req.get('X-Hub-Signature-256') || req.get('X-Signature') || '';
    if (!secret || !signature) return false;
    const payload = JSON.stringify(req.body || {});
    const hmac = crypto.createHmac('sha256', secret);
    const digest = 'sha256=' + hmac.update(payload).digest('hex');
    return crypto.timingSafeEqual(Buffer.from(digest), Buffer.from(signature));
  } catch {
    return false;
  }
}

router.post('/', (req, res) => {
  const secret = process.env.WEBHOOK_SECRET || '';
  const valid = verifyHmacSignature(req, secret);
  if (!valid) {
    logger.security('webhook-invalid-signature', { ip: req.ip, url: req.originalUrl });
    return res.status(401).json({ success: false, error: 'Invalid webhook signature' });
  }
  logger.info('Webhook received', { body: req.body });
  res.json({ success: true });
});

module.exports = router;
