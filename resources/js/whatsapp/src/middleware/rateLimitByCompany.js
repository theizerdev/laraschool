const rateLimit = require('express-rate-limit');

// Crear el limiter UNA sola vez en la inicialización de la app.
// Usamos valores dinámicos basados en req.company mediante funciones en max/keyGenerator/message.
const rateLimitByCompany = rateLimit({
  windowMs: 60 * 1000, // 1 minuto
  // Valor dinámico de límite por minuto según la compañía
  max: (req) => {
    const perMinute = req?.company?.rateLimitPerMinute;
    return typeof perMinute === 'number' && perMinute > 0 ? perMinute : 30;
  },
  // Agrupar por compañía (multi-tenant)
  keyGenerator: (req) => {
    const companyId = req?.company?.id ?? 'global';
    return `company_${companyId}`;
  },
  // Mensaje informativo con el límite de la compañía
  message: (req) => ({
    success: false,
    error: `Límite de ${req?.company?.rateLimitPerMinute ?? 30} mensajes por minuto excedido`
  }),
  standardHeaders: true,
  legacyHeaders: false,
  // Opcional: desactivar validaciones si fuese necesario
  // validate: false,
});

module.exports = rateLimitByCompany;
