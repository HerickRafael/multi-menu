Rollback & checklist para índice UNIQUE em payment_methods

Resumo
------
Este documento descreve os passos para rollback e deploy seguro relacionados à adição do índice UNIQUE `uk_payment_methods_company_type_icon` (company_id, type, icon).

Arquivos relevantes
-------------------
- database/migrations/20251009_add_icon_to_payment_methods.sql  (adiciona coluna `icon` e backfill)
- database/migrations/20251009_add_unique_payment_methods_icon.sql (adiciona índice UNIQUE)
- database/migrations/20251009_drop_unique_payment_methods_icon.sql (rollback para remover índice)

Checklist rápido (pré-deploy)
-----------------------------
1. Backup do banco (obrigatório):

   mysqldump -h 127.0.0.1 -u root menu > ~/backup_menu_$(date +%F_%T).sql

2. Verificar duplicatas (caso não executado antes):

   -- verificar duplicatas por icon
   SELECT company_id, `type`, icon, COUNT(*) AS cnt
   FROM payment_methods
   WHERE icon IS NOT NULL AND TRIM(icon) <> ''
   GROUP BY company_id, `type`, icon
   HAVING cnt > 1;

3. Garantir que o site esteja em modo de manutenção (opcional para produção com alto tráfego).

Aplicando migration no servidor (deploy)
---------------------------------------
- Copie os arquivos SQL para o servidor e aplique na ordem necessária (primeiro coluna/backfill, depois UNIQUE):

  mysql -h HOST -u USER -p DBNAME < 20251009_add_icon_to_payment_methods.sql
  mysql -h HOST -u USER -p DBNAME < 20251009_add_unique_payment_methods_icon.sql

Rollback (remover índice)
-------------------------
1. Para remover o índice de forma segura usando o arquivo de rollback criado:

   mysql -h HOST -u USER -p DBNAME < 20251009_drop_unique_payment_methods_icon.sql

2. Alternativamente, executar manualmente (apenas se tiver certeza de que o índice existe):

   ALTER TABLE payment_methods DROP INDEX uk_payment_methods_company_type_icon;

Nota: remover o índice NÃO apaga dados, apenas remove a restrição de unicidade.

Procedimento de emergência (rollback rápido)
-------------------------------------------
1. Restaurar backup completo (se algo maior falhar):

   mysql -h HOST -u USER -p DBNAME < ~/backup_menu_YYYY-MM-DD_HH:MM:SS.sql

2. Caso seja necessário apenas remover o índice, aplicar o script de rollback (ver acima).

Pontos de atenção
-----------------
- Antes de adicionar UNIQUE em produção, verificar novamente duplicatas e, se existirem, decidir política de resolução (manualmente remover ou consolidar registros).
- O prefixo `icon(191)` foi usado por compatibilidade de tamanho do índice com utf8mb4; ajuste se sua coluna tiver outro charset/collation.
- Mantenha `meta` como fallback por um período (o código já implementa fallback), e remova o fallback apenas quando tiver 100% de segurança sobre os dados.

Contatos & logs
---------------
- Registre horário do deploy, usuário que aplicou as mudanças e outputs dos comandos SQL.
- Se desejar, eu posso gerar um pequeno script de limpeza para detectar/mesclar duplicatas automaticamente (requer regras de negócio).
