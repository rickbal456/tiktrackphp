-- Custom domains + pivot

CREATE TABLE IF NOT EXISTS custom_domains (
  id CHAR(36) NOT NULL PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  domain VARCHAR(255) NOT NULL UNIQUE,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  verification_method VARCHAR(32) NOT NULL DEFAULT 'dns_a',
  verification_token VARCHAR(500) NULL,
  verification_error TEXT NULL,
  ssl_mode VARCHAR(32) NOT NULL DEFAULT 'full',
  verified_at DATETIME(3) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_cd_user (user_id),
  INDEX idx_cd_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS landing_page_domains (
  id CHAR(36) NOT NULL PRIMARY KEY,
  landing_page_id CHAR(36) NOT NULL,
  custom_domain_id CHAR(36) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lpd_lp FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
  CONSTRAINT fk_lpd_cd FOREIGN KEY (custom_domain_id) REFERENCES custom_domains(id) ON DELETE CASCADE,
  UNIQUE KEY uq_lp_domain (landing_page_id, custom_domain_id),
  INDEX idx_lpd_domain (custom_domain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
