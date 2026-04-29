<?php

namespace Tests\EduTime\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cancel_sets_cancelled_by_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $time = Carbon::now('Europe/Budapest')->addDays(2)->format('Y-m-d H:i:s');

        $appt = Appointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_time' => $time,
            'status' => 'active',
        ]);

        Sanctum::actingAs($student);

        $this->deleteJson("/api/student/appointments/{$appt->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appt->id,
            'status' => 'cancelled_by_student',
        ]);
    }

    public function test_teacher_cancel_sets_cancelled_by_teacher(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $time = Carbon::now('Europe/Budapest')->addDays(2)->format('Y-m-d H:i:s');

        $appt = Appointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_time' => $time,
            'status' => 'active',
        ]);

        Sanctum::actingAs($teacher);

        $this->deleteJson("/api/teacher/appointments/{$appt->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appt->id,
            'status' => 'cancelled_by_teacher',
        ]);
    }
}
