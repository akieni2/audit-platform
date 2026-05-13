<?php

namespace App\Models;

/**
 * Unité auditée rattachée à une mission (même table que {@see Service}).
 * Alias métier Phase 2 — pas de duplication de table.
 */
class MissionService extends Service
{
    protected $table = 'services';
}
