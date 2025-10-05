tokens
======

Objetivo
- Centralizar design tokens (cores, tipografia, espaçamentos, raios) para padronização.

Tokens propostos (valores iniciais extraídos do projeto)
- Colors
  - --color-bg: #f3f4f6   (atual: --bg)
  - --color-card: #ffffff (atual: --card)
  - --color-border: #e5e7eb (atual: --border)
  - --color-muted: #6b7280 (atual: --muted)
  - --color-text: #0f172a (atual: --text)
  - --color-accent: #f59e0b (atual: --accent)
  - --color-accent-active: #d97706 (atual: --accent-active)
  - --color-accent-ink: #ffffff (atual: --accent-ink)

- Tipografia
  - --font-family-base: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif
  - --font-size-base: 16px
  - Escala (ex):
    - --font-size-xs: 12px
    - --font-size-sm: 13px
    - --font-size-md: 15px
    - --font-size-lg: 18px

- Espaçamentos (spacing scale)
  - --space-0: 0
  - --space-1: 4px
  - --space-2: 8px
  - --space-3: 12px
  - --space-4: 16px (p-4)
  - --space-5: 20px
  - --space-6: 24px
  - --space-7: 28px

- Radius
  - --radius-sm: 6px
  - --radius-md: 12px
  - --radius-lg: 18px
  - --radius-pill: 999px

- Sombra / Elevation (exemplos)
  - --shadow-sm: 0 2px 6px rgba(15,23,42,.06)
  - --shadow-md: 0 8px 20px -16px rgba(15,23,42,.35)

Observações
- Muitos valores já são definidos inline nas views (ex.: :root no `profile.php` e variáveis dinâmicas em `home.php`). O próximo passo é extrair estes tokens para um arquivo global (ex.: `assets/css/tokens.css` ou `app/views/public/_tokens.php`) e referenciar.
- Se usar Tailwind: criar um arquivo `tailwind.config.js` central com colors/spacing/radius e usar safelist para classes geradas dinamicamente no PHP.

Como validar
- Substituir gradualmente as referências inline por `var(--token-name)` e garantir que visual não quebre.
- Rodar testes visuais manuais nas telas críticas.
