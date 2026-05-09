<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('department_id')->constrained('roles')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true)->after('password');
            }

            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('fonction');
            }

            /* Champ historique unique : telephone (pas de colonne phone). */
            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone')->nullable()->after('matricule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }

            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            }

            if (Schema::hasColumn('users', 'active')) {
                $table->dropColumn('active');
            }

            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }

            /* Ne pas supprimer telephone : ajoutée ici uniquement si elle manquait au déploiement. */
        });
    }
};
