<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_date_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            $table->timestamps();

            $table->unique(
                ['teacher_id', 'date', 'start_time', 'end_time'],
                'teacher_date_avail_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_date_availabilities');
    }
};
