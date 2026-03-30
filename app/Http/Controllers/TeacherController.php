<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;


class TeacherController extends Controller
{
    //az index() lekeri a tanarokat listaba -> az id, name, email mezoket/tanar ugye ((a get miatt ad vissze collection-t=listat))
    public function index()
    {
        return User::where('role', 'teacher')
            ->select('id', 'name', 'email')
            ->get();
    }

    //ez egy konkret tanar kivalasztott adatait keri le, ha nem lesz meg, akk 404 hibat ad vissza a findorfail miatt
    public function show($id)
    {
        return User::where('role', 'teacher')
            ->select('id', 'name', 'email')
            ->findOrFail($id);
    }

}
