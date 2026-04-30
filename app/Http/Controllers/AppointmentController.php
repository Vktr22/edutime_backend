<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PhpParser\Node\Stmt\TryCatch;

class AppointmentController extends Controller
{

    public function studentIndex()
    {
        $id = Auth::id();
        return Appointment::with('teacher:id,name,email')
            ->where('student_id', Auth::id())
            ->orderBy('lesson_time')
            ->get();
    }

    public function studentBook(Request $request, $teacher_id)
    {

        $request->validate([
            'lesson_time' => 'required|date_format:Y-m-d H:i:s'
        ]);

        $lessonTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $request->lesson_time,
            'Europe/Budapest'
        );

        $lessonTimeStr = $lessonTime->format('Y-m-d H:i:s');

        if ($lessonTime->lessThanOrEqualTo(Carbon::now('Europe/Budapest'))) {
            return response()->json([
                'message' => 'Már elkezdődött időpontra nem lehet foglalni.'
            ], 422);
        }

        $student = $request->user();

        User::where('role', 'teacher')->findOrFail($teacher_id);

        $exists = Appointment::where('teacher_id', $teacher_id)
            ->where('lesson_time', $lessonTimeStr)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Az időpont már foglalt'], 409);
        }


        $studentHasClash = Appointment::where('student_id', $student->id)
            ->where('lesson_time', $lessonTimeStr)
            ->where('status', 'active')
            ->exists();

        if ($studentHasClash) {
            return response()->json([
                'message' => 'Már van foglalásod erre az időpontra.'
            ], 409);
        }

        try {

            $appt = Appointment::create([
                'teacher_id'  => $teacher_id,
                'student_id'  => $student->id,
                'lesson_time' => $lessonTimeStr,
                'status'      => 'active',
            ]);

            return response()->json($appt, 200);
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23000') {
                return response()->json([
                    'message' => 'Már van aktív foglalásod erre az időpontra.'
                ], 409);
            }
            throw $e;
        }
    }

    public function studentCancel($id)
    {
        $appointment = Appointment::findOrFail($id);

        if ($appointment->student_id !== Auth::id()) {
            abort(403);
        }

        if ($appointment->lesson_time <= now()) {
            return response()->json([
                'message' => 'Múltbeli időpont nem törölhető'
            ], 422);
        }

        $appointment->status = 'cancelled_by_student';
        $appointment->save();

        return response()->json([
            'message' => 'Időpont sikeresen törölve'
        ]);
    }

    public function teacherIndex()
    {
        return Appointment::with('student:id,name,email')
            ->where('teacher_id', Auth::id())
            ->select('id', 'teacher_id', 'student_id', 'lesson_time', 'status')
            ->get();
    }

    public function teacherCancel(int $id)
    {
        $appointment = Appointment::findOrFail($id);

        if ($appointment->teacher_id !== Auth::id()) {
            abort(403);
        }

        if ($appointment->lesson_time <= now()) {
            return response()->json([
                'message' => 'Múltbeli időpont nem törölhető'
            ], 422);
        }

        $appointment->status = 'cancelled_by_teacher';
        $appointment->save();

        return response()->json([
            'message' => 'Időpont sikeresen törölve'
        ]);
    }
}
