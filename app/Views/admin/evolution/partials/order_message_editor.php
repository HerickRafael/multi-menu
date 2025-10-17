<?php
/**
 * Editor de Template de Mensagem de Pedido
 * Template parcial para ser incluído na página de configuração da instância
 */
?>

<div class="mb-3">
  <label class="block text-sm font-medium text-slate-700 mb-2">
    Formato da Mensagem
  </label>
  
  <!-- Campos Disponíveis -->
  <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-4 mb-3">
    <div class="flex items-center gap-2 mb-3">
      <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
      </svg>
      <h4 class="font-semibold text-slate-800">Personalize os Campos da Mensagem</h4>
    </div>
    
    <p class="text-sm text-slate-600 mb-3">Selecione os campos que deseja incluir na notificação:</p>
    
    <!-- Grid de Campos -->
    <div class="grid grid-cols-2 gap-2 mb-3">
      <!-- Campos de Pedido -->
      <div class="space-y-2">
        <p class="text-xs font-semibold text-slate-700 mb-2">📋 Dados do Pedido</p>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="company_name" checked>
          <span>🍔 Nome da Empresa</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="order_number" checked>
          <span>📋 Número do Pedido</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="order_status" checked>
          <span>🔔 Status do Pedido</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="order_date" checked>
          <span>⏰ Data e Hora</span>
        </label>
      </div>
      
      <!-- Campos de Cliente -->
      <div class="space-y-2">
        <p class="text-xs font-semibold text-slate-700 mb-2">👤 Dados do Cliente</p>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="customer_name" checked>
          <span>👤 Nome do Cliente</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="customer_phone">
          <span>📱 Telefone</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="customer_address">
          <span>📍 Endereço</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="delivery_type" checked>
          <span>🚗 Tipo de Entrega</span>
        </label>
      </div>
    </div>
    
    <!-- Campos de Pagamento e Valores -->
    <div class="grid grid-cols-2 gap-2 mb-3">
      <div class="space-y-2">
        <p class="text-xs font-semibold text-slate-700 mb-2">💰 Pagamento e Valores</p>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="payment_method" checked>
          <span>💳 Forma de Pagamento</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="payment_change">
          <span>💵 Troco Para</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="subtotal" checked>
          <span>💵 Subtotal</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="delivery_fee">
          <span>🚚 Taxa de Entrega</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="total" checked>
          <span>💰 Total</span>
        </label>
      </div>
      
      <!-- Campos de Produtos -->
      <div class="space-y-2">
        <p class="text-xs font-semibold text-slate-700 mb-2">🛒 Produtos</p>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="items_list" checked>
          <span>🛒 Lista de Itens</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="item_quantity" checked>
          <span>🔢 Quantidade</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="item_price" checked>
          <span>💵 Preço Unitário</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="item_subtotal" checked>
          <span>💰 Subtotal do Item</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="item_customization">
          <span>⚙️ Personalizações</span>
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
          <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="item_observations">
          <span>📝 Observações</span>
        </label>
      </div>
    </div>
    
    <!-- Campos Extras -->
    <div class="space-y-2">
      <p class="text-xs font-semibold text-slate-700 mb-2">➕ Informações Extras</p>
      <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
        <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="order_notes">
        <span>📝 Observações do Pedido</span>
      </label>
      <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
        <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="estimated_time">
        <span>⏱️ Tempo Estimado</span>
      </label>
      <label class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white/50 p-2 rounded transition">
        <input type="checkbox" class="order-field-toggle rounded text-indigo-600" data-field="system_source" checked>
        <span>📱 Origem (Sistema Automático)</span>
      </label>
    </div>
    
    <!-- Ações Rápidas -->
    <div class="flex gap-2 mt-4 pt-3 border-t border-indigo-200">
      <button type="button" id="selectAllFields" class="text-xs px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        ✓ Selecionar Todos
      </button>
      <button type="button" id="deselectAllFields" class="text-xs px-3 py-1.5 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
        ✗ Desmarcar Todos
      </button>
      <button type="button" id="resetDefaultFields" class="text-xs px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition ml-auto">
        🔄 Restaurar Padrão
      </button>
    </div>
  </div>
  
  <!-- Preview da Mensagem -->
  <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
    <div class="flex items-center gap-2 mb-3">
      <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
      </svg>
      <h4 class="font-semibold text-slate-800">Preview da Mensagem</h4>
    </div>
    
    <div class="bg-white border border-slate-200 rounded-lg p-3 font-mono text-xs text-slate-700 whitespace-pre-line leading-relaxed max-h-96 overflow-auto" id="messagePreview">
      <!-- Preview será gerado aqui -->
    </div>
    
    <p class="text-xs text-slate-500 mt-2">
      ℹ️ Este é um exemplo de como a mensagem será enviada. Os valores reais virão dos pedidos.
    </p>
  </div>
</div>
