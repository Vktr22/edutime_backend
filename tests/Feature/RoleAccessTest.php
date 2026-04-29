<?php

namespace Tests\EduTime\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_access_teachers_list(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $this->getJson('/api/teachers')->assertStatus(200);
    }

    public function test_teacher_cannot_access_teachers_list(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $this->getJson('/api/teachers')->assertStatus(403)
            ->assertJsonFragment(['message' => 'Only students allowed']);
    }
}
