#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Uso: $0 <migrate|seed>" >&2
  exit 1
fi

TASK="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCKER_COMPOSE_SCRIPT="${SCRIPT_DIR}/docker_compose.sh"

cmd=""

if "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f "/var/www/html/bin/${TASK}"; then
  cmd="php /var/www/html/bin/${TASK}"
elif "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f /var/www/html/artisan; then
  if [[ "${TASK}" == "migrate" ]]; then
    cmd='php /var/www/html/artisan migrate --force'
  else
    cmd='php /var/www/html/artisan db:seed --force'
  fi
elif "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -x /var/www/html/vendor/bin/phinx || \
     "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f /var/www/html/vendor/bin/phinx; then
  if [[ "${TASK}" == "migrate" ]]; then
    cmd='/var/www/html/vendor/bin/phinx migrate || /var/www/html/vendor/bin/phinx migrate -e production'
  else
    cmd='/var/www/html/vendor/bin/phinx seed:run'
  fi
elif "${DOCKER_COMPOSE_SCRIPT}" exec -T app test -f /var/www/html/bin/console; then
  if [[ "${TASK}" == "migrate" ]]; then
    cmd='php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction'
  else
    cmd='php /var/www/html/bin/console doctrine:fixtures:load --no-interaction'
  fi
fi

if [[ -z "${cmd}" ]]; then
  echo "❌ Nenhuma rotina de ${TASK} encontrada (bin/${TASK}, artisan, phinx, doctrine)."
  exit 1
fi

if ! "${DOCKER_COMPOSE_SCRIPT}" exec -T app sh -lc "${cmd}"; then
  echo "Fallback: executando ${TASK} em um container temporário..."
  "${DOCKER_COMPOSE_SCRIPT}" run --rm --no-deps app sh -lc "${cmd}"
fi
