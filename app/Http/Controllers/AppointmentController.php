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

    // Student – saját időpontok
    public function studentIndex()
    {
        $id = Auth::id();   //az aktualisan bejelentkezettfel hasznalo id-jat adja vissza az auth::id(), amit mentjuk az $id- ba
        //terjen vissza azokkal az idopontokkal+adataival(tanarnak az id,name,email) (a with miatt azonnal), ahooool a student_id megegyezik a fenti $id ertekevel es listakent adja vissza ugye a get() miatt(collection)
        return Appointment::with('teacher:id,name,email')
            ->where('student_id', Auth::id())
            ->orderBy('lesson_time')    // Diák időpontjai időrendben, a megvalósulás dátuma szerint
            ->get();
    }

    // Student – foglalás
    public function studentBook(Request $request, $teacher_id)
    {

        //ez megnézi, h a diak altal foglalni keszulo idopont formailag valid e
        //Ezzel kizárjuk, hogy valahonnan ISO/UTC (...Z) csússzon be és eltolja az időt. UI nem változik, csak kevesebb “rejtett rossz adat” kerül be.
        $request->validate([
            'lesson_time' => 'required|date_format:Y-m-d H:i:s'
        ]);

        $lessonTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $request->lesson_time,
            'Europe/Budapest'
        );

        // KEREKÍTÉS / NORMALIZÁLÁS (ha valaha másodperc eltérés jönne)
        $lessonTimeStr = $lessonTime->format('Y-m-d H:i:s');

        if ($lessonTime->lessThanOrEqualTo(Carbon::now('Europe/Budapest'))) {
            return response()->json([
                'message' => 'Már elkezdődött időpontra nem lehet foglalni.'
            ], 422);
        }

        //visszaadja az adott bejelentkezett felhasznalot(sanct token alapjan) és ez fontos h itt legyen mert:
        /*
            A foglalásnál a student ID-t nem a kliens küldi (mert hamisítható lenne),
            hanem a backend maga állapítja meg, így biztonságos.
        */
        $student = $request->user();

        User::where('role', 'teacher')->findOrFail($teacher_id);

        $exists = Appointment::where('teacher_id', $teacher_id)
            ->where('lesson_time', $lessonTimeStr)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Az időpont már foglalt'], 409);
        }


        //Controller validáció: diák időütközés tiltása
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
            // MySQL/MariaDB unique constraint violation: SQLSTATE 23000
            if (($e->errorInfo[0] ?? null) === '23000') {
                return response()->json([
                    'message' => 'Már van aktív foglalásod erre az időpontra.'
                ], 409);
            }
            throw $e;
        }
    }

    // student - Diák által kezdeményezett időponttörlés (státuszváltással)
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

    // Teacher – saját órái
    // Tanár saját időpontjai státuszmezővel együtt, frontend döntési logikához
    public function teacherIndex()
    {
        return Appointment::with('student:id,name,email')
            ->where('teacher_id', Auth::id())
            ->select('id', 'teacher_id', 'student_id', 'lesson_time', 'status')
            ->get();
    }

    // Tanár által kezdeményezett időponttörlés státuszváltással (nem fizikai törlés)
    public function teacherCancel(int $id)
    {
        $appointment = Appointment::findOrFail($id);

        // Ellenőrizzük, hogy az időpont valóban a bejelentkezett tanárhoz tartozik
        if ($appointment->teacher_id !== Auth::id()) {
            abort(403);
        }

        // Múltbeli időpontot nem lehet törölni
        if ($appointment->lesson_time <= now()) {
            return response()->json([
                'message' => 'Múltbeli időpont nem törölhető'
            ], 422);
        }

        // Az időpont státuszának módosítása tanári törlésre
        $appointment->status = 'cancelled_by_teacher';
        $appointment->save();

        return response()->json([
            'message' => 'Időpont sikeresen törölve'
        ]);
    }
}
