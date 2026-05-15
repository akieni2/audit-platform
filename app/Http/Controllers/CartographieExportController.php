<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Services\Export\TpmoCartographyExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CartographieExportController extends Controller
{
    public function __construct(
        private TpmoCartographyExportService $exports,
    ) {}

    public function workbook(Mission $mission): StreamedResponse
    {
        $this->authorize('view', $mission);

        return $this->exports->downloadWorkbook($mission);
    }

    public function actifs(Mission $mission): StreamedResponse
    {
        $this->authorize('view', $mission);

        return $this->exports->downloadActifs($mission);
    }

    public function matrice(Mission $mission): StreamedResponse
    {
        $this->authorize('view', $mission);

        return $this->exports->downloadMatrice($mission);
    }

    public function carteThermique(Mission $mission): StreamedResponse
    {
        $this->authorize('view', $mission);

        return $this->exports->downloadCarteThermique($mission);
    }
}
