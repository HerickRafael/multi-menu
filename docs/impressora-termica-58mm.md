# Otimização de Impressão Térmica 58mm

## 🖨️ Sistema Otimizado para Mini Impressora Bluetooth Térmica 58mm

### Melhorias Implementadas

#### 1. **Configuração Específica para 58mm**
```php
private const WIDTH = 58;        // Largura em mm
private const MARGIN = 2;        // Margem lateral de 2mm
```
- Largura exata de 58mm (padrão de impressoras térmicas portáteis)
- Margens otimizadas para melhor aproveitamento do papel
- Auto page break ativado para pedidos longos

#### 2. **Linhas Separadoras Tracejadas**
- Substituídas linhas sólidas por linhas tracejadas mais leves
- Melhor contraste e economia de tinta térmica
- Código personalizado para desenhar traços com espaços

#### 3. **Estrutura do Cupom**

##### **Cabeçalho:**
- Logo da empresa (20mm, centralizado)
- Nome da empresa em MAIÚSCULAS (fonte 12, bold)
- Endereço (fonte 7, quebra automática)
- Telefone/WhatsApp
- Linha separadora

##### **Dados do Pedido:**
- Número do pedido destacado (PEDIDO #XXX)
- Data e hora formatados (dd/mm/AAAA HH:mm)
- Linha separadora

##### **Informações do Cliente:**
```
CLIENTE
Nome do cliente
Tel: (XX) XXXXX-XXXX

ENDERECO
Rua completa, número, bairro

PAGAMENTO
Método de pagamento
```

##### **Itens com Detalhamento Completo:**
```
1x Nome do Produto
  > Combo Group:
    2x Opção selecionada (+R$ X,XX)
    1x Outra opção
  > Personalizacao:
    + 2x Ingrediente adicionado (+R$ X,XX)
    - 1x Ingrediente removido
    + 1x Extra (Gratis)
  > Obs: Observações do item
R$ XX,XX
```

**Características:**
- Quantidade antes do nome (Nx)
- Combo com seta ">" e nome do grupo
- Opções do combo indentadas com 2 espaços
- Personalização em itálico
- Prefixo "+" para adição, "-" para remoção
- Preço adicional entre parênteses quando aplicável
- Marca "Gratis" quando não há custo adicional
- Observações em itálico com prefixo "> Obs:"

##### **Totais:**
```
Subtotal:           R$ XX,XX
Taxa de Entrega:    R$ X,XX
Desconto:         - R$ X,XX
───────────────────────────
TOTAL:              R$ XX,XX
```
- Valores alinhados à direita
- Total em negrito (fonte 10)
- Desconto com sinal negativo

##### **Observações Gerais:**
```
OBSERVACOES
Texto das observações do pedido
com quebra automática
```

##### **Rodapé:**
```
Obrigado pela preferencia!
Volte sempre!
───────────────────────────
[espaço de 8mm para corte]
```

### 4. **Otimizações Técnicas**

#### **Remoção de Emojis:**
```php
$s = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $s);  // Emoticons
$s = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $s);   // Símbolos diversos
$s = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $s);   // Dingbats
```
- Remove emojis que causam problemas em FPDF
- Garante compatibilidade com ISO-8859-1
- Mantém apenas caracteres imprimíveis

#### **Quebra de Texto Inteligente:**
- `MultiCell()` para endereços e observações longas
- Quebra automática respeitando a largura do papel
- Indentação visual para hierarquia de informações

#### **Espaçamento Otimizado:**
```php
$pdf->Ln(1);  // 1mm entre subitens
$pdf->Ln(2);  // 2mm entre itens
$pdf->Ln(3);  // 3mm entre seções
$pdf->Ln(8);  // 8mm no final para corte
```

### 5. **Tamanhos de Fonte**

| Elemento | Fonte | Tamanho | Estilo |
|----------|-------|---------|--------|
| Nome empresa | Arial | 12 | Bold |
| Número pedido | Arial | 10 | Bold |
| Total | Arial | 10 | Bold |
| Títulos seção | Arial | 9 | Bold |
| Títulos campo | Arial | 8 | Bold |
| Texto normal | Arial | 8 | Normal |
| Detalhes/Obs | Arial | 7 | Normal/Italic |
| Rodapé | Arial | 7 | Normal |

### 6. **Comparação Antes x Depois**

#### **Antes:**
```
QTD  DESCRIÇÃO           VALOR
1    Woll Smash     R$ 62,30
```
❌ Sem detalhes de combo
❌ Sem personalização
❌ Informação incompleta

#### **Depois:**
```
1x Woll Smash
  > Bled Costela 90 (carne):
    6x Bled Costela 90 (carne) (+R$ 36,00)
  > Personalizacao:
    + 1x Maionese
    + 1x Cebola
    + 1x Queijo Cheddar
R$ 62,30
```
✅ Combo detalhado
✅ Personalização completa
✅ Preços adicionais visíveis
✅ Fácil leitura para cozinha

### 7. **Benefícios**

1. **Para a Cozinha:**
   - Todas as personalizações visíveis
   - Fácil identificação de ingredientes extras/removidos
   - Observações destacadas

2. **Para o Cliente:**
   - Comprovante detalhado do pedido
   - Transparência nos valores
   - Informações de contato da empresa

3. **Para Impressão:**
   - Otimizado para papel 58mm
   - Economia de papel (formato compacto)
   - Quebra automática de página para pedidos grandes
   - Compatível com impressoras Bluetooth portáteis

### 8. **Compatibilidade**

✅ **Testado com:**
- Mini Impressora Bluetooth Térmica 58mm
- FPDF (biblioteca PHP)
- Charset ISO-8859-1
- Papel térmico padrão 58mm x rolo

✅ **Suporta:**
- Múltiplos itens por pedido
- Combo com múltiplos grupos
- Personalização ilimitada
- Observações longas (quebra automática)
- Endereços completos
- Métodos de pagamento diversos

### 9. **Arquivo de Teste**

Execute:
```bash
php test_print_system.php
```

Resultado esperado:
```
✓ Conexão com banco estabelecida
✓ Empresa encontrada
✓ Pedido encontrado
✓ Itens do pedido carregados: X itens
✓ PDF gerado com sucesso!
  Tamanho: ~3-5 KB (dependendo do pedido)
```

### 10. **Próximos Passos**

Para usar em produção:
1. Acesse: `/admin/{slug}/orders/show?id={order_id}`
2. Clique no botão "Imprimir"
3. PDF será aberto em nova aba
4. Use Ctrl+P ou Cmd+P para imprimir
5. Selecione sua impressora térmica
6. Configure:
   - Tamanho: 58mm ou Custom (58mm width)
   - Margem: Mínima
   - Orientação: Retrato

---

**Data**: 17/10/2025  
**Desenvolvedor**: Sistema Automatizado  
**Tipo**: Otimização de Impressão Térmica  
**Status**: ✅ IMPLEMENTADO E TESTADO
