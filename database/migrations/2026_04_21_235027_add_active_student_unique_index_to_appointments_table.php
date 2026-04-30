<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void

    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('active_student_id')->nullable()->after('student_id');
        });

        DB::statement("
            UPDATE appointments
            SET active_student_id = CASE
                WHEN status = 'active' THEN student_id
                ELSE NULL
            END
        ");

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique(
                ['active_student_id', 'lesson_time'],
                'appointments_active_student_lesson_time_unique'
            );
        });
    }



    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_active_student_lesson_time_unique');
            $table->dropColumn('active_student_id');
        });
    }
};
