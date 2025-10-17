<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Sistema de Lazy Loading</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/lazy-loading.css">
    <style>
        .section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .test-item {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .test-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .test-item p {
            padding: 0.75rem;
            font-size: 0.875rem;
            text-align: center;
        }
        .spacer {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-4xl font-bold mb-2">Sistema de Lazy Loading - Testes</h1>
        <p class="text-gray-600 mb-8">Role a página para ver as imagens sendo carregadas progressivamente</p>

        <!-- Teste 1: Imagens Eager (Above the Fold) -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">1. Imagens Eager (Carregamento Imediato)</h2>
            <p class="text-gray-600 mb-4">Estas imagens usam <code class="bg-gray-200 px-2 py-1 rounded">eager: true</code> e carregam imediatamente.</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <img src="https://picsum.photos/400/300?random=1" 
                         alt="Imagem 1" 
                         class="lazy-load"
                         data-eager="true">
                    <p>Logo / Banner Principal</p>
                </div>
                <div class="test-item">
                    <img src="https://picsum.photos/400/300?random=2" 
                         alt="Imagem 2">
                    <p>Hero Image (sem lazy)</p>
                </div>
            </div>
        </div>

        <!-- Spacer para forçar scroll -->
        <div class="spacer">
            ⬇️ Role para baixo ⬇️
        </div>

        <!-- Teste 2: Lazy Loading Normal -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">2. Lazy Loading Normal</h2>
            <p class="text-gray-600 mb-4">Estas imagens carregam quando entram no viewport (com shimmer).</p>
            
            <div class="test-grid">
                <?php for($i = 3; $i <= 8; $i++): ?>
                <div class="test-item">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/400/300?random=<?= $i ?>"
                         alt="Produto <?= $i ?>"
                         class="lazy-load lazy-card">
                    <p>Produto <?= $i ?></p>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Spacer -->
        <div class="spacer">
            ⬇️ Continue rolando ⬇️
        </div>

        <!-- Teste 3: Lazy Loading com Fallback -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">3. Lazy Loading com Fallback</h2>
            <p class="text-gray-600 mb-4">URLs inválidas mostram a imagem de fallback.</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://invalid-url-that-will-fail.com/image.jpg"
                         data-fallback="https://via.placeholder.com/400x300/ff6b6b/ffffff?text=Fallback+1"
                         alt="Teste Fallback 1"
                         class="lazy-load lazy-card">
                    <p>URL Inválida → Fallback</p>
                </div>
                <div class="test-item">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/400/300?random=broken"
                         data-fallback="https://via.placeholder.com/400x300/4ecdc4/ffffff?text=Fallback+2"
                         alt="Teste Fallback 2"
                         class="lazy-load lazy-card">
                    <p>Com Fallback</p>
                </div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="spacer">
            ⬇️ Mais testes abaixo ⬇️
        </div>

        <!-- Teste 4: Diferentes Tamanhos -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">4. Classes de Tamanho</h2>
            <p class="text-gray-600 mb-4">Diferentes classes aplicam min-height apropriado.</p>
            
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <h3 class="font-semibold mb-2">lazy-thumb</h3>
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/100/100?random=10"
                         alt="Thumb"
                         class="lazy-load lazy-thumb w-12 h-12 rounded-lg">
                </div>
                <div>
                    <h3 class="font-semibold mb-2">lazy-card</h3>
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/200/200?random=11"
                         alt="Card"
                         class="lazy-load lazy-card w-24 h-24 rounded-xl">
                </div>
                <div>
                    <h3 class="font-semibold mb-2">lazy-banner</h3>
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/400/200?random=12"
                         alt="Banner"
                         class="lazy-load lazy-banner w-full rounded-lg">
                </div>
                <div>
                    <h3 class="font-semibold mb-2">lazy-hero</h3>
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/400/400?random=13"
                         alt="Hero"
                         class="lazy-load lazy-hero w-full rounded-2xl">
                </div>
            </div>
        </div>

        <!-- Spacer -->
        <div class="spacer">
            ⬇️ Teste de conteúdo dinâmico abaixo ⬇️
        </div>

        <!-- Teste 5: Conteúdo Dinâmico (AJAX) -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">5. Conteúdo Dinâmico (AJAX)</h2>
            <p class="text-gray-600 mb-4">Clique no botão para carregar mais imagens dinamicamente.</p>
            
            <button onclick="loadMoreImages()" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition mb-4">
                Carregar Mais Imagens
            </button>
            
            <div id="dynamic-content" class="test-grid"></div>
        </div>

        <!-- Teste 6: Eventos de Lazy Loading -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">6. Eventos de Carregamento</h2>
            <p class="text-gray-600 mb-4">Monitor de eventos disparados durante o lazy loading.</p>
            
            <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm h-64 overflow-auto" id="event-log">
                <div>📋 Log de eventos (role para ver)...</div>
            </div>
            
            <div class="test-grid mt-4">
                <?php for($i = 20; $i <= 23; $i++): ?>
                <div class="test-item">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                         data-src="https://picsum.photos/400/300?random=<?= $i ?>"
                         alt="Evento <?= $i ?>"
                         class="lazy-load lazy-card event-tracked"
                         data-id="<?= $i ?>">
                    <p>Imagem <?= $i ?></p>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Stats -->
        <div class="section">
            <h2 class="text-2xl font-bold mb-4">📊 Estatísticas</h2>
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600" id="stat-total">0</div>
                    <div class="text-sm text-gray-600">Total de Imagens</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-green-600" id="stat-loaded">0</div>
                    <div class="text-sm text-gray-600">Carregadas</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-yellow-600" id="stat-loading">0</div>
                    <div class="text-sm text-gray-600">Carregando</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-red-600" id="stat-error">0</div>
                    <div class="text-sm text-gray-600">Erros</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/lazy-loading.js"></script>
    <script>
        // Contador de imagens dinâmico
        let dynamicCounter = 100;

        // Função para carregar mais imagens
        function loadMoreImages() {
            const container = document.getElementById('dynamic-content');
            const html = Array.from({length: 6}, (_, i) => {
                const num = dynamicCounter++;
                return `
                    <div class="test-item">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                             data-src="https://picsum.photos/400/300?random=${num}"
                             alt="Dinâmico ${num}"
                             class="lazy-load lazy-card">
                        <p>Dinâmico ${num}</p>
                    </div>
                `;
            }).join('');
            
            container.innerHTML += html;
            
            // IMPORTANTE: Re-inicializar lazy loading
            window.reinitLazyLoading(container);
            
            logEvent('🔄 Carregadas 6 novas imagens via AJAX');
            updateStats();
        }

        // Logger de eventos
        function logEvent(message) {
            const log = document.getElementById('event-log');
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.textContent = `[${time}] ${message}`;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }

        // Monitorar eventos de lazy loading
        document.addEventListener('lazyloaded', function(e) {
            const id = e.target.dataset.id;
            if (id) {
                logEvent(`✅ Imagem ${id} carregada com sucesso`);
            }
            updateStats();
        });

        document.addEventListener('lazyerror', function(e) {
            const id = e.target.dataset.id;
            if (id) {
                logEvent(`❌ Erro ao carregar imagem ${id}`);
            }
            updateStats();
        });

        // Atualizar estatísticas
        function updateStats() {
            const total = document.querySelectorAll('img.lazy-load').length;
            const loaded = document.querySelectorAll('img.lazy-loaded').length;
            const loading = document.querySelectorAll('img.lazy-loading').length;
            const error = document.querySelectorAll('img.lazy-error').length;
            
            document.getElementById('stat-total').textContent = total;
            document.getElementById('stat-loaded').textContent = loaded;
            document.getElementById('stat-loading').textContent = loading;
            document.getElementById('stat-error').textContent = error;
        }

        // Atualizar stats a cada segundo
        setInterval(updateStats, 1000);
        
        // Log inicial
        logEvent('🚀 Sistema de lazy loading inicializado');
        
        // Observer para detectar quando imagens entram no viewport
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && entry.target.classList.contains('lazy-load')) {
                    const id = entry.target.dataset.id;
                    if (id && !entry.target.classList.contains('lazy-loaded') && !entry.target.dataset.logged) {
                        entry.target.dataset.logged = 'true';
                        logEvent(`👁️ Imagem ${id} entrou no viewport`);
                    }
                }
            });
        });

        // Observar todas as imagens com tracking
        document.querySelectorAll('.event-tracked').forEach(function(img) {
            observer.observe(img);
        });
    </script>
</body>
</html>
