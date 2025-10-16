# Validações Obrigatórias de Endereço no Checkout

## Resumo das Mudanças

Implementadas validações obrigatórias para os campos de endereço no checkout, conforme solicitado:

### ✅ **Campos Modificados:**

#### 1. **Cidade** (`app/views/public/checkout.php`)
- **Alterado**: Adicionado atributo `required` ao select de cidade
- **Comportamento**: Usuário deve obrigatoriamente selecionar uma cidade

#### 2. **Bairro** (`app/views/public/checkout.php`)
- **Alterado**: Adicionado atributo `required` ao select de bairro  
- **Comportamento**: Usuário deve obrigatoriamente selecionar um bairro

#### 3. **Rua/Avenida** (`app/views/public/checkout.php`)
- **Mantido**: Campo já era obrigatório (`required`)
- **Comportamento**: Campo de texto obrigatório para nome da rua

#### 4. **Número** (`app/views/public/checkout.php`)
- **Alterado**: 
  - Tipo do input alterado de `text` para `number`
  - Adicionados atributos `min="1"` e `step="1"`
  - Adicionada validação JavaScript para aceitar apenas números
- **Comportamento**: Aceita apenas números inteiros positivos

### ✅ **Validações Backend (`app/controllers/PublicCartController.php`):**

#### Nova Validação Adicionada:
```php
// Validar se o número contém apenas dígitos
if ($clean['number'] !== '' && !preg_match('/^\d+$/', $clean['number'])) {
    $errors[] = 'O número do endereço deve conter apenas números.';
}
```

### ✅ **Validações Frontend (JavaScript):**

#### Validação em Tempo Real:
```javascript
// Validação do campo número - apenas números
const numberInput = document.querySelector('input[name="address[number]"]');
if (numberInput) {
    numberInput.addEventListener('input', (e) => {
        // Remove todos os caracteres que não são números
        e.target.value = e.target.value.replace(/[^\d]/g, '');
    });
    
    numberInput.addEventListener('keydown', (e) => {
        // Permitir apenas números, backspace, delete, tab e arrow keys
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
        if (!allowedKeys.includes(e.key) && (e.key < '0' || e.key > '9')) {
            e.preventDefault();
        }
    });
}
```

### ✅ **Validações Implementadas:**

| Campo | Tipo | Validação Frontend | Validação Backend | Status |
|-------|------|-------------------|-------------------|--------|
| **Cidade** | Select | `required` | Obrigatório | ✅ |
| **Bairro** | Select | `required` | Obrigatório | ✅ |
| **Rua/Avenida** | Text | `required` | Obrigatório | ✅ |
| **Número** | Number | `required`, `min="1"`, apenas números | Regex `/^\d+$/` | ✅ |

### ✅ **Experiência do Usuário:**

#### **Antes:**
- Campos cidade e bairro eram opcionais
- Campo número aceitava texto
- Validação apenas no backend

#### **Depois:**
- Todos os campos principais são obrigatórios
- Campo número aceita apenas números
- Validação em tempo real no frontend + backend
- Mensagens de erro mais específicas

### ✅ **Mensagens de Erro:**

1. `"Selecione uma cidade atendida."`
2. `"Selecione um bairro atendido."`
3. `"Informe a rua/avenida."`
4. `"Informe o número do endereço."`
5. `"O número do endereço deve conter apenas números."` *(Nova)*

### ✅ **Testes Recomendados:**

1. **Teste de Campos Obrigatórios:**
   - Tentar submeter formulário sem selecionar cidade
   - Tentar submeter formulário sem selecionar bairro
   - Tentar submeter formulário sem preencher rua
   - Tentar submeter formulário sem preencher número

2. **Teste de Validação de Número:**
   - Tentar digitar letras no campo número
   - Tentar colar texto com letras no campo número
   - Verificar se aceita apenas números positivos

3. **Teste de Experiência:**
   - Verificar se validação em tempo real funciona
   - Verificar se mensagens de erro aparecem corretamente
   - Confirmar que checkout funciona com dados válidos

### ✅ **Compatibilidade:**

- ✅ **HTML5**: Suporte a `type="number"` e `required`
- ✅ **JavaScript**: Validação funciona em todos os navegadores modernos
- ✅ **Fallback**: Validação backend garante segurança mesmo com JS desabilitado
- ✅ **Responsivo**: Campos mantêm comportamento responsivo