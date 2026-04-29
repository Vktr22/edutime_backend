<?php

namespace Tests\EduTime\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserRoleTest extends TestCase
{
    public function test_isTeacher_and_isStudent_helpers(): void
    {
        $u = new User();
        $u->role = 'teacher';
        $this->assertTrue($u->isTeacher());
        $this->assertFalse($u->isStudent());

        $u->role = 'student';
        $this->assertTrue($u->isStudent());
        $this->assertFalse($u->isTeacher());
    }
}