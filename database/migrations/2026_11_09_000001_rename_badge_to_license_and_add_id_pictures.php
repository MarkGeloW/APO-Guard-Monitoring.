<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('security_personnel', 'badge_number')
            && ! Schema::hasColumn('security_personnel', 'license_number')) {
            Schema::table('security_personnel', function (Blueprint $table) {
                $table->string('license_number')->nullable()->after('user_id');
            });

            DB::table('security_personnel')->update([
                'license_number' => DB::raw('badge_number'),
            ]);

            Schema::table('security_personnel', function (Blueprint $table) {
                $table->dropUnique(['badge_number']);
                $table->dropColumn('badge_number');
            });

            Schema::table('security_personnel', function (Blueprint $table) {
                $table->unique('license_number');
            });
        }

        if (! Schema::hasColumn('security_personnel', 'id_picture_paths')) {
            Schema::table('security_personnel', function (Blueprint $table) {
                $table->json('id_picture_paths')->nullable()->after('license_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('security_personnel', 'id_picture_paths')) {
            Schema::table('security_personnel', function (Blueprint $table) {
                $table->dropColumn('id_picture_paths');
            });
        }

        if (Schema::hasColumn('security_personnel', 'license_number')
            && ! Schema::hasColumn('security_personnel', 'badge_number')) {
            Schema::table('security_personnel', function (Blueprint $table) {
                $table->string('badge_number')->nullable()->after('user_id');
            });

            DB::table('security_personnel')->update([
                'badge_number' => DB::raw('license_number'),
            ]);

            Schema::table('security_personnel', function (Blueprint $table) {
                $table->dropUnique(['license_number']);
                $table->dropColumn('license_number');
            });

            Schema::table('security_personnel', function (Blueprint $table) {
                $table->unique('badge_number');
            });
        }
    }
};
