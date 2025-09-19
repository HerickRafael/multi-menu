-- Tabelas de personalização de produtos
CREATE TABLE IF NOT EXISTS product_custom_groups (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  name       VARCHAR(200) NOT NULL,
  type       ENUM('single','extra','addon','component') NOT NULL DEFAULT 'extra',
  min_qty    INT NOT NULL DEFAULT 0,
  max_qty    INT NOT NULL DEFAULT 99,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_pcg_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_custom_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id   INT UNSIGNED NOT NULL,
  label      VARCHAR(200) NOT NULL,
  delta      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_pci_group FOREIGN KEY (group_id) REFERENCES product_custom_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
