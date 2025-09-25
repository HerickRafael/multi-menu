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
    if brew link --overwrite --force node; then
      brew postinstall node || true
      return 0
    fi
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
