-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 19/09/2025 às 10:39
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `menu`
--

-- --------------------------------------------------------
-- Estrutura da tabela `companies`
-- --------------------------------------------------------
CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `highlight_text` text DEFAULT NULL,
  `min_order` decimal(10,2) DEFAULT NULL,
  `avg_delivery_min_from` int(11) DEFAULT NULL,
  `avg_delivery_min_to` int(11) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `menu_header_text_color` varchar(20) DEFAULT NULL,
  `menu_header_button_color` varchar(20) DEFAULT NULL,
  `menu_header_bg_color` varchar(20) DEFAULT NULL,
  `menu_logo_border_color` varchar(20) DEFAULT NULL,
  `menu_group_title_bg_color` varchar(20) DEFAULT NULL,
  `menu_group_title_text_color` varchar(20) DEFAULT NULL,
  `menu_welcome_bg_color` varchar(20) DEFAULT NULL,
  `menu_welcome_text_color` varchar(20) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `companies` (
  `id`, `slug`, `name`, `whatsapp`, `address`, `highlight_text`, `min_order`,
  `avg_delivery_min_from`, `avg_delivery_min_to`, `logo`, `banner`,
  `menu_header_text_color`, `menu_header_button_color`, `menu_header_bg_color`,
  `menu_logo_border_color`, `menu_group_title_bg_color`, `menu_group_title_text_color`,
  `menu_welcome_bg_color`, `menu_welcome_text_color`, `active`, `created_at`
) VALUES
(1, 'wollburger', 'Wollburger', '55', '', '', NULL, NULL, NULL, NULL, NULL,
 '#FFFFFF', '#FACC15', '#5B21B6', '#7C3AED', '#FACC15', '#000000', '#6B21A8', '#FFFFFF', 1, '2025-09-11 01:38:16');

-- --------------------------------------------------------
-- Estrutura da tabela `categories`
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`id`, `company_id`, `name`, `sort_order`, `active`) VALUES
(1, 1, 'herick', 0, 1);

-- --------------------------------------------------------
-- Estrutura da tabela `company_hours`
-- --------------------------------------------------------
CREATE TABLE `company_hours` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `weekday` tinyint(4) NOT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 0,
  `open1` time DEFAULT NULL,
  `close1` time DEFAULT NULL,
  `open2` time DEFAULT NULL,
  `close2` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `company_hours` (`id`, `company_id`, `weekday`, `is_open`, `open1`, `close1`, `open2`, `close2`) VALUES
(1, 1, 1, 0, NULL, NULL, NULL, NULL),
(2, 1, 2, 0, NULL, NULL, NULL, NULL),
(3, 1, 3, 0, NULL, NULL, NULL, NULL),
(4, 1, 4, 0, NULL, NULL, NULL, NULL),
(5, 1, 5, 0, NULL, NULL, NULL, NULL),
(6, 1, 6, 0, NULL, NULL, NULL, NULL),
(7, 1, 7, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Estrutura da tabela `customers`
-- --------------------------------------------------------
CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `whatsapp_e164` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `last_login_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `orders`
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','completed','canceled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `products`
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `promo_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` enum('simple','combo') NOT NULL DEFAULT 'simple',
  `price_mode` enum('fixed','sum') NOT NULL DEFAULT 'fixed',
  `allow_customize` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`id`, `company_id`, `category_id`, `name`, `description`, `price`, `promo_price`, `sku`, `image`, `type`, `price_mode`, `allow_customize`, `active`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'herick', 'herick', 10.00, NULL, NULL, NULL, 'simple', 'fixed', 0, 1, 1, '2025-09-11 01:58:33', NULL, NULL);

-- --------------------------------------------------------
-- Estrutura da tabela `combo_groups`
-- --------------------------------------------------------
CREATE TABLE `combo_groups` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` enum('single','remove','add','swap','component','extra','addon') DEFAULT 'single',
  `min_qty` int(11) DEFAULT 0,
  `max_qty` int(11) DEFAULT 1,
  `sort` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `combo_group_items`
-- --------------------------------------------------------
CREATE TABLE `combo_group_items` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `simple_product_id` int(11) NOT NULL,
  `delta_price` decimal(10,2) DEFAULT 0.00,
  `is_default` tinyint(1) DEFAULT 0,
  `sort` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `order_items`
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `ingredients`
-- --------------------------------------------------------
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(30) NOT NULL DEFAULT '',
  `unit_value` decimal(10,3) NOT NULL DEFAULT 1.000,
  `min_qty` int(11) NOT NULL DEFAULT 0,
  `max_qty` int(11) NOT NULL DEFAULT 1,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `product_custom_groups`
-- --------------------------------------------------------
CREATE TABLE `product_custom_groups` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` enum('single','extra','addon','component') NOT NULL DEFAULT 'extra',
  `min_qty` int(11) NOT NULL DEFAULT 0,
  `max_qty` int(11) NOT NULL DEFAULT 99,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `product_custom_items`
-- --------------------------------------------------------
CREATE TABLE `product_custom_items` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `ingredient_id` int(11) DEFAULT NULL,
  `label` varchar(200) NOT NULL,
  `delta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `default_qty` int(11) NOT NULL DEFAULT 1,
  `min_qty` int(11) NOT NULL DEFAULT 0,
  `max_qty` int(11) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('root','owner','staff') NOT NULL DEFAULT 'owner',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `password_hash`, `role`, `active`, `created_at`) VALUES
(1, NULL, 'Super Admin', 'admin@multimenu.local', '$2y$10$CKOmjzNNcv/FFQOrMgvxUeMGpBDPDSwywoL7XGrXdGHsI2gyKhoN.', 'root', 1, '2025-09-11 01:49:38'),
(2, 1, 'Dono Wollburger', 'owner@wollburger.local', '$2y$10$2LxL1b0Jr3m6y8oE0EJk2uYw7s5qf7o8x7mY4O1mF0b4oE2Y5eTZu', 'owner', 1, '2025-09-11 01:49:38'),
(3, 1, 'Atendente 1', 'staff1@wollburger.local', '$2y$10$2LxL1b0Jr3m6y8oE0EJk2uYw7s5qf7o8x7mY4O1mF0b4oE2Y5eTZu', 'staff', 1, '2025-09-11 01:49:38');

-- --------------------------------------------------------
-- Índices para tabelas despejadas
-- --------------------------------------------------------
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

ALTER TABLE `company_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_day` (`company_id`,`weekday`);

ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_whatsapp` (`company_id`,`whatsapp_e164`);

ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_status` (`company_id`,`status`);

ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `category_id` (`category_id`);

ALTER TABLE `combo_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `combo_group_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `simple_product_id` (`simple_product_id`);

ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ingredient_company_idx` (`company_id`);

ALTER TABLE `product_custom_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pcg_product_idx` (`product_id`);

ALTER TABLE `product_custom_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pci_group_idx` (`group_id`),
  ADD KEY `pci_ingredient_idx` (`ingredient_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `company_id` (`company_id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `company_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `combo_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `combo_group_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `product_custom_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `product_custom_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

-- --------------------------------------------------------
-- Restrições para tabelas despejadas
-- --------------------------------------------------------
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `company_hours`
  ADD CONSTRAINT `company_hours_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `combo_groups`
  ADD CONSTRAINT `combo_groups_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `combo_group_items`
  ADD CONSTRAINT `combo_group_items_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `combo_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `combo_group_items_ibfk_2` FOREIGN KEY (`simple_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `ingredients`
  ADD CONSTRAINT `fk_ingredients_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `product_custom_groups`
  ADD CONSTRAINT `fk_pcg_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `product_custom_items`
  ADD CONSTRAINT `fk_pci_group` FOREIGN KEY (`group_id`) REFERENCES `product_custom_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pci_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
