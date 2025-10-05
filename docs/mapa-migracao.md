mapa-migracao
==============

Resumo do inventário (classes encontradas e proposta de unificação)

Classes atuais (exemplos encontrados no código)
- .card
- .ghost-btn
- .cta
- .address-card
- .tag
- .category-tab
- .menu-header
- .field
- .grid-two
- .footer
- .back
- .title
- .address-actions
- .address-meta
- .ghost-btn
- .menu-group-title
- .status-badge

Proposta de nomes consolidados (nova convenção BEM-ish / ui- prefix)
- .ui-card              <- unifica `.card`, `main.card` e variações
  - alias: .card
- .ui-btn--ghost        <- `.ghost-btn`
  - alias: .ghost-btn
- .ui-btn--primary      <- `.cta`
  - alias: .cta
- .ui-address-card      <- `.address-card`
- .ui-badge             <- `.tag`, `.status-badge`
  - alias: .tag, .status-badge
- .ui-tab               <- `.category-tab`
  - alias: .category-tab
- .ui-header            <- `.menu-header`
  - alias: .menu-header
- .ui-field             <- `.field`, `label.field`
  - alias: .field
- .ui-grid--2           <- `.grid-two`
  - alias: .grid-two
- .ui-footer            <- `.footer`
- .ui-back              <- `.back`
- .ui-title             <- `.title`
- .ui-actions           <- `.address-actions`
- .ui-meta              <- `.address-meta`

Planos de migração (etapas)
1) Tokens centralizados (colors/spacing/radius) - não altera classes.
2) Extrair componentes server-side (parciais em `app/views/public/components/`) sem alterar classes: criar cópias `_card.php`, `_button.php`, `_badge.php` que usam as classes novas + mantêm aliases.
3) Substituir includes de `partials_card.php` por `components/_card.php` progressivamente.
4) Refatorar JS para usar `data-` attributes (ex: `data-ui-accordion`) em vez de selectores por classe quando possível.
5) Remoção suave: manter aliases CSS (classes antigas redirecionando para novas) por 2 releases; documentar no `deprecations.md`.

Mapeamento de arquivos que repetem blocos
- `app/views/public/home.php` e `home.php` (duplicação de mesma view) — unificar em uma única view.
- `partials_card.php` usado em várias páginas: ótimo candidato para consolidar.
- CSS inline repetido (ex.: `.menu-header` em `home.php` e `home.php` raiz) — extrair para componente.

Observações
- Mantenha comportamento de cálculo de preços e fluxo do pedido inalterado: não alterar lógica PHP de `Order`, `CartStorage` e controllers durante refactor inicial.
- Registrar testes manuais após cada PR.
