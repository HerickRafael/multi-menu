#!/usr/bin/env bash
set -euo pipefail

if ! command -v brew >/dev/null 2>&1; then
  echo 'Instalando Homebrew...'
  /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
fi

if brew bundle --help 2>&1 | grep -q -- "--no-lock"; then
  bundle_cmd=(brew bundle --no-lock)
else
  bundle_cmd=(brew bundle)
fi

run_bundle() {
  "${bundle_cmd[@]}"
}

relink_node() {
  if brew list --versions node >/dev/null 2>&1; then
    echo 'Corrigindo links do Node...'
    if link_output=$(brew link --overwrite --force node 2>&1); then
      printf '%s\n' "$link_output"
      brew postinstall node || true
      return 0
    fi

    printf '%s\n' "$link_output"

    if fix_node_permissions "$link_output"; then
      echo 'Reexecutando brew link node após corrigir permissões...'
      if brew link --overwrite --force node; then
        brew postinstall node || true
        return 0
      fi
    fi
  fi
  return 1
}

fix_node_permissions() {
  local link_output="$1"
  local fixed=0

  while IFS= read -r line; do
    if [[ $line =~ ^/.+\ is\ not\ writable\.$ ]]; then
      local path="${line% is not writable.}"
      echo "Tentando ajustar permissões em ${path}..."
      if sudo chown -R "$(whoami)" "$path"; then
        fixed=1
      else
        echo "Não foi possível ajustar permissões em ${path}" >&2
      fi
    fi
  done <<< "$link_output"

  if (( fixed )); then
    return 0
  fi

  return 1
}

if ! run_bundle; then
  if relink_node; then
    run_bundle || exit 1
  else
    exit 1
  fi
fi
