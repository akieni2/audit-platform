# Application Android DGCPT

## Identité

- Nom : `DGCPT`
- Identifiant Android : `ga.dgcpt.plateforme`
- URL applicative : `https://www.dgcpt.ga`
- Version actuelle : `1.2` (`versionCode` 3)
- Android minimal : API 24 (Android 7)

L'application est une enveloppe Android Capacitor sécurisée de la plateforme web. Les connexions HTTP en clair sont interdites et la navigation applicative est limitée au domaine DGCPT.

## Prérequis de compilation

- Android Studio et Android SDK
- Java 21 (le JBR d'Android Studio convient)
- Node.js et npm

Sous Windows :

```powershell
$env:JAVA_HOME='C:\Program Files\Android\Android Studio\jbr'
$env:ANDROID_HOME="$env:LOCALAPPDATA\Android\Sdk"
$env:ANDROID_SDK_ROOT=$env:ANDROID_HOME
npm install
npm run android:apk
```

L'APK de développement est créé dans :

`android/app/build/outputs/apk/debug/app-debug.apk`

## Essai sur un téléphone

1. Autoriser temporairement l'installation d'applications provenant du navigateur ou du gestionnaire de fichiers Android.
2. Copier puis ouvrir `DGCPT-pilote-1.0-debug.apk` sur le téléphone.
3. Tester la connexion, les menus, les formulaires, les questionnaires, les cours CFDT, les vidéos, les PDF et les téléchargements.
4. Désactiver ensuite l'autorisation d'installation depuis cette source.

Cette version est signée avec la clé Android de développement. Elle ne doit pas être publiée sur Google Play.

## Publication Google Play

La publication nécessitera une clé d'envoi conservée hors du dépôt et un Android App Bundle (`.aab`) signé. La clé, ses mots de passe et les fichiers de signature ne doivent jamais être ajoutés à Git.
