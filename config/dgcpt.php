<?php

return [

    'institution_name' => env(
        'DGCPT_INSTITUTION_NAME',
        'DGCPT — Direction Générale de la Comptabilité Publique et du Trésor'
    ),

    /*
    |--------------------------------------------------------------------------
    | Compte système Super Administrateur
    |--------------------------------------------------------------------------
    */

    'super_admin_email' => env('DGCPT_SUPER_ADMIN_EMAIL', 'admin@dgcpt.ga'),

    /*
    |--------------------------------------------------------------------------
    | Alerte demandes d'enrôlement (si aucun super_admin actif en base)
    |--------------------------------------------------------------------------
    */

    'enrollment_alert_email' => env('DGCPT_ENROLLMENT_ALERT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Rotation des mots de passe (préparation expiration — futur)
    |--------------------------------------------------------------------------
    */

    'password_rotation_days' => (int) env('DGCPT_PASSWORD_ROTATION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Règles de validation des mots de passe (inscription, admin, profil…)
    |--------------------------------------------------------------------------
    |
    | Par défaut : longueur minimale seulement (8), sans complexité obligatoire,
    | pour ne pas bloquer la création de comptes. Renforcer via .env si besoin.
    |
    */

    'password_min_length' => max(1, min(255, (int) env('DGCPT_PASSWORD_MIN_LENGTH', 8))),

    'password_require_mixed_case' => filter_var(
        env('DGCPT_PASSWORD_MIXED_CASE', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'password_require_numbers' => filter_var(
        env('DGCPT_PASSWORD_NUMBERS', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'password_require_symbols' => filter_var(
        env('DGCPT_PASSWORD_SYMBOLS', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Vérification « mot de passe compromis » (Have I Been Pwned)
    |--------------------------------------------------------------------------
    |
    | Nécessite un accès HTTPS sortant. Sur réseau cloisonné, laisser à false
    | ou la validation échouera sans message évident côté utilisateur.
    | Mettre à true en production lorsque l’accès à l’API est autorisé.
    |
    */

    'password_uncompromised' => filter_var(
        env('DGCPT_PASSWORD_UNCOMPROMISED', false),
        FILTER_VALIDATE_BOOLEAN
    ),

];
