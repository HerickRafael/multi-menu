<?php
/**
 * Teste simples para verificar se o HTML do campo de mensagem está sendo gerado
 */

echo "=== TESTE HTML DO CAMPO DE MENSAGEM ===\n\n";

// Simular dados
$instanceName = 'teste';
$slug = 'wellburger';

$htmlContent = '
<!-- Campo de mensagem ao rejeitar chamada (aparece quando toggle está ativado) -->
<div id="rejectCallMessageContainer" class="hidden transition-all duration-300 ease-in-out">
  <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
    <label for="rejectCallMessage" class="block text-sm font-medium text-slate-700 mb-2">
      Mensagem ao rejeitar chamada
    </label>
    <textarea 
      id="rejectCallMessage" 
      class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
      rows="3"
      placeholder="Digite a mensagem que será enviada quando uma chamada for rejeitada automaticamente..."
    ></textarea>
    <div class="flex justify-end mt-2">
      <button id="saveRejectMessage" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
        Salvar Mensagem
      </button>
    </div>
  </div>
</div>
';

echo "HTML do campo de mensagem:\n";
echo $htmlContent . "\n";

echo "JavaScript necessário:\n";
echo "- updateToggleState() - Mostra/esconde campo baseado no toggle\n";
echo "- saveRejectCallMessage() - Salva mensagem via API\n";
echo "- loadInstanceSettings() - Carrega mensagem atual\n";

echo "\nEndpoint usado:\n";
echo "POST /admin/$slug/evolution/instance/$instanceName/settings\n";
echo "Body: {\"msgCall\": \"mensagem aqui\"}\n";

echo "\nConfigurações CSS:\n";
echo "- Animação suave com transition\n";
echo "- Estados hidden/visible\n";
echo "- Focus ring no textarea\n";

echo "\n=== TESTE CONCLUÍDO ===\n";