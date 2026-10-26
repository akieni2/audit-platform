# Collecteur « Audit des serveurs & Data Analytics »

Le collecteur exécute des contrôles **en lecture seule**, transmet les résultats en HTTPS et conserve le JSON brut comme preuve de mission. Une anomalie produit un risque `detected`, jamais un constat définitif : la validation humaine reste obligatoire.

## Une démarche universelle, pas une liste de technologies

Windows, Active Directory, Ubuntu, MySQL et SQL Server sont les premiers cas pratiques du laboratoire. Le moteur accepte tout serveur ou équipement dès lors que ses contrôles sont enregistrés dans le catalogue.

Le flux méthodologique est : **actif → contrôle → observation → preuve → écart → risque → disposition du référentiel → traitement → validation humaine**.

- `server_audit_control_definitions` décrit le contrôle, sa portée, son objectif, la valeur attendue, le risque et le traitement ;
- `server_audit_control_references` rattache le contrôle à une ou plusieurs sources avec organisme, document, version, chapitre, section et disposition ;
- la portée peut viser une plateforme, un type d'actif, un produit ou un rôle, tandis que `*` rend le contrôle universel ;
- chaque résultat conserve un instantané des citations et du traitement applicables au moment de l'audit ;
- le script transmet les faits et la preuve. La qualification finale demeure sous la responsabilité de l'auditeur.

Pour prendre en charge AIX, PostgreSQL, Oracle Database, un hyperviseur ou un équipement réseau, on ajoute un profil de contrôles et un collecteur correspondant sans modifier le moteur d'ingestion.

## Export Data Analytics

Depuis la fiche d'une mission, le lien **Exporter Excel / Power Query** produit un classeur structuré comprenant :

- `Synthese` : indicateurs de la mission et de la collecte ;
- `Grille_Constats` : modèle de consolidation à 16 colonnes du laboratoire J1 ;
- `Resultats_Techniques` : population brute des contrôles, actifs, valeurs observées et empreintes de preuve ;
- `Referentiels` : organismes, documents, versions, chapitres, sections, dispositions et traitements ;
- `Enumerations` : criticités, statuts et échéances normalisés ;
- `Power_Query` : étapes recommandées de typage, déduplication, jointure et filtrage.

Les feuilles sont plates, dotées d'en-têtes filtrables et conçues pour être chargées dans Power Query ou Power BI. Les identifiants d'exécution et empreintes SHA-256 restent présents pour préserver la traçabilité jusqu'à la preuve d'origine.

## Préparation

```bash
php artisan migrate --force
php artisan db:seed --class=ServerAuditControlCatalogSeeder --force
php artisan audit:collector-token auditeur@dgcpt.ga 123
```

Conserver le jeton hors du script et limiter les droits du fichier qui le contient. Révoquer le jeton Sanctum à la fin de la campagne.

## Ubuntu / MySQL

```bash
export PLATFORM_URL=https://www.dgcpt.ga
export MISSION_ID=123
export AUDIT_TOKEN='jeton-affiché-une-seule-fois'
export ASSET_UUID='uuid-fourni-par-la-commande'
export MYSQL_AUDIT_DEFAULTS_FILE=/root/.my-audit.cnf
bash scripts/collectors/ubuntu-server-audit.sh
```

Le fichier MySQL doit appartenir à root avec le mode `600` et contenir un compte SQL dédié, limité aux vues système nécessaires.

## Windows Server / Active Directory / SQL Server

```powershell
$env:AUDIT_TOKEN = Read-Host "Jeton du collecteur"
.\scripts\collectors\windows-server-audit.ps1 -PlatformUrl https://www.dgcpt.ga -MissionId 123 -Token $env:AUDIT_TOKEN -AssetUuid 'UUID' -SqlInstance 'localhost'
```

## Kali / Nmap

Le scan ne doit viser que le périmètre autorisé par la lettre de mission.

```bash
export PLATFORM_URL=https://www.dgcpt.ga MISSION_ID=123 AUDIT_TOKEN='…' ASSET_UUID='…'
export AUTHORIZED_TARGET=192.168.56.0/24 EXPECTED_PORTS=22,80,443,445,1433,3306,3389
bash scripts/collectors/kali-nmap-audit.sh
```

## Traçabilité

- `server_audit_assets` : machines auditées ;
- `server_audit_runs` : exécutions et empreinte SHA-256 ;
- `server_audit_results` : résultats par contrôle ;
- `mission_documents` : preuve JSON brute ;
- `identified_risks` : anomalies provisoires visibles dans le registre des risques.

Un résultat conforme ultérieur ne clôture pas automatiquement le risque : un auditeur doit examiner la contre-preuve.
