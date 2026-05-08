-- Landing pages + sections

CREATE TABLE IF NOT EXISTS landing_pages (
  id CHAR(36) NOT NULL PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  config_id CHAR(36) NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  click_id_param VARCHAR(64) NOT NULL DEFAULT 'ttclid',
  event_name VARCHAR(191) NOT NULL DEFAULT 'ClickButton',
  page_title VARCHAR(255) NOT NULL DEFAULT 'Please Wait...',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_lp_config FOREIGN KEY (config_id) REFERENCES tiktok_configs(id) ON DELETE SET NULL,
  INDEX idx_landing_pages_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS landing_page_sections (
  id CHAR(36) NOT NULL PRIMARY KEY,
  landing_page_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  urls JSON NOT NULL,
  delay_ms INT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lps_lp FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE CASCADE,
  INDEX idx_lps_lp (landing_page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
