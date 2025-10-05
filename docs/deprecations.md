deprecations
=============

Objetivo
- Listar classes e padrões que serão removidos e a estratégia de compatibilidade.

Classes marcadas para deprecar (exemplo inicial)
- .card-produto (se existir) -> migrar para .ui-card
- .card (manter como alias por 2 releases, depois remover)
- .ghost-btn -> migrar para .ui-btn--ghost (alias mantido)
- .cta -> migrar para .ui-btn--primary
- .tag -> migrar para .ui-badge
- .menu-header -> migrar para .ui-header

Estratégia de compatibilidade
1) Criar arquivo `assets/css/ui.aliases.css` contendo regras como:
   .card { composes: ui-card; } /* ou .ui-card { } e .card { @apply ... } */
   .ghost-btn { @apply ui-btn--ghost; }
   (manter por 2 ciclos de release)
2) Atualizar parcialmente as views para usar as novas classes e `data-ui-*` attributes. Não remover aliases até confirmar estabilidade.
3) Criar safelist em `tailwind.config.js` se Tailwind for usado e houver classes dinâmicas geradas por PHP.
4) Documentar na wiki / CHANGELOG com PRs pequenos e checklist de regressão.

Cronograma sugerido
- Fase 0 (1 semana): Tokens + componente Card + aliases
- Fase 1 (2 semanas): Accordion + Divider + Totals + testes manuais
- Fase 2 (2 semanas): Buttons/Steppers/Toasts + integração KDS

Validação
- Teste manual em telas críticas: catálogo, produto, combo, carrinho, checkout, KDS.
- Checklist de regressão: preços, totais, botões, acordeons, KDS em tempo real.

Notas finais
- Antes de remover qualquer classe, garantir 2 PRs: uma que adiciona as novas classes e aliases; outra que remove os aliases (após 2 releases).
