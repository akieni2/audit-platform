<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entretiens', function (Blueprint $table) {
            if (! Schema::hasColumn('entretiens', 'conducted_by')) {
                $table->foreignId('conducted_by')->nullable()->after('questionnaire_template_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('entretiens', 'interviewed_person')) {
                $table->string('interviewed_person')->nullable()->after('conducted_by');
            }
            if (! Schema::hasColumn('entretiens', 'interviewed_role')) {
                $table->string('interviewed_role')->nullable()->after('interviewed_person');
            }
            if (! Schema::hasColumn('entretiens', 'conducted_at')) {
                $table->timestamp('conducted_at')->nullable()->after('interviewed_role');
            }
            if (! Schema::hasColumn('entretiens', 'status')) {
                $table->string('status', 32)->default('draft')->after('conducted_at');
            }
            if (! Schema::hasColumn('entretiens', 'validation_status')) {
                $table->string('validation_status', 32)->nullable()->after('status');
            }
            if (! Schema::hasColumn('entretiens', 'synthesis')) {
                $table->text('synthesis')->nullable()->after('validation_status');
            }
        });

        if (Schema::hasColumn('entretiens', 'status')) {
            DB::table('entretiens')
                ->whereNotNull('date_entretien')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'draft');
                })
                ->update(['status' => 'completed']);
        }

        foreach (DB::table('entretiens')->select(['id', 'responsable_nom', 'role', 'date_entretien'])->get() as $row) {
            $upd = [];
            if (Schema::hasColumn('entretiens', 'interviewed_person') && $row->responsable_nom) {
                $upd['interviewed_person'] = $row->responsable_nom;
            }
            if (Schema::hasColumn('entretiens', 'interviewed_role') && $row->role) {
                $upd['interviewed_role'] = $row->role;
            }
            if (Schema::hasColumn('entretiens', 'conducted_at') && $row->date_entretien) {
                $upd['conducted_at'] = $row->date_entretien.' 00:00:00';
            }
            if ($upd !== []) {
                DB::table('entretiens')->where('id', $row->id)->update($upd);
            }
        }
    }

    public function down(): void
    {
        Schema::table('entretiens', function (Blueprint $table) {
            foreach (['conducted_by', 'interviewed_person', 'interviewed_role', 'conducted_at', 'status', 'validation_status', 'synthesis'] as $col) {
                if (Schema::hasColumn('entretiens', $col)) {
                    if ($col === 'conducted_by') {
                        $table->dropForeign(['conducted_by']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
