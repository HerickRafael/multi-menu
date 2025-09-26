INSERT INTO companies (
  id,
  slug,
  name,
  whatsapp,
  address,
  highlight_text,
  min_order,
  delivery_after_hours_fee,
  delivery_free_enabled,
  avg_delivery_min_from,
  avg_delivery_min_to,
  logo,
  banner,
  menu_header_text_color,
  menu_header_button_color,
  menu_header_bg_color,
  menu_logo_border_color,
  menu_group_title_bg_color,
  menu_group_title_text_color,
  menu_welcome_bg_color,
  menu_welcome_text_color,
  active,
  created_at
) VALUES (
  1,
  'wollburger',
  'WollBurger',
  '11999998888',
  'Av. Central, 123, São Paulo - SP',
  'Peça os clássicos da casa!',
  39.90,
  5.00,
  0,
  25,
  45,
  NULL,
  NULL,
  '#FFFFFF',
  '#F97316',
  '#1F2937',
  '#F59E0B',
  '#F97316',
  '#FFFFFF',
  '#111827',
  '#F9FAFB',
  1,
  '2024-01-01 12:00:00'
) ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO company_hours (id, company_id, weekday, is_open, open1, close1, open2, close2) VALUES
  (1, 1, 1, 0, NULL, NULL, NULL, NULL),
  (2, 1, 2, 1, '11:00:00', '15:00:00', '18:00:00', '22:30:00'),
  (3, 1, 3, 1, '11:00:00', '15:00:00', '18:00:00', '22:30:00'),
  (4, 1, 4, 1, '11:00:00', '15:00:00', '18:00:00', '22:30:00'),
  (5, 1, 5, 1, '11:00:00', '15:00:00', '18:00:00', '23:30:00'),
  (6, 1, 6, 1, '12:00:00', '16:00:00', '19:00:00', '00:30:00'),
  (7, 1, 7, 1, '12:00:00', '16:00:00', '19:00:00', '00:30:00')
ON DUPLICATE KEY UPDATE is_open = VALUES(is_open);

INSERT INTO categories (id, company_id, name, sort_order, active) VALUES
  (1, 1, 'Burgers', 0, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (
  id,
  company_id,
  category_id,
  name,
  description,
  price,
  promo_price,
  sku,
  image,
  type,
  price_mode,
  allow_customize,
  active,
  sort_order,
  created_at
) VALUES (
  1,
  1,
  1,
  'Classic Burger',
  'Hambúrguer artesanal com queijo, alface, tomate e molho especial.',
  29.90,
  NULL,
  'CB-001',
  NULL,
  'simple',
  'fixed',
  1,
  1,
  1,
  '2024-01-01 12:05:00'
) ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO delivery_cities (id, company_id, name, created_at) VALUES
  (1, 1, 'São Paulo', '2024-01-01 12:10:00')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO delivery_zones (id, company_id, city_id, neighborhood, fee, created_at) VALUES
  (1, 1, 1, 'Centro', 5.90, '2024-01-01 12:10:00')
ON DUPLICATE KEY UPDATE fee = VALUES(fee);

INSERT INTO ingredients (
  id,
  company_id,
  name,
  cost,
  sale_price,
  unit,
  unit_value,
  min_qty,
  max_qty,
  image_path,
  created_at,
  updated_at
) VALUES
  (1, 1, 'Queijo Cheddar', 1.20, 3.00, 'gramas', 30.000, 0, 2, NULL, '2024-01-01 12:15:00', '2024-01-01 12:15:00'),
  (2, 1, 'Bacon Crocante', 2.80, 5.00, 'gramas', 40.000, 0, 2, NULL, '2024-01-01 12:15:00', '2024-01-01 12:15:00')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO product_custom_groups (
  id,
  product_id,
  name,
  type,
  min_qty,
  max_qty,
  sort_order
) VALUES
  (1, 1, 'Adicionais', 'extra', 0, 2, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO product_custom_items (
  id,
  group_id,
  ingredient_id,
  label,
  delta,
  is_default,
  default_qty,
  min_qty,
  max_qty,
  sort_order
) VALUES
  (1, 1, 1, 'Queijo Cheddar Extra', 4.50, 0, 1, 0, 2, 1),
  (2, 1, 2, 'Bacon Crocante', 6.50, 0, 1, 0, 2, 2)
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO users (id, company_id, name, email, password_hash, role, active, created_at) VALUES
  (1, NULL, 'Root Admin', 'admin@multimenu.local', '$2y$10$CKOmjzNNcv/FFQOrMgvxUeMGpBDPDSwywoL7XGrXdGHsI2gyKhoN.', 'root', 1, '2024-01-01 12:00:00'),
  (2, 1, 'Owner Demo', 'owner@demoburger.local', '$2y$10$2LxL1b0Jr3m6y8oE0EJk2uYw7s5qf7o8x7mY4O1mF0b4oE2Y5eTZu', 'owner', 1, '2024-01-01 12:00:00')
ON DUPLICATE KEY UPDATE name = VALUES(name);
