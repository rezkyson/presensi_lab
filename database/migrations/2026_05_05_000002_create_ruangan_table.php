<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ruangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('ruangan')->insert([
            ['nama' => 'LAB 1', 'keterangan' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'LAB 2', 'keterangan' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'LAB 3', 'keterangan' => null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruangan');
    }
};
