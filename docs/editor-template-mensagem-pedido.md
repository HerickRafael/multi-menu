# Editor de Template de Mensagem de Pedido - Documentação

## Visão Geral

Sistema de personalização de mensagens de notificação de pedidos que permite aos usuários selecionar quais campos desejam incluir nas notificações enviadas via WhatsApp.

## Arquivos Criados/Modificados

### Novos Arquivos

1. **`public/assets/js/order-message-editor.js`**
   - Editor JavaScript para personalização dos campos
   - Gerencia preview em tempo real
   - Funções para salvar/carregar configuração

2. **`app/Views/admin/evolution/partials/order_message_editor.php`**
   - Template HTML com checkboxes para cada campo
   - Interface visual com preview da mensagem
   - Botões de ação rápida (selecionar todos, desmarcar, restaurar padrão)

### Arquivos Modificados

1. **`app/Views/admin/evolution/instance_config.php`**
   - Incluído o partial do editor de template
   - Adicionado script order-message-editor.js
   - Modificadas funções de salvar/carregar para incluir message_fields

2. **`app/services/OrderNotificationService.php`**
   - Nova função `generateCustomOrderMessage()` que respeita os campos configurados
   - Modificado `sendToNumbers()` para passar message_fields
   - Mantida função `generateStandardOrderMessage()` como fallback

## Campos Disponíveis

### 📋 Dados do Pedido
- **company_name**: Nome da empresa
- **order_number**: Número do pedido
- **order_status**: Status (NOVO PEDIDO)
- **order_date**: Data e hora

### 👤 Dados do Cliente
- **customer_name**: Nome do cliente
- **customer_phone**: Telefone
- **customer_address**: Endereço completo
- **delivery_type**: Tipo de entrega (Entrega/Retirada)

### 💰 Pagamento e Valores
- **payment_method**: Forma de pagamento
- **payment_change**: Troco para (se aplicável)
- **subtotal**: Subtotal dos itens
- **delivery_fee**: Taxa de entrega
- **total**: Valor total

### 🛒 Produtos
- **items_list**: Lista de itens (ativa/desativa toda seção)
- **item_quantity**: Quantidade de cada item
- **item_price**: Preço unitário
- **item_subtotal**: Subtotal do item
- **item_customization**: Personalizações (ex: sem cebola)
- **item_observations**: Observações do item

### ➕ Informações Extras
- **order_notes**: Observações gerais do pedido
- **estimated_time**: Tempo estimado de preparo
- **system_source**: Origem (Sistema Automático)

## Configuração Padrão

Por padrão, os seguintes campos vêm marcados:
- Nome da empresa ✓
- Número do pedido ✓
- Status do pedido ✓
- Data e hora ✓
- Nome do cliente ✓
- Tipo de entrega ✓
- Forma de pagamento ✓
- Subtotal ✓
- Total ✓
- Lista de itens ✓
- Quantidade ✓
- Preço unitário ✓
- Subtotal do item ✓
- Sistema automático ✓

## Como Usar

### Na Interface Admin

1. Acesse: Admin → Evolution → Instâncias → [Instância] → Notificação de Pedido
2. Role até a seção "Formato da Mensagem"
3. Marque/desmarque os campos desejados
4. Visualize o preview em tempo real
5. Use botões de ação rápida:
   - **✓ Selecionar Todos**: Marca todos os campos
   - **✗ Desmarcar Todos**: Desmarca todos
   - **🔄 Restaurar Padrão**: Volta para configuração padrão
6. Clique em "Salvar Configuração"

### Programaticamente

```javascript
// Obter campos selecionados
const fields = window.OrderMessageEditor.getSelectedFields();

// Carregar configuração salva
window.OrderMessageEditor.loadFieldsConfig({
    message_fields: {
        company_name: true,
        order_number: true,
        customer_name: true,
        // ... outros campos
    }
});

// Atualizar preview manualmente
window.OrderMessageEditor.updatePreview();
```

## Estrutura de Dados

### JSON de Configuração Salva

```json
{
    "enabled": true,
    "primary_number": "5511987654321",
    "secondary_number": "5511123456789",
    "message_fields": {
        "company_name": true,
        "order_number": true,
        "order_status": true,
        "order_date": true,
        "customer_name": true,
        "customer_phone": false,
        "customer_address": false,
        "delivery_type": true,
        "payment_method": true,
        "payment_change": false,
        "subtotal": true,
        "delivery_fee": false,
        "total": true,
        "items_list": true,
        "item_quantity": true,
        "item_price": true,
        "item_subtotal": true,
        "item_customization": false,
        "item_observations": false,
        "order_notes": false,
        "estimated_time": false,
        "system_source": true
    }
}
```

## Exemplo de Mensagem Gerada

### Com Todos os Campos

```
🍔 *WOLLBURGER*
🔔 *NOVO PEDIDO!*
━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 *Pedido:* #1234
👤 *Cliente:* João Silva
📱 *Telefone:* (11) 98765-4321
📍 *Endereço:* Rua das Flores, 123 - Centro
🚗 *Tipo:* Entrega
💳 *Pagamento:* Cartão de Crédito
💵 *Subtotal:* R$ 45,00
🚚 *Taxa de Entrega:* R$ 5,00
💰 *Total:* R$ 50,00

🛒 *ITENS:*
• 2x X-Burger
  💵 Unit: R$ 15,00 | Total: R$ 30,00
  ⚙️ Sem cebola
  📝 Bem passado
• 1x Batata Frita G
  💵 Unit: R$ 12,00 | Total: R$ 12,00
• 1x Coca-Cola 2L
  💵 Unit: R$ 10,00 | Total: R$ 10,00
  📝 Gelada

📝 *Observações:* Sem cebola, por favor
⏱️ *Tempo estimado:* 30-40 minutos
⏰ 16/10/2025 14:30
📱 Sistema Automático

✨ *Preparar pedido!* 🚀
```

### Apenas Campos Essenciais

```
🍔 *WOLLBURGER*
🔔 *NOVO PEDIDO!*
━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 *Pedido:* #1234
👤 *Cliente:* João Silva
💳 *Pagamento:* Cartão de Crédito
💰 *Total:* R$ 50,00

🛒 *ITENS:*
• 2x X-Burger
• 1x Batata Frita G
• 1x Coca-Cola 2L

⏰ 16/10/2025 14:30
📱 Sistema Automático

✨ *Preparar pedido!* 🚀
```

## Fluxo de Funcionamento

1. **Usuário configura campos na interface**
   - Marca/desmarca checkboxes
   - Visualiza preview em tempo real
   - Salva configuração

2. **Sistema armazena configuração**
   - Salva em `instance_configs` no banco
   - Vincula à instância específica
   - JSON com campos selecionados

3. **Novo pedido é criado**
   - Sistema detecta novo pedido
   - Busca configuração da instância
   - Carrega message_fields

4. **Mensagem é gerada**
   - `generateCustomOrderMessage()` é chamada
   - Processa apenas campos marcados como true
   - Formata mensagem personalizada

5. **Mensagem é enviada**
   - Via Evolution API
   - Para números configurados
   - Formato otimizado para WhatsApp

## API JavaScript

### Métodos Disponíveis

```javascript
// Inicializar editor
window.OrderMessageEditor.init();

// Atualizar preview
window.OrderMessageEditor.updatePreview();

// Obter campos selecionados
const fields = window.OrderMessageEditor.getSelectedFields();
// Retorna: { company_name: true, order_number: true, ... }

// Carregar configuração
window.OrderMessageEditor.loadFieldsConfig({
    message_fields: { ... }
});

// Salvar configuração (retorna objeto)
const config = window.OrderMessageEditor.saveFieldsConfig();
```

## Personalização

### Adicionar Novo Campo

1. **Adicionar checkbox no HTML** (`partials/order_message_editor.php`):
```php
<label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
  <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="novo_campo">
  <span>🆕 Novo Campo</span>
</label>
```

2. **Adicionar ao defaultFields** (`order-message-editor.js`):
```javascript
const defaultFields = {
    // ... campos existentes
    novo_campo: false
};
```

3. **Adicionar lógica no generateCustomOrderMessage** (`OrderNotificationService.php`):
```php
if (!empty($messageFields['novo_campo'])) {
    $message .= "🆕 *Novo Campo:* {$valor}\n";
}
```

## Compatibilidade

- ✅ Evolution API v2.x
- ✅ WhatsApp Business
- ✅ WhatsApp Personal
- ✅ Múltiplas instâncias
- ✅ Configuração por instância

## Troubleshooting

### Preview não atualiza
- Verificar se `order-message-editor.js` está carregado
- Abrir console e verificar erros JavaScript
- Verificar se elementos têm IDs corretos

### Campos não salvam
- Verificar resposta da API no Network tab
- Verificar se `message_fields` está no payload
- Verificar permissões do banco de dados

### Mensagem não respeita campos
- Verificar se `generateCustomOrderMessage` é chamada
- Ver logs do PHP para debug
- Verificar se config tem `message_fields`

## Logs e Debug

### JavaScript
```javascript
// Ver campos selecionados
console.log(window.OrderMessageEditor.getSelectedFields());

// Ver preview atual
console.log(document.getElementById('messagePreview').textContent);
```

### PHP
```php
// No OrderNotificationService.php
error_log("Message fields: " . json_encode($messageFields));
error_log("Generated message: " . $message);
```

## Melhorias Futuras

- [ ] Templates pré-definidos (Simples, Completo, Personalizado)
- [ ] Drag & drop para reordenar campos
- [ ] Editor de texto livre com placeholders
- [ ] Suporte a múltiplos idiomas
- [ ] Emojis personalizáveis
- [ ] Formatação de texto (negrito, itálico)
- [ ] Condições (ex: só mostrar troco se pagamento = dinheiro)
- [ ] Campos calculados (ex: troco retornado)
- [ ] Export/import de templates
- [ ] Biblioteca de templates compartilhados

## Suporte

Para problemas ou dúvidas:
1. Verificar logs do PHP
2. Verificar console do navegador
3. Verificar documentação da Evolution API
4. Consultar este arquivo de documentação
