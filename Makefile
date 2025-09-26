#!/usr/bin/env bash
set -euo pipefail

task="${1:-}"
if [[ "$task" != "migrate" && "$task" != "seed" ]]; then
  echo "Uso: $0 {migrate|seed}" >&2
  exit 1
fi

# Resolve paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
DOCKER_COMPOSE_SCRIPT="${ROOT_DIR}/scripts/docker_compose.sh"

if [[ ! -x "${DOCKER_COMPOSE_SCRIPT}" ]]; then
  echo "Erro: ${DOCKER_COMPOSE_SCRIPT} não encontrado ou sem permissão de execução." >&2
  echo "Dê:  chmod +x ${DOCKER_COMPOSE_SCRIPT}" >&2
  exit 1
fi

# Comandos candidatos no container (WORKDIR costuma ser /var/www/html)
php_bin_task="php /var/www/html/bin/${task}"
artisan_migrate="php /var/www/html/artisan migrate --force"
artisan_seed="php /var/www/html/artisan db:seed --force"
phinx_migrate="/var/www/html/vendor/bin/phinx migrate || /var/www/html/vendor/bin/phinx migrate -e production"
phinx_seed="/var/www/html/vendor/bin/phinx seed:run"
doctrine_migrate="php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction"
doctrine_seed="php /var/www/html/bin/console doctrine:fixtures:load --no-interaction"

# Escolhe comando
cmd=""
if "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f /var/www/html/bin/"${task}"; then
  cmd="${php_bin_task}"
elif "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f /var/www/html/artisan; then
  if [[ "$task" == "migrate" ]]; then cmd="${artisan_migrate}"; else cmd="${artisan_seed}"; fi
elif "${DOCKER_COMPOSE_SCRIPT}" exec -T app sh -lc 'test -x /var/www/html/vendor/bin/phinx || test -f /var/www/html/vendor/bin/phinx'; then
  if [[ "$task" == "migrate" ]]; then cmd="${phinx_migrate}"; else cmd="${phinx_seed}"; fi
elif "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f /var/www/html/bin/console; then
  if [[ "$task" == "migrate" ]]; then cmd="${doctrine_migrate}"; else cmd="${doctrine_seed}"; fi
else
  echo "❌ Nenhuma rotina de ${task} encontrada (bin/${task}, artisan, phinx, doctrine)." >&2
  exit 1
fi

# Tenta em container já rodando; se falhar, usa container temporário
if ! "${DOCKER_COMPOSE_SCRIPT}" exec -T app sh -lc "${cmd}"; then
  echo "Fallback: executando ${task} em um container temporário..."
  "${DOCKER_COMPOSE_SCRIPT}" run --rm --no-deps app sh -lc "${cmd}"
fi
