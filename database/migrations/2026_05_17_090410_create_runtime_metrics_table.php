<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('runtime_metrics')) {
            return;
        }

        Schema::create('runtime_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 160)->index();
            $table->string('metric_type', 32)->default('counter')->index();
            $table->string('scope_type', 80)->nullable()->index();
            $table->string('scope_id', 80)->nullable()->index();
            $table->string('dimensions_hash', 64);
            $table->json('dimensions')->nullable();
            $table->decimal('value', 20, 6)->default(0);
            $table->timestamp('recorded_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['metric_key', 'metric_type', 'scope_type', 'scope_id', 'dimensions_hash'],
                'runtime_metrics_unique_scope'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runtime_metrics');
    }
};
