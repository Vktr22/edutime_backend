<?php

namespace Tests\EduTime\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Appointment;
use App\Models\User;

class AppointmentActiveStudentIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_student_id_is_set_when_status_active(): void
    {
        $teacher = User::factory()->create(['role'=>'teacher']);
        $student = User::factory()->create(['role'=>'student']);

        $appt = Appointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'active',
        ]);

        $this->assertEquals($student->id, $appt->active_student_id);
    }

    public function test_active_student_id_is_null_when_cancelled(): void
    {
        $teacher = User::factory()->create(['role'=>'teacher']);
        $student = User::factory()->create(['role'=>'student']);

        $appt = Appointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'cancelled_by_student',
        ]);

        $this->assertNull($appt->active_student_id);
    }
}