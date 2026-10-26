param(
    [Parameter(Mandatory=$true)][string]$PlatformUrl,
    [Parameter(Mandatory=$true)][int]$MissionId,
    [Parameter(Mandatory=$true)][string]$Token,
    [Parameter(Mandatory=$true)][Guid]$AssetUuid,
    [string]$AssetRole = "Windows Server",
    [string]$SqlInstance = ""
)

$ErrorActionPreference = "Stop"
$started = (Get-Date).ToUniversalTime().ToString("o")
$checks = [System.Collections.Generic.List[object]]::new()

function Add-Check([string]$Code, [string]$Status, [string]$Expected, [string]$Observed, [string]$RawOutput) {
    $checks.Add(@{code=$Code; status=$Status; expected=$Expected; observed=$Observed; raw_output=$RawOutput})
}

try {
    Import-Module ActiveDirectory -ErrorAction Stop
    $members = Get-ADGroupMember -Identity "Domain Admins" -Recursive | Select-Object Name,SamAccountName,ObjectClass
    $raw = ($members | ConvertTo-Json -Depth 4)
    Add-Check "WIN-AD-ADMINS" "not_applicable" "Adhésions nominatives, justifiées et revues" "Revue humaine requise : $($members.Count) membre(s)" $raw
} catch {
    Add-Check "WIN-AD-ADMINS" "not_applicable" "Contrôleur de domaine accessible" $_.Exception.Message $_.Exception.ToString()
}

try {
    $policy = Get-ADDefaultDomainPasswordPolicy | Select-Object MinPasswordLength,ComplexityEnabled,PasswordHistoryCount,MaxPasswordAge
    $fail = ($policy.MinPasswordLength -lt 14 -or -not $policy.ComplexityEnabled)
    Add-Check "WIN-PASSWORD-POLICY" $(if($fail){"fail"}else{"pass"}) "Longueur minimale >= 14 et complexité activée" "Min=$($policy.MinPasswordLength); Complexité=$($policy.ComplexityEnabled)" ($policy | ConvertTo-Json)
} catch {
    $raw = (net accounts) -join "`n"
    Add-Check "WIN-PASSWORD-POLICY" "error" "Politique de domaine collectée" $_.Exception.Message $raw
}

$smb = Get-SmbServerConfiguration | Select-Object EnableSMB1Protocol,EnableSMB2Protocol,RequireSecuritySignature
Add-Check "WIN-SMB1" $(if($smb.EnableSMB1Protocol){"fail"}else{"pass"}) "SMBv1 désactivé" "EnableSMB1Protocol=$($smb.EnableSMB1Protocol)" ($smb | ConvertTo-Json)

$audit = (auditpol /get /category:*) -join "`n"
$auditFail = -not ($audit -match "Réussite|Success")
Add-Check "WIN-AUDITPOL" $(if($auditFail){"fail"}else{"pass"}) "Stratégie d'audit avancée active" $(if($auditFail){"Aucun audit de réussite détecté"}else{"Catégories actives détectées"}) $audit

if ($SqlInstance -and (Get-Command sqlcmd -ErrorAction SilentlyContinue)) {
    $sa = (sqlcmd -S $SqlInstance -E -W -h -1 -Q "SET NOCOUNT ON; SELECT CONCAT(is_disabled,'|',is_policy_checked,'|',is_expiration_checked) FROM sys.sql_logins WHERE name='sa';") -join "`n"
    $saFail = $sa.Trim().StartsWith("0|")
    Add-Check "MSSQL-SA" $(if($saFail){"fail"}else{"pass"}) "Compte sa désactivé ou strictement protégé" $sa.Trim() $sa
    $xp = (sqlcmd -S $SqlInstance -E -W -h -1 -Q "SET NOCOUNT ON; SELECT CAST(value_in_use AS varchar(10)) FROM sys.configurations WHERE name='xp_cmdshell';") -join "`n"
    Add-Check "MSSQL-XPCMDSHELL" $(if($xp.Trim() -eq "1") {"fail"} else {"pass"}) "xp_cmdshell désactivé" "value_in_use=$($xp.Trim())" $xp
}

$ip = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.IPAddress -notlike '127.*' -and $_.PrefixOrigin -ne 'WellKnown'} | Select-Object -First 1 -ExpandProperty IPAddress)
$payload = @{
    run_uuid = [guid]::NewGuid().ToString(); collector_version = "1.0.0"; started_at = $started;
    completed_at = (Get-Date).ToUniversalTime().ToString("o");
    asset = @{uuid=$AssetUuid.ToString(); hostname=$env:COMPUTERNAME; ip_address=$ip; platform="windows"; operating_system=(Get-CimInstance Win32_OperatingSystem).Caption; role=$AssetRole};
    checks = $checks
} | ConvertTo-Json -Depth 8

Invoke-RestMethod -Method Post -Uri "$($PlatformUrl.TrimEnd('/'))/api/v1/missions/$MissionId/server-audit/runs" -Headers @{Authorization="Bearer $Token";Accept="application/json"} -ContentType "application/json" -Body ([Text.Encoding]::UTF8.GetBytes($payload))
