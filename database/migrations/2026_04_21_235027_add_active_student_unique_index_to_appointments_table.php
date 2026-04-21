<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    
    public function up(): void
    {
        // generated column: MariaDB-ben ezt biztosan SQL-lel
        DB::statement("
            ALTER TABLE appointments
            ADD COLUMN active_student_id BIGINT UNSIGNED
            GENERATED ALWAYS AS (
                CASE WHEN status = 'active' THEN student_id ELSE NULL END
            ) STORED
        ");

        // index már mehet Laravelrel
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
        });

        DB::statement("ALTER TABLE appointments DROP COLUMN active_student_id");
    }
};
