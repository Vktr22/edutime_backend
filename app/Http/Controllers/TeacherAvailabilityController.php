<?php

namespace App\Http\Controllers;

use App\Models\TeacherDateAvailability;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TeacherAvailabilityController extends Controller
{
    /*
        ez a controller lehetove teszi, h a bejelentkezett tanar:
            lekerdezze a sajat heti munkaidejet,
            uj munkaido savot adjon hozza,
            torolje a sajat munkaido savjait
        (csak a sajat adatait kezeli)
    */
    public function index()
    {
        $teacherId = Auth::id();

        return TeacherDateAvailability::where('teacher_id', $teacherId)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }


    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $teacherId = Auth::id();

        // ✅ Teljes dátum-idő összeállítása
        $slotStart = Carbon::parse($request->date . ' ' . $request->start_time);

        // ✅ Múltbeli idősáv tiltása
        if ($slotStart->isPast()) {
            return response()->json([
                'message' => 'Múltbeli időpontra nem lehet elérhetőséget létrehozni.'
            ], 422);
        }

        // ✅ Duplikáció tiltása (ugyanarra a napra ugyanaz az idősáv)
        $exists = TeacherDateAvailability::where('teacher_id', $teacherId)
            ->where('date', $request->date)
            ->where('start_time', $request->start_time)
            ->where('end_time', $request->end_time)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ez az időpont már létezik.'
            ], 422);
        }

        $availability = TeacherDateAvailability::create([
            'teacher_id' => $teacherId,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return response()->json($availability);
    }

    public function availableSlots($id)
    {
        // 1) Megnézzük, hogy létezik-e a tanár (és valóban teacher role-ja van-e)
        $teacher = User::where('role', 'teacher')->findOrFail($id);

        // 2) Lekérjük a tanár összes megadott elérhetőségi sávját
        $availabilities = TeacherDateAvailability::where('teacher_id', $id)->get();

        // 3) Lekérjük azokat az időpontokat, amelyekre már van foglalás
        //    (csak a lesson_time mezőt kérjük le)
        //    majd Carbon objektummá alakítjuk őket az összehasonlításhoz
        $booked = Appointment::where('teacher_id', $id)
            ->where('status', 'active')
            ->pluck('lesson_time')
            ->map(fn($x) => Carbon::parse($x, 'Europe/Budapest')->format('Y-m-d H:i'))
            ->toArray();


        // Az elérhetőségi sávokból 60 perces, még szabad és jövőbeli időpontokat gyűjtünk.
        foreach ($availabilities as $a) {
            $start = Carbon::parse("{$a->date} {$a->start_time}", 'Europe/Budapest');
            $end   = Carbon::parse("{$a->date} {$a->end_time}", 'Europe/Budapest');

            while ($start->copy()->addMinutes(60) <= $end) {
                if (
                    $start->isFuture() &&
                    !in_array($start->format('Y-m-d H:i'), $booked)
                ) {
                    $result[] = [
                        'start' => $start->format('Y-m-d H:i:s')
                    ];
                }

                $start->addMinutes(60);
            }
        }


        // 9) Visszaadjuk a foglalható időpontokat JSON válaszként
        return response()->json($result);
    }

    public function destroy(Request $request, $id)
    {
        $teacherId = $request->user()->id;

        $availability = TeacherDateAvailability::where('teacher_id', $teacherId)
            ->where('id', $id)
            ->firstOrFail();

        // Availability sáv kezdete/vége dátumidőként
        $start = Carbon::parse("{$availability->date} {$availability->start_time}");
        $end   = Carbon::parse("{$availability->date} {$availability->end_time}");

        // Van-e aktív foglalás, ami ebbe a sávba esik?
        // (end-et exclusive-ként kezeljük)
        $hasActiveBooking = Appointment::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->where('lesson_time', '>=', $start->format('Y-m-d H:i:s'))
            ->where('lesson_time', '<',  $end->format('Y-m-d H:i:s'))
            ->exists();

        if ($hasActiveBooking) {
            return response()->json([
                'message' => 'Nem törölhető ez az elérhetőség, mert van rá foglalt időpont.'
            ], 422);
        }

        $availability->delete();

        return response()->json([
            'message' => 'Availability deleted successfully',
        ]);
    }
}
