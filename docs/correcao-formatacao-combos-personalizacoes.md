# Correção de Formatação - Combos e Personalizações

## Data
17 de Outubro de 2025

## Problema Identificado

As mensagens estavam quebrando linha incorretamente:

```
1x Bled Costela 90 (carne) (+ R$ 7
50)
1x Cebola (+ R$ 0
30)
```

## Causa Raiz

**Problema 1**: O `explode(',')` estava dividindo strings usando vírgulas, mas os preços também contêm vírgulas (`R$ 7,50`), causando quebra incorreta.

**Problema 2**: Nomes longos com parênteses não estavam sendo processados corretamente.

**Problema 3**: Valores não seguiam o padrão do subtotal (tinha `+ R$` ao invés de `R$`).

## Soluções Implementadas

### 1. Separação Inteligente de Itens

**Antes**:
```php
$customItems = explode(',', $customization);
```

**Depois**:
```php
$customItems = preg_split('/,\s+(?=\d|[A-Z]|Sem|[\+\-])/i', $customization);
```

**Explicação**: O `preg_split` só quebra em vírgulas que são seguidas por:
- Dígito (`\d`) - início de item como "1x"
- Letra maiúscula (`[A-Z]`) - início de palavra
- "Sem" - remoção de item
- Símbolos `+` ou `-`

Isso evita quebrar em vírgulas dentro de preços.

### 2. Extração Robusta de Preços

```php
// Extrair preço do final: "(+ R$ X,XX)"
if (preg_match('/\(\+\s*R\$\s*([\d,\.]+)\)\s*$/', $customItem, $priceMatch)) {
    $itemPrice = floatval(str_replace(',', '.', $priceMatch[1]));
    $itemName = trim(preg_replace('/\s*\(\+\s*R\$\s*[\d,\.]+\)\s*$/', '', $customItem));
}
```

Busca o padrão `(+ R$ X,XX)` no final da string e remove, deixando apenas o nome.

### 3. Truncamento de Nomes Longos

```php
// Garantir que a linha cabe (máximo 32 chars)
$availableSpace = 32 - strlen($customValue);
if (strlen($customLine) >= $availableSpace) {
    $maxNameLength = $availableSpace - 1;
    if (strlen($customLine) > $maxNameLength) {
        $customLine = substr($customLine, 0, $maxNameLength);
    }
}
```

Se o nome for muito longo, trunca para caber na linha de 32 caracteres, garantindo pelo menos 1 espaço antes do valor.

### 4. Padrão Unificado de Valores

Todos os valores agora usam `R$ X,XX` (sem o `+`) para ficar consistente com subtotal e taxa:

```php
$customValue = 'R$ ' . number_format($itemPrice, 2, ',', '.');  // Sem o "+"
```

## Resultado Final

### Antes (Quebrado):
```
1x Woll Smash           R$ 24,80
  1x Bled Costela 90 (carne) (+ R$ 7
  50)
  1x Maionese
  1x Cebola (+ R$ 0
  30)
  1x Queijo Cheddar (+ R$ 1
  00)
```

### Depois (Correto):
```
1x Woll Smash           R$ 24,80
  1x Bled Costela 90 (ca R$ 7,50
  1x Maionese
  1x Cebola              R$ 0,30
  1x Queijo Cheddar      R$ 1,00
```

## Arquivos Modificados

1. **app/services/OrderNotificationService.php**
   - Linha ~217: Separação de combos com `preg_split`
   - Linha ~256: Separação de personalizações com `preg_split`
   - Linha ~268-285: Extração de preços do final da string
   - Linha ~298-315: Truncamento de nomes longos
   - Linha ~223-238: Truncamento para combos

2. **app/controllers/PublicCartController.php**
   - Linha ~1347: Adicionado verificação `!empty($itemName)` para evitar itens vazios

## Casos de Teste

### Caso 1: Nome com Parênteses e Preço
**Input**: `"1x Bled Costela 90 (carne) (+ R$ 7,50)"`
**Output**: `"  1x Bled Costela 90 (ca R$ 7,50"` ✅

### Caso 2: Item Sem Preço
**Input**: `"1x Maionese"`
**Output**: `"  1x Maionese"` ✅

### Caso 3: Nome Curto com Preço
**Input**: `"1x Cebola (+ R$ 0,30)"`
**Output**: `"  1x Cebola              R$ 0,30"` ✅

### Caso 4: Remoção
**Input**: `"Sem Cebola"`
**Output**: `"  Sem Cebola"` ✅

## Benefícios

1. ✅ Valores não quebram mais linha
2. ✅ Nomes com parênteses são processados corretamente
3. ✅ Todos os valores alinhados à direita (coluna 32)
4. ✅ Padrão consistente com subtotal e taxa
5. ✅ Nomes longos são truncados para caber
6. ✅ Funciona com vírgula decimal em preços

## Observações

- Nomes muito longos (>22 caracteres com indentação) serão truncados
- Exemplo: "Bled Costela 90 (carne)" → "Bled Costela 90 (ca"
- O truncamento garante que o valor sempre apareça alinhado
- Novos pedidos já usarão a formatação correta
- Pedidos antigos no banco manterão o formato antigo

---

**Autor**: GitHub Copilot  
**Status**: ✅ Implementado e Testado  
**Versão**: 2.0 - Formatação Robusta
