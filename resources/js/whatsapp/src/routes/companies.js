const express = require('express');
const router = express.Router();
const jwt = require('jsonwebtoken');
const Company = require('../models/Company');
const logger = require('../utils/logger');

// Middleware de autenticación JWT
const authenticateToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
        return res.status(401).json({ error: 'Token de autorización requerido' });
    }

    const jwtSecret = process.env.JWT_SECRET || 'base64:ItiVlmjSSgrh2LFDfR0JGtPXHRAthPOWSMw6WyrgwIk=';
    jwt.verify(token, jwtSecret, (err, user) => {
        if (err) {
            return res.status(403).json({ error: 'Token inválido' });
        }
        req.user = user;
        next();
    });
};

// Registrar empresa
router.post('/register', authenticateToken, async (req, res) => {
    try {
        const { name, api_key, webhook_url, rate_limit_per_minute } = req.body;

        if (!name || !api_key) {
            return res.status(400).json({ 
                success: false, 
                error: 'name y api_key son requeridos' 
            });
        }

        const company = await Company.create({
            name,
            apiKey: api_key,
            webhookUrl: webhook_url,
            rateLimitPerMinute: rate_limit_per_minute || 60,
            isActive: true
        });

        logger.info(`Empresa registrada: ${name} (ID: ${company.id})`);

        res.json({
            success: true,
            message: 'Empresa registrada exitosamente',
            company_id: company.id,
            api_key: company.apiKey
        });

    } catch (error) {
        logger.error('Error registrando empresa:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Listar empresas
router.get('/list', authenticateToken, async (req, res) => {
    try {
        const companies = await Company.findAll({
            attributes: ['id', 'name', 'apiKey', 'isActive', 'createdAt']
        });

        res.json({
            success: true,
            companies
        });
    } catch (error) {
        logger.error('Error listando empresas:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Validar API key
const validateApiKey = async (req, res, next) => {
    try {
        const apiKey = req.headers['x-api-key'];
        const companyId = req.headers['x-company-id'];

        if (!apiKey) {
            return res.status(401).json({ 
                success: false,
                error: 'API key requerida en header X-API-Key' 
            });
        }

        let company;
        
        if (companyId) {
            company = await Company.findOne({
                where: {
                    id: parseInt(companyId),
                    apiKey: apiKey,
                    isActive: true
                }
            });
        } else {
            company = await Company.findOne({
                where: {
                    apiKey: apiKey,
                    isActive: true
                }
            });
        }

        if (!company) {
            return res.status(401).json({ 
                success: false,
                error: 'API key inválida o empresa inactiva',
                status_code: 401
            });
        }

        req.company = {
            id: company.id,
            company_id: company.id,
            name: company.name,
            api_key: company.apiKey,
            is_active: company.isActive,
            rateLimitPerMinute: company.rateLimitPerMinute,
            dailyMessageLimit: company.dailyMessageLimit
        };
        
        next();
    } catch (error) {
        logger.error('Error validando API key:', error);
        res.status(500).json({ 
            success: false,
            error: 'Error interno del servidor' 
        });
    }
};

module.exports = { router, validateApiKey };
