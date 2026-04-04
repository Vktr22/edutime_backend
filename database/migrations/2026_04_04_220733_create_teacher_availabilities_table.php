<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');

            // 1 = Monday  7= Sunday
            $table->unsignedTinyInteger('weekday');

            $table->time('start_time');
            $table->time('end_time');

            $table->timestamps();

            // optional: prevent exact duplicates
            $table->unique(
                ['teacher_id', 'weekday', 'start_time', 'end_time'],
                'teacher_avail_unique'
            );

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
    }
};
