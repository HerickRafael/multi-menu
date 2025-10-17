# Filtro de Ingredientes Inclusos

## Resumo
Implementado filtro para ocultar ingredientes "Inclusos" (sem custo adicional) nas notificações do WhatsApp e nos recibos PDF impressos, mantendo apenas extras pagos e remoções visíveis.

## Data
12 de Janeiro de 2025

## Problema
Os pedidos estavam exibindo TODOS os ingredientes, incluindo os que vinham de forma padrão (inclusos) sem custo adicional. Isso poluía a visualização e confundia tanto o cliente quanto a cozinha.

### Exemplo do problema:
```
Pão Brioche - Incluso
Alface - Incluso
Tomate - Incluso
Maionese - Incluso
Bacon + R$ 3,00
Cheddar + R$ 2,50
```

### Resultado esperado:
```
+ Bacon (+R$ 3,00)
+ Cheddar (+R$ 2,50)
- Cebola (removida)
```

## Solução Implementada

### 1. ThermalReceipt.php (app/services/ThermalReceipt.php)

**Localização**: Linhas 195-245

**Lógica de Filtro**:
```php
// Verificar se é incluso (sem preço e ação de adicionar, mas não é remoção)
// Inclusos: price = 0 e action = 'add'
// Devem aparecer: price > 0 (extras) OU action = 'remove' (removidos)
$isIncluso = ($customPrice == 0 && $customAction === 'add');

// Mostrar apenas se NÃO for incluso
if (!$isIncluso) {
    $customItemsToShow[] = [
        'name' => $customName,
        'action' => $customAction,
        'qty' => $customQty,
        'price' => $customPrice
    ];
}
```

**Alterações**:
- Adicionado array `$customItemsToShow` para armazenar apenas itens filtrados
- Implementada verificação `$isIncluso` baseada em `price == 0` e `action === 'add'`
- Seção "Personalização" só aparece se houver itens para exibir
- Mantém exibição de remoções (`action === 'remove'`) e extras (`price > 0`)

### 2. PublicCartController.php (app/controllers/PublicCartController.php)

**Localização**: Linhas 1301-1365

**Lógica de Filtro**:
```php
// Verificar se é "Incluso" - NÃO mostrar
// Incluso = sem preço E sem modificação de quantidade
$isIncluso = (
    $status === 'Incluso' || 
    ($price == 0 && ($deltaQty === null || $deltaQty == 0))
);

// Decidir se mostra baseado no tipo e se não é incluso
$shouldShow = false;

if (!$isIncluso) {
    // Para tipos addon/single: sempre mostrar
    if (in_array($groupType, ['addon', 'single'])) {
        $shouldShow = true;
    }
    // Para tipo qty: mostrar apenas se delta_qty != 0 ou tem preço
    elseif ($groupType === 'qty') {
        if ($deltaQty !== null && $deltaQty != 0) {
            $shouldShow = true;
        } elseif ($price > 0) {
            $shouldShow = true;
        }
    }
}
```

**Alterações**:
- Adicionada extração do campo `$status` dos dados de customização
- Implementada verificação de `status === 'Incluso'` como condição primária
- Verificação secundária: `price == 0 && deltaQty == 0` (sem mudança na quantidade)
- Formatação de preço adicionada: `(+ R$ X,XX)` para extras pagos
- Lógica de exibição ajustada para respeitar tipos de grupo (addon/single/qty)

## Critérios de Exibição

### ✅ MOSTRAR (aparecem no recibo e WhatsApp):

1. **Extras pagos**: Ingredientes adicionados com custo
   - Exemplo: `+ Bacon (+R$ 3,00)`
   - Condição: `price > 0`

2. **Ingredientes removidos**: Personalizações negativas
   - Exemplo: `- Cebola`
   - Condição: `action === 'remove'`

3. **Modificações de quantidade**: Delta diferente de zero
   - Exemplo: `+2x Queijo`
   - Condição: `delta_qty != 0`

4. **Addons e seleções únicas**: Sempre relevantes
   - Condição: `groupType === 'addon' || groupType === 'single'`

### ❌ OCULTAR (não aparecem):

1. **Ingredientes inclusos**: Vêm de padrão sem custo
   - Exemplo: ~~`Alface - Incluso`~~
   - Condição: `status === 'Incluso'` OU `(price == 0 && delta_qty == 0)`

2. **Itens sem modificação e sem custo**
   - Condição: `price == 0 && action === 'add' && delta_qty == 0`

## Arquivos Modificados

1. **app/services/ThermalReceipt.php**
   - Função: `generatePdf()` - seção de personalização
   - Linhas: 195-245

2. **app/controllers/PublicCartController.php**
   - Função: `finalize()` - processamento de customização
   - Linhas: 1301-1365

## Teste

### Arquivo de Teste
`test_ingredient_filter.php` - Simula pedido com mix de ingredientes inclusos e extras

### Como testar:
```bash
php test_ingredient_filter.php
```

### Resultado esperado:
```
✗ OCULTAR Alface               - R$ 0,00 (incluso - OCULTAR)
✗ OCULTAR Tomate               - R$ 0,00 (incluso - OCULTAR)
✗ OCULTAR Maionese             - R$ 0,00 (incluso - OCULTAR)
✓ MOSTRAR Bacon                - R$ 3,00 (tem preço - extra)
✓ MOSTRAR Cheddar              - R$ 2,50 (tem preço - extra)
✓ MOSTRAR Cebola               - R$ 0,00 (remoção - sempre mostra)
```

## Estrutura de Dados

### JSON customization_data (exemplo):
```json
[
  {
    "name": "Alface",
    "action": "add",
    "quantity": 1,
    "price": 0
  },
  {
    "name": "Bacon",
    "action": "add",
    "quantity": 1,
    "price": 3.00
  },
  {
    "name": "Cebola",
    "action": "remove",
    "quantity": 1,
    "price": 0
  }
]
```

### Campos relevantes:
- `name`: Nome do ingrediente
- `action`: 'add' (adicionar) ou 'remove' (remover)
- `quantity`: Quantidade
- `price`: Preço unitário (0 = incluso, >0 = extra)
- `status`: 'Incluso', 'Extra', etc (quando disponível)
- `delta_qty`: Diferença de quantidade em relação ao padrão

## Benefícios

1. **Clareza visual**: Recibos mais limpos e focados
2. **Menos confusão**: Cozinha vê apenas o que precisa fazer diferente
3. **Experiência melhor**: Cliente não recebe lista gigante de ingredientes óbvios
4. **Consistência**: WhatsApp e PDF exibem mesmas informações
5. **Destaque em extras**: Itens pagos ficam evidentes com preço formatado

## Notas Técnicas

- O filtro funciona tanto no PDF (ThermalReceipt) quanto no WhatsApp (via PublicCartController)
- A string de personalização já é filtrada antes de ser enviada ao OrderNotificationService
- Não foi necessário modificar OrderNotificationService.php pois ele recebe strings já formatadas
- Compatibilidade mantida com dados antigos que não possuem campo `status`
- Fallback automático para lógica baseada em `price` e `delta_qty`

## Exemplos de Saída

### Antes:
```
> Personalizacao:
  + Pão Brioche (Gratis)
  + Hambúrguer 180g (Gratis)
  + Alface (Gratis)
  + Tomate (Gratis)
  + Maionese (Gratis)
  + Bacon (+R$ 3,00)
  + Cheddar (+R$ 2,50)
  - Cebola
```

### Depois:
```
> Personalizacao:
  + Bacon (+R$ 3,00)
  + Cheddar (+R$ 2,50)
  - Cebola
```

## Próximos Passos (Opcional)

- [ ] Adicionar toggle no admin para habilitar/desabilitar filtro
- [ ] Permitir configurar quais tipos mostrar (inclusos/extras/remoções)
- [ ] Adicionar indicador visual diferente para remoções vs extras
- [ ] Considerar agrupar por tipo (Extras: ... / Remover: ...)

---

**Autor**: GitHub Copilot  
**Revisão**: 12/01/2025  
**Status**: ✅ Implementado e Testado
