-- Ensure ingredients.company_id matches companies.id for FK compatibility
SET @fk_name := (
  SELECT CONSTRAINT_NAME
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'ingredients'
    AND COLUMN_NAME = 'company_id'
    AND REFERENCED_TABLE_NAME = 'companies'
  LIMIT 1
);

SET @sql := IF(
  @fk_name IS NOT NULL,
  CONCAT('ALTER TABLE `ingredients` DROP FOREIGN KEY `', @fk_name, '`'),
  'SET @dummy = 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `ingredients`
  MODIFY `company_id` INT UNSIGNED NOT NULL;

ALTER TABLE `ingredients`
  ADD CONSTRAINT `fk_ingredients_company`
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE;
