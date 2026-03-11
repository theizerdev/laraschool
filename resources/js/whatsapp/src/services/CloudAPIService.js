const axios = require('axios');
const logger = require('../utils/logger');

class CloudAPIService {
  async sendTemplate({ to, template_name, language, components, companyId }) {
    try {
      const enabled = process.env.WHATSAPP_CLOUD_API_ENABLED === 'true';
      const token = process.env.WHATSAPP_TOKEN;
      const phoneId = process.env.WHATSAPP_PHONE_ID;
      if (!enabled || !token || !phoneId) {
        return { success: false, error: 'Cloud API not configured' };
      }
      const url = `https://graph.facebook.com/v20.0/${phoneId}/messages`;
      const payload = {
        messaging_product: 'whatsapp',
        to: to.replace(/\D/g, ''),
        type: 'template',
        template: {
          name: template_name,
          language: { code: language },
          components: components || []
        }
      };
      const res = await axios.post(url, payload, {
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }
      });
      logger.info('CloudAPI template sent', { companyId, to: payload.to, template_name });
      return { success: true, messageId: res.data?.messages?.[0]?.id || null, data: res.data };
    } catch (error) {
      logger.error('CloudAPI template error', { error: error.message });
      return { success: false, error: error.response?.data || error.message };
    }
  }
}

module.exports = new CloudAPIService();
