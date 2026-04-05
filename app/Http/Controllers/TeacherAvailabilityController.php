<?php

namespace App\Http\Controllers;

use App\Models\TeacherAvailability;
use Illuminate\Http\Request;

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
