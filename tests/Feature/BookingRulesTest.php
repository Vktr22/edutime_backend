<?php

namespace Tests\EduTime\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;

class BookingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_book_past_time_422(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        Sanctum::actingAs($student);

        $past = Carbon::now('Europe/Budapest')->subDay()->format('Y-m-d H:i:s');

        $this->postJson("/api/teachers/{$teacher->id}/appointments", [
            'lesson_time' => $past,
        ])->assertStatus(422);
    }

    public function test_teacher_time_conflict_409(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $time = Carbon::now('Europe/Budapest')->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        Appointment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_time' => $time,
            'status' => 'active',
        ]);

        Sanctum::actingAs($student);

        $this->postJson("/api/teachers/{$teacher->id}/appointments", [
            'lesson_time' => $time,
        ])->assertStatus(409)
            ->assertJsonFragment(['message' => 'Az időpont már foglalt']);
    }

    public function test_student_time_conflict_409(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);

        $time = Carbon::now('Europe/Budapest')->addDays(2)->setTime(11, 0)->format('Y-m-d H:i:s');

        Appointment::create([
            'teacher_id' => $teacher1->id,
            'student_id' => $student->id,
            'lesson_time' => $time,
            'status' => 'active',
        ]);

        Sanctum::actingAs($student);

        $this->postJson("/api/teachers/{$teacher2->id}/appointments", [
            'lesson_time' => $time,
        ])->assertStatus(409)
            ->assertJsonFragment(['message' => 'Már van foglalásod erre az időpontra.']);
    }

    public function test_student_can_book_future_time_200(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        Sanctum::actingAs($student);

        $time = Carbon::now('Europe/Budapest')->addDays(3)->setTime(12, 0)->format('Y-m-d H:i:s');

        $this->postJson("/api/teachers/{$teacher->id}/appointments", [
            'lesson_time' => $time,
        ])->assertStatus(200)
            ->assertJsonFragment(['status' => 'active']);
    }
}
