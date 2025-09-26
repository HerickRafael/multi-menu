#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Uso: $0 <migrate|seed>" >&2
  exit 1
fi

TASK="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
DOCKER_COMPOSE_SCRIPT="${SCRIPT_DIR}/docker_compose.sh"

if [[ ! -x "${DOCKER_COMPOSE_SCRIPT}" ]]; then
  echo "❌ ${DOCKER_COMPOSE_SCRIPT} não encontrado ou sem permissão de execução." >&2
  exit 1
fi

run_in_existing_container() {
  set +e
  "${DOCKER_COMPOSE_SCRIPT}" exec -T app "$@"
  local status=$?
  set -e
  return ${status}
}

run_in_temporary_container() {
  set +e
  "${DOCKER_COMPOSE_SCRIPT}" run --rm --no-deps app "$@"
  local status=$?
  set -e
  return ${status}
}

primary_cmd=()
secondary_cmd=()

if [[ -f "${ROOT_DIR}/bin/${TASK}" ]]; then
  primary_cmd=(php /var/www/html/bin/"${TASK}")
elif [[ -f "${ROOT_DIR}/artisan" ]]; then
  if [[ "${TASK}" == "migrate" ]]; then
    primary_cmd=(php /var/www/html/artisan migrate --force)
  else
    primary_cmd=(php /var/www/html/artisan db:seed --force)
  fi
elif [[ -x "${ROOT_DIR}/vendor/bin/phinx" || -f "${ROOT_DIR}/vendor/bin/phinx" ]]; then
  if [[ "${TASK}" == "migrate" ]]; then
    primary_cmd=(/var/www/html/vendor/bin/phinx migrate)
    secondary_cmd=(/var/www/html/vendor/bin/phinx migrate -e production)
  else
    primary_cmd=(/var/www/html/vendor/bin/phinx seed:run)
  fi
elif [[ -f "${ROOT_DIR}/bin/console" ]]; then
  if [[ "${TASK}" == "migrate" ]]; then
    primary_cmd=(php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction)
  else
    primary_cmd=(php /var/www/html/bin/console doctrine:fixtures:load --no-interaction)
  fi
fi

if [[ ${#primary_cmd[@]} -eq 0 ]]; then
  echo "❌ Nenhuma rotina de ${TASK} encontrada (bin/${TASK}, artisan, phinx, doctrine)." >&2
  exit 1
fi

if run_in_existing_container "${primary_cmd[@]}"; then
  exit 0
fi

if [[ ${#secondary_cmd[@]} -gt 0 ]]; then
  if run_in_existing_container "${secondary_cmd[@]}"; then
    exit 0
  fi
fi

echo "Fallback: executando ${TASK} em um container temporário..."
if run_in_temporary_container "${primary_cmd[@]}"; then
  exit 0
fi

if [[ ${#secondary_cmd[@]} -gt 0 ]]; then
  if run_in_temporary_container "${secondary_cmd[@]}"; then
    exit 0
  fi
fi

echo "❌ Falha ao executar ${TASK} mesmo após fallback." >&2
exit 1
