#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Uso: $0 <migrate|seed>" >&2
  exit 1
fi

TASK="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

case "$TASK" in
  migrate|seed)
    "${SCRIPT_DIR}/run_app_task.sh" "$TASK"
    ;;
  *)
    echo "Tarefa desconhecida: $TASK" >&2
    exit 1
    ;;
esac
