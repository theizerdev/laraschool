const logger = require('../utils/logger');

const errorHandler = (err, req, res, next) => {
  logger.error('Error no manejado:', {
    error: err.message,
    stack: err.stack,
    url: req.url,
    method: req.method,
    ip: req.ip,
    userAgent: req.get('User-Agent')
  });

  // Error de validación
  if (err.name === 'ValidationError') {
    return res.status(400).json({
      success: false,
      error: 'Error de validación',
      details: err.message
    });
  }

  // Error de Sequelize
  if (err.name === 'SequelizeError' || err.name?.startsWith('Sequelize')) {
    return res.status(500).json({
      success: false,
      error: 'Error de base de datos',
      details: err.message
    });
  }

  // Violación de unicidad (e.g., messageId duplicado)
  if (err.name === 'SequelizeUniqueConstraintError') {
    return res.status(409).json({
      success: false,
      error: 'Conflicto: registro duplicado',
      field: err.errors?.[0]?.path,
      value: err.errors?.[0]?.value
    });
  }

  // Error de sintaxis JSON
  if (err instanceof SyntaxError && err.status === 400 && 'body' in err) {
    return res.status(400).json({
      success: false,
      error: 'JSON inválido'
    });
  }

  // Error genérico
  res.status(err.status || 500).json({
    success: false,
    error: process.env.NODE_ENV === 'production' 
      ? 'Error interno del servidor' 
      : err.message,
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
};

module.exports = errorHandler;
