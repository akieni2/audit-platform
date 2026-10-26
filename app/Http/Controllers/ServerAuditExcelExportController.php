<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Services\ServerAudit\ServerAuditExcelExportService;
use Symfony\Component\HttpFoundation\Response;

class ServerAuditExcelExportController extends Controller
{
    public function __invoke(Mission $mission, ServerAuditExcelExportService $exporter): Response
    {
        abort_unless(Mission::query()->visibleToUser(request()->user())->whereKey($mission->id)->exists(), 403);
        $path = $exporter->build($mission);
        $name = 'audit-data-analytics-'.($mission->reference ?: $mission->id).'-'.now()->format('Ymd-His').'.xlsx';

        $contents = file_get_contents($path);
        @unlink($path);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }
}
