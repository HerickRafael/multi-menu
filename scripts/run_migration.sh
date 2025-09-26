#!/usr/bin/env bash
set -euo pipefail
DC="$(dirname "$0")/docker_compose.sh"

pick_cmd() {
  if $DC exec -T app test -f /var/www/html/bin/migrate; then
    echo "php /var/www/html/bin/migrate"
  elif $DC exec -T app test -f /var/www/html/artisan; then
    echo "php /var/www/html/artisan migrate --force"
  elif $DC exec -T app test -x /var/www/html/vendor/bin/phinx || $DC exec -T app test -f /var/www/html/vendor/bin/phinx; then
    echo "/var/www/html/vendor/bin/phinx migrate || /var/www/html/vendor/bin/phinx migrate -e production"
  elif $DC exec -T app test -f /var/www/html/bin/console; then
    echo "php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction"
  else
    echo ""
  fi
}

cmd="$(pick_cmd)"
[ -n "$cmd" ] || { echo "Nenhuma rotina de migrate encontrada."; exit 1; }

if ! $DC exec -T app sh -lc "$cmd"; then
  echo "Fallback: executando migrate em um container temporário..."
  $DC run --rm --no-deps app sh -lc "$cmd"
fi
