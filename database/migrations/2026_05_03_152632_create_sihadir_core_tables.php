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
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->string('prodi');
            $table->unsignedSmallInteger('angkatan');
            $table->boolean('wajah_terdaftar')->default(false);
            $table->timestamps();
        });

        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nip')->unique();
            $table->string('bidang_studi')->nullable();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas');
            $table->string('prodi');
            $table->unsignedTinyInteger('semester');
            $table->string('tahun_akademik');
            $table->timestamps();

            $table->unique(['nama_kelas', 'prodi', 'semester', 'tahun_akademik'], 'kelas_identity_unique');
        });

        Schema::create('kelas_mahasiswa', function (Blueprint $table) {
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['kelas_id', 'mahasiswa_id']);
        });

        Schema::create('kelas_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->string('mata_kuliah');
            $table->timestamps();

            $table->unique(['kelas_id', 'dosen_id', 'mata_kuliah'], 'kelas_dosen_mata_kuliah_unique');
        });

        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->string('mata_kuliah');
            $table->string('hari');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruangan');
            $table->timestamps();

            $table->index(['hari', 'jam_mulai', 'jam_selesai']);
        });

        Schema::create('sesi_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status')->default('aktif');
            $table->timestamp('dibuka_at')->nullable();
            $table->timestamp('ditutup_at')->nullable();
            $table->timestamps();

            $table->unique(['jadwal_id', 'tanggal', 'status'], 'sesi_jadwal_tanggal_status_unique');
        });

        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_id')->constrained('sesi_absensi')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expired_at');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();

            $table->index(['sesi_id', 'expired_at']);
        });

        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_id')->constrained('sesi_absensi')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('status')->default('hadir');
            $table->timestamp('timestamp')->nullable();
            $table->string('metode')->nullable();
            $table->timestamps();

            $table->unique(['sesi_id', 'mahasiswa_id'], 'presensi_sesi_mahasiswa_unique');
            $table->index(['status', 'timestamp']);
        });

        Schema::create('face_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('foto_path');
            $table->json('face_descriptor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_data');
        Schema::dropIfExists('presensi');
        Schema::dropIfExists('qr_tokens');
        Schema::dropIfExists('sesi_absensi');
        Schema::dropIfExists('jadwal');
        Schema::dropIfExists('kelas_dosen');
        Schema::dropIfExists('kelas_mahasiswa');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('dosen');
        Schema::dropIfExists('mahasiswa');
    }
};
