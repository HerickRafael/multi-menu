<?php
// admin/evolution/instance_config.php — Configuração da Instância Evolution (design unificado)

$title = 'Configuração Evolution - ' . ($company['name'] ?? 'Empresa');
$activeSlug = $slug ?? ($company['slug'] ?? '');
$backUrl = base_url('admin/' . rawurlencode($activeSlug) . '/evolution');

// helper de escape

ob_start(); ?>

<style>
/* Estilos específicos para o campo de mensagem de rejeição */
#rejectCallMessageContainer {
  transform: translateY(-10px);
  opacity: 0;
  max-height: 0;
  overflow: hidden;
}

#rejectCallMessageContainer:not(.hidden) {
  transform: translateY(0);
  opacity: 1;
  max-height: 200px;
  transition: all 0.3s ease-in-out;
}

#rejectCallMessage:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

#saveRejectMessage:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

<!-- Sistemas centralizados carregados via layout principal -->

<div class="mx-auto max-w-6xl p-4">

  <!-- HEADER -->
  <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <div class="flex items-center gap-3">
        <a href="<?= e($backUrl) ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
          </svg>
        </a>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Configuração da Instância</h1>
          <p class="text-sm text-slate-600"><?= e($instanceName) ?> — Gerencie sua conexão WhatsApp</p>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <!-- Progress indicator -->
      <div id="loadingProgress" class="hidden items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700">
        <div class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></div>
        <span id="loadingText">Carregando...</span>
      </div>
      
      <button id="btnRefresh" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
        <svg class="refresh-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
          <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
        </svg>
        Atualizar
      </button>
    </div>
  </div>

  <!-- INSTANCE HEADER CARD -->
  <section class="mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <!-- Skeleton loading para header - inicialmente visível -->
    <div id="headerSkeleton" class="p-6">
      <div class="animate-pulse">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-xl bg-slate-200"></div>
            <div>
              <div class="h-6 bg-slate-200 rounded w-32 mb-2"></div>
              <div class="h-4 bg-slate-200 rounded w-48"></div>
            </div>
          </div>
          <div class="h-6 bg-slate-200 rounded-full w-20"></div>
        </div>
        
        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center gap-2">
            <div class="flex-1 h-4 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
            <div class="h-6 w-6 bg-slate-200 rounded"></div>
          </div>
        </div>
        
        <div class="flex items-center justify-end gap-2 pt-2">
          <div class="h-8 w-8 bg-slate-200 rounded-lg"></div>
          <div class="h-8 bg-slate-200 rounded-lg w-20"></div>
          <div class="h-8 bg-slate-200 rounded-lg w-24"></div>
        </div>
      </div>
    </div>
    
    <!-- Conteúdo real - inicialmente oculto para evitar flash -->
    <div id="headerContent" class="p-6 hidden">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="h-12 w-12 rounded-xl admin-gradient-bg grid place-items-center text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-slate-900"><?= e($instanceName) ?></h2>
            <p class="text-sm text-slate-600">Instância WhatsApp Evolution</p>
          </div>
        </div>
        <?php 
          $status = $instanceData['connectionStatus'] ?? ($instanceData['status'] ?? 'disconnected');
          $isConnected = $status === 'open';
          
          // Mapear status para classes CSS unificadas
          $statusClass = match($status) {
            'open' => 'status-connected',
            'connecting' => 'status-connecting', 
            'close', 'disconnected' => 'status-disconnected',
            default => 'status-pending'
          };
          
          $statusText = match($status) {
            'open' => 'Conectado',
            'connecting' => 'Conectando',
            'close', 'disconnected' => 'Desconectado', 
            default => ucfirst($status)
          };
        ?>
        <span id="statusPill" class="status-pill <?= $statusClass ?>">
          <span class="status-dot"></span>
          <?= $statusText ?>
        </span>
      </div>

      <!-- Token -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-2">Token da Instância</label>
        <div class="relative">
          <input id="tokenInput" type="password" value="<?= e($instanceData['token'] ?? 'N/A') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-24 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" readonly />
          <div class="absolute inset-y-0 right-2 flex items-center gap-1">
            <button id="toggleMask" class="p-2 rounded-lg hover:bg-slate-100" title="Mostrar/ocultar">
              <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            <button id="copyToken" class="p-2 rounded-lg hover:bg-slate-100" title="Copiar">
              <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Connection Banner - Only show when disconnected -->
      <?php if (!$isConnected): ?>
      <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-4 flex items-center gap-4">
        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm flex-1">Para conectar, escaneie o QR code com seu WhatsApp</p>
        <div class="flex items-center gap-2">
          <button id="btnQr" class="px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white">Obter QR Code</button>
          <button id="btnRefreshState" class="p-2 rounded-lg hover:bg-amber-100" title="Recarregar estado">
            <svg class="refresh-icon w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
              <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
              <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
            </svg>
          </button>
          <button id="btnRestart" class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700">REINICIAR</button>
          <button id="btnDisconnect" class="px-3 py-2 rounded-lg text-sm font-medium bg-red-100 hover:bg-red-200 text-red-700">DESCONECTAR</button>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Action buttons for connected instances -->
      <?php if ($isConnected): ?>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button id="btnRefreshState" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500" title="Recarregar estado">
          <svg class="refresh-icon w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
          </svg>
        </button>
        <button id="btnRestart" class="px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700">REINICIAR</button>
        <button id="btnDisconnect" class="px-3 py-2 rounded-lg text-sm font-medium bg-red-100 hover:bg-red-200 text-red-700">DESCONECTAR</button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- STATISTICS CARDS -->
  <section class="grid gap-6 md:grid-cols-3">
    <!-- Skeleton loading para todos os cards de estatísticas -->
    <div id="statsSkeleton" class="contents">
      <!-- Skeleton Card 1 - Contatos -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg skeleton-enhanced"></div>
          <div class="flex-1 space-y-2">
            <div class="h-5 rounded skeleton-enhanced" style="width: 4.5rem;"></div>
            <div class="h-9 rounded skeleton-enhanced" style="width: 3rem; animation-delay: 0.2s;"></div>
          </div>
        </div>
      </div>
      
      <!-- Skeleton Card 2 - Chats -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg skeleton-enhanced" style="animation-delay: 0.3s;"></div>
          <div class="flex-1 space-y-2">
            <div class="h-5 rounded skeleton-enhanced" style="width: 3rem; animation-delay: 0.4s;"></div>
            <div class="h-9 rounded skeleton-enhanced" style="width: 3.5rem; animation-delay: 0.5s;"></div>
          </div>
        </div>
      </div>
      
      <!-- Skeleton Card 3 - Mensagens -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg skeleton-enhanced" style="animation-delay: 0.6s;"></div>
          <div class="flex-1 space-y-2">
            <div class="h-5 rounded skeleton-enhanced" style="width: 5rem; animation-delay: 0.7s;"></div>
            <div class="h-9 rounded skeleton-enhanced" style="width: 4rem; animation-delay: 0.8s;"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Cards reais de estatísticas -->
    <div id="statsContent" class="contents hidden">
      <!-- Card 1 - Contatos -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 stat-card" style="opacity: 0; transform: translateY(20px);">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg bg-blue-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Contatos</h3>
            <p class="text-3xl font-bold text-slate-900 stat-value" data-stat="contacts"><?= number_format($instanceData['_count']['Contact'] ?? 0) ?></p>
          </div>
        </div>
      </div>
      
      <!-- Card 2 - Chats -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 stat-card" style="opacity: 0; transform: translateY(20px);">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg bg-green-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Chats</h3>
            <p class="text-3xl font-bold text-slate-900 stat-value" data-stat="chats"><?= number_format($instanceData['_count']['Chat'] ?? 0) ?></p>
          </div>
        </div>
      </div>
      
      <!-- Card 3 - Mensagens -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 stat-card" style="opacity: 0; transform: translateY(20px);">
        <div class="flex items-center gap-4">
          <div class="size-12 rounded-lg bg-purple-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v6a2 2 0 01-2 2h-3l-4 4z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Mensagens</h3>
            <p class="text-3xl font-bold text-slate-900 stat-value" data-stat="messages"><?= number_format($instanceData['_count']['Message'] ?? 0) ?></p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- INSTANCE INFO SECTION -->
  <section class="mt-6 rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
    <!-- Skeleton loading para seção de informações -->
    <div id="infoSkeleton" class="grid gap-8 lg:grid-cols-2">
      <div class="animate-pulse">
        <div class="h-6 bg-slate-200 rounded w-40 mb-4"></div>
        <div class="space-y-3">
          <div class="flex justify-between py-2 border-b border-slate-100">
            <div class="h-4 bg-slate-200 rounded w-16"></div>
            <div class="h-4 bg-slate-200 rounded w-24"></div>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <div class="h-4 bg-slate-200 rounded w-20"></div>
            <div class="h-4 bg-slate-200 rounded w-32"></div>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <div class="h-4 bg-slate-200 rounded w-18"></div>
            <div class="h-4 bg-slate-200 rounded w-28"></div>
          </div>
          <div class="flex justify-between py-2">
            <div class="h-4 bg-slate-200 rounded w-24"></div>
            <div class="h-4 bg-slate-200 rounded w-28"></div>
          </div>
        </div>
      </div>
      
      <div class="animate-pulse">
        <div class="h-6 bg-slate-200 rounded w-28 mb-4"></div>
        <div class="space-y-4">
          <div class="h-4 bg-slate-200 rounded w-32 mb-4"></div>
          <div class="space-y-3">
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
            <div class="h-12 bg-slate-100 rounded-lg"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Conteúdo real -->
    <div id="infoContent" class="grid gap-8 lg:grid-cols-2 hidden">
      <div>
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Informações da Instância</h3>
        <dl class="space-y-3">
          <div class="flex justify-between py-2 border-b border-slate-100">
            <dt class="text-sm text-slate-600">Cliente:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= e($instanceData['clientName'] ?? 'evolutionvictor') ?></dd>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <dt class="text-sm text-slate-600">Integração:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= e($instanceData['integration'] ?? 'WHATSAPP-BAILEYS') ?></dd>
          </div>
          <div class="flex justify-between py-2 border-b border-slate-100">
            <dt class="text-sm text-slate-600">Criado em:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= isset($instanceData['createdAt']) ? date('d/m/Y H:i', strtotime($instanceData['createdAt'])) : 'N/A' ?></dd>
          </div>
          <div class="flex justify-between py-2">
            <dt class="text-sm text-slate-600">Última atualização:</dt>
            <dd class="text-sm font-medium text-slate-900"><?= isset($instanceData['updatedAt']) ? date('d/m/Y H:i', strtotime($instanceData['updatedAt'])) : 'N/A' ?></dd>
          </div>
        </dl>
      </div>

      <div>
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Configurações</h3>
        
        <!-- Skeleton loading para configurações -->
        <div id="settingsSkeletonLoader" class="space-y-4">
          <div class="animate-pulse">
            <!-- Skeleton para aviso da API (se houver) -->
            <div class="mb-4 p-3 bg-slate-100 border border-slate-200 rounded-lg">
              <div class="flex items-center gap-2">
                <div class="w-5 h-5 bg-slate-300 rounded"></div>
                <div class="h-4 bg-slate-300 rounded w-32"></div>
              </div>
              <div class="h-3 bg-slate-200 rounded w-80 mt-1"></div>
            </div>
            <!-- Skeleton para toggles -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-24 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-40"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-28 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-44"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-26 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-36"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-30 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-38"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-32 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-48"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <div class="h-4 bg-slate-300 rounded w-34 mb-1"></div>
                  <div class="h-3 bg-slate-200 rounded w-52"></div>
                </div>
                <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Conteúdo real das configurações -->
        <div id="settingsContent" class="hidden">
          <!-- Configurações de Comportamento -->
          <?php if (empty($company['evolution_server_url']) || empty($company['evolution_api_key'])): ?>
          <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm font-medium text-amber-800">Evolution API não configurada</span>
            </div>
            <p class="text-xs text-amber-700 mt-1">Configure o servidor e chave da API nas configurações da empresa para usar essas funcionalidades.</p>
          </div>
          <?php endif; ?>
          
          <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Rejeitar chamadas</p>
              <p class="text-xs text-slate-500">Recusar automaticamente chamadas recebidas</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusRejectCalls" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleRejectCalls" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          
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
          
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Ler mensagens</p>
              <p class="text-xs text-slate-500">Marcar mensagens como lidas automaticamente</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusReadMessages" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleReadMessages" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Sempre online</p>
              <p class="text-xs text-slate-500">Manter status online constantemente</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusAlwaysOnline" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleAlwaysOnline" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Ignorar grupos</p>
              <p class="text-xs text-slate-500">Não processar mensagens de grupos</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusGroupsIgnore" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleGroupsIgnore" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Visualizar status</p>
              <p class="text-xs text-slate-500">Marcar status como visualizado automaticamente</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusReadStatus" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleReadStatus" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900">Sincronizar histórico</p>
              <p class="text-xs text-slate-500">Sincronizar histórico completo do WhatsApp</p>
            </div>
            <div class="flex items-center gap-2">
              <span id="statusSyncFullHistory" class="text-xs text-slate-400 hidden">Carregando...</span>
              <button id="toggleSyncFullHistory" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
                <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
              </button>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- NOTIFICAÇÃO DE PEDIDO -->
  <section class="mb-6 rounded-2xl bg-white border border-slate-200 shadow-sm">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        Notificação de Pedido
      </h3>
      
      <!-- Skeleton loading para notificação de pedido -->
      <div id="orderNotificationSkeleton" class="space-y-4">
        <div class="animate-pulse">
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="h-4 bg-slate-300 rounded w-40 mb-1"></div>
              <div class="h-3 bg-slate-200 rounded w-60"></div>
            </div>
            <div class="h-6 w-11 bg-slate-200 rounded-full"></div>
          </div>
          
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50">
            <div class="h-4 bg-slate-300 rounded w-32 mb-3"></div>
            <div class="h-10 bg-slate-200 rounded w-full mb-3"></div>
            <div class="h-8 bg-slate-200 rounded w-28"></div>
          </div>
        </div>
      </div>

      <!-- Conteúdo real da notificação de pedido -->
      <div id="orderNotificationContent" class="hidden">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-slate-900">Notificar novos pedidos</p>
            <p class="text-xs text-slate-500">Enviar mensagem para números WhatsApp quando houver novo pedido</p>
          </div>
          <div class="flex items-center gap-2">
            <span id="statusOrderNotification" class="text-xs text-slate-400 hidden">Carregando...</span>
            <button id="toggleOrderNotification" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-200 transition-colors duration-200 ease-in-out hover:bg-slate-300" data-enabled="false" data-loading="false">
              <span class="pointer-events-none inline-block h-5 w-5 translate-x-0 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"></span>
            </button>
          </div>
        </div>

        <!-- Container para configuração de números - inicialmente oculto -->
        <div id="orderNotificationGroupContainer" class="hidden border border-slate-200 rounded-xl p-4 bg-slate-50">
          <!-- Aviso sobre grupos em manutenção -->
          <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
              </svg>
              <div>
                <p class="text-sm font-medium text-amber-800">Notificação via grupos em manutenção</p>
                <p class="text-xs text-amber-700 mt-1">No momento, as notificações estão sendo enviadas para números individuais. Os grupos WhatsApp estarão disponíveis em breve.</p>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="orderNotificationNumber1" class="block text-sm font-medium text-slate-700 mb-2">
              Número Principal para Notificações
            </label>
            <input type="tel" id="orderNotificationNumber1" placeholder="Ex: 5511999999999" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" maxlength="20">
            <p class="text-xs text-slate-500 mt-1">Digite o número com código do país (Ex: 5511999999999)</p>
          </div>
          
          <div class="mb-3">
            <label for="orderNotificationNumber2" class="block text-sm font-medium text-slate-700 mb-2">
              Número Secundário (opcional)
            </label>
            <input type="tel" id="orderNotificationNumber2" placeholder="Ex: 5511888888888" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" maxlength="20">
            <p class="text-xs text-slate-500 mt-1">Número adicional para receber cópia das notificações</p>
          </div>
          
          <div class="mb-3">
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Formato da Mensagem
            </label>
            <div class="bg-slate-100 border border-slate-200 rounded-lg p-3 text-sm">
              <p class="text-slate-600 mb-2">A mensagem será formatada automaticamente com:</p>
              <ul class="text-xs text-slate-500 space-y-1">
                <li>🍔 <strong>Cabeçalho</strong> - Nome da empresa e novo pedido</li>
                <li>📋 <strong>Pedido</strong> - Número do pedido</li>
                <li>👤 <strong>Cliente</strong> - Nome completo do cliente</li>
                <li>� <strong>Pagamento</strong> - Forma de pagamento escolhida</li>
                <li>�💰 <strong>Total</strong> - Valor total formatado</li>
                <li>� <strong>Itens</strong> - Lista com quantidades e preços</li>
                <li>⏰ <strong>Data/Hora</strong> - Timestamp do pedido</li>
                <li>📱 <strong>Origem</strong> - Sistema automático</li>
              </ul>
              <p class="text-xs text-slate-400 mt-2">✨ Formato otimizado para WhatsApp mobile</p>
            </div>
          </div>
          
          <button id="saveOrderNotification" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Salvar Configuração
          </button>
        </div>
      </div>
    </div>
  </section>

</div>

  <!-- QR CODE MODAL -->
  <div id="qrModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" id="qrModalBg"></div>
    <div class="relative w-[520px] max-w-[92vw] rounded-2xl bg-white shadow-xl border border-slate-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-semibold text-slate-900">QR Code da Instância</h4>
        <button class="p-2 rounded-lg hover:bg-slate-100 text-slate-500" id="closeQr" aria-label="Fechar">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="bg-slate-50 rounded-xl p-8 border border-slate-200" id="qrContainer">
        <div class="text-center">
          <svg class="mx-auto mb-4 w-8 h-8 loading-refresh-icon text-indigo-600" xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
          </svg>
          <p class="text-sm text-slate-600">Carregando QR Code</p>
        </div>
      </div>
      <div class="mt-6 flex justify-end gap-3">
        <button id="closeQr2" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">Fechar</button>
      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div id="toasts" class="fixed bottom-4 right-4 space-y-2 z-[200]"></div>

  <script>
    // Cache busting - forçar reload do JavaScript
    console.log('Evolution Instance Config JS - v2.1 - <?= date("Y-m-d H:i:s") ?>');
    
    const el = (id) => document.getElementById(id);
    const instanceName = '<?= htmlspecialchars($instanceName) ?>';
    const baseUrl = '<?= base_url('admin/' . rawurlencode($company['slug']) . '/evolution/instance/') ?>';

    console.log('Configuração inicial:');
    console.log('- instanceName:', instanceName);
    console.log('- baseUrl:', baseUrl);
    console.log('- URL de grupos:', baseUrl + instanceName + '/groups');

    // Sistema de toast profissional - reutilizar admin-common.js
    function toast(message, type = 'info') {
      if (window.AdminCommon && window.AdminCommon.showToast) {
        // Mapear tipos para compatibilidade
        const typeMap = { 'ok': 'success', 'warn': 'warning' };
        window.AdminCommon.showToast(message, typeMap[type] || type);
      } else {
        // Fallback melhorado com animações suaves
        const wrap = document.createElement('div');
        const base = 'pointer-events-auto px-4 py-3 rounded-xl text-sm shadow-lg border transform transition-all duration-300 ease-in-out';
        const palette = {
          info:    'bg-blue-50 border-blue-200 text-blue-800',
          success: 'bg-green-50 border-green-200 text-green-800', 
          warn:    'bg-amber-50 border-amber-200 text-amber-800',
          warning: 'bg-amber-50 border-amber-200 text-amber-800',
          error:   'bg-red-50 border-red-200 text-red-800'
        }
        
        wrap.className = base + ' ' + (palette[type] || palette.info);
        wrap.textContent = message;
        wrap.style.transform = 'translateX(100%)';
        wrap.style.opacity = '0';
        
        const toastsEl = el('toasts');
        if (toastsEl) {
          toastsEl.appendChild(wrap);
          
          // Animação de entrada
          requestAnimationFrame(() => {
            wrap.style.transform = 'translateX(0)';
            wrap.style.opacity = '1';
          });
          
          // Animação de saída
          setTimeout(() => {
            wrap.style.transform = 'translateX(100%)';
            wrap.style.opacity = '0';
            setTimeout(() => wrap.remove(), 300);
          }, 4700);
        }
      }
    }
    
    // Micro-interactions para melhor feedback visual
    const MicroInteractions = {
      // Pulse effect para elementos carregando
      pulse(element) {
        if (!element) return;
        element.classList.add('animate-pulse');
        return () => element.classList.remove('animate-pulse');
      },
      
      // Bounce effect para feedbacks positivos
      bounce(element) {
        if (!element) return;
        element.style.transform = 'scale(1.05)';
        element.style.transition = 'transform 0.15s ease-out';
        setTimeout(() => {
          element.style.transform = 'scale(1)';
        }, 150);
      },
      
      // Shake effect para erros
      shake(element) {
        if (!element) return;
        element.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
          element.style.animation = '';
        }, 500);
      }
    };
    
    // CSS já carregado via skeleton.css - não precisa adicionar inline
    
    // Sistema de estados visuais profissional (usando SkeletonSystem centralizado)
    const VisualStates = window.SkeletonSystem ? window.SkeletonSystem.VisualStates : {
      // Fallbacks básicos caso SkeletonSystem não esteja carregado
      applyLoadingState(element) {
        if (!element) return;
        element.classList.add('skeleton-basic');
        return () => element.classList.remove('skeleton-basic');
      },
      
      revealWithAnimation(element, animation = 'fadeInScale') {
        if (!element) return;
        element.classList.add('animate-' + animation.replace(/([A-Z])/g, '-$1').toLowerCase());
      },
      
      enhanceButtons() {
        document.querySelectorAll('button').forEach(button => {
          if (button.hasAttribute('data-enhanced')) return;
          button.setAttribute('data-enhanced', 'true');
          
          button.addEventListener('click', () => {
            if (!button.disabled && MicroInteractions) {
              MicroInteractions.bounce(button);
            }
          });
        });
      }
    };

    // Sidebar mobile
    el('btnOpenSidebar')?.addEventListener('click', () => {
      const sb = document.getElementById('sidebar');
      sb?.classList.toggle('hidden');
    });

    // Token copy + mask
    el('copyToken').addEventListener('click', async () => {
      const input = el('tokenInput');
      if (window.AdminCommon && window.AdminCommon.copyToClipboard) {
        window.AdminCommon.copyToClipboard(input.value, 'Token copiado para a área de transferência!');
      } else {
        // Fallback
        try { 
          await navigator.clipboard.writeText(input.value); 
          toast('Token copiado para a área de transferência', 'ok'); 
        } catch { 
          toast('Não foi possível copiar', 'error'); 
        }
      }
    });

    el('toggleMask').addEventListener('click', () => {
      const input = el('tokenInput');
      input.type = input.type === 'password' ? 'text' : 'password';
    });

    // QR modal
    const modal = el('qrModal');
    el('btnQr')?.addEventListener('click', async () => {
      modal.classList.remove('hidden');
      el('qrContainer').innerHTML = `
        <div class="text-center text-slate-400">
          <svg class="mx-auto mb-2 w-8 h-8 loading-refresh-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
          </svg>
          Gerando QR Code
        </div>
      `;
      
      try {
        const response = await fetch(baseUrl + instanceName + '/qr_code');
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.qr) {
          el('qrContainer').innerHTML = `
            <div class="text-center">
              <img src="${result.qr}" class="max-w-full rounded-lg mx-auto mb-3" alt="QR Code" />
              <p class="text-sm text-slate-400">Escaneie este código com seu WhatsApp</p>
              <p class="text-xs text-slate-500 mt-1">WhatsApp > Menu (⋮) > Dispositivos conectados > Conectar dispositivo</p>
            </div>
          `;
          toast('QR Code gerado com sucesso!', 'ok');
        } else {
          el('qrContainer').innerHTML = `
            <div class="text-center text-red-400">
              <svg class="mx-auto mb-2 w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10"/>
                <path d="m15 9-6 6"/>
                <path d="m9 9 6 6"/>
              </svg>
              ${result.error || 'Erro ao carregar QR Code'}
            </div>
          `;
          toast(result.error || 'Erro ao gerar QR Code', 'error');
        }
      } catch (error) {
        el('qrContainer').innerHTML = `
          <div class="text-center text-red-400">
            <svg class="mx-auto mb-2 w-12 h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10"/>
              <path d="m15 9-6 6"/>
              <path d="m9 9 6 6"/>
            </svg>
            Erro de conexão
          </div>
        `;
        toast('Erro de conexão ao gerar QR Code', 'error');
      }
    });

    el('closeQr').addEventListener('click', () => modal.classList.add('hidden'));
    el('closeQr2').addEventListener('click', () => modal.classList.add('hidden'));
    el('qrModalBg').addEventListener('click', () => modal.classList.add('hidden'));

    // Actions
    el('btnRestart').addEventListener('click', async () => {
      if (!confirm('Deseja realmente reiniciar esta instância?')) return;
      
      try {
        el('btnRestart').disabled = true;
        el('btnRestart').textContent = 'REINICIANDO...';
        
        const response = await fetch(baseUrl + instanceName + '/restart', {method: 'POST'});
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          toast(result.message || 'Instância reiniciada com sucesso', 'ok');
          setTimeout(() => location.reload(), 2500);
        } else {
          toast(result.error || 'Erro ao reiniciar', 'error');
        }
      } catch (error) {
        toast('Erro de conexão', 'error');
      } finally {
        el('btnRestart').disabled = false;
        el('btnRestart').textContent = 'REINICIAR';
      }
    });

    el('btnRefreshState').addEventListener('click', refreshStats);

    el('btnDisconnect').addEventListener('click', async () => {
      if (!confirm('⚠️ ATENÇÃO: Deseja realmente desconectar esta instância?\n\nIsso irá deslogar o WhatsApp e você precisará escanear o QR Code novamente.')) return;
      
      try {
        el('btnDisconnect').disabled = true;
        el('btnDisconnect').textContent = 'DESCONECTANDO...';
        
        const response = await fetch(baseUrl + instanceName + '/disconnect', {method: 'POST'});
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          toast(result.message || 'Instância desconectada com sucesso', 'warn');
          setTimeout(() => location.reload(), 2000);
        } else {
          toast(result.error || 'Erro ao desconectar', 'error');
        }
      } catch (error) {
        toast('Erro de conexão', 'error');
      } finally {
        el('btnDisconnect').disabled = false;
        el('btnDisconnect').textContent = 'DESCONECTAR';
      }
    });

    // Refresh stats com loading profissional
    async function refreshStats(showToast = true) {
      const refreshBtn = el('btnRefresh');
      
      // Verificar se o botão existe
      if (!refreshBtn) {
        console.error('Botão btnRefresh não encontrado!');
        if (showToast) {
          toast('Erro: Botão de atualizar não encontrado', 'error');
        }
        return;
      }
      
      console.log('Botão encontrado:', refreshBtn);
      
      // Usar loading system do admin-common.js se disponível
      let removeLoading = () => {};
      
      // Sistema de loading simplificado - sem AdminCommon.js
      const originalHtml = refreshBtn.innerHTML;
      const originalDisabled = refreshBtn.disabled;
      
      // Aplicar loading state
      refreshBtn.innerHTML = `
        <svg class="h-4 w-4 loading-refresh-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"></path>
          <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"></path>
        </svg>
        Atualizando
      `;
      refreshBtn.disabled = true;
      refreshBtn.classList.add('opacity-75');
      
      // Função para remover loading
      removeLoading = () => {
        try {
          if (refreshBtn && originalHtml) {
            refreshBtn.innerHTML = originalHtml;
            refreshBtn.disabled = originalDisabled;
            refreshBtn.classList.remove('opacity-75');
            console.log('Loading state removido com sucesso');
          }
        } catch (error) {
          console.error('Erro ao remover loading state:', error);
        }
      };
      
      // Timeout de segurança para garantir que o loading seja removido
      const safetyTimeout = setTimeout(() => {
        console.warn('Timeout de segurança ativado - removendo loading state');
        removeLoading();
      }, 10000); // 10 segundos
      
      try {
        console.log('Iniciando refresh stats para:', baseUrl + instanceName + '/stats');
        
        const response = await fetch(baseUrl + instanceName + '/stats', {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
          console.error('Response not ok:', response.status, response.statusText);
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('Result:', result);
        
        if (result.success) {
          const stats = result.data;
          console.log('Stats recebidas com sucesso:', stats);
          
          // Atualizar apenas os contadores - sem toasts
          try {
            document.querySelectorAll('[data-stat]').forEach(el => {
              const stat = el.dataset.stat;
              if (stats[stat] !== undefined) {
                el.textContent = new Intl.NumberFormat('pt-BR').format(stats[stat]);
              }
            });
            
            console.log('Contadores atualizados com sucesso');
          } catch (error) {
            console.error('Erro ao atualizar contadores:', error);
          }
          
          // Sem toast de sucesso
        } else {
          console.error('API retornou erro:', result.error);
          // Sem toast de erro
        }
      } catch (error) {
        console.error('Erro ao atualizar estatísticas:', error);
        // Sem toast de erro
      } finally {
        // Limpar timeout de segurança
        clearTimeout(safetyTimeout);
        
        // Remover loading state
        console.log('Executando removeLoading no finally...');
        removeLoading();
        console.log('removeLoading executado com sucesso');
      }
    }

    // Toggle switches functionality
    function setupToggleSwitch(elementId, settingKey) {
      const toggle = el(elementId);
      if (!toggle) return;

      const statusElementId = elementId.replace('toggle', 'status');
      const statusEl = el(statusElementId);

      toggle.addEventListener('click', async () => {
        // Evitar cliques múltiplos durante operação
        if (toggle.dataset.loading === 'true') {
          return;
        }

        const currentState = toggle.dataset.enabled === 'true';
        const newState = !currentState;
        
        // Mostrar loading
        toggle.dataset.loading = 'true';
        toggle.style.opacity = '0.6';
        if (statusEl) {
          statusEl.classList.remove('hidden');
          statusEl.textContent = 'Salvando...';
          statusEl.className = 'text-xs text-blue-500';
        }
        
        try {
          // Usar Evolution API diretamente
          const evolutionApiUrl = '<?= e($company['evolution_server_url'] ?? '') ?>';
          const apiKey = '<?= e($company['evolution_api_key'] ?? '') ?>';
          
          if (!evolutionApiUrl || !apiKey) {
            throw new Error('Configuração da Evolution API não encontrada');
          }
          
          // Buscar configurações atuais
          const currentResponse = await fetch(`${evolutionApiUrl}/settings/find/<?= e($instanceName) ?>`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'apikey': apiKey
            }
          });
          
          if (!currentResponse.ok) {
            throw new Error('Erro ao buscar configurações atuais');
          }
          
          const currentSettings = await currentResponse.json();
          
          // Configurações padrão
          const defaultSettings = {
            rejectCall: false,
            msgCall: '',
            groupsIgnore: false,
            alwaysOnline: false,
            readMessages: false,
            readStatus: false,
            syncFullHistory: false
          };
          
          // Mesclar: padrão + atual + nova alteração
          const finalSettings = {
            ...defaultSettings,
            ...currentSettings,
            [settingKey]: newState
          };
          
          // Salvar configurações completas
          const saveResponse = await fetch(`${evolutionApiUrl}/settings/set/<?= e($instanceName) ?>`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'apikey': apiKey
            },
            body: JSON.stringify(finalSettings)
          });
          
          if (saveResponse.ok) {
            // Atualizar estado visual
            toggle.dataset.enabled = newState.toString();
            updateToggleState(toggle, newState);
            
            const settingNames = {
              'rejectCall': 'Rejeitar chamadas',
              'readMessages': 'Ler mensagens',
              'alwaysOnline': 'Sempre online',
              'groupsIgnore': 'Ignorar grupos',
              'readStatus': 'Visualizar status',
              'syncFullHistory': 'Sincronizar histórico'
            };
            
            // Feedback visual de sucesso
            if (statusEl) {
              statusEl.classList.add('hidden');
            }
          } else {
            throw new Error(data.error || 'Erro desconhecido');
          }
        } catch (error) {
          console.error('Erro ao salvar configuração:', error);
          
          // Feedback visual de erro
          if (statusEl) {
            statusEl.textContent = 'Erro';
            statusEl.className = 'text-xs text-red-500';
            setTimeout(() => {
              statusEl.classList.add('hidden');
            }, 3000);
          }
          
          toast('Erro ao salvar configuração: ' + error.message, 'error');
        } finally {
          // Remover loading
          toggle.dataset.loading = 'false';
          toggle.style.opacity = '1';
        }
      });
    }

    function updateToggleState(toggle, enabled) {
      const thumb = toggle.querySelector('span');
      if (enabled) {
        toggle.classList.remove('bg-slate-200');
        toggle.classList.add('admin-gradient-bg');
        thumb.classList.remove('translate-x-0');
        thumb.classList.add('translate-x-5');
      } else {
        toggle.classList.add('bg-slate-200');
        toggle.classList.remove('admin-gradient-bg');
        thumb.classList.add('translate-x-0');
        thumb.classList.remove('translate-x-5');
      }
      
      // Lógica específica para o toggle "Rejeitar chamadas"
      if (toggle.id === 'toggleRejectCalls') {
        const messageContainer = el('rejectCallMessageContainer');
        if (messageContainer) {
          if (enabled) {
            messageContainer.classList.remove('hidden');
            // Pequeno delay para permitir a animação CSS
            setTimeout(() => {
              messageContainer.style.maxHeight = '200px';
              messageContainer.style.opacity = '1';
              messageContainer.style.transform = 'translateY(0)';
            }, 10);
          } else {
            messageContainer.style.maxHeight = '0';
            messageContainer.style.opacity = '0';
            messageContainer.style.transform = 'translateY(-10px)';
            // Esconder completamente após a animação
            setTimeout(() => {
              messageContainer.classList.add('hidden');
            }, 300);
          }
        }
      }
    }

    // Função para salvar mensagem de rejeição
    async function saveRejectCallMessage() {
      const messageInput = el('rejectCallMessage');
      const saveButton = el('saveRejectMessage');
      
      if (!messageInput || !saveButton) return;
      
      const message = messageInput.value.trim();
      
      // Mostrar loading no botão
      const originalText = saveButton.textContent;
      saveButton.textContent = 'Salvando...';
      saveButton.disabled = true;
      
      try {
        // Primeiro buscar configurações atuais
        const evolutionApiUrl = '<?= e($company['evolution_server_url'] ?? '') ?>';
        const apiKey = '<?= e($company['evolution_api_key'] ?? '') ?>';
        
        if (!evolutionApiUrl || !apiKey) {
          throw new Error('Configuração da Evolution API não encontrada');
        }
        
        // Buscar configurações atuais
        const currentResponse = await fetch(`${evolutionApiUrl}/settings/find/<?= e($instanceName) ?>`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'apikey': apiKey
          }
        });
        
        if (!currentResponse.ok) {
          throw new Error('Erro ao buscar configurações atuais');
        }
        
        const currentSettings = await currentResponse.json();
        
        // Mesclar com nova mensagem
        const updatedSettings = {
          ...currentSettings,
          msgCall: message
        };
        
        // Salvar configurações completas
        const saveResponse = await fetch(`${evolutionApiUrl}/settings/set/<?= e($instanceName) ?>`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'apikey': apiKey
          },
          body: JSON.stringify(updatedSettings)
        });
        
        if (saveResponse.ok) {
          toast('Mensagem de rejeição salva com sucesso!', 'success');
        } else {
          throw new Error(data.error || 'Erro desconhecido');
        }
        
      } catch (error) {
        console.error('Erro ao salvar mensagem:', error);
        toast('Erro ao salvar mensagem: ' + error.message, 'error');
      } finally {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
      }
    }

    // === FUNÇÕES PARA NOTIFICAÇÃO DE PEDIDO ===
    
    // Carregar grupos da instância
    async function loadInstanceGroups() {
      try {
        console.log('Iniciando busca de grupos para instância:', instanceName);
        
        const url = baseUrl + instanceName + '/groups';
        console.log('URL da requisição:', url);
        
        const response = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
          // Se a resposta não for ok, vamos ver se é um problema de roteamento
          const text = await response.text();
          console.log('Response text:', text.substring(0, 200));
          throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text.substring(0, 100)}`);
        }
        
        const result = await response.json();
        console.log('Resultado da API de grupos:', result);
        
        if (result.success) {
          const groups = result.data || [];
          console.log(`${groups.length} grupos encontrados`);
          return groups;
        } else {
          throw new Error(result.error || 'Erro desconhecido da API');
        }
      } catch (error) {
        console.error('Erro ao carregar grupos:', error);
        // Não retornar dados fictícios - deixar que o erro seja tratado
        throw error;
      }
    }

    // Configurar seletor de grupos
    async function setupGroupSelector() {
      const groupSelect = el('orderNotificationGroup');
      if (!groupSelect) {
        console.error('Elemento orderNotificationGroup não encontrado');
        return;
      }
      
      // Mostrar loading
      groupSelect.innerHTML = '<option value="">🔄 Carregando grupos...</option>';
      groupSelect.disabled = true;
      
      try {
        const groups = await loadInstanceGroups();
        
        // Limpar e adicionar opções
        groupSelect.innerHTML = '<option value="">Selecione um grupo</option>';
        
        if (groups.length > 0) {
          groups.forEach((group, index) => {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = `${group.subject} (${group.participants} participantes)`;
            groupSelect.appendChild(option);
            
            console.log(`Grupo ${index + 1}:`, {
              id: group.id,
              subject: group.subject,
              participants: group.participants
            });
          });
          
          toast(`${groups.length} grupos encontrados`, 'success');
        } else {
          groupSelect.innerHTML = '<option value="">❌ Nenhum grupo encontrado</option>';
          toast('Nenhum grupo encontrado nesta instância', 'warn');
        }
        
      } catch (error) {
        console.error('Erro ao configurar seletor de grupos:', error);
        groupSelect.innerHTML = '<option value="">❌ Erro ao carregar grupos</option>';
        toast('Erro ao carregar grupos: ' + error.message, 'error');
      } finally {
        groupSelect.disabled = false;
      }
    }

    // Configurar toggle de notificação de pedido
    function setupOrderNotificationToggle() {
      const toggle = el('toggleOrderNotification');
      const container = el('orderNotificationGroupContainer');
      
      if (!toggle || !container) return;
      
      toggle.addEventListener('click', async () => {
        const currentState = toggle.dataset.enabled === 'true';
        const newState = !currentState;
        
        // Atualizar estado visual do toggle
        updateToggleState(toggle, newState);
        toggle.dataset.enabled = newState.toString();
        
        // Mostrar/ocultar container de configuração
        if (newState) {
          container.classList.remove('hidden');
          
          // Buscar grupos imediatamente quando ativado
          toast('Carregando grupos da instância...', 'info');
          await setupGroupSelector();
          
          // Animação suave
          setTimeout(() => {
            container.style.maxHeight = container.scrollHeight + 'px';
            container.style.opacity = '1';
          }, 10);
        } else {
          container.style.maxHeight = '0';
          container.style.opacity = '0';
          setTimeout(() => {
            container.classList.add('hidden');
          }, 300);
        }
      });
    }

    // Salvar configuração de notificação de pedido
    async function saveOrderNotificationConfig() {
      const toggle = el('toggleOrderNotification');
      const number1Input = el('orderNotificationNumber1');
      const number2Input = el('orderNotificationNumber2');
      const saveButton = el('saveOrderNotification');
      
      if (!toggle || !number1Input || !number2Input || !saveButton) return;
      
      const isEnabled = toggle.dataset.enabled === 'true';
      const number1 = number1Input.value.trim();
      const number2 = number2Input.value.trim();
      
      if (isEnabled && !number1) {
        toast('Por favor, digite pelo menos o número principal para receber as notificações', 'error');
        number1Input.focus();
        return;
      }
      
      // Validar formato dos números (básico)
      const phoneRegex = /^[0-9]{10,15}$/;
      if (isEnabled && number1 && !phoneRegex.test(number1)) {
        toast('Formato do número principal inválido. Use apenas números (10-15 dígitos)', 'error');
        number1Input.focus();
        return;
      }
      
      if (isEnabled && number2 && !phoneRegex.test(number2)) {
        toast('Formato do número secundário inválido. Use apenas números (10-15 dígitos)', 'error');
        number2Input.focus();
        return;
      }
      
      // Mostrar loading no botão
      const originalText = saveButton.textContent;
      saveButton.textContent = 'Salvando...';
      saveButton.disabled = true;
      
      try {
        const response = await fetch(baseUrl + instanceName + '/order-notification', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            enabled: isEnabled,
            primary_number: number1,
            secondary_number: number2
          })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          toast('Configuração de notificação salva com sucesso!', 'success');
          
          // Mostrar resumo dos números configurados
          const numbers = [];
          if (number1) numbers.push(number1);
          if (number2) numbers.push(number2);
          
          if (isEnabled && numbers.length > 0) {
            setTimeout(() => {
              toast(`Notificações ativas para: ${numbers.join(', ')}`, 'info');
            }, 1000);
          }
        } else {
          throw new Error(result.error || 'Erro desconhecido');
        }
        
      } catch (error) {
        console.error('Erro ao salvar configuração:', error);
        toast('Erro ao salvar configuração: ' + error.message, 'error');
      } finally {
        saveButton.textContent = originalText;
        saveButton.disabled = false;
      }
    }

    // Carregar configuração de notificação de pedido
    async function loadOrderNotificationConfig() {
      try {
        const response = await fetch(baseUrl + instanceName + '/order-notification', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
          const config = result.data;
          const toggle = el('toggleOrderNotification');
          const number1Input = el('orderNotificationNumber1');
          const number2Input = el('orderNotificationNumber2');
          const container = el('orderNotificationGroupContainer');
          
          if (toggle) {
            toggle.dataset.enabled = config.enabled.toString();
            updateToggleState(toggle, config.enabled);
            
            if (config.enabled && container) {
              container.classList.remove('hidden');
              setTimeout(() => {
                container.style.maxHeight = container.scrollHeight + 'px';
                container.style.opacity = '1';
              }, 10);
            }
          }
          
          // Preencher os números salvos
          if (number1Input && config.primary_number) {
            number1Input.value = config.primary_number;
          }
          
          if (number2Input && config.secondary_number) {
            number2Input.value = config.secondary_number;
          }
          
          // Log para debug
          console.log('Configuração de notificação carregada:', config);
        }
        
      } catch (error) {
        console.error('Erro ao carregar configuração de notificação:', error);
      }
    }

    // === FIM DAS FUNÇÕES DE NOTIFICAÇÃO DE PEDIDO ===

    // Inicializar toggles (usando nomes corretos da Evolution API v2)
    // POST usa camelCase, GET retorna underscore
    setupToggleSwitch('toggleRejectCalls', 'rejectCall');
    setupToggleSwitch('toggleReadMessages', 'readMessages');
    setupToggleSwitch('toggleAlwaysOnline', 'alwaysOnline');
    setupToggleSwitch('toggleGroupsIgnore', 'groupsIgnore');
    setupToggleSwitch('toggleReadStatus', 'readStatus');
    setupToggleSwitch('toggleSyncFullHistory', 'syncFullHistory');
    
    // Configurar botão de salvar mensagem
    const saveMessageBtn = el('saveRejectMessage');
    if (saveMessageBtn) {
      saveMessageBtn.addEventListener('click', saveRejectCallMessage);
    }

    // Configurar bloco de notificação de pedido
    setupOrderNotificationToggle();
    
    const saveOrderNotificationBtn = el('saveOrderNotification');
    if (saveOrderNotificationBtn) {
      saveOrderNotificationBtn.addEventListener('click', saveOrderNotificationConfig);
    }

    // Carregar configurações atuais dos toggles
    async function loadInstanceSettings() {
      // Mostrar indicadores de carregamento
      ['statusRejectCalls', 'statusReadMessages', 'statusAlwaysOnline', 'statusGroupsIgnore', 'statusReadStatus', 'statusSyncFullHistory'].forEach(id => {
        const statusEl = el(id);
        if (statusEl) {
          statusEl.classList.remove('hidden');
          statusEl.textContent = 'Carregando...';
          statusEl.className = 'text-xs text-slate-400';
        }
      });

      try {
        // Tentar endpoint local primeiro, fallback para API direta
        let result;
        
        try {
          const response = await fetch(`<?= base_url('admin/' . $slug . '/evolution/instance/' . $instanceName . '/settings') ?>`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json'
            }
          });
          
          if (response.ok) {
            result = await response.json();
          } else {
            throw new Error('Endpoint local não disponível');
          }
        } catch (localError) {
          console.log('Endpoint local falhou, usando API direta:', localError.message);
          
          // Fallback: chamar Evolution API diretamente
          const evolutionApiUrl = '<?= e($company['evolution_server_url'] ?? '') ?>';
          const apiKey = '<?= e($company['evolution_api_key'] ?? '') ?>';
          
          if (!evolutionApiUrl || !apiKey) {
            throw new Error('Configuração da Evolution API não encontrada');
          }
          
          const directResponse = await fetch(`${evolutionApiUrl}/settings/find/<?= e($instanceName) ?>`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'apikey': apiKey
            }
          });
          
          if (directResponse.ok) {
            const directData = await directResponse.json();
            result = { success: true, data: directData };
          } else {
            throw new Error(`Evolution API error: ${directResponse.status}`);
          }
        }
        
        if (result.success) {
          // A resposta pode ter diferentes estruturas dependendo da versão da API
          const settings = result.data || {};
          
          console.log('Configurações carregadas:', settings);
          
          // Mapear configurações para toggles (API usa camelCase tanto no GET quanto no POST)
          const toggleMappings = [
            { settingKey: 'rejectCall', toggleId: 'toggleRejectCalls', statusId: 'statusRejectCalls' },
            { settingKey: 'readMessages', toggleId: 'toggleReadMessages', statusId: 'statusReadMessages' },
            { settingKey: 'alwaysOnline', toggleId: 'toggleAlwaysOnline', statusId: 'statusAlwaysOnline' },
            { settingKey: 'groupsIgnore', toggleId: 'toggleGroupsIgnore', statusId: 'statusGroupsIgnore' },
            { settingKey: 'readStatus', toggleId: 'toggleReadStatus', statusId: 'statusReadStatus' },
            { settingKey: 'syncFullHistory', toggleId: 'toggleSyncFullHistory', statusId: 'statusSyncFullHistory' }
          ];
          
          toggleMappings.forEach(mapping => {
            const toggle = el(mapping.toggleId);
            const statusEl = el(mapping.statusId);
            
            console.log(`Debug ${mapping.settingKey}:`, {
              elementFound: !!toggle,
              statusElementFound: !!statusEl,
              settingValue: settings[mapping.settingKey],
              isEnabled: settings[mapping.settingKey] === true
            });
            
            if (toggle && statusEl) {
              const isEnabled = settings[mapping.settingKey] === true;
              
              toggle.dataset.enabled = isEnabled.toString();
              updateToggleState(toggle, isEnabled);
              
              // Esconder status
              statusEl.classList.add('hidden');
            }
          });
          
          // Carregar mensagem de rejeição de chamada
          const rejectCallMessage = el('rejectCallMessage');
          if (rejectCallMessage && settings.msgCall !== undefined) {
            rejectCallMessage.value = settings.msgCall || '';
          }
        } else {
          console.log('Erro ao buscar configurações:', result.error);
          hideLoadingIndicators();
          toast('Erro ao carregar configurações da instância', 'warn');
        }
      } catch (error) {
        console.log('Erro ao carregar configurações:', error);
        hideLoadingIndicators();
        // Não mostrar toast para erro de configurações opcionais
      }
    }

    function hideLoadingIndicators() {
      ['statusRejectCalls', 'statusReadMessages', 'statusAlwaysOnline', 'statusGroupsIgnore', 'statusReadStatus', 'statusSyncFullHistory'].forEach(id => {
        const statusEl = el(id);
        if (statusEl) {
          statusEl.classList.add('hidden');
        }
      });
    }

    // Sistema de skeleton loading usando SkeletonSystem centralizado
    const SkeletonLoader = window.SkeletonSystem ? window.SkeletonSystem.createSkeletonLoader({
      header: { skeleton: 'headerSkeleton', content: 'headerContent' },
      stats: { skeleton: 'statsSkeleton', content: 'statsContent' },
      info: { skeleton: 'infoSkeleton', content: 'infoContent' },
      settings: { skeleton: 'settingsSkeletonLoader', content: 'settingsContent' }
    }) : {
      // Fallback básico caso SkeletonSystem não esteja carregado
      elements: {
        header: { skeleton: 'headerSkeleton', content: 'headerContent' },
        stats: { skeleton: 'statsSkeleton', content: 'statsContent' },
        info: { skeleton: 'infoSkeleton', content: 'infoContent' },
        settings: { skeleton: 'settingsSkeletonLoader', content: 'settingsContent' }
      },
      
      show() {
        // Fallback: apenas esconder conteúdo real
        Object.values(this.elements).forEach(({ content }) => {
          const contentEl = el(content);
          if (contentEl) contentEl.classList.add('hidden');
        });
      },
      
      // Esconder skeleton com smooth reveal
      hide() {
        const staggerDelay = 150; // Delay entre animações
        let currentDelay = 0;
        
        // Header reveal
        setTimeout(() => {
          this.revealElement(this.elements.header.skeleton, this.elements.header.content);
        }, currentDelay);
        currentDelay += staggerDelay;
        
        // Stats reveal com animação especial para cards
        setTimeout(() => {
          this.revealStatsCards();
        }, currentDelay);
        currentDelay += staggerDelay;
        
        // Info reveal
        setTimeout(() => {
          this.revealElement(this.elements.info.skeleton, this.elements.info.content, 'grid');
        }, currentDelay);
        currentDelay += staggerDelay;
        
        // Settings reveal
        setTimeout(() => {
          this.revealElement(this.elements.settings.skeleton, this.elements.settings.content);
        }, currentDelay);
      },
      
      // Revelar elemento com animações suaves
      revealElement(skeletonId, contentId, displayType = 'block') {
        const skeleton = el(skeletonId);
        const content = el(contentId);
        
        if (!skeleton || !content) return;
        
        // Esconder skeleton
        if (displayType === 'contents') {
          skeleton.style.display = 'none';
        } else {
          skeleton.classList.add('hidden');
        }
        
        // Mostrar conteúdo real
        if (displayType === 'contents') {
          content.classList.remove('hidden');
          content.style.display = displayType;
        } else {
          content.style.display = displayType;
        }
        
        this.smoothRevealElement(content);
      },
      
      // Revelar cards de estatísticas com animação especial
      revealStatsCards() {
        const skeleton = el(this.elements.stats.skeleton);
        const content = el(this.elements.stats.content);
        
        if (!skeleton || !content) return;
        
        // Esconder skeleton
        skeleton.style.display = 'none';
        
        // Mostrar conteúdo
        content.classList.remove('hidden');
        content.style.display = 'contents';
        
        // Animar cada card individualmente
        const cards = content.querySelectorAll('.stat-card');
        cards.forEach((card, index) => {
          setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
            
            // Micro-bounce no final
            setTimeout(() => {
              card.style.transform = 'translateY(-2px)';
              setTimeout(() => {
                card.style.transform = 'translateY(0)';
              }, 150);
            }, 400);
          }, index * 150); // 150ms delay entre cada card
        });
      },
      
      // Animação suave de revelação com efeitos profissionais
      smoothRevealElement(element, delay = 0) {
        setTimeout(() => {
          // Usar sistema de animações profissional
          VisualStates.revealWithAnimation(element, 'slideInFromBottom');
          
          // Fallback para browsers que não suportam animation
          element.style.opacity = '0';
          element.style.transform = 'translateY(20px)';
          element.style.transition = 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
          
          // Trigger reflow
          element.offsetHeight;
          
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }, delay);
      }
    };
    
    // Funções de compatibilidade (reutilizando interface existente)
    function showSkeletonLoading() {
      // Skeleton já visível por padrão, apenas garantir conteúdo oculto
      SkeletonLoader.show();
    }
    
    function hideSkeletonLoading() {
      SkeletonLoader.hide();
    }

    // Sistema de inicialização profissional com indicadores de progresso
    const PageLoader = {
      minLoadingTime: 1200, // 1.2 segundos mínimo para melhor percepção
      maxLoadingTime: 6000,  // 6 segundos máximo antes de forçar reveal
      
      // Indicador de progresso
      showProgress(text = 'Carregando...') {
        const progressEl = el('loadingProgress');
        const textEl = el('loadingText');
        if (progressEl && textEl) {
          textEl.textContent = text;
          progressEl.classList.remove('hidden');
          progressEl.classList.add('flex');
        }
      },
      
      hideProgress() {
        const progressEl = el('loadingProgress');
        if (progressEl) {
          progressEl.classList.add('hidden');
          progressEl.classList.remove('flex');
        }
      },
      
      async initialize() {
        const startTime = Date.now();
        
        // Mostrar progress indicator (skeleton já visível)
        this.showProgress('Iniciando...');
        
        try {
          // Fase 1: Carregamento de dados principais
          this.showProgress('Carregando dados da instância...');
          
          const loadingPromises = [
            this.loadStatsWithProgress(),
            this.loadSettingsWithProgress(),
            this.ensureMinimumLoadingTime(startTime)
          ];
          
          // Aguardar todos os carregamentos
          await Promise.race([
            Promise.all(loadingPromises),
            this.createTimeoutPromise()
          ]);
          
          // Fase 2: Finalizando
          this.showProgress('Finalizando...');
          await new Promise(resolve => setTimeout(resolve, 200));
          
          // Smooth reveal
          this.showProgress('Pronto!');
          setTimeout(() => {
            SkeletonLoader.hide();
            this.hideProgress();
          }, 300);
          
        } catch (error) {
          console.error('Erro durante inicialização:', error);
          this.showProgress('Erro - tentando continuar...');
          
          setTimeout(() => {
            SkeletonLoader.hide();
            this.hideProgress();
          }, 500);
        }
      },
      
      async loadStatsWithProgress() {
        return new Promise((resolve) => {
          try {
            // Pequeno delay para mostrar progresso
            setTimeout(() => {
              refreshStats(false);
              resolve();
            }, 300);
          } catch (error) {
            console.warn('Erro ao carregar estatísticas:', error);
            resolve(); // Não bloquear por erro de stats
          }
        });
      },
      
      async loadSettingsWithProgress() {
        return new Promise((resolve) => {
          try {
            // Delay escalonado para visual melhor
            setTimeout(() => {
              loadInstanceSettings();
              
              // Carregar também o bloco de notificação de pedido
              setTimeout(() => {
                const orderNotificationSkeleton = el('orderNotificationSkeleton');
                const orderNotificationContent = el('orderNotificationContent');
                
                console.log('Elementos de notificação encontrados:', {
                  skeleton: !!orderNotificationSkeleton,
                  content: !!orderNotificationContent
                });
                
                if (orderNotificationSkeleton && orderNotificationContent) {
                  orderNotificationSkeleton.classList.add('hidden');
                  orderNotificationContent.classList.remove('hidden');
                  
                  // Carregar configurações salvas
                  console.log('Carregando configurações de notificação...');
                  loadOrderNotificationConfig();
                }
              }, 300);
              
              resolve();
            }, 500);
          } catch (error) {
            console.warn('Erro ao carregar configurações:', error);
            resolve(); // Não bloquear por erro de settings
          }
        });
      },
      
      async ensureMinimumLoadingTime(startTime) {
        const elapsed = Date.now() - startTime;
        const remainingTime = Math.max(0, this.minLoadingTime - elapsed);
        
        if (remainingTime > 0) {
          await new Promise(resolve => setTimeout(resolve, remainingTime));
        }
      },
      
      createTimeoutPromise() {
        return new Promise((resolve) => {
          setTimeout(resolve, this.maxLoadingTime);
        });
      }
    };

    // Inicialização completa da página com UX profissional
    function initializeProfessionalUX() {
      // 1. Aplicar melhorias visuais
      VisualStates.enhanceButtons();
      
      // 2. Inicializar carregamento
      PageLoader.initialize().then(() => {
        // 3. Aplicar animações finais após carregamento
        setTimeout(() => {
          // Reveal final com stagger para todos os elementos principais
          const mainElements = document.querySelectorAll('section, .rounded-2xl');
          mainElements.forEach((el, index) => {
            if (el.offsetParent !== null) { // Apenas elementos visíveis
              VisualStates.revealWithAnimation(el, 'fadeInScale');
            }
          });
        }, 500);
      });
    }
    
    // Inicialização com múltiplos pontos de entrada
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(initializeProfessionalUX, 50);
    });
    
    // Fallback para DOM já carregado
    if (document.readyState === 'loading') {
      // DOMContentLoaded irá disparar
    } else {
      // DOM já pronto - inicializar imediatamente
      setTimeout(initializeProfessionalUX, 50);
    }
    
    // Refresh button - apenas um event listener
    el('btnRefresh')?.addEventListener('click', () => refreshStats(true));
    
    // Auto refresh removido temporariamente para evitar conflitos
  </script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>