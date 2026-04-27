<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'student_id',
        'active_student_id',   // <-- ADD
        'lesson_time',
        'status',
    ];


    protected $casts = [
        'lesson_time' => 'datetime:Y-m-d H:i:s',
    ];


    protected static function booted(): void
    {
        // Mindig konzisztensen tartjuk az active_student_id-t
        static::saving(function (Appointment $appointment) {
            $appointment->active_student_id =
                ($appointment->status === 'active')
                ? $appointment->student_id
                : null;
        });
    }



    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
