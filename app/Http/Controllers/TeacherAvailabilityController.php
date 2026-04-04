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
}
