<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->unique(['jadwal_id', 'tanggal'], 'sesi_jadwal_tanggal_unique');
        });

        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->dropUnique('sesi_jadwal_tanggal_status_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->unique(['jadwal_id', 'tanggal', 'status'], 'sesi_jadwal_tanggal_status_unique');
        });

        Schema::table('sesi_absensi', function (Blueprint $table) {
            $table->dropUnique('sesi_jadwal_tanggal_unique');
        });
    }
};
