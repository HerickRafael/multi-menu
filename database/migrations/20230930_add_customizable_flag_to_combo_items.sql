-- Adiciona flag para itens de combo permitirem personalização
ALTER TABLE combo_group_items
  ADD COLUMN allow_customization TINYINT(1) NOT NULL DEFAULT 0 AFTER is_default;
