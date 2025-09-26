SHELL := /bin/bash
SO := $( shell uname)
DOCKER_COMPOSE_SCRIPT := $(CURDIR) /scripts/docker_compose.sh
EXECUTAR_TAREFA_APP := $(CURDIR) /scripts/run_app_task.sh
COMPOSER_BIN := $( comando shell -v composer 2>/dev/null)
NPM_BIN := $( comando shell -v npm 2>/dev/null)
PHP_BIN := $( comando shell -v php 2>/dev/null)

.PHONY : configuração brew docker-env env composer-install npm-install docker-up migrate seed hooks down logs xampp

configuração: brew docker-env env composer-install npm-install docker-up migrate seed hooks

infusão:
	@if [ " $(OS) " = "Darwin" ]; então \
		bash $(CURDIR) /scripts/brew_setup.sh; \
	outro \
		echo 'Homebrew não é necessário neste sistema. Pulando etapa.'; \
	ser

ambiente docker:
	@if ! comando -v docker >/dev/null 2>&1; então \
		echo 'Docker CLI não encontrado. Instale Docker Desktop ou use brew install docker.'; \
		saída 1; \
	ser; \
	se ! docker info >/dev/null 2>&1; então \
		se comando -v colima >/dev/null 2>&1; então \
			echo 'Inicializando Colima...'; \
			colima start --cpu 4 --memória 4 --disco 40; \
		outro \
			echo 'Docker não está em execução. Inicie o Docker Desktop e rode make setup novamente.'; \
			saída 1; \
		ser; \
	outro \
		echo 'Docker engine disponível.'; \
	ser; \
	$(DOCKER_COMPOSE_SCRIPT) versão >/dev/null 2>&1 && echo 'Docker Compose disponível.'

ambiente:
	@if [ ! -f .env ]; então \
		echo 'Copiando .env.example para .env'; \
		cp .env.exemplo .env; \
	ser; \
	se [ -n " ​​$(PHP_BIN) " ]; então \
		se [ -f bin/generate-key ]; então \
			php bin/gerar-chave; \
		elif [ -f artesão ]; então \
			chave php artisan:generate --force; \
		outro \
			echo 'Nenhuma rotina de geração de chave encontrada (bin/generate-key ou artisan).'; \
		ser; \
	elif $(DOCKER_COMPOSE_SCRIPT) versão >/dev/null 2>&1; então \
		echo 'Gerando APP_KEY dentro do container...'; \
		$(DOCKER_COMPOSE_SCRIPT) execute --rm --no-deps aplicativo sh -lc '\
			se [ -f bin/gerar-chave ]; então php bin/gerar-chave; \
			elif [ -f artisan ]; então chave php artisan:generate --force; \
			else echo "Nenhuma rotina de geração de chave encontrada (bin/generate-key ou artisan)."; exit 1; fi'; \
	outro \
		echo 'PHP não encontrado para gerar APP_KEY.'; \
		saída 1; \
	ser

compositor-instalação:
	@if [ -f composer.json ]; então \
		se [ -n " ​​$(COMPOSER_BIN) " ]; então \
			$(COMPOSER_BIN) instalar --no-interaction --prefer-dist; \
		elif $(DOCKER_COMPOSE_SCRIPT) versão >/dev/null 2>&1; então \
			echo 'Executando composer install via container...'; \
			$(DOCKER_COMPOSE_SCRIPT) execute --rm --no-deps app composer install --no-interaction --prefer-dist; \
		outro \
			echo 'Composer não encontrado.'; \
			saída 1; \
		ser; \
	ser

npm-instalação:
	@if [ -f pacote.json ]; então \
		se [ -n " ​​$(NPM_BIN) " ]; então \
			$(NPM_BIN) instalar; \
		elif $(DOCKER_COMPOSE_SCRIPT) versão >/dev/null 2>&1; então \
			echo 'Executando npm install via container Node...'; \
			$(DOCKER_COMPOSE_SCRIPT) execute --rm --no-deps node npm install; \
		outro \
			echo 'npm não encontrado. Instale Node.js ou use Docker.'; \
			saída 1; \
		ser; \
	outro \
		echo 'package.json não encontrado. Pulando npm install.'; \
	ser

docker-up:
	$(DOCKER_COMPOSE_SCRIPT) up -d --build

migrar:
	@ $(RUN_APP_TASK) migrar

semente:
	@ $(RUN_APP_TASK) semente

ganchos:
	@if [ -f fornecedor/bin/grumphp ]; então \
		fornecedor/bin/grumphp git:init; \
	outro \
		echo 'GrumPHP não encontrado. Certifique-se de que o composer install foi executado.'; \
		saída 1; \
	ser

abaixo:
	$(DOCKER_COMPOSE_SCRIPT) inativo

registros:
	$(DOCKER_COMPOSE_SCRIPT) registros -f

xampp: ambiente compositor-instalação
	@if [ -n " ​​$(PHP_BIN) " ]; então \
		php -S 127.0.0.1:8000 -t público; \
	outro \
		echo 'PHP CLI não encontrado. Instale PHP para executar make xampp.'; \
		saída 1; \
	ser