-- Cadastro de ingredientes e tabelas de personalização de produtos

CREATE TABLE IF NOT EXISTS ingredients (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id  INT UNSIGNED NOT NULL, -- Certifique-se de que o tipo é INT UNSIGNED
  name        VARCHAR(200) NOT NULL,
  min_qty     INT          NOT NULL DEFAULT 0,
  max_qty     INT          NOT NULL DEFAULT 1,
  image_path  VARCHAR(255) DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ingredients_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE companies MODIFY id INT UNSIGNED AUTO_INCREMENT;
