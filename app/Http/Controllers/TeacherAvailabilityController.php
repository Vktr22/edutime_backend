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
        
        //menti de ugye a tid-t nem a user adja meg
        $availability = TeacherAvailability::create([
            'teacher_id' => $teacherId,
            'weekday'    => $validated['weekday'],
            'start_time' => $validated['start_time'],
            'end_time'   => $validated['end_time'],
        ]);
        //sikeres mentes
        return response()->json($availability, 201);

        // check overlapping ranges
        //ez megakadalyozza, h uj idosav felvitele eseten ne legyen utkozes
        $overlap = TeacherAvailability::where('teacher_id', $teacherId)
            ->where('weekday', $validated['weekday'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhere(function ($sub) use ($validated) {
                        $sub->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                    });
        })->exists();

        if ($overlap) {
            return response()->json(['message' => 'Ez az időszak ütközik egy már létező munkasávval.'], 409);
        }

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
