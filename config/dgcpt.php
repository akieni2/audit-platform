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

];
