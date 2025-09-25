SHELL := /bin/bash
OS := $(shell uname)
DOCKER_COMPOSE_SCRIPT := $(CURDIR)/scripts/docker_compose.sh
COMPOSER_BIN := $(shell command -v composer 2>/dev/null)
NPM_BIN := $(shell command -v npm 2>/dev/null)
PHP_BIN := $(shell command -v php 2>/dev/null)

.PHONY: setup brew docker-env env composer-install npm-install docker-up migrate seed hooks down logs xampp

setup: brew docker-env env composer-install npm-install docker-up migrate seed hooks

brew:
	@if [ "$(OS)" = "Darwin" ]; then \
		bash $(CURDIR)/scripts/brew_setup.sh; \
	else \
		echo 'Homebrew não é necessário neste sistema. Pulando etapa.'; \
	fi

docker-env:
	@if ! command -v docker >/dev/null 2>&1; then \
		echo 'Docker CLI não encontrado. Instale Docker Desktop ou use brew install docker.'; \
		exit 1; \
	fi; \
	if ! docker info >/dev/null 2>&1; then \
		if command -v colima >/dev/null 2>&1; then \
			echo 'Inicializando Colima...'; \
			colima start --cpu 4 --memory 4 --disk 40; \
		else \
			echo 'Docker não está em execução. Inicie o Docker Desktop e rode make setup novamente.'; \
			exit 1; \
		fi; \
	else \
		echo 'Docker engine disponível.'; \
	fi; \
	$(DOCKER_COMPOSE_SCRIPT) version >/dev/null 2>&1 && echo 'Docker Compose disponível.'

env:
	@if [ ! -f .env ]; then \
		echo 'Copiando .env.example para .env'; \
		cp .env.example .env; \
	fi; \
	if [ -n "$(PHP_BIN)" ]; then \
		php bin/generate-key; \
	elif $(DOCKER_COMPOSE_SCRIPT) version >/dev/null 2>&1; then \
		echo 'Gerando APP_KEY dentro do container...'; \
		$(DOCKER_COMPOSE_SCRIPT) run --rm --no-deps app php /var/www/html/bin/generate-key; \
	else \
		echo 'PHP não encontrado para gerar APP_KEY.'; \
		exit 1; \
	fi

composer-install:
	@if [ -f composer.json ]; then \
		if [ -n "$(COMPOSER_BIN)" ]; then \
			$(COMPOSER_BIN) install --no-interaction --prefer-dist; \
		elif $(DOCKER_COMPOSE_SCRIPT) version >/dev/null 2>&1; then \
			echo 'Executando composer install via container...'; \
			$(DOCKER_COMPOSE_SCRIPT) run --rm --no-deps app composer install --no-interaction --prefer-dist; \
		else \
			echo 'Composer não encontrado.'; \
			exit 1; \
		fi; \
	fi

npm-install:
	@if [ -f package.json ]; then \
		if [ -n "$(NPM_BIN)" ]; then \
			$(NPM_BIN) install; \
		elif $(DOCKER_COMPOSE_SCRIPT) version >/dev/null 2>&1; then \
			echo 'Executando npm install via container Node...'; \
			$(DOCKER_COMPOSE_SCRIPT) run --rm --no-deps node npm install; \
		else \
			echo 'npm não encontrado. Instale Node.js ou utilize Docker.'; \
			exit 1; \
		fi; \
	else \
		echo 'package.json não encontrado. Pulando npm install.'; \
	fi

docker-up:
	$(DOCKER_COMPOSE_SCRIPT) up -d --build

migrate:
	$(DOCKER_COMPOSE_SCRIPT) run --rm --no-deps app php /var/www/html/bin/migrate

seed:
	$(DOCKER_COMPOSE_SCRIPT) run --rm --no-deps app php /var/www/html/bin/seed

hooks:
	@if [ -f vendor/bin/grumphp ]; then \
		vendor/bin/grumphp git:init; \
	else \
		echo 'GrumPHP não encontrado. Certifique-se de que o composer install foi executado.'; \
		exit 1; \
	fi

down:
	$(DOCKER_COMPOSE_SCRIPT) down

logs:
	$(DOCKER_COMPOSE_SCRIPT) logs -f

xampp: env composer-install
	@if [ -n "$(PHP_BIN)" ]; then \
		php -S 127.0.0.1:8000 -t public; \
	else \
		echo 'PHP CLI não encontrado. Instale PHP para executar make xampp.'; \
		exit 1; \
	fi
