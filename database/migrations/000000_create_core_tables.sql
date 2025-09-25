SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL,
  name VARCHAR(150) NOT NULL,
  whatsapp VARCHAR(20) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  highlight_text TEXT DEFAULT NULL,
  min_order DECIMAL(10,2) DEFAULT NULL,
  avg_delivery_min_from INT DEFAULT NULL,
  avg_delivery_min_to INT DEFAULT NULL,
  logo VARCHAR(255) DEFAULT NULL,
  banner VARCHAR(255) DEFAULT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY companies_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  KEY categories_company_idx (company_id),
  CONSTRAINT categories_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS company_hours (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  weekday TINYINT NOT NULL,
  is_open TINYINT(1) NOT NULL DEFAULT 0,
  open1 TIME DEFAULT NULL,
  close1 TIME DEFAULT NULL,
  open2 TIME DEFAULT NULL,
  close2 TIME DEFAULT NULL,
  UNIQUE KEY company_day_unique (company_id, weekday),
  CONSTRAINT company_hours_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  whatsapp VARCHAR(20) NOT NULL,
  whatsapp_e164 VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login_at DATETIME DEFAULT NULL,
  UNIQUE KEY customers_company_whatsapp_unique (company_id, whatsapp_e164),
  CONSTRAINT customers_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_phone VARCHAR(20) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','completed','canceled') NOT NULL DEFAULT 'pending',
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY orders_company_status_idx (company_id, status),
  CONSTRAINT orders_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  promo_price DECIMAL(10,2) DEFAULT NULL,
  sku VARCHAR(100) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  type ENUM('simple','combo') NOT NULL DEFAULT 'simple',
  price_mode ENUM('fixed','sum') NOT NULL DEFAULT 'fixed',
  allow_customize TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME DEFAULT NULL,
  KEY products_company_idx (company_id),
  KEY products_category_idx (category_id),
  CONSTRAINT products_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT products_category_fk FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS combo_groups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  type ENUM('single','remove','add','swap','component','extra','addon') DEFAULT 'single',
  min_qty INT DEFAULT 0,
  max_qty INT DEFAULT 1,
  sort INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY combo_groups_product_idx (product_id),
  CONSTRAINT combo_groups_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS combo_group_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL,
  simple_product_id INT UNSIGNED NOT NULL,
  delta_price DECIMAL(10,2) DEFAULT 0.00,
  is_default TINYINT(1) DEFAULT 0,
  allow_customize TINYINT(1) NOT NULL DEFAULT 0,
  sort INT DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY combo_group_items_group_idx (group_id),
  KEY combo_group_items_product_idx (simple_product_id),
  CONSTRAINT combo_group_items_group_fk FOREIGN KEY (group_id) REFERENCES combo_groups(id) ON DELETE CASCADE,
  CONSTRAINT combo_group_items_product_fk FOREIGN KEY (simple_product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  KEY order_items_order_idx (order_id),
  KEY order_items_product_idx (product_id),
  CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT order_items_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('root','owner','staff') NOT NULL DEFAULT 'owner',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY users_email_unique (email),
  KEY users_company_idx (company_id),
  CONSTRAINT users_company_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
