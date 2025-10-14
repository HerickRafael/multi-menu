# JavaScript Reutilizável - Documentação

## Visão Geral

O arquivo `public/assets/js/admin-common.js` centraliza funções JavaScript comuns utilizadas em todo o sistema administrativo, promovendo reutilização de código e consistência.

## Principais Benefícios

- ✅ **Redução de duplicação**: Eliminação de código JavaScript repetido
- ✅ **Consistência**: Comportamento uniforme em todo o sistema
- ✅ **Manutenibilidade**: Alterações centralizadas se propagam automaticamente
- ✅ **Fallbacks**: Sistema robusto com fallbacks para compatibilidade

## Funções Disponíveis

### 🔔 Sistema de Notificações

#### `showToast(message, type, duration)`
```javascript
// Exibe notificação toast unificada
showToast('Operação realizada!', 'success');
showToast('Erro ao processar', 'error', 7000);

// Tipos disponíveis: 'info', 'success', 'warn', 'error'
```

### 📊 Sistema de Status

#### `getStatusClass(status)` 
```javascript
// Mapeia status para classes CSS unificadas
const cssClass = getStatusClass('connected'); // retorna 'status-connected'
```

#### `getStatusText(status)`
```javascript
// Mapeia status para texto exibido
const text = getStatusText('open'); // retorna 'Conectado'
```

#### `createStatusPill(status, customText, showDot)`
```javascript
// Cria HTML completo do status pill
const html = createStatusPill('connected');
const htmlCustom = createStatusPill('pending', 'Aguardando', false);
```

### 🌐 Utilitários de API

#### `postJson(url, body, options)`
```javascript
// Requisição POST com JSON
const result = await postJson('/api/endpoint', { id: 123 });
```

#### `getJson(url, options)`
```javascript
// Requisição GET com tratamento de erro
const data = await getJson('/api/data');
```

### 🎯 Seletores DOM

#### `$(selector)` e `$$(selector)`
```javascript
// Seletores mais concisos
const element = $('#myElement');       // getElementById ou querySelector
const elements = $$('.my-class');      // querySelectorAll
```

#### `waitForElement(selector, timeout)`
```javascript
// Aguarda elemento aparecer no DOM
const element = await waitForElement('#dynamic-content', 5000);
```

### ⏳ Estados de Loading

#### `setButtonLoading(button, loadingText)`
```javascript
// Adiciona loading a botão
const removeLoading = setButtonLoading('#submit-btn', 'Salvando...');
// ... operação assíncrona
removeLoading(); // Remove o loading
```

#### `toggleAutoIndicator(indicator, show)`
```javascript
// Mostra/esconde indicador automático
toggleAutoIndicator('#sync-indicator', true);  // mostrar
toggleAutoIndicator('#sync-indicator', false); // esconder
```

### 📝 Utilitários de Formulário

#### `formDataToObject(formData)`
```javascript
// Converte FormData para objeto
const formData = new FormData(form);
const obj = formDataToObject(formData);
```

#### `submitFormAjax(form, url, onSuccess, onError)`
```javascript
// Submete formulário via AJAX
submitFormAjax(
  '#my-form',
  '/api/submit',
  (response) => console.log('Sucesso:', response),
  (error) => console.log('Erro:', error)
);
```

### 📋 Área de Transferência

#### `copyToClipboard(text, successMessage)`
```javascript
// Copia texto para clipboard
copyToClipboard('texto-para-copiar', 'Copiado com sucesso!');
```

### 🔄 Refresh Automático

#### Classe `AutoRefresh`
```javascript
// Gerencia refresh automático
const autoRefresh = new AutoRefresh(myRefreshFunction, 30000);
autoRefresh.start(); // inicia
autoRefresh.stop();  // para
autoRefresh.toggle(); // alterna
```

### 🔍 Busca/Filtros

#### `setupLiveSearch(inputSelector, itemSelector, filterFunction)`
```javascript
// Busca em tempo real
setupLiveSearch('#search', '.card', (item, query) => {
  return item.textContent.toLowerCase().includes(query);
});
```

## Integração e Uso

### 1. Inclusão Automática
O arquivo é incluído automaticamente no layout administrativo (`app/Views/admin/layout.php`).

### 2. Uso nas Páginas
```javascript
// Verificar se AdminCommon está disponível
if (window.AdminCommon && window.AdminCommon.showToast) {
  window.AdminCommon.showToast('Mensagem', 'success');
} else {
  // Fallback para compatibilidade
  console.log('AdminCommon não disponível');
}
```

### 3. Padrão de Fallback
Todas as páginas mantêm código de fallback para garantir funcionamento mesmo se o arquivo comum não carregar:

```javascript
function myFunction() {
  if (window.AdminCommon && window.AdminCommon.targetFunction) {
    // Usar função centralizada
    return window.AdminCommon.targetFunction(params);
  } else {
    // Código de fallback local
    return localImplementation(params);
  }
}
```

## Páginas Refatoradas

### ✅ Evolution - Instâncias (`instances.php`)
- **Toast**: `showToast()` centralizada
- **Status**: `createStatusPill()` e `getStatusClass()` 
- **Loading**: `setButtonLoading()` e `toggleAutoIndicator()`
- **API**: `getJson()` para requisições
- **Clipboard**: `copyToClipboard()` para copiar UUID
- **Search**: `setupLiveSearch()` para busca em tempo real
- **Forms**: `submitFormAjax()` para criação de instâncias

### ✅ Evolution - Configuração (`instance_config.php`)
- **Toast**: `showToast()` com mapeamento de tipos
- **Status**: `createStatusPill()` para atualização de status
- **Clipboard**: `copyToClipboard()` para token

### ⏳ Próximas Refatorações
- [ ] `app/Views/admin/orders/index.php`
- [ ] `app/Views/admin/orders/show.php`
- [ ] `app/Views/admin/payments/index.php`
- [ ] `app/Views/admin/kds/index.php`

## Mapeamento de Status Unificado

### Conexão/Evolution
- `open`, `connected` → `status-connected` (Verde)
- `connecting` → `status-connecting` (Azul)
- `close`, `disconnected` → `status-disconnected` (Vermelho)

### Pedidos
- `concluido`, `completed`, `delivered` → `status-connected` (Verde)
- `preparando`, `preparing` → `status-connecting` (Azul)
- `pendente`, `pending`, `novo`, `waiting` → `status-pending` (Amarelo)
- `cancelado`, `cancelled` → `status-disconnected` (Vermelho)
- `error`, `failed` → `status-error` (Vermelho escuro)

## Classes CSS Correspondentes

```css
.status-pill { /* base pill styling */ }
.status-connected { /* verde - sucesso */ }
.status-connecting { /* azul - em progresso */ }
.status-pending { /* amarelo - aguardando */ }
.status-disconnected { /* vermelho - erro/cancelado */ }
.status-error { /* vermelho escuro - erro crítico */ }
```

## Considerações de Performance

- **Lazy Loading**: Funções só são executadas quando necessárias
- **Fallbacks Eficientes**: Código de fallback mantém funcionalidade básica
- **Event Delegation**: Uso eficiente de event listeners
- **Debouncing**: Implementado automaticamente em funções de busca

## Manutenção

### Adicionando Novas Funções
1. Adicionar função em `admin-common.js`
2. Adicionar ao objeto `window.AdminCommon`
3. Implementar fallback nas páginas que usarão
4. Atualizar documentação

### Modificando Funções Existentes
1. Manter compatibilidade com versões anteriores
2. Testar em todas as páginas que usam a função
3. Atualizar documentação se necessário

## Debugging

```javascript
// Verificar se AdminCommon carregou
console.log('AdminCommon disponível:', !!window.AdminCommon);

// Listar funções disponíveis
console.log('Funções:', Object.keys(window.AdminCommon || {}));
```