#!/usr/bin/env bash
set -euo pipefail

# Verifica Docker CLI
if ! command -v docker >/dev/null 2>&1; then
  echo 'Docker CLI não encontrado. Instale Docker Desktop ou o pacote docker.' >&2
  exit 1
fi

# Prepara variáveis de ambiente caso não haja buildx (evita falhas em alguns ambientes)
build_env=()
if ! docker buildx version >/dev/null 2>&1; then
  build_env+=(DOCKER_BUILDKIT=0)
  build_env+=(COMPOSE_DOCKER_CLI_BUILD=0)
fi

# Usa Compose V2 se disponível, senão faz fallback para docker-compose (V1)
if docker compose version >/dev/null 2>&1; then
  exec env "${build_env[@]}" docker compose "$@"
elif command -v docker-compose >/dev/null 2>&1; then
  exec env "${build_env[@]}" docker-compose "$@"
fi

echo 'docker compose não disponível. Instale Docker Desktop (Compose V2) ou `brew install docker-compose`.' >&2
exit 1
