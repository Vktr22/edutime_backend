<?php

namespace App\Http\Controllers;

use App\Models\TeacherAvailability;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;

class TeacherAvailabilityController extends Controller
{
    /*
        ez a controller lehetove teszi, h a bejelentkezett tanar:
            lekerdezze a sajat heti munkaidejet,
            uj munkaido savot adjon hozza,
            torolje a sajat munkaido savjait
        (csak a sajat adatait kezeli)
    */
    public function index(Request $request)
    {
        $teacherId = $request->user()->id;

        //ahhol tanarid=tanarid, rendezze het napjai szerint sorba, azon bellul idorend
        return TeacherAvailability::where('teacher_id', $teacherId)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();        //visszaadja a teljes listat json-kent
    }

    
    public function store(Request $request)
    {
        $teacherId = $request->user()->id;  //user azonositas

        //helyes formatumot ell(ha hibas 422)
        $validated = $request->validate([
            'weekday'    => 'required|integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $day = $validated['weekday'];
        $newStart = $validated['start_time'];
        $newEnd = $validated['end_time'];

        
        // 1. Lekérjük az adott napi időszakokat
        $existing = TeacherAvailability::where('teacher_id', $teacherId)
            ->where('weekday', $day)
            ->orderBy('start_time')
            ->get();

        
        // 2. Megnézzük, hogy van-e átfedés
        $mergedStart = $newStart;
        $mergedEnd = $newEnd;
        $overlaps = [];

        foreach ($existing as $slot) {
            if ($slot->end_time >= $newStart && $slot->start_time <= $newEnd) {
                // Van átfedés → összevonjuk
                $mergedStart = min($mergedStart, $slot->start_time);
                $mergedEnd = max($mergedEnd, $slot->end_time);
                $overlaps[] = $slot->id;
            }
        }

        
        // 3. Ha volt átfedés → töröljük a régieket
        if (!empty($overlaps)) {
            TeacherAvailability::whereIn('id', $overlaps)->delete();
        }

        
        // 4. Mentjük az új (összevont) intervallumot
        $availability = TeacherAvailability::create([
            'teacher_id' => $teacherId,
            'weekday'    => $day,
            'start_time' => $mergedStart,
            'end_time' => $mergedEnd,
        ]);
        //sikeres mentes
        return response()->json($availability, 201);
    }

    public function availableSlots($id){
        // 1) Megnézzük, hogy létezik-e a tanár (és valóban teacher role-ja van-e)
        $teacher = User::where('role', 'teacher')->findOrFail($id);

        // 2) Lekérjük a tanár összes megadott elérhetőségi sávját
        //    (pl. hétfő 08–12, kedd 14–17 stb.)
        $availabilities = $teacher->availabilities()->get();

        // 3) Lekérjük azokat az időpontokat, amelyekre már van foglalás
        //    (csak a lesson_time mezőt kérjük le)
        //    majd Carbon objektummá alakítjuk őket az összehasonlításhoz
        $booked = Appointment::where('teacher_id', $id)
            ->pluck('lesson_time')
            ->map(fn($x) => Carbon::parse($x))
            ->toArray();

        // 4) Egy tanóra hossza percben
        //    (később ez könnyen paraméterezhető lenne)
        $slotLength = 60;

        // 5) Ebbe a tömbbe gyűjtjük majd a valóban foglalható időpontokat
        $result = [];

        // 6) 7 napra előre generáljuk az időpontokat
        for ($i = 0; $i < 7; $i++) {

            // 6.1) Az aktuálisan vizsgált dátum (ma + i nap)
            $date = Carbon::now()->addDays($i);

            // 6.2) A dátumhoz tartozó hét napja (1 = hétfő, 7 = vasárnap)
            $weekday = $date->dayOfWeekIso;

            // 6.3) Kiválasztjuk azokat az availability sávokat,
            //      amelyek erre a hét napra vonatkoznak
            $dayAvailabilities = $availabilities->where('weekday', $weekday);

            // 7) Végigmegyünk az adott napi összes elérhetőségi sávon
            foreach ($dayAvailabilities as $a) {

                // 7.1) Az availability sávból (pl. 08–12)
                //      legeneráljuk az órakezdési időpontokat (pl. 08:00, 09:00, 10:00)
                $slots = $this->generateSlotsForDay(
                    $weekday,
                    $date->format('Y-m-d') . ' ' . $a->start_time,
                    $date->format('Y-m-d') . ' ' . $a->end_time,
                    $slotLength
                );

                // 8) Az így kapott slotokon végigmegyünk
                foreach ($slots as $s) {

                    // 8.1) A slot időpontját teljes dátum+idő formára alakítjuk
                    $dt = Carbon::parse($s);

                    // 8.2) Ellenőrizzük, hogy ez az időpont már foglalt-e
                    //      (benne van-e a booked tömbben)
                    $isBooked = in_array($dt, $booked);

                    // 8.3) Csak akkor adjuk hozzá az eredményhez, ha:
                    //      - nincs már lefoglalva
                    //      - és a jövőben van (nem múltbeli időpont)
                    if (!$isBooked && $dt->isFuture()) {
                        $result[] = [
                            'start' => $dt->format('Y-m-d H:i:s')
                        ];
                    }
                }
            }
        }

        // 9) Visszaadjuk a foglalható időpontokat JSON válaszként
        return response()->json($result);
    }


    //ez a megadott idointervallumokbol 60perces kis slot-okat keszit
    private function generateSlotsForDay($weekday, $start, $end, $slotLength){
        $slots = [];
        
        $current = \Carbon\Carbon::parse($start);
        $endTime = \Carbon\Carbon::parse($end);

        while ($current->copy()->addMinutes($slotLength) <= $endTime) {
        // Teljes dátum+idő mentése, hogy a slot helyesen jövőbelinek számítson    
        $slots[] = $current->format('Y-m-d H:i:s');
            $current->addMinutes($slotLength);
        }

        return $slots;
    }

    public function destroy(Request $request, $id)
    {
        $teacherId = $request->user()->id;  //a torlendo availability rekord id-ja

        //a tanar csak a sajat rekordjat torolheti (ha maset 404)
        //vagyis NINCS unauthorized delete
        $availability = TeacherAvailability::where('teacher_id', $teacherId)
            ->where('id', $id)
            ->firstOrFail();
        //maga a torles
        $availability->delete();
        //frontendnek valasz
        return response()->json([
            'message' => 'Availability deleted successfully',
        ]);
    }


}
