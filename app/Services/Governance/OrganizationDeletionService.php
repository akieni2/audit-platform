<?php

namespace App\Services\Governance;

use App\Models\Department;
use Illuminate\Support\Facades\DB;

class OrganizationDeletionService
{
    /** @return array{departments:int} */
    public function deleteTree(Department $department): array
    {
        return DB::transaction(function () use ($department): array {
            $count = $this->deleteNode($department);

            return ['departments' => $count];
        });
    }

    private function deleteNode(Department $department): int
    {
        $count = 0;
        foreach ($department->children()->get() as $child) {
            $count += $this->deleteNode($child);
        }

        $department->delete();

        return $count + 1;
    }
}
