SHELL := /bin/bash
OS := $(shell uname)
DOCKER_COMPOSE_CMD := $(shell if command -v docker >/dev/null 2>&1; then if docker compose version >/dev/null 2>&1; then echo "docker compose"; fi; fi)
ifeq ($(DOCKER_COMPOSE_CMD),)
DOCKER_COMPOSE_CMD := $(shell if command -v docker-compose >/dev/null 2>&1; then echo docker-compose; fi)
endif
COMPOSER_BIN := $(shell command -v composer 2>/dev/null)
NPM_BIN := $(shell command -v npm 2>/dev/null)
PHP_BIN := $(shell command -v php 2>/dev/null)

.PHONY: setup brew docker-env env composer-install npm-install docker-up migrate seed hooks down logs xampp

setup: brew docker-env env composer-install npm-install docker-up migrate seed hooks

brew:
	@if [ "$(OS)" = "Darwin" ]; then \
	if ! command -v brew >/dev/null 2>&1; then \
	echo 'Instalando Homebrew...'; \
	/bin/bash -c "$$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"; \
	fi; \
	if brew bundle --help 2>&1 | grep -q -- "--no-lock"; then \
		BUNDLE_CMD="brew bundle --no-lock"; \
	else \
		BUNDLE_CMD="brew bundle"; \
	fi; \
	if ! eval "$$BUNDLE_CMD"; then \
		if brew list --formula node >/dev/null 2>&1 \
		   && brew info node 2>/dev/null | grep -Eqi 'not (currently )?linked'; then \
			echo 'Corrigindo links do Node...'; \
			if brew link --overwrite --force node; then \
				brew postinstall node || true; \
				eval "$$BUNDLE_CMD" || exit 1; \
			else \
				exit 1; \
			fi; \
		else \
			exit 1; \
		fi; \
	fi; \
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
	fi

env:
	@if [ ! -f .env ]; then \
	echo 'Copiando .env.example para .env'; \
	cp .env.example .env; \
	fi; \
	if [ -n "$(PHP_BIN)" ]; then \
	php bin/generate-key; \
	elif [ -n "$(DOCKER_COMPOSE_CMD)" ]; then \
	echo 'Gerando APP_KEY dentro do container...'; \
	$(DOCKER_COMPOSE_CMD) run --rm --no-deps app php bin/generate-key; \
	else \
	echo 'PHP não encontrado para gerar APP_KEY.'; \
	exit 1; \
	fi

composer-install:
	@if [ -f composer.json ]; then \
	if [ -n "$(COMPOSER_BIN)" ]; then \
	$(COMPOSER_BIN) install --no-interaction --prefer-dist; \
	elif [ -n "$(DOCKER_COMPOSE_CMD)" ]; then \
	echo 'Executando composer install via container...'; \
	$(DOCKER_COMPOSE_CMD) run --rm --no-deps app composer install --no-interaction --prefer-dist; \
	else \
	echo 'Composer não encontrado.'; \
	exit 1; \
	fi; \
	fi

npm-install:
	@if [ -f package.json ]; then \
	if [ -n "$(NPM_BIN)" ]; then \
	$(NPM_BIN) install; \
	elif [ -n "$(DOCKER_COMPOSE_CMD)" ]; then \
	echo 'Executando npm install via container Node...'; \
	$(DOCKER_COMPOSE_CMD) run --rm --no-deps node npm install; \
	else \
	echo 'npm não encontrado. Instale Node.js ou utilize Docker.'; \
	exit 1; \
	fi; \
	else \
	echo 'package.json não encontrado. Pulando npm install.'; \
	fi

docker-up:
	@if [ -z "$(DOCKER_COMPOSE_CMD)" ]; then \
	echo 'docker compose não disponível.'; \
	exit 1; \
	fi; \
	$(DOCKER_COMPOSE_CMD) up -d --build

migrate:
	@if [ -z "$(DOCKER_COMPOSE_CMD)" ]; then \
	echo 'docker compose não disponível para executar migrações.'; \
	exit 1; \
	fi; \
	$(DOCKER_COMPOSE_CMD) run --rm --no-deps app php bin/migrate

seed:
	@if [ -z "$(DOCKER_COMPOSE_CMD)" ]; then \
	echo 'docker compose não disponível para executar seeds.'; \
	exit 1; \
	fi; \
	$(DOCKER_COMPOSE_CMD) run --rm --no-deps app php bin/seed

hooks:
	@if [ -f vendor/bin/grumphp ]; then \
	vendor/bin/grumphp git:init; \
	else \
	echo 'GrumPHP não encontrado. Certifique-se de que o composer install foi executado.'; \
	exit 1; \
	fi

down:
	@if [ -n "$(DOCKER_COMPOSE_CMD)" ]; then \
	$(DOCKER_COMPOSE_CMD) down; \
	else \
	echo 'docker compose não disponível.'; \
	fi

logs:
	@if [ -n "$(DOCKER_COMPOSE_CMD)" ]; then \
	$(DOCKER_COMPOSE_CMD) logs -f; \
	else \
	echo 'docker compose não disponível.'; \
	fi

xampp: env composer-install
	@if [ -n "$(PHP_BIN)" ]; then \
	php -S 127.0.0.1:8000 -t public; \
	else \
	echo 'PHP CLI não encontrado. Instale PHP para executar make xampp.'; \
	exit 1; \
	fi
