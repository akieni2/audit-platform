#!/usr/bin/env bash
set -euo pipefail

: "${PLATFORM_URL:?PLATFORM_URL requis}"
: "${MISSION_ID:?MISSION_ID requis}"
: "${AUDIT_TOKEN:?AUDIT_TOKEN requis}"
: "${ASSET_UUID:?ASSET_UUID requis}"

started="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
workdir="$(mktemp -d)"
trap 'rm -rf "$workdir"' EXIT

sshd_output="$(sudo sshd -T 2>&1 || true)"
permit_root="$(printf '%s\n' "$sshd_output" | awk '$1=="permitrootlogin" {print $2; exit}')"
password_auth="$(printf '%s\n' "$sshd_output" | awk '$1=="passwordauthentication" {print $2; exit}')"
sudo_output="$(sudo grep -RhsE '^[[:space:]]*[^#].*NOPASSWD' /etc/sudoers /etc/sudoers.d 2>&1 || true)"
auditd_output="$(systemctl is-active auditd 2>&1 || true)"

mysql_root_status="not_applicable"; mysql_root_observed="MySQL non configuré pour la collecte"; mysql_root_raw=""
mysql_tls_status="not_applicable"; mysql_tls_observed="MySQL non configuré pour la collecte"; mysql_tls_raw=""
if command -v mysql >/dev/null 2>&1 && [[ -n "${MYSQL_AUDIT_DEFAULTS_FILE:-}" ]]; then
  mysql_root_raw="$(mysql --defaults-extra-file="$MYSQL_AUDIT_DEFAULTS_FILE" -NBe "SELECT CONCAT(User,'@',Host) FROM mysql.user WHERE User='root' AND Host IN ('%','0.0.0.0');" 2>&1 || true)"
  if [[ "$mysql_root_raw" == *"root@"* ]]; then mysql_root_status="fail"; else mysql_root_status="pass"; fi
  mysql_root_observed="${mysql_root_raw:-Aucun compte root générique}"
  mysql_tls_raw="$(mysql --defaults-extra-file="$MYSQL_AUDIT_DEFAULTS_FILE" -NBe "SHOW VARIABLES LIKE 'require_secure_transport';" 2>&1 || true)"
  if printf '%s' "$mysql_tls_raw" | grep -Eqi '[[:space:]]ON$'; then mysql_tls_status="pass"; else mysql_tls_status="fail"; fi
  mysql_tls_observed="${mysql_tls_raw:-Variable indisponible}"
fi

export started permit_root password_auth sshd_output sudo_output auditd_output mysql_root_status mysql_root_observed mysql_root_raw mysql_tls_status mysql_tls_observed mysql_tls_raw
python3 - "$workdir/payload.json" <<'PY'
import json, os, platform, socket, sys, uuid
def check(code, status, expected, observed, raw):
    return {"code": code, "status": status, "expected": expected, "observed": observed, "raw_output": raw}
checks = [
 check("LINUX-ROOT-SSH", "pass" if os.environ.get("permit_root") == "no" else "fail", "PermitRootLogin no", "PermitRootLogin=" + os.environ.get("permit_root", "indéterminé"), os.environ.get("sshd_output", "")),
 check("LINUX-SSH-PASSWORD", "pass" if os.environ.get("password_auth") == "no" else "fail", "PasswordAuthentication no", "PasswordAuthentication=" + os.environ.get("password_auth", "indéterminé"), os.environ.get("sshd_output", "")),
 check("LINUX-SUDO-NOPASSWD", "pass" if not os.environ.get("sudo_output") else "fail", "Aucune règle NOPASSWD injustifiée", os.environ.get("sudo_output") or "Aucune règle détectée", os.environ.get("sudo_output", "")),
 check("LINUX-AUDITD", "pass" if os.environ.get("auditd_output") == "active" else "fail", "auditd actif", os.environ.get("auditd_output", "indéterminé"), os.environ.get("auditd_output", "")),
 check("MYSQL-ROOT-WILDCARD", os.environ["mysql_root_status"], "Aucun root@% ou root@0.0.0.0", os.environ["mysql_root_observed"], os.environ["mysql_root_raw"]),
 check("MYSQL-TLS", os.environ["mysql_tls_status"], "require_secure_transport=ON", os.environ["mysql_tls_observed"], os.environ["mysql_tls_raw"]),
]
payload={"run_uuid":str(uuid.uuid4()),"collector_version":"1.0.0","started_at":os.environ["started"],"completed_at":__import__('datetime').datetime.now(__import__('datetime').timezone.utc).isoformat(),"asset":{"uuid":os.environ["ASSET_UUID"],"hostname":socket.gethostname(),"ip_address":socket.gethostbyname(socket.gethostname()),"platform":"linux","operating_system":platform.platform(),"role":os.environ.get("ASSET_ROLE","Ubuntu Server")},"checks":checks}
open(sys.argv[1],"w",encoding="utf-8").write(json.dumps(payload,ensure_ascii=False))
PY

curl --fail-with-body --silent --show-error -X POST \
  -H "Authorization: Bearer $AUDIT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data-binary "@$workdir/payload.json" "${PLATFORM_URL%/}/api/v1/missions/$MISSION_ID/server-audit/runs"
