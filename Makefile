SHELL := /bin/bash
.SHELLFLAGS := -eo pipefail -c

.PHONY: setup docker-up migrate seed down logs ps bash help

SCRIPTS_DIR := scripts
DOCKER_COMPOSE_SCRIPT := $(SCRIPTS_DIR)/docker_compose.sh
DB_TASK_SCRIPT := $(SCRIPTS_DIR)/db_task.sh

setup: docker-up migrate seed

docker-up:
	@$(DOCKER_COMPOSE_SCRIPT) up -d --build

migrate:
	@bash $(DB_TASK_SCRIPT) migrate

seed:
	@bash $(DB_TASK_SCRIPT) seed

down:
	@$(DOCKER_COMPOSE_SCRIPT) down

logs:
	@$(DOCKER_COMPOSE_SCRIPT) logs -f

ps:
	@$(DOCKER_COMPOSE_SCRIPT) ps

bash:
	@$(DOCKER_COMPOSE_SCRIPT) exec app bash || $(DOCKER_COMPOSE_SCRIPT) run --rm app bash

help:
	@echo "make setup   # sobe containers, roda migrate e seed"
	@echo "make migrate # roda migrations"
	@echo "make seed    # roda seeds"
