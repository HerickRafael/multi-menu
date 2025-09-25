#!/usr/bin/env bash
set -euo pipefail

# Instala o Homebrew se não existir
if ! command -v brew >/dev/null 2>&1; then
  echo 'Instalando Homebrew...'
  /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
fi

# Define o comando do Brewfile, preferindo --no-lock se disponível
if brew bundle --help 2>&1 | grep -q -- "--no-lock"; then
  bundle_cmd=(brew bundle --no-lock)
else
  bundle_cmd=(brew bundle)
fi

run_bundle() {
  "${bundle_cmd[@]}"
}

# Tenta ajustar permissões com base na saída do brew link
fix_node_permissions() {
  local link_output="$1"
  local fixed=0

  # Procura linhas do tipo: "/usr/local/lib/node_modules is not writable."
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

  (( fixed )) && return 0 || return 1
}

# (Re)cria links do Node e tenta corrigir permissões se necessário
relink_node() {
  if brew list --versions node >/dev/null 2>&1; then
    echo 'Corrigindo links do Node...'
    local link_output
    if link_output="$(brew link --overwrite --force node 2>&1)"; then
      printf '%s\n' "$link_output"
      brew postinstall node || true
      return 0
    fi

    # Se falhou, mostra a saída e tenta corrigir permissões
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

# Executa o Brewfile; em caso de erro, tenta consertar o Node e tenta novamente
if ! run_bundle; then
  if relink_node; then
    run_bundle || exit 1
  else
    exit 1
  fi
fi
