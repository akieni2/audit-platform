<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('copri_menu_enabled')->nullable()->after('must_change_password');
        });

        $permissionId = DB::table('permissions')->where('slug', 'access_copri_menu')->value('id');
        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'slug' => 'access_copri_menu',
                'name' => 'Accéder au menu COPRI',
                'group' => 'executive',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table('roles')->whereIn('slug', [
            'super_admin', 'copri', 'inspecteur_services', 'inspecteur_adjoint',
            'inspecteur_verificateur', 'inspecteur_verificateur_adjoint',
            'directeur', 'directeur_adjoint', 'chef_service',
        ])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'access_copri_menu')->value('id');
        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('copri_menu_enabled');
        });
    }
};
