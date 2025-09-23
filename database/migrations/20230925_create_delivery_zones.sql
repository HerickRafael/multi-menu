CREATE TABLE `delivery_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `city` varchar(120) NOT NULL,
  `neighborhood` varchar(120) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `delivery_zones_company_city_idx` (`company_id`, `city`, `neighborhood`),
  CONSTRAINT `delivery_zones_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
