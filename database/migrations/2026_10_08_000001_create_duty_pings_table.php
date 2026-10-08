<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_pings', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->foreignId('security_personnel_id')->constrained('security_personnel')->cascadeOnDelete();
            $table->foreignId('deployment_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('distance_meters')->nullable();
            $table->boolean('within_geofence')->default(false);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['deployment_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_pings');
    }
};
