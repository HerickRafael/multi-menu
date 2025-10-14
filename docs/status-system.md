# Sistema de Status Reutilizável

## Classes CSS Disponíveis

O sistema inclui as seguintes classes CSS globais no `admin/layout.php`:

### Status Pills Base
- `.status-pill` - Container principal
- `.status-dot` - Ponto colorido opcional

### Status Específicos
- `.status-connected` - Verde (conectado, concluído, ativo)
- `.status-disconnected` - Vermelho (desconectado, cancelado, inativo)  
- `.status-pending` - Âmbar (pendente, aguardando)
- `.status-connecting` - Azul (conectando, preparando, em progresso)
- `.status-error` - Vermelho (erro, falha)

## Função Helper PHP

```php
status_pill($status, $text = null, $showDot = true)
```

### Mapeamento Automático
A função mapeia automaticamente diferentes valores para as classes corretas:

#### Evolution/Conexão
- `'open'` → `status-connected`
- `'connecting'` → `status-connecting`
- `'disconnected'` → `status-disconnected`
- `'close'` → `status-disconnected`

#### Pedidos
- `'concluido'`, `'concluded'` → `status-connected`
- `'cancelado'`, `'cancelled'` → `status-disconnected`
- `'pendente'`, `'pending'` → `status-pending`
- `'preparando'`, `'preparing'` → `status-connecting`
- `'erro'`, `'error'`, `'failed'` → `status-error`

## Exemplos de Uso

### PHP
```php
// Uso básico
<?= status_pill('connected') ?>

// Com texto customizado
<?= status_pill('open', 'Conectado') ?>

// Sem ponto
<?= status_pill('pending', 'Aguardando', false) ?>
```

### HTML/CSS Direto
```html
<span class="status-pill status-connected">
  <span class="status-dot"></span>
  Conectado
</span>
```

### JavaScript
```javascript
function getStatusChip(status) {
  const statusMap = {
    'connected': 'status-connected',
    'pending': 'status-pending',
    'disconnected': 'status-disconnected'
  };
  
  const statusClass = statusMap[status] || 'status-pending';
  return `<span class="status-pill ${statusClass}"><span class="status-dot"></span>${status}</span>`;
}
```

## Onde Está Sendo Usado

- ✅ Evolution - Página de instâncias (`instances.php`)
- ✅ Evolution - Configuração de instância (`instance_config.php`)
- ✅ Pedidos - Lista de pedidos (`orders/index.php`)
- ✅ Pedidos - Detalhes do pedido (`orders/show.php`)

## Vantagens

1. **Consistência Visual** - Todos os status têm a mesma aparência
2. **Facilidade de Uso** - Uma função/classe para todos os casos
3. **Manutenibilidade** - Mudanças de design em um local só
4. **Reutilização** - Funciona para Evolution, pedidos e qualquer outro módulo
5. **Flexibilidade** - Suporte a texto customizado e opções visuais