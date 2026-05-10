<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Compte système Super Administrateur
    |--------------------------------------------------------------------------
    */

    'super_admin_email' => env('DGCPT_SUPER_ADMIN_EMAIL', 'admin@dgcpt.ga'),

    /*
    |--------------------------------------------------------------------------
    | Rotation des mots de passe (préparation expiration — futur)
    |--------------------------------------------------------------------------
    */

    'password_rotation_days' => (int) env('DGCPT_PASSWORD_ROTATION_DAYS', 90),

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
