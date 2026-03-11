-- Tabla de consentimientos (opt-in / opt-out)
CREATE TABLE IF NOT EXISTS consents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  companyId INT NOT NULL,
  phone VARCHAR(20) NOT NULL,
  optedIn BOOLEAN DEFAULT TRUE,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_company_phone (companyId, phone)
);
