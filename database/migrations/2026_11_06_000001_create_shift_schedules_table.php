<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal shift kasir yang dibuat admin atau pemilik toko.
 * Kasir hanya dapat masuk dan membuka shift sesuai jadwal ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');                   // bisa melewati tengah malam
            $table->string('catatan', 100)->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users', 'user_id')->restrictOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'waktu_mulai']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            // Satu jadwal hanya dapat dipakai untuk satu shift.
            $table->foreignId('schedule_id')->nullable()->after('user_id')->unique()
                ->constrained('shift_schedules', 'schedule_id')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_id');
        });

        Schema::dropIfExists('shift_schedules');
    }
};
