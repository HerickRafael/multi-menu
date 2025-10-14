# Correções de Status e Contagens - Evolution Instances

## Problemas Identificados e Solucionados

### 🔧 **Status das Instâncias**

**Problema**: As instâncias mostravam status "pending" mesmo quando conectadas na API Evolution.

**Causa**: O código estava usando apenas o status local do banco de dados, não sincronizando com o status real da API Evolution.

**Solução**: 
1. ✅ Integração com endpoint `/instance/fetchInstances` para obter dados atualizados
2. ✅ Mapeamento correto de status da API (`"connectionStatus": "open"` → `"connected"`)
3. ✅ Fallback para status local quando API não disponível

### 📊 **Contadores de Conversas e Mensagens**

**Problema**: Contadores sempre mostravam "0" para usuários e mensagens.

**Causa**: Não havia integração com dados de contagem da API Evolution.

**Solução**:
1. ✅ Uso do campo `_count` da API Evolution que retorna:
   - `Chat`: número de conversas/chats
   - `Message`: número total de mensagens
2. ✅ Exibição das contagens reais na interface

### 🔄 **Sincronização de Dados**

**Implementações**:
1. ✅ **Controller atualizado**: Sincroniza automaticamente com API Evolution
2. ✅ **Rota AJAX**: `/admin/{slug}/evolution/instances/data` para atualizações sem reload
3. ✅ **Botão Refresh**: Atualiza dados em tempo real via AJAX

## Dados Obtidos da API Evolution

### Exemplo de Resposta da API:
```json
{
  "id": "c2231a4b-26ee-4036-9c28-215c1fe8294b",
  "name": "wollburger",
  "connectionStatus": "open",
  "profileName": "@WollBuger",
  "_count": {
    "Message": 5659,
    "Contact": 1745,
    "Chat": 442
  }
}
```

### Mapeamento para Interface:
- **Status**: `"connectionStatus": "open"` → Badge "Conectado" (verde)
- **Conversas**: `"_count.Chat": 442` → "442" usuários 
- **Mensagens**: `"_count.Message": 5659` → "5659" mensagens

## Arquivos Modificados

### 1. **AdminEvolutionController.php**
```php
// Método instances() - Sincroniza dados com API
public function instances($params) {
    // Obter instâncias locais
    $instances = EvolutionInstance::allForCompany((int)$company['id']);
    
    // Enriquecer com dados da API Evolution
    $res = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
    
    // Mapear status e contadores
    foreach ($instances as &$instance) {
        // connectionStatus: open → status: connected
        // _count.Chat → chat_count
        // _count.Message → message_count
    }
}

// Novo método instancesData() - Endpoint AJAX
public function instancesData($params) {
    // Retorna JSON com dados atualizados
    header('Content-Type: application/json');
    echo json_encode(['instances' => $processedInstances]);
}
```

### 2. **instances.php (View)**
```php
// Processamento de dados atualizado
$name = $inst['profile_name'] ?? $inst['label'] ?: ($inst['api_number'] ?? $inst['number'] ?: 'Instance');
'users' => (string)($inst['chat_count'] ?? 0),      // Conversas
'messages' => (string)($inst['message_count'] ?? 0), // Mensagens  
'status' => $status, // Status real da API
```

### 3. **web.php (Rotas)**
```php
// Nova rota para AJAX
$router->get('/admin/{slug}/evolution/instances/data','AdminEvolutionController@instancesData');
```

## Status Suportados

### 🟢 **Conectado** (`connected`)
- API Status: `"open"`, `"connected"`, `"ready"`
- Exibição: Badge verde com ✓ "Conectado"

### 🟡 **Pendente** (`pending`) 
- API Status: `"connecting"`, `"qr_code"`, `"qr"`
- Exibição: Badge amarelo com ⏳ "Pendente"

### 🔴 **Desconectado** (`disconnected`)
- API Status: outros valores ou sem resposta
- Exibição: Badge vermelho com ❌ "Desconectado"

## Funcionalidades da Interface

### 🔄 **Refresh Inteligente**
```javascript
// Botão refresh com loading state
document.getElementById('refreshBtn').addEventListener('click', async () => {
    // Mostra spinner
    refreshBtn.innerHTML = '<svg class="animate-spin">...</svg>';
    
    // Busca dados atualizados via AJAX
    const response = await fetch('/admin/wollburger/evolution/instances/data');
    const data = await response.json();
    
    // Atualiza interface sem reload
    instances.length = 0;
    instances.push(...data.instances);
    mount();
    
    toast('Dados atualizados com sucesso!');
});
```

### 📊 **Exibição de Contadores**
- **Usuários/Conversas**: Número de chats ativos
- **Mensagens**: Total de mensagens processadas
- **Status Visual**: Chips coloridos por status

## Resultados

### ✅ **Antes**
- Status: Sempre "pending" 
- Usuários: Sempre "0"
- Mensagens: Sempre "0"

### ✅ **Depois**  
- Status: "conectado" (sincronizado com API)
- Usuários: "442" (número real de conversas)
- Mensagens: "5659" (número real de mensagens)

### 🚀 **Benefícios**
1. **Status Real**: Sincronização automática com API Evolution
2. **Dados Precisos**: Contadores reais de conversas e mensagens
3. **UX Melhorada**: Refresh sem reload + feedback visual
4. **Performance**: Cache local + atualizações sob demanda

---

**Status**: ✅ **Implementado e Testado**  
**Data**: 12 de outubro de 2025  
**API Version**: Evolution API v2.3