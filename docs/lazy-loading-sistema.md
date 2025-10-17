# Sistema Centralizado de Lazy Loading para Imagens

## Visão Geral

Sistema unificado de lazy loading para todas as imagens do sistema (público e admin), que melhora a performance e experiência do usuário através do carregamento progressivo de imagens.

## Arquivos do Sistema

### JavaScript
- **`public/assets/js/lazy-loading.js`** - Script principal de lazy loading
  - Inicialização automática
  - Suporte a IntersectionObserver
  - Fallback para navegadores antigos
  - Re-inicialização automática para conteúdo dinâmico

### CSS
- **`public/assets/css/lazy-loading.css`** - Estilos visuais
  - Estados das imagens (loading, loaded, error)
  - Animação shimmer
  - Classes utilitárias por tamanho

### PHP Helper
- **`app/helpers/lazy_loading_helper.php`** - Funções auxiliares PHP
  - `lazyImageAttrs()` - Gera atributos HTML
  - `lazyImage()` - Gera tag completa
  - `shouldUseLazyLoading()` - Lógica condicional
  - `lazyContainerAttrs()` - Container com skeleton

## Como Usar

### Método 1: HTML Manual (Simples)

```html
<!-- Imagem com lazy loading -->
<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
     data-src="<?= base_url('uploads/produto.jpg') ?>"
     alt="Nome do Produto"
     class="lazy-load lazy-card"
     data-fallback="<?= base_url('assets/placeholder.png') ?>">
```

### Método 2: Helper PHP (Recomendado)

```php
<!-- Método 1: Apenas atributos -->
<img <?= lazyImageAttrs($imageSrc, 'Nome do Produto', [
    'class' => 'rounded-lg object-cover',
    'sizes' => 'card',
    'fallback' => base_url('assets/placeholder.png')
]) ?>>

<!-- Método 2: Tag completa -->
<?= lazyImage($imageSrc, 'Nome do Produto', [
    'class' => 'w-24 h-24 rounded-xl object-cover',
    'sizes' => 'card'
]) ?>

<!-- Sem lazy loading (carrega imediatamente) -->
<?= lazyImage($logoSrc, 'Logo', [
    'eager' => true,
    'class' => 'h-16 w-16'
]) ?>
```

### Método 3: Com Container Skeleton

```php
<!-- Container com loading skeleton -->
<div <?= lazyContainerAttrs(['class' => 'w-24 h-24 rounded-xl']) ?>>
    <?= lazyImage($imageSrc, 'Produto', ['sizes' => 'card']) ?>
</div>
```

## Classes CSS Disponíveis

### Classes Principais
- **`lazy-load`** - Marca a imagem para lazy loading (obrigatória)
- **`lazy-loading`** - Estado durante carregamento (automático)
- **`lazy-loaded`** - Estado após carregamento (automático)
- **`lazy-error`** - Estado em caso de erro (automático)

### Classes de Tamanho
- **`lazy-thumb`** - Miniaturas (48x48px mínimo)
- **`lazy-card`** - Cards de produto (96x96px mínimo)
- **`lazy-banner`** - Banners (144px altura mínima)
- **`lazy-hero`** - Imagens hero (300px altura mínima)

### Container
- **`lazy-container`** - Container com skeleton loader

## Atributos Data

### Obrigatórios
- **`data-src`** - URL da imagem a ser carregada

### Opcionais
- **`data-fallback`** - URL de fallback se a imagem falhar
- **`data-loaded`** - Indica se já foi carregada (gerenciado automaticamente)

## JavaScript API

### Função Global

```javascript
// Re-inicializa lazy loading em novo conteúdo
window.reinitLazyLoading(containerElement);

// Exemplo: após carregar produtos via AJAX
fetch('/api/produtos')
    .then(r => r.text())
    .then(html => {
        document.getElementById('produtos').innerHTML = html;
        window.reinitLazyLoading(document.getElementById('produtos'));
    });
```

### Eventos Customizados

```javascript
// Quando uma imagem é carregada
img.addEventListener('lazyloaded', function(e) {
    console.log('Imagem carregada:', e.detail.src);
});

// Quando há erro no carregamento
img.addEventListener('lazyerror', function(e) {
    console.log('Erro ao carregar:', e.detail.src);
});
```

## Opções do Helper PHP

### lazyImageAttrs() / lazyImage()

```php
[
    // Classes CSS adicionais
    'class' => 'rounded-lg object-cover',
    
    // Desabilita lazy loading (carrega imediatamente)
    'eager' => false,
    
    // URL de fallback caso a imagem não carregue
    'fallback' => base_url('assets/placeholder.png'),
    
    // Tamanho pré-definido (thumb, card, banner, hero)
    'sizes' => 'card',
    
    // Atributos HTML adicionais
    'attributes' => [
        'width' => '96',
        'height' => '96',
        'loading' => 'lazy', // Fallback nativo do navegador
        'decoding' => 'async'
    ]
]
```

## Quando NÃO usar Lazy Loading

Use `'eager' => true` para:

1. **Above the fold**: Imagens visíveis sem scroll
2. **Logo da empresa**: Elementos da marca
3. **Imagens críticas**: Que afetam LCP (Largest Contentful Paint)
4. **Hero images**: Banner principal da página

```php
// Logo (sempre visível)
<?= lazyImage($logoSrc, 'Logo', ['eager' => true]) ?>

// Banner principal
<?= lazyImage($heroBanner, 'Destaque', [
    'eager' => true,
    'class' => 'w-full h-48 object-cover'
]) ?>
```

## Exemplos Práticos

### Card de Produto

```php
<div class="bg-white rounded-2xl p-4 flex gap-3">
    <div class="w-24 h-24 rounded-xl overflow-hidden">
        <?= lazyImage(
            base_url($produto['image']),
            $produto['name'],
            [
                'class' => 'w-full h-full object-cover',
                'sizes' => 'card',
                'fallback' => base_url('assets/placeholder.png')
            ]
        ) ?>
    </div>
    <div class="flex-1">
        <h3><?= e($produto['name']) ?></h3>
        <p><?= e($produto['description']) ?></p>
    </div>
</div>
```

### Lista de Produtos Admin

```php
<?php foreach ($produtos as $p): ?>
<tr>
    <td>
        <img <?= lazyImageAttrs(
            base_url($p['image']),
            $p['name'],
            [
                'class' => 'h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200',
                'sizes' => 'thumb'
            ]
        ) ?>>
    </td>
    <td><?= e($p['name']) ?></td>
</tr>
<?php endforeach; ?>
```

### Banner com Skeleton

```php
<div <?= lazyContainerAttrs([
    'class' => 'w-full h-48 rounded-2xl overflow-hidden',
    'style' => 'aspect-ratio: 16/9'
]) ?>>
    <?= lazyImage(
        base_url($banner),
        'Banner Promocional',
        [
            'class' => 'w-full h-full object-cover',
            'sizes' => 'banner'
        ]
    ) ?>
</div>
```

### Galeria com AJAX

```javascript
// HTML inicial
<div id="galeria"></div>

// JavaScript
function carregarProdutos() {
    fetch('/api/produtos')
        .then(r => r.text())
        .then(html => {
            const galeria = document.getElementById('galeria');
            galeria.innerHTML = html;
            
            // Re-inicializa lazy loading para as novas imagens
            window.reinitLazyLoading(galeria);
        });
}
```

## Performance

### Benefícios

1. **Redução de banda**: Carrega apenas imagens visíveis
2. **Tempo de carregamento**: Página inicial mais rápida
3. **UX aprimorada**: Feedback visual com shimmer
4. **SEO**: Melhora Core Web Vitals

### Métricas Esperadas

- **LCP**: Redução de 20-40% com uso correto de `eager`
- **Banda**: Economia de 50-70% em páginas longas
- **FCP**: Melhora de 15-25%

## Compatibilidade

- ✅ Chrome/Edge 51+
- ✅ Firefox 55+
- ✅ Safari 12.1+
- ✅ Opera 38+
- ✅ Fallback automático para navegadores antigos

## Troubleshooting

### Imagens não carregam

Verifique:
1. `data-src` está correto
2. Classe `lazy-load` está presente
3. Script `lazy-loading.js` está incluído
4. Console do navegador para erros

### Shimmer não aparece

Adicione a classe de tamanho apropriada:
```html
<img class="lazy-load lazy-card" ...>
```

### Conteúdo AJAX não funciona

Use `reinitLazyLoading()`:
```javascript
window.reinitLazyLoading(containerElement);
```

## Migração do Código Antigo

### Antes (código antigo)

```html
<img src="<?= $src ?>" 
     loading="lazy"
     onload="this.style.opacity='1'">
```

### Depois (novo sistema)

```html
<img <?= lazyImageAttrs($src, $alt, ['sizes' => 'card']) ?>>
```

## Manutenção

### Arquivos a Incluir nos Layouts

**Public** (`app/Views/public/layout.php`):
```html
<link rel="stylesheet" href="<?= base_url('assets/css/lazy-loading.css') ?>">
<script src="<?= base_url('assets/js/lazy-loading.js') ?>"></script>
```

**Admin** (`app/Views/admin/layout.php`):
```html
<link rel="stylesheet" href="<?= base_url('assets/css/lazy-loading.css') ?>">
<script src="<?= base_url('assets/js/lazy-loading.js') ?>"></script>
```

### Helper no Bootstrap

Incluir em `app/Core/bootstrap.php` ou equivalente:
```php
require_once __DIR__ . '/../helpers/lazy_loading_helper.php';
```

## Checklist de Implementação

- [x] Criar `lazy-loading.js`
- [x] Criar `lazy-loading.css`
- [x] Criar `lazy_loading_helper.php`
- [x] Incluir CSS/JS nos layouts (public e admin)
- [ ] Atualizar views públicas
- [ ] Atualizar views admin
- [ ] Testar em diferentes navegadores
- [ ] Medir performance antes/depois
- [ ] Documentar casos especiais

## Próximos Passos

1. Aplicar em todas as views públicas
2. Aplicar em todas as views admin
3. Adicionar suporte a `srcset` para imagens responsivas
4. Implementar preload para imagens críticas
5. Integrar com sistema de cache de imagens
