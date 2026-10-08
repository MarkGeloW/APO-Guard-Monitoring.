<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('security_personnel_id')->constrained('security_personnel')->cascadeOnDelete();
            $table->timestamp('time_in')->nullable();
            $table->decimal('time_in_latitude', 10, 7)->nullable();
            $table->decimal('time_in_longitude', 10, 7)->nullable();
            $table->unsignedInteger('time_in_distance_meters')->nullable();
            $table->boolean('time_in_verified')->default(false);
            $table->timestamp('time_out')->nullable();
            $table->decimal('time_out_latitude', 10, 7)->nullable();
            $table->decimal('time_out_longitude', 10, 7)->nullable();
            $table->unsignedInteger('time_out_distance_meters')->nullable();
            $table->boolean('time_out_verified')->default(false);
            $table->enum('status', ['pending', 'present', 'late', 'absent', 'completed'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
