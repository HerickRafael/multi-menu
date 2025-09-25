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

if ! run_bundle; then
  if brew list --formula node >/dev/null 2>&1 \
     && brew info node 2>/dev/null | grep -Eqi 'not (currently )?linked'; then
    echo 'Corrigindo links do Node...'
    if brew link --overwrite --force node; then
      brew postinstall node || true
      run_bundle || exit 1
    else
      exit 1
    fi
  else
    exit 1
  fi
fi
