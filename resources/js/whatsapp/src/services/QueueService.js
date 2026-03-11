let redis;
try {
  redis = require('../config/redis');
} catch (error) {
  redis = null;
}
const logger = require('../utils/logger');

class QueueService {
  constructor() {
    this.queueKey = 'whatsapp:message_queue';
    this.processingKey = 'whatsapp:processing';
    this.isProcessing = false;
    this.whatsappService = null;
    this.memoryQueue = [];
  }

  setWhatsAppService(service) {
    this.whatsappService = service;
  }

  async addMessage(messageData) {
    try {
      const message = {
        id: Date.now().toString(),
        ...messageData,
        timestamp: new Date().toISOString(),
        retries: 0
      };

      if (redis) {
        await redis.lPush(this.queueKey, JSON.stringify(message));
        logger.info(`Message queued: ${message.id}`);
        
        if (!this.isProcessing) {
          this.processQueue();
        }
      } else {
        // Fallback a memoria
        this.memoryQueue.push(message);
        logger.warn('Redis not available, processing message from memory queue');
        await this.processMessage(this.memoryQueue.shift());
      }
      
      return message.id;
    } catch (error) {
      logger.error('Error adding message to queue:', error);
      throw error;
    }
  }

  async processQueue() {
    if (this.isProcessing || !redis) return;
    
    this.isProcessing = true;
    logger.info('Starting queue processing');

    try {
      while (true) {
        const messageStr = await redis.brPop(redis.commandOptions({ isolated: true }), this.queueKey, 5);
        if (!messageStr) break;

        const message = JSON.parse(messageStr.element);
        await this.processMessage(message);
      }
    } catch (error) {
      logger.error('Queue processing error:', error);
    } finally {
      this.isProcessing = false;
      logger.info('Queue processing stopped');
    }
  }

  async processMessage(message) {
    try {
      if (!this.whatsappService) {
        throw new Error('WhatsAppService instance not set in QueueService');
      }
      await this.whatsappService.sendMessage(message.to, message.content, {
        type: message.type || 'text',
        companyId: message.companyId
      });
      logger.info(`Message processed: ${message.id}`);
    } catch (error) {
      logger.error(`Failed to process message ${message.id}:`, error);
      
      if (message.retries < 3 && redis) {
        message.retries++;
        await redis.lPush(this.queueKey, JSON.stringify(message));
        logger.info(`Message requeued: ${message.id}, retry: ${message.retries}`);
      } else {
        logger.error(`Message failed permanently: ${message.id}`);
      }
    }
  }

  async getQueueSize() {
    if (redis) return await redis.lLen(this.queueKey);
    return this.memoryQueue.length;
  }
}

module.exports = new QueueService();
