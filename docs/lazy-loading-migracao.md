# Guia de Migração: Aplicando Lazy Loading nas Views

Este documento mostra exemplos práticos de como migrar as views existentes para usar o novo sistema de lazy loading.

## Views Públicas

### 1. home.php - Banner e Logo

**ANTES:**
```php
<img src="<?= $bannerUrl ?>" class="w-full h-36 md:h-48 object-cover" alt="Banner">
<img src="<?= base_url($company['logo'] ?? 'assets/logo-placeholder.png') ?>"
     class="rounded-full"
     alt="Logo">
```

**DEPOIS:**
```php
<!-- Banner: eager porque é above the fold -->
<?= lazyImage($bannerUrl, 'Banner', [
    'eager' => true,
    'class' => 'w-full h-36 md:h-48 object-cover'
]) ?>

<!-- Logo: eager porque é elemento de marca -->
<?= lazyImage(
    base_url($company['logo'] ?? 'assets/logo-placeholder.png'),
    'Logo',
    [
        'eager' => true,
        'class' => 'rounded-full'
    ]
) ?>
```

### 2. cart.php - Imagens dos Produtos

**ANTES:**
```php
<img src="<?= e($uploadSrc($item['product']['image'])) ?>" 
     alt="<?= e($item['product']['name'] ?? '') ?>">
```

**DEPOIS:**
```php
<?= lazyImage(
    $uploadSrc($item['product']['image']),
    $item['product']['name'] ?? '',
    [
        'class' => 'w-full h-full object-cover',
        'sizes' => 'thumb',
        'fallback' => base_url('assets/logo-placeholder.png')
    ]
) ?>
```

### 3. product.php - Imagem Hero do Produto

**ANTES:**
```php
<img class="hero-product" src="<?= e($imgSrc) ?>" alt="<?= e($imgAlt) ?>">
```

**DEPOIS:**
```php
<!-- Imagem hero: eager porque é LCP element -->
<?= lazyImage($imgSrc, $imgAlt, [
    'eager' => true,
    'class' => 'hero-product'
]) ?>
```

### 4. customization.php - Opções de Customização

**ANTES:**
```php
<img src="<?= e($img ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+') ?>" 
     alt="" 
     onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
```

**DEPOIS:**
```php
<?= lazyImage(
    $img ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+',
    '',
    [
        'class' => 'w-20 h-20 object-cover rounded-lg',
        'sizes' => 'thumb',
        'fallback' => 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'
    ]
) ?>
```

### 5. checkout.php - Ícones de Pagamento

**ANTES:**
```php
<img src="<?= function_exists('base_url') ? base_url('assets/card-brands/pix.svg') : '/assets/card-brands/pix.svg' ?>" 
     alt="PIX" 
     class="payment-icon">
```

**DEPOIS:**
```php
<!-- Ícones SVG pequenos: eager porque são críticos para UX -->
<?= lazyImage(
    base_url('assets/card-brands/pix.svg'),
    'PIX',
    [
        'eager' => true,
        'class' => 'payment-icon'
    ]
) ?>
```

## Views Admin

### 1. products/index.php - Lista de Produtos

**ANTES:**
```php
<img src="<?= e(base_url($p['image'])) ?>" 
     alt="" 
     class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200">
```

**DEPOIS:**
```php
<img <?= lazyImageAttrs(
    base_url($p['image']),
    $p['name'] ?? '',
    [
        'class' => 'h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200',
        'sizes' => 'thumb',
        'fallback' => base_url('assets/logo-placeholder.png')
    ]
) ?>>
```

### 2. dashboard/index.php - Logo da Empresa

**ANTES:**
```php
<img src="<?= e(base_url($companyLogo)) ?>" 
     alt="Logo" 
     class="h-16 w-16 rounded-[0.9rem] object-cover ring-1 ring-black/10">
```

**DEPOIS:**
```php
<!-- Logo: eager porque é elemento fixo no topo -->
<?= lazyImage(
    base_url($companyLogo),
    'Logo',
    [
        'eager' => true,
        'class' => 'h-16 w-16 rounded-[0.9rem] object-cover ring-1 ring-black/10'
    ]
) ?>
```

### 3. ingredients/index.php - Lista de Ingredientes

**ANTES:**
```php
<img src="<?= e(base_url($item['image_path'])) ?>" alt=""
     class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200">
```

**DEPOIS:**
```php
<img <?= lazyImageAttrs(
    base_url($item['image_path']),
    $item['name'] ?? '',
    [
        'class' => 'h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200',
        'sizes' => 'thumb'
    ]
) ?>>
```

### 4. payments/index.php - Ícones de Bandeiras

**ANTES:**
```php
<img src="<?= e($src . (str_contains($src, '?') ? '&' : '?') . 'v=' . time()) ?>" 
     alt="Bandeira" 
     class="max-w-full max-h-full object-contain" />
```

**DEPOIS:**
```php
<?php
$cacheBustedSrc = $src . (str_contains($src, '?') ? '&' : '?') . 'v=' . time();
?>
<img <?= lazyImageAttrs(
    $cacheBustedSrc,
    'Bandeira',
    [
        'class' => 'max-w-full max-h-full object-contain',
        'sizes' => 'thumb'
    ]
) ?>>
```

### 5. evolution/index.php - QR Code

**ANTES:**
```php
<img src="data:image/png;base64,<?= e($inst['qr_code']) ?>" 
     alt="QR code" 
     class="h-48">
```

**DEPOIS:**
```php
<!-- QR Code: eager porque precisa ser visto imediatamente -->
<?= lazyImage(
    'data:image/png;base64,' . e($inst['qr_code']),
    'QR code',
    [
        'eager' => true,
        'class' => 'h-48'
    ]
) ?>
```

## Padrões por Tipo de Imagem

### Imagens Above the Fold (Usar eager=true)
```php
// Banner principal
<?= lazyImage($banner, 'Banner', ['eager' => true, 'sizes' => 'banner']) ?>

// Logo da empresa
<?= lazyImage($logo, 'Logo', ['eager' => true]) ?>

// Imagem hero do produto
<?= lazyImage($product['image'], $product['name'], ['eager' => true, 'sizes' => 'hero']) ?>
```

### Imagens em Lista (Usar lazy loading)
```php
// Cards de produtos
<?php foreach ($products as $p): ?>
    <?= lazyImage(
        base_url($p['image']),
        $p['name'],
        ['sizes' => 'card', 'fallback' => base_url('assets/placeholder.png')]
    ) ?>
<?php endforeach; ?>
```

### Miniaturas em Tabelas (Usar lazy loading)
```php
// Tabelas admin
<td>
    <img <?= lazyImageAttrs(
        base_url($item['image']),
        $item['name'],
        ['sizes' => 'thumb', 'class' => 'h-12 w-12 rounded-lg object-cover']
    ) ?>>
</td>
```

### Ícones SVG (Geralmente eager)
```php
// Ícones de pagamento, bandeiras, etc.
<?= lazyImage($iconPath, $iconName, ['eager' => true, 'class' => 'payment-icon']) ?>
```

### Imagens Dinâmicas (AJAX/Modal)
```javascript
// Ao carregar via AJAX
fetch('/api/produtos')
    .then(r => r.text())
    .then(html => {
        container.innerHTML = html;
        window.reinitLazyLoading(container); // IMPORTANTE!
    });
```

## Checklist de Migração por View

### Views Públicas
- [ ] `home.php` - Banner, logo, cards de produtos
- [ ] `cart.php` - Imagens de produtos no carrinho
- [ ] `product.php` - Imagem hero, opções de combo
- [ ] `checkout.php` - Ícones de pagamento
- [ ] `customization.php` - Opções de customização
- [ ] `profile.php` - Avatar do usuário
- [ ] `components/_card.php` - Cards de produtos ✅ (já feito)

### Views Admin
- [ ] `dashboard/index.php` - Logo, produtos recentes
- [ ] `products/index.php` - Lista de produtos
- [ ] `ingredients/index.php` - Lista de ingredientes
- [ ] `payments/index.php` - Bandeiras e ícones
- [ ] `evolution/index.php` - QR codes
- [ ] `evolution/instances.php` - Fotos de perfil

## Testes Necessários

### Performance
1. Testar tempo de carregamento antes/depois
2. Medir banda utilizada (Network tab)
3. Verificar Core Web Vitals (LCP, FID, CLS)

### Funcionalidade
1. Imagens carregam ao scroll
2. Fallbacks funcionam quando imagem falha
3. Shimmer aparece durante carregamento
4. Conteúdo AJAX funciona com reinit

### Compatibilidade
1. Testar em Chrome/Edge
2. Testar em Firefox
3. Testar em Safari
4. Testar em mobile

## Scripts Úteis

### Buscar todas as tags img no projeto
```bash
grep -r "<img" app/Views/ --include="*.php" | wc -l
```

### Buscar imagens sem lazy loading
```bash
grep -r '<img.*src=' app/Views/ --include="*.php" | grep -v 'lazy-load' | grep -v 'data-src'
```

### Contar imagens por view
```bash
for file in app/Views/**/*.php; do
    count=$(grep -c "<img" "$file" 2>/dev/null || echo 0)
    if [ $count -gt 0 ]; then
        echo "$count - $file"
    fi
done
```
