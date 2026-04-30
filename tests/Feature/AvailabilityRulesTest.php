<?php

namespace Tests\EduTime\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\TeacherDateAvailability;
use App\Models\Appointment;
use Carbon\Carbon;

class AvailabilityRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_create_duplicate_availability_422(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $date = Carbon::now('Europe/Budapest')->addDays(5)->format('Y-m-d');

        TeacherDateAvailability::create([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $this->postJson('/api/teacher/availability', [
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Ez az időpont már létezik.']);
    }

    public function test_teacher_cannot_delete_availability_with_active_booking_422(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($teacher);

        $date = Carbon::now('Europe/Budapest')->addDays(6)->format('Y-m-d');

        $avail = TeacherDateAvailability::create([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
        ]);

        Appointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_time' => $date . ' 10:00:00',
            'status' => 'active',
        ]);

        $this->deleteJson("/api/teacher/availability/{$avail->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Nem törölhető ez az elérhetőség, mert van rá foglalt időpont.']);
    }
}
