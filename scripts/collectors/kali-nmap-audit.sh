#!/usr/bin/env bash
set -euo pipefail

: "${PLATFORM_URL:?PLATFORM_URL requis}"; : "${MISSION_ID:?MISSION_ID requis}"; : "${AUDIT_TOKEN:?AUDIT_TOKEN requis}"; : "${ASSET_UUID:?ASSET_UUID requis}"; : "${AUTHORIZED_TARGET:?AUTHORIZED_TARGET requis}"
EXPECTED_PORTS="${EXPECTED_PORTS:-22,80,443}"
export EXPECTED_PORTS AUTHORIZED_TARGET ASSET_UUID
workdir="$(mktemp -d)"; trap 'rm -rf "$workdir"' EXIT
started="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
nmap -sT -sV -T3 -oA "$workdir/scan" "$AUTHORIZED_TARGET" >/dev/null
open_ports="$(awk '/Ports:/{for(i=1;i<=NF;i++) if($i ~ /\/open\//) print $i}' "$workdir/scan.gnmap" | sed 's#\([0-9]*\)/.*#\1#' | sort -nu | paste -sd, -)"
unexpected="$(comm -23 <(tr ',' '\n' <<<"$open_ports" | sort -nu) <(tr ',' '\n' <<<"$EXPECTED_PORTS" | sort -nu) | paste -sd, -)"
status="pass"; [[ -n "$unexpected" ]] && status="fail"
export started open_ports unexpected status
python3 - "$workdir/payload.json" "$workdir/scan.nmap" <<'PY'
import datetime,json,os,platform,socket,sys,uuid
raw=open(sys.argv[2],encoding='utf-8',errors='replace').read()
p={"run_uuid":str(uuid.uuid4()),"collector_version":"1.0.0","started_at":os.environ['started'],"completed_at":datetime.datetime.now(datetime.timezone.utc).isoformat(),"asset":{"uuid":os.environ['ASSET_UUID'],"hostname":socket.gethostname(),"ip_address":socket.gethostbyname(socket.gethostname()),"platform":"kali","operating_system":platform.platform(),"role":"Scanner Nmap autorisé"},"checks":[{"code":"NMAP-UNEXPECTED-PORT","status":os.environ['status'],"expected":"Ports autorisés uniquement: "+os.environ.get('EXPECTED_PORTS',''),"observed":"Ports ouverts: "+os.environ['open_ports']+"; inattendus: "+(os.environ['unexpected'] or 'aucun'),"raw_output":raw,"metadata":{"target":os.environ['AUTHORIZED_TARGET']}}]}
open(sys.argv[1],'w',encoding='utf-8').write(json.dumps(p,ensure_ascii=False))
PY
curl --fail-with-body --silent --show-error -X POST -H "Authorization: Bearer $AUDIT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' --data-binary "@$workdir/payload.json" "${PLATFORM_URL%/}/api/v1/missions/$MISSION_ID/server-audit/runs"
