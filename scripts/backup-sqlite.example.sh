#!/usr/bin/env bash
# Sauvegarde SQLite — adapter BACKUP_DIR et chemin base.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB="${ROOT}/database/database.sqlite"
BACKUP_DIR="${BACKUP_DIR:-${ROOT}/storage/backups}"
STAMP="$(date +%Y%m%d_%H%M%S)"

mkdir -p "${BACKUP_DIR}"

if [[ -f "${DB}" ]]; then
  cp "${DB}" "${BACKUP_DIR}/database_${STAMP}.sqlite"
  echo "OK: ${BACKUP_DIR}/database_${STAMP}.sqlite"
else
  echo "Base introuvable: ${DB}" >&2
  exit 1
fi
