<?php

/**
 * Fournisseurs applicatifs.
 *
 * Horizon n’est enregistré que si le paquet est présent dans vendor,
 * afin qu’un `composer install` incomplet ou un environnement sans Horizon
 * ne bloque pas artisan (Ubuntu / CI / poste dev).
 */
$providers = [
    App\Providers\AppServiceProvider::class,
];

if (class_exists(\Laravel\Horizon\HorizonApplicationServiceProvider::class)) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

return $providers;
