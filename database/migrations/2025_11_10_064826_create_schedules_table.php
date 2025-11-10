<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('waktu_mulai');
            $table->bigInteger('waktu_selesai');
            $table->foreignId('worker_id')->constrained('workers')->onDelete('cascade');
            $table->foreignId('jobdesc_id')->constrained('jobdescs')->onDelete('cascade');
            $table->foreignId('superfisor_id')->constrained('workers')->onDelete('cascade');
            $table->string('tempat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
