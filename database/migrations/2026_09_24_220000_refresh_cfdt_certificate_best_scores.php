<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cfdt_certificates')->orderBy('id')->each(function ($certificate) {
            $bestScore = DB::table('cfdt_attempts')
                ->where('enrollment_id', $certificate->enrollment_id)
                ->where('passed', true)
                ->max('percentage');

            if ($bestScore === null || (float) $bestScore <= (float) $certificate->score) return;

            DB::table('cfdt_certificates')->where('id', $certificate->id)->update([
                'score' => $bestScore,
                'signature_hash' => hash('sha256', $certificate->verification_token.'|'.$certificate->enrollment_id.'|'.(float) $bestScore),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Les anciens scores erronés ne doivent pas être restaurés.
    }
};
