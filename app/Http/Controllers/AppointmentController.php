<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    
    // Student – saját időpontok
    public function studentIndex(){
        $id = Auth::id();   //az aktualisan bejelentkezettfel hasznalo id-jat adja vissza az auth::id(), amit mentjuk az $id- ba
            //terjen vissza azokkal az idopontokkal+adataival(tanarnak az id,name,email) (a with miatt azonnal), ahooool a student_id megegyezik a fenti $id ertekevel es listakent adja vissza ugye a get() miatt(collection)
        return Appointment::with('teacher:id,name,email')
            ->where('student_id', $id)
            ->get();
    }
    
    // Student – foglalás
    public function studentBook(Request $request, $teacher_id){
        
        //ez megnézi, h a diak altal foglalni keszulo idopont formailag valid e
        //required --kotelezokitolteni date--valodi datum kell, after:now --- mindenképpen a most után, vagyis csak jovobeni idopontoot lehet
        $request->validate([
            'lesson_time' => 'required|date|after:now'
        ]);

        //visszaadja az adott bejelentkezett felhasznalot(sanct token alapjan) és ez fontos h itt legyen mert:
        /*
            A foglalásnál a student ID-t nem a kliens küldi (mert hamisítható lenne),
            hanem a backend maga állapítja meg, így biztonságos.
        */
        $student = $request->user();

        //letezike az a tech akihez akar foglalni
        $teacher = User::where('role', 'teacher')->findOrFail($teacher_id);

        //foglalt e mar ez az idopont--> ua a teach ua-ban az idopontban van e mar foglalva
        $exists = Appointment::where('teacher_id', $teacher_id)
            ->where('lesson_time', $request->lesson_time)
            ->exists();
        //ha foglalt ez az uzi
        if ($exists) {
            return response()->json(['message' => 'Az időpont már foglalt'], 409);
        }

        //ha minden okes, akk menti az idopontot
        //vagyis uj sor az appointments tablaba , json-ban visszakuldi 
        return Appointment::create([
            'teacher_id' => $teacher_id,
            'student_id' => $student->id,
            'lesson_time' => $request->lesson_time
        ]);
    }

    // student - Diák által kezdeményezett időponttörlés (státuszváltással)
    public function studentCancel($id){
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
    public function teacherIndex(){
        return Appointment::with('student:id,name,email')
            ->where('teacher_id', Auth::id())
            ->select('id', 'teacher_id', 'student_id', 'lesson_time', 'status')
            ->get();
    }

    // Tanár által kezdeményezett időponttörlés státuszváltással (nem fizikai törlés)
    public function teacherCancel(int $id){
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
