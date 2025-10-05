componentes
===========

Objetivo
- Definir API, estados e uso de componentes reutilizáveis para facilitar migração e padronização.

Componentes prioritários

1) Card (ui-card)
- Uso: produto, resumo de checkout, blocos em perfil/checkout
- Estrutura HTML recomendada:
  <div class="ui-card" data-ui-component="card">
    <!-- header / tag / badge -->
    <!-- body (nome, desc, price) -->
    <!-- footer (actions) -->
  </div>
- Estados: default, highlighted (promo), disabled
- Acessibilidade: role="article" quando for item de lista
- Observação: `partials_card.php` é ponto central — refatorar para gerar esta estrutura.

2) Accordion (ui-accordion)
- Uso: ingredientes, adicionais, FAQs
- HTML:
  <div class="ui-accordion" data-ui-component="accordion">
    <button class="ui-accordion__trigger" aria-expanded="false" aria-controls="panel-1">Título <span class="ui-accordion__icon"></span></button>
    <div id="panel-1" class="ui-accordion__panel" hidden>Conteúdo</div>
  </div>
- JS: alternar `aria-expanded`, `hidden` e adicionar classe `.is-open` para animações.
- Icon: girar com `.is-open`.

3) Divider (ui-divider)
- Variantes: solid, dashed
- Implementação: `<hr class="ui-divider ui-divider--dashed">` ou `<div class="ui-divider"></div>`

4) Totals (ui-totals)
- Uso: subtotal, taxa, total
- Estrutura:
  <div class="ui-totals">
    <div class="ui-totals__row"><span>Subtotal</span><strong>R$ 10,00</strong></div>
    <!-- ... -->
    <div class="ui-totals__total"><span>Total</span><strong>R$ 15,00</strong></div>
  </div>
- Verificação: manter formatação numérica via helpers PHP (não mover lógica matemática para view).

5) Badge (ui-badge)
- Uso: tags de promoção, principal
- Classes: `.ui-badge`, variantes `.ui-badge--warning`, `.ui-badge--success`

6) Button (ui-btn)
- Variantes: `.ui-btn--primary`, `.ui-btn--ghost`, `.ui-btn--outline`
- Atributos: `data-action`, `data-loading` para controle JS

7) QuantityStepper (ui-stepper)
- Uso: adicionar/remover unidades no card e no cart
- Acessibilidade: uso de `button` para + e - e `input[type=number]` com `aria-live="polite"` para atualizações.

8) Toast/Popup (ui-toast)
- Centralizar em um container `#ui-toasts` e empilhar notificações.
- Manter os sons/alerts existentes (arquivo `public/audio/meu-alerta.mp3`) e invocar via JS sem mudar a URL.

JS e hooks
- Usar `data-ui-*` attributes para marcar comportamentos (ex.: `data-ui-component="accordion"`, `data-ui-role="qty-stepper"`).
- Evitar dependência de classes visuais para seleção JS; facilita renomeação de classes.

Arquivos sugeridos
- `app/views/public/components/_card.php`
- `app/views/public/components/_accordion.php`
- `app/views/public/components/_totals.php`
- `app/assets/js/ui.js` (comportamentos comuns)
- `app/assets/css/ui.css` (estilos base + aliases)

Integração com PHP
- Componentes PHP devem receber apenas dados e formatar via helpers existentes (por ex.: `number_format`, `e()`), mantendo lógica de negócio em modelos/controllers.
