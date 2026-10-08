<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_personnel', function (Blueprint $table) {
            $table->string('face_image_path')->nullable()->after('notes');
            $table->json('face_signature')->nullable()->after('face_image_path');
            $table->timestamp('face_enrolled_at')->nullable()->after('face_signature');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('time_in_face_verified')->default(false)->after('time_in_verified');
            $table->string('time_in_face_path')->nullable()->after('time_in_face_verified');
            $table->float('time_in_face_score')->nullable()->after('time_in_face_path');
            $table->boolean('time_out_face_verified')->default(false)->after('time_out_verified');
            $table->string('time_out_face_path')->nullable()->after('time_out_face_verified');
            $table->float('time_out_face_score')->nullable()->after('time_out_face_path');
        });
    }

    public function down(): void
    {
        Schema::table('security_personnel', function (Blueprint $table) {
            $table->dropColumn(['face_image_path', 'face_signature', 'face_enrolled_at']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'time_in_face_verified',
                'time_in_face_path',
                'time_in_face_score',
                'time_out_face_verified',
                'time_out_face_path',
                'time_out_face_score',
            ]);
        });
    }
};
