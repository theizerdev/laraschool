const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/database');

const Consent = sequelize.define('Consent', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  companyId: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  phone: {
    type: DataTypes.STRING,
    allowNull: false
  },
  optedIn: {
    type: DataTypes.BOOLEAN,
    defaultValue: true
  }
}, {
  tableName: 'consents',
  timestamps: true,
  indexes: [
    { fields: ['companyId', 'phone'], unique: true }
  ]
});

module.exports = Consent;
